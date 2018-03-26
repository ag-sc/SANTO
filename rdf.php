<?php
session_start();

function scioData($data) {
        return "<http://scio/data/".$data.">";
}
function type() {
        return "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>";
}
function label() {
    return "<http://www.w3.org/2000/01/rdf-schema#label>";
}
function scioClass($data) {
        return "<http://psink.de/scio/".$data.">";
}
function scioClassId($classId) {
        return "<http://psink.de/scio/".className($classId).">";
}
function scioClassName($class) {
        return "<http://psink.de/scio/$class>";
}
function scioRelation($relation) {
        return "<http://psink.de/scio/".$relation.">";
}

require_once("php/publication.php");
require_once("php/user.php");
$activepub = Publications::byId($_GET['publication']);
if (empty($activepub)) {
    die("No publication specified. Usage: ?publication={ID}");
}

$onlyforuser = null;
if (!empty($_GET['user'])) {
    $onlyforuser = Users::byMail($_GET['user']);
    if (empty($onlyforuser)) {
        die("Requested username not found");
    }
}

$outfile = 'rdf';
$exportpub = Publications::byId($_GET['publication']);
if ($exportpub) {
    $filename = $exportpub->name;
    $outfile = str_replace("/","",$filename);
    $filename = str_replace("+", "%20", urlencode($filename));
}
$outfile .= "_".Users::loginUser()->mail;
$outfile.= ".n-triples";

header('Content-disposition: attachment; filename="'.$outfile.'"');
header("Content-type: text/plain");
$alltypes = array();
$triples = array();
$allslots = array();
$labels = array();
foreach($activepub->slotData($onlyforuser) as $entry) {
    $allslots[$entry->id()] = $entry;
}

function dump($entry, $key) {
    print_r("\t$key: ".json_encode($entry->get($key)).PHP_EOL);
}

$necessarynodes = array();
$alreadyhandled = array();

function addentry($entry, $force = false) {
    global $alltypes, $triples, $allslots, $labels, $necessarynodes, $alreadyhandled;
    $handled = false;
    $exported = null;
    $parent = null;
    
    if (array_key_exists($entry->get('id'), $alreadyhandled)) {
        return $entry;
    }

    $parent = !empty($entry->parentId()) && array_key_exists($entry->parentId(), $allslots) ? $allslots[$entry->parentId()] : null;
    if ($entry->isRelation()) {
        #print_r($entry->get('id')." isRel inst:".$entry->getInstance()." class:".$entry->getClassName()." p:".$parent->getInstance()." dt?".$entry->isDataProperty()." rel:".$entry->getRelation()." isindiv?".json_encode($entry->isIndividualName()).PHP_EOL);
        if (!empty($parent) && $entry->isDataProperty() && ($entry->hasAnnotation() || $entry->hasDataGroup() || $force)) {
            $triples[] = array(scioData($parent->getInstance()), scioRelation($entry->getRelation()), "\"".$entry->getText()."\"");
            addentry($parent, true);
            $handled = true;
            $alreadyhandled[$entry->get("id")] = true;
            $exported = $parent->getInstance();
        } else if (!empty($parent) && ($entry->hasAnnotation() || $entry->hasDataGroup() || array_key_exists($entry->getInstance(), $necessarynodes) || $force)) {
            if ($entry->isIndividualName()) {
                $triples[] = array(scioData($parent->getInstance()), scioRelation($entry->getRelation()), scioClass($entry->getClassName()));
            } else {
                $triples[] = array(scioData($parent->getInstance()), scioRelation($entry->getRelation()), scioData($entry->getInstance()));
                $exported = $entry->getInstance();
            }
            addentry($parent, true);
            $handled = true;
            $alreadyhandled[$entry->get("id")] = true;
        } else {
            $handled = false;
        }
    } else {
        if ($entry->hasDataName() && !empty($entry->getInstance())) {
            $labels[$entry->getInstance()] = $entry->getDataName();
        }
        if (!empty($entry->getInstance())) {
            $exported = $entry->getInstance();
        }
        if (!empty($parent)) {
            addentry($parent, true);
        }
        $handled = true;
        $alreadyhandled[$entry->get("id")] = true;
    }

    if ($handled) {
        if (!empty($parent) && !empty($parent->getInstance()) && !empty($parent->getClassName()) && !$parent->isDataProperty())  {
            $alltypes[$parent->getInstance()] = $parent->getClassName();
        }
        if (!empty($entry->getInstance()) && !empty($entry->getClassName()) && !$entry->isDataProperty()) {
            if (!$entry->isIndividualName()) {
            $alltypes[$entry->getInstance()] = $entry->getClassName();
            }
        }
    }

    if (!empty($exported) && !array_key_exists($exported, $alltypes)) {
        $necessarynodes[$exported] = true;
    }
    
    if ($handled) {
        return $entry;
    } else {
        return null;
    }
}

$needsmore = false;
do {
    // iterate over nodes until all recursive paths are satisfied
    $handled_thisiteration = array();
    foreach($activepub->slotData($onlyforuser) as $entry) {
        if (array_key_exists($entry->get('id'), $alreadyhandled)) {
            continue; // skip
        }

        $handled = addentry($entry);
        if ($handled !== null) {
            $handled_thisiteration[$entry->get('id')] = true;
        }
        
        if (!$handled && false) {
            print_r('not handled: '.$entry->getInstance().'<<<'.json_encode(array_key_exists($entry->getInstance(), $necessarynodes)).'...'.json_encode($entry->data).PHP_EOL);
            /*dump($entry, "DataProperty");
            dump($entry, "ClassName");
            dump($entry, "ClassId");
            dump($entry, "AnnotationId");
            dump($entry, "id");
            dump($entry, "Parent");
            dump($entry, "DataGroup");
            dump($entry, "IndividualName");
            if ($entry->hasDataName()) {
                print_r("\tLabel: ".json_encode($entry->getDataName()).PHP_EOL);
            }*/
            dump($entry, "Relation");
            print_r("\tInstance: ".$entry->getInstance().PHP_EOL);
        }
    }

} while ($needsmore); 


foreach($alltypes as $rdf_instance => $rdf_type) {
    print_r(scioData($rdf_instance)." ".type()." ".scioClass($rdf_type)." .".PHP_EOL);
}
foreach($labels as $rdf_instance => $label) {
    if (!empty($label) && !empty($rdf_instance)) {
        print_r(scioData($rdf_instance)." ".label()." \"".$label."\"".PHP_EOL);
    }
}
foreach($triples as $triple) {
    print_r(implode(" ", $triple)." .".PHP_EOL);
}
?>

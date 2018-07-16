<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once("php/functions.php");
require_once("php/configuration.php");

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return utf8_encode($d);
    }
    return $d;
}
function type() {
        return "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>";
}
function label() {
    return "<http://www.w3.org/2000/01/rdf-schema#label>";
}
function getPrefix($prefixtype, $defaulval) {
    return Configuration::instance()->get("export", "prefix_".$prefixtype, $defaulval);
}
function scioData($data) {
        return "<".getPrefix("data", "http://scio/data/").$data.">";
}
function scioClass($data) {
        return "<".getPrefix("class", "http://psink.de/scio/").$data.">";
}
function scioClassId($classId) {
        return "<".getPrefix("class", "http://psink.de/scio/").className($classId).">";
}
function scioClassName($class) {
        return "<".getPrefix("class", "http://psink.de/scio/").$class.">";
}
function scioRelation($relation) {
        return "<".getPrefix("rel", "http://psink.de/scio/").$relation.">";
}
function className($classId) {
    if($stmt = Database::prepare("SELECT Name FROM `Class` WHERE `Id` = ?")) {
        $stmt->bind_param("i", $classId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error());
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}


require_once("php/database.php");
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

if (empty($onlyforuser)) {
    $onlyforuser = Users::loginUser();
}

$outfile = 'rdf';
$exportpub = Publications::byId($_GET['publication']);
if ($exportpub) {
    $filename = $exportpub->name;
    $outfile = str_replace("/","",$filename);
    $filename = str_replace("+", "%20", urlencode($filename));
}
$outfile .= "_".$onlyforuser->mail;
if (!empty($_GET["output"]) && $_GET['output'] == 'annotation') {
    $outfile.= ".annodb";
} else {
    $outfile.= ".n-triples";
}

if (empty($_GET['debug'])) { // non-empty debug parameter skips download headers
    header('Content-disposition: attachment; filename="'.$outfile.'"');
}
header("Content-type: text/plain; charset=utf-8");
$alltypes = array();
$triples = array();
$anno2triples = array();
$allslots = array();
$labels = array();
foreach($activepub->slotData($onlyforuser) as $entry) {
    $allslots[$entry->id()] = $entry;
}

function dump($entry, $key) {
    print_r("\t$key: ".json_encode(utf8ize($entry->get($key))).PHP_EOL);
}

$necessarynodes = array();
$alreadyhandled = array();

function addentry($entry, $force = false) {
    global $alltypes, $triples, $allslots, $labels, $necessarynodes, $alreadyhandled, $anno2triples;
    $handled = false;
    $exported = null;
    $parent = null;
    
    if (array_key_exists($entry->get('id'), $alreadyhandled)) {
        return $entry;
    }

    $parent = !empty($entry->parentId()) && array_key_exists($entry->parentId(), $allslots) ? $allslots[$entry->parentId()] : null;
    //print_r("addentry ".str_replace("\n", "", var_export($entry, true)).PHP_EOL);
    if ($entry->isRelation()) {
        if (!empty($parent) && $entry->isDataProperty() && ($entry->hasAnnotation() || $entry->hasDataGroup() || $force)) {
            $newtriple = array(scioData($parent->getInstance()), scioRelation($entry->getRelation()), "\"".$entry->getText()."\"");
            $triples[] = $newtriple;
            if (!empty($entry->getAnnotationId())) {
                if (!array_key_exists($entry->getAnnotationId(), $anno2triples)) {
                    $anno2triples[$entry->getAnnotationId()] = array();
                }
                $anno2triples[$entry->getAnnotationId()][] = $newtriple;
            }
            addentry($parent, true);
            $handled = true;
            $alreadyhandled[$entry->get("id")] = true;
            $exported = $parent->getInstance();
        } else if (!empty($parent) && ($entry->get("ManuallySet") || $entry->hasAnnotation() || $entry->hasDataGroup() || array_key_exists($entry->getInstance(), $necessarynodes) || $force)) {
            if ($entry->isIndividualName()) {
                $rel_subclasses = null;
                if (!empty($entry->get("RelationDomain"))) {
                    $rel_subclasses = getSubclasses($entry->get("RelationDomain"));
                }
                /*if ($entry->getRelation() === "hasCompoundBiologicalRelation") {
                    var_dump($entry->getInstance());
                    print_r($entry->get("ClassId").PHP_EOL);
                    print_r($parent->isRelation().PHP_EOL);
                    print_r($parent->get("RelationRange").PHP_EOL);
                    var_dump($entry);
                    print_r("PARENT");
                    var_dump($parent);

                    print_r($entry->get('id')." isRel inst:".$entry->getInstance()." class:".$entry->getClassName()." p:".$parent->getInstance()." dt?".$entry->isDataProperty()." rel:".$entry->getRelation()." isindiv?".json_encode($entry->isIndividualName())." manual? ".$entry->get("ManuallySet").PHP_EOL);
                    $tmp = $entry;
                    $entry = $parent;
                    print_r("parent ".$entry->get('id')." isRel inst:".$entry->getInstance()." class:".$entry->getClassName()." p:".$parent->getInstance()." dt?".$entry->isDataProperty()." rel:".$entry->getRelation()." isindiv?".json_encode($entry->isIndividualName())." manual? ".$entry->get("ManuallySet").PHP_EOL);
                    $entry = $tmp;
                    print_r("parent-class ".$parent->get("ClassId").PHP_EOL);
                    print_r("entry rel ".$entry->isRelation().PHP_EOL);
                    print_r("entry reldomain".$entry->get("RelationDomain").PHP_EOL);     

                    if (!empty($rel_subclasses)) {
                        print_r("SC ".in_array($parent->get("ClassId"), $rel_subclasses).PHP_EOL);
                    }
                    print_r("----------------------------------------------------------------------------------------------------------------------------".PHP_EOL);
            }*/
                if (empty($rel_subclasses) || in_array($parent->get("ClassId"), $rel_subclasses)) {
                    $newtriple = array(scioData($parent->getInstance()), scioRelation($entry->getRelation()), scioClass($entry->getClassName()));
                    $triples[] = $newtriple;
                    if (!empty($entry->getAnnotationId())) {
                        if (!array_key_exists($entry->getAnnotationId(), $anno2triples)) {
                            $anno2triples[$entry->getAnnotationId()] = array();
                        }
                        $anno2triples[$entry->getAnnotationId()][] = $newtriple;
                    }
                }
            } else {
                if (!$parent->isRelation()) {

                    $newtriple = array(scioData($parent->getInstance()), scioRelation($entry->getRelation()), scioData($entry->getInstance()));
                    $triples[] = $newtriple;
                    if (!empty($entry->getAnnotationId())) {
                        if (!array_key_exists($entry->getAnnotationId(), $anno2triples)) {
                            $anno2triples[$entry->getAnnotationId()] = array();
                        }
                        $anno2triples[$entry->getAnnotationId()][] = $newtriple;
                    }
                    $exported = $entry->getInstance();

                } else {
                    // echo "SKIP ".$entry->getRelation()." ".$entry->getText();
                    // print_r($entry->get('id')." isRel inst:".$entry->getInstance()." class:".$entry->getClassName()." p:".$parent->getInstance()." dt?".$entry->isDataProperty()." rel:".$entry->getRelation()." isindiv?".json_encode($entry->isIndividualName())." isdataprop? ".json_encode($entry->isDataProperty())." hasAnno?".json_encode($entry->hasAnnotation())." hasDG?".json_encode($entry->hasDataGroup()).PHP_EOL);

                    // var_dump($entry);
                }
            }
            addentry($parent, true);
            $handled = true;
            $alreadyhandled[$entry->get("id")] = true;
        } else {
            $handled = false;
            //print_r($entry->get('id')." isRel inst:".$entry->getInstance()." class:".$entry->getClassName()." p:".$parent->getInstance()." dt?".$entry->isDataProperty()." rel:".$entry->getRelation()." isindiv?".json_encode($entry->isIndividualName()).PHP_EOL);
            //print_r("\t !empty_parent? ". (!empty($parent)) . " hasanno? ".$entry->hasAnnotation()." hasdatagroup? ". $entry->hasDataGroup()." inst in necessary? ".array_key_exists($entry->getInstance(), $necessarynodes)." force ". $force.PHP_EOL);

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
        if ($entry->getAnnotationId()) { // TODO current
            $anno2triple[$entry->getAnnotationId()] = array( scioData($rdf_instance), type(), scioClass($rdf_type) );
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
                if (!empty($entry->get("dgname"))) {
                    $alltypes[$entry->getInstance()] = $entry->get("dgname");
                } else {
                    $alltypes[$entry->getInstance()] = $entry->getClassName();
                }
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
            print_r('not handled: '.$entry->getInstance().'<<<'.json_encode(utf8ize(array_key_exists($entry->getInstance(), $necessarynodes))).'...'.json_encode(utf8ize($entry->data)).PHP_EOL);
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

function printRDF($alltypes, $triples) {
    foreach($alltypes as $rdf_instance => $rdf_type) {
        print_r(scioData($rdf_instance)." ".type()." ".scioClass($rdf_type)." .".PHP_EOL);
    }
    /* foreach($labels as $rdf_instance => $label) {
        if (!empty($label) && !empty($rdf_instance)) {
            print_r(scioData($rdf_instance)." ".label()." \"".$label."\"".PHP_EOL);
        }
    }*/
    foreach($triples as $triple) {
        print_r(implode(" ", $triple)." .".PHP_EOL);
    }
}

function spimplode($arr) {
    if (sizeof($arr) === 0) {
        return "";
    } else {
        return implode(" ", $arr).".";
    }
}

function printAnnotations($alltypes, $triples, $anno2triples) {
    global $activepub, $onlyforuser;
    if (empty($onlyforuser)) {
        die("missing user");
    }
    if($stmt = Database::prepare("SELECT * FROM `Annotation` WHERE `PublicationId` = ? AND `User` = ? ORDER BY `Sentence`,`Onset`")) {
        $stmt->bind_param("ii", $activepub->id, $onlyforuser->id);
        $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error());
        $result = $stmt->get_result();
        $annotations = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".Database::error());
    $slotdata = $activepub->slotData($onlyforuser);
    echo "# AnnotationID, ClassType, DocCharOnset(incl), DocCharOffset(excl), Text, Meta, Instances\n";
    foreach ($annotations as $annotation) {
        if($stmt = Database::prepare("SELECT Onset FROM `Token` WHERE `Sentence` = ? AND `Number` = ? AND `PublicationId` = ?")) {
            $stmt->bind_param("iii", $annotation["Sentence"], $annotation["Onset"], $activepub->id);
            $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error());
            $result = $stmt->get_result();
            $onset = $result->fetch_row()[0];
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".Database::error());
        if($stmt = Database::prepare("SELECT Offset FROM `Token` WHERE `Sentence` = ? AND `Number` = ? AND `PublicationId` = ?")) {
            $stmt->bind_param("iii", $annotation["Sentence"], $annotation["Offset"], $activepub->id);
            $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error());
            $result = $stmt->get_result();
            $offset = $result->fetch_row()[0];
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".Database::error());
        $instances = ""; // getInstances($slotdata, $annotation);
        if (array_key_exists($annotation["Index"], $anno2triples)) {
            $instances = "\"".str_replace("\"", "\\\"", implode(" ", array_map("spimplode", $anno2triples[$annotation["Index"]])))."\"";
        }
        echo "{$annotation["Index"]}, ".className($annotation["Class"]).", $onset, $offset, \"{$annotation["Text"]}\", \"{$annotation["annometa"]}\", $instances\n";
    }
}

if (!empty($_GET['output']) && $_GET["output"] === 'annotation') {
    // rdf export with annotation data
    printAnnotations($alltypes, $triples, $anno2triples); 
} else {
    // rdf export only
    printRDF($alltypes, $triples);
}
?>

<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once ("php/constants.php");
require_once ("php/user.php");
require_once("php/publication.php");

$DBUSER = DB_USER;
$DBPASSWD = DB_PW;
$DATABASE = DB_SCHEMA;
$HOST = DB_HOST;

$publication = $_GET["publication"];

if (!isset($_GET["publication"]))
    die("No publication specified. Usage: ?publication={ID}&output={document|annotation|csv}\nfor output=annotation or csv: specify user={username}");


$foruser = null;
if (!empty($_GET['user'])) {
    $foruser = Users::byMail($_GET['user']);
    if (empty($foruser)) {
        die("Requested username not found");
    }
}
if (empty($foruser)) {
    if (!empty(Users::loginUser())) {
        $foruser = Users::loginUser();
    }
}

function getInstances($slotdata, $annotation) {
    $inst = array();
    foreach($slotdata as $entry) {
        if (!($entry->get("AnnotationId") === $annotation['Id'])) {
            continue;
        }

        $subj = "<literal>";

        if (!$entry->isDataProperty()) {
            $subj = $entry->getInstance();
        }
        
        $rel = $entry->getRelationName();
        if (empty($rel)) {
            $rel = type();
        } else {
            $rel = "<http://psink.de/scio/".$rel.">";
        }
        
        $parentId = $entry->parentId();
        $paren = "<null>";
        if (!empty($parentId)) {
            $parentEntry = null;
            foreach ($slotdata as $match) {
                if ($match->id() === $parentId) {
                    $parentEntry = $match;
                    break;
                }
            }
            if (!empty($parentEntry)) {
                $paren = $parentEntry->getInstance();
            }
        }

        if ($subj !== "<literal>") {
            $subj = "<http://scio/data/".$subj.">";
        }
        if ($paren !== "<null>") {
            $paren = "<http://scio/data/".$paren.">";
        }

        $inst[] = implode(" ", array($paren, $rel, $subj));
    }
    if (count($inst) > 0) {
        return "\"".implode(", ", $inst)."\"";
    } else {
        return "";
    }
}

function printAnnotation() {
    global $mysqli, $publication, $foruser;
    if (empty($foruser)) {
        die("missing user");
    }
    $pub = Publications::byId($publication);
    if($stmt = $mysqli->prepare("SELECT * FROM `Annotation` WHERE `PublicationId` = ? AND `User` = ? ORDER BY `Sentence`,`Onset`")) {
        $stmt->bind_param("ii", $publication, $foruser->id);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        $annotations = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
    $slotdata = $pub->slotData($foruser);
    echo "# AnnotationID, ClassType, DocCharOnset(incl), DocCharOffset(excl), Text, Meta, Instances\n";
    foreach ($annotations as $annotation) {
        if($stmt = $mysqli->prepare("SELECT Onset FROM `Token` WHERE `Sentence` = ? AND `Number` = ? AND `PublicationId` = ?")) {
            $stmt->bind_param("iii", $annotation["Sentence"], $annotation["Onset"], $publication);
            $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
            $result = $stmt->get_result();
            $onset = $result->fetch_row()[0];
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".$mysqli->error);
        if($stmt = $mysqli->prepare("SELECT Offset FROM `Token` WHERE `Sentence` = ? AND `Number` = ? AND `PublicationId` = ?")) {
            $stmt->bind_param("iii", $annotation["Sentence"], $annotation["Offset"], $publication);
            $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
            $result = $stmt->get_result();
            $offset = $result->fetch_row()[0];
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".$mysqli->error);
        $instances = getInstances($slotdata, $annotation);
        echo "{$annotation["Index"]}, ".className($annotation["Class"]).", $onset, $offset, \"{$annotation["Text"]}\", \"{$annotation["annometa"]}\", $instances\n";
    }
}
function printDocument() {
    global $mysqli, $publication;
    if($stmt = $mysqli->prepare("SELECT * FROM `Token` WHERE `PublicationId` = ? ORDER BY `Sentence`,`Number`")) {
        $stmt->bind_param("i", $publication);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        $tokens = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    $sentence = 0;
    $global = 1;
    $sentenceOnset = 0;
    echo "# DocID, SentenceNr, SenTokenPos, DocTokenPos, SenCharOnset(incl), SenCharOffset(excl), DocCharOnset(incl), DocCharOffset(excl), Text\n";
    foreach ($tokens as $token) {
        if ($sentence != $token["Sentence"]) {
            $sentence = $token["Sentence"];
            $sentenceOnset = $token["Onset"];
        }
        $docOffset = $token["Offset"];
        $senOnset = $token["Onset"] - $sentenceOnset;
        $senOffset = $token["Offset"] - $sentenceOnset;
        echo "$publication, $sentence, {$token["Number"]}, $global, $senOnset, $senOffset, {$token["Onset"]}, $docOffset, \"{$token["Text"]}\"\n";

        $global += 1;
    }
}

function className($classId) {
    global $mysqli;
    if($stmt = $mysqli->prepare("SELECT Name FROM `Class` WHERE `Id` = ?")) {
        $stmt->bind_param("i", $classId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}

function relationName($relationId) {
    global $mysqli;
    if($stmt = $mysqli->prepare("SELECT Relation FROM `Relation` WHERE `Id` = ?")) {
        $stmt->bind_param("i", $relationId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
function relationClass($relationId) {
    global $mysqli;
    if($stmt = $mysqli->prepare("SELECT `Range` FROM `Relation` WHERE `Id` = ?")) {
        $stmt->bind_param("i", $relationId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}

function dataTypeProperty($data) {
    global $mysqli;
    if($stmt = $mysqli->prepare("SELECT DataProperty FROM `Relation` WHERE `Id` = ?")) {
        $stmt->bind_param("i", $data["RelationId"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}

function scioData($data) {
    return "<http://scio/data/".className($data["ClassId"])."_{$data["id"]}>";
}
function type() {
    return "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>";
}
function scioClass($data) {
    return "<http://psink.de/scio/".className($data["ClassId"]).">";
}
function scioClassId($classId) {
    return "<http://psink.de/scio/".className($classId).">";
}
function scioClassName($class) {
    return "<http://psink.de/scio/$class>";
}
function scioRelation($relation) {
    return "<http://psink.de/scio/".relationName($relation).">";
}

function getData($dataId) {
    global $mysqli;

    if($stmt = $mysqli->prepare("SELECT * FROM `Data` WHERE `id` = ?")) {
        $stmt->bind_param("i", $dataId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC)[0];
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}

function dataTypeClass($data) {
    echo scioData($data)." ".type()." ".scioClass($data)." .\n";
}
function dataRelationData($data1, $relation, $data2) {
    echo scioData($data1)." ".scioRelation($relation)." ".scioData($data2)." .\n";
}
function dataRelationClass($data, $relation, $class) {
    echo scioData($data)." ".scioRelation($relation)." ".scioClassName($class)." .\n";
}
function dataRelationClassId($data, $relation, $class) {
    echo scioData($data)." ".scioRelation($relation)." ".scioClassId($class)." .\n";
}
function dataRelationAnnotation($data1, $relation, $data2) {
    echo scioData($data1) . " " . scioRelation($relation) . " ".scioData($data2)."\n";
}
function dataRelationAnnotationData($data, $relation, $annotation) {
    echo scioData($data) . " " . scioRelation($relation) . " \"$annotation\" .\n";
}
function isGroupProperty($data) {
    global $mysqli;

    if($stmt = $mysqli->prepare("SELECT COUNT(*) FROM `Group` WHERE `Group` = ?")) {
        $stmt->bind_param("i", $data["ClassId"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        return $result->fetch_row()[0] != 0;
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
function isNamedIndividual($data) {
    global $mysqli;

    if($stmt = $mysqli->prepare("SELECT `IndividualName` FROM `Class` WHERE `Id` = ?")) {
        $stmt->bind_param("i", $data["ClassId"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}

function printData($data, $parent = null) {
    global $mysqli;

    if($stmt = $mysqli->prepare("SELECT * FROM `Relation` WHERE `Id` = ?")) {
        $stmt->bind_param("i", $data["RelationId"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        $relation = $result->fetch_assoc();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
    $desiredClass = !$data["Parent"] || $relation["Range"] == $data["ClassId"];
    $dataTypeProperty = $relation["DataProperty"];
    $annotationId = $data["AnnotationId"];
    $dataGroup = $data["DataGroup"];

    if (!$data["Parent"] || (!isGroupProperty($data) && !dataTypeProperty($data) && !isNamedIndividual($data) && !$desiredClass)) {
        dataTypeClass($data);
    }

    if ($dataGroup) {
        $parent = getData($dataGroup);
        dataRelationData($parent, $relation["Id"], $data);
    } else if ($annotationId) {
        if ($dataTypeProperty) {
            if($stmt = $mysqli->prepare("SELECT Text FROM `Annotation` WHERE `Id` = ?")) {
                $stmt->bind_param("i", $annotationId);
                $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
                $result = $stmt->get_result();
                $annotation = $result->fetch_row()[0];
                $stmt->close();
            } else die("ERROR(".__LINE__."): ".$mysqli->error);

            dataRelationAnnotationData($parent, $relation["Id"], $annotation);
        } else if (!isNamedIndividual($data)) {
            if($stmt = $mysqli->prepare("SELECT Name FROM Class WHERE Id = (SELECT `Class` FROM Annotation WHERE `Id` = ?)")) {
                $stmt->bind_param("i", $annotationId);
                $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
                $result = $stmt->get_result();
                $class = $result->fetch_row()[0];
                $stmt->close();
            } else die("ERROR(".__LINE__."): ".$mysqli->error);

            dataRelationAnnotation($parent, $relation["Id"], $data);
        } else {
            if($stmt = $mysqli->prepare("SELECT Name FROM Class WHERE Id = (SELECT `Class` FROM Annotation WHERE `Id` = ?)")) {
                $stmt->bind_param("i", $annotationId);
                $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
                $result = $stmt->get_result();
                $class = $result->fetch_row()[0];
                $stmt->close();
            } else die("ERROR(".__LINE__."): ".$mysqli->error);

            dataRelationClass($parent, $relation["Id"], $class);
        }
    } else {
        if (!$desiredClass)
            dataRelationClassId($parent, $relation["Id"], $data["ClassId"]);
    }


    if($stmt = $mysqli->prepare("SELECT * FROM `Data` WHERE `Parent` = ?")) {
        $stmt->bind_param("i", $data["id"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        $children = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
//
//    $parentClass = $data["ClassId"];
//
    foreach ($children as $child) {
//        printData($child["id"]);
//        $relation = $child["RelationId"];
//        $childClass = $child["ClassId"];
//        $annotation = $child["AnnotationId"];
//        if (dataTypeProperty($relation)) {
//            dataRelationAnnotation($data["ClassId"], $dataId, $relation, $child["AnnotationId"]);
//        } else {
//            if (relationClass($relation) != $childClass) {
//                dataRelationClass($data["ClassId"], $dataId, $relation, $childClass);
//            }
//        }
        printData($child, $data);
    }
}

function printCsv() {
    global $mysqli, $publication;

    //if($stmt = $mysqli->prepare("SELECT * FROM `Data` WHERE `PublicationId` = ? AND `RelationId` IS NULL ORDER BY `Id`")) {
    if($stmt = $mysqli->prepare("SELECT * FROM Data left join Class ON Data.ClassId = Class.Id left join Annotation ON Data.AnnotationId = Annotation.Id left join Relation ON Relation.Id = Data.RelationId WHERE Data.`PublicationId` = ? AND `RelationId` IS NULL ORDER BY Data.`Id`")) {
        $stmt->bind_param("i", $publication);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
        $parents = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    foreach ($parents as $parent) {
        /*foreach ($parent as $key => $val) {
            print("$key => $val\n");
    }*/
        printData($parent);
    }
}

if (!isset($_GET["output"]))
    die("No output specified. Usage: ?publication={ID}&output={document|annotation|csv}");


$outfile = 'document';
$exportpub = Publications::byId($_GET['publication']);
if ($exportpub) {
    $filename = $exportpub->name;
    $outfile = str_replace("/","",$filename);
    $filename = str_replace("+", "%20", urlencode($filename));
}
if (!empty($foruser)) { 
    $outfile .= "_".$foruser->mail;
} else {
    $outfile .= "_export";
}
if ($_GET['output'] == 'annotation') {
    $outfile.= ".annodb";
} else {
    $outfile.= ".csv";
}

header('Content-disposition: attachment; filename="'.$outfile.'"');
header("Content-type: text/plain; charset=utf-8");
switch ($_GET["output"]) {
    case "document": printDocument(); break;
    case "annotation": printAnnotation(); break;
    case "csv": 
        // printCsv();
        if (!empty($_GET['publication']) && is_numeric($_GET['publication'])) {
            header("Location: rdf.php?publication=".$_GET['publication']);
        }
        break;
    default: die("Wrong output. Usage: ?publication={ID}&output={document|annotation|csv}");
}



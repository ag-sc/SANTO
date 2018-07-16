<?php

require_once("php/functions.php");

die("disable die() command if you are really sure");
$version = 62;

$classes = array();
$csvFile = file("data/scio_v_{$version}_classes.csv");
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $classes[] = array_combine(array("class"), str_getcsv($line, "\t"));
}

$relations = array();
$csvFile = file("data/scio_v_{$version}_relations.csv");
$i = 0;
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $relations[] = array_combine(array("domain", "relation", "range", "from", "to", "isDataTypeProperty"), str_getcsv($line, "\t"));
    $relations[$i]["from"] = ($relations[$i]["from"] == "?" ? 1 : $relations[$i]["from"]);
    $relations[$i]["to"] = ($relations[$i]["to"] == "?" ? 1 : $relations[$i]["to"]);
    $relations[$i]["isDataTypeProperty"] = (bool)$relations[$i]["isDataTypeProperty"];
    ++$i;
}

$subclasses = array();
$csvFile = file("data/scio_v_{$version}_subclasses.csv");
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $subclasses[] = array_combine(array("superclass", "subclass"), str_getcsv($line, "\t"));
}

$groups = array();
$csvFile = file("data/groups.csv");
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    var_dump(str_getcsv($line, "\t"));
    $groups[] = array_combine(array("group", "heading", "name", "order"), str_getcsv($line, "\t"));
}




foreach ($classes as $class) {
    if($stmt = $mysqli->prepare("INSERT IGNORE INTO `Class` (`Name`) VALUES(?);")) {
        $stmt->bind_param("s", $class["class"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
foreach ($groups as $group) {
    if($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $group["group"]);
        $stmt->execute() or die("ERROR: ".$mysqli->error);
        $stmt->bind_result($groupId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    if($stmt = $mysqli->prepare("INSERT IGNORE INTO `Group` (`Group`, `Heading`, `Order`) VALUES(?, ?, ?);")) {
        $stmt->bind_param("isi", $groupId, $group["heading"], $group["order"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
foreach ($relations as $relation) {
    if($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $relation["domain"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($domainId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
    if($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $relation["range"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($rangeId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    if($stmt = $mysqli->prepare("INSERT IGNORE INTO `Relation` (`Domain`, `Relation`, `Range`, `From`, `To`, `DataProperty`) VALUES(?, ?, ?, ?, ?, ?);")) {
        $stmt->bind_param("isissi", $domainId, $relation["relation"], $rangeId, $relation["from"], $relation["to"], $relation["isDataTypeProperty"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
foreach ($subclasses as $subclass) {
    if($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $subclass["superclass"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($superclassId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
    if($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $subclass["subclass"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($subclassId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    if($stmt = $mysqli->prepare("INSERT IGNORE INTO `SubClass` (`SuperClass`, `SubClass`) VALUES(?, ?);")) {
        $stmt->bind_param("ii", $superclassId, $subclassId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}


$annotations = array();
$csvFile = file("data/Ates_et_al.a1");
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $annotation = array_combine(array("index", "class", "onset", "offset", "text"), str_getcsv($line, "\t"));
    $annotation["onset"] = (int)$annotation["onset"];
    $annotation["offset"] = $annotation["onset"] + strlen($annotation["text"]);
    $annotations[] = $annotation;
}
foreach ($annotations as $annotation) {
    if($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $annotation["class"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($classId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    if($stmt = $mysqli->prepare("INSERT IGNORE INTO `Annotation` (`PublicationId`, `Index`, `Class`, `Onset`, `Offset`, `Text`) VALUES(?, ?, ?, ?, ?, ?);")) {
        $publicationId = 1;
        $stmt->bind_param("isiiis", $publicationId, $annotation["index"], $classId, $annotation["onset"], $annotation["offset"], $annotation["text"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}

<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid method');
}

$classes = array();
$csvFile = file($_FILES['classes']['tmp_name']);
$i = 0;
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $classes[] = array_combine(array("class", "individualName", "description"), str_getcsv($line, "\t"));
//    ++$i;
//    $classes[$i]["individualName"] = $classes[$i]["individualName"] == "true";
}
echo 'found '.sizeof($classes).' classes<br />';
//var_dump($classes);


$relations = array();
$csvFile = file($_FILES['relations']['tmp_name']);
$i = 0;
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $relations[] = array_combine(array("domain", "relation", "range", "from", "to", "isDataTypeProperty", "mergedName", "description"), str_getcsv($line, "\t"));
    $relations[$i]["from"] = ($relations[$i]["from"] == "?" ? 1 : $relations[$i]["from"]);
    $relations[$i]["to"] = ($relations[$i]["to"] == "?" ? 1 : $relations[$i]["to"]);
    $relations[$i]["isDataTypeProperty"] = $relations[$i]["isDataTypeProperty"] == 'true';
    ++$i;
}
echo 'found '.sizeof($relations).' relations<br />';

$subclasses = array();
$csvFile = file($_FILES['subclasses']['tmp_name']);
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $subclasses[] = array_combine(array("superclass", "subclass"), str_getcsv($line, "\t"));
}
echo 'found '.sizeof($subclasses).' subclasses<br />';

$groups = array();
$csvFile = file($_FILES['groups']['tmp_name']);
foreach ($csvFile as $line) {
    if (empty(trim($line))) {
        continue;
    }
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        continue;
    }
    $groups[] = array_combine(array("group", "heading", "name", "order"), str_getcsv($line, "\t"));
}
echo 'found '.sizeof($groups).' groups<br />';

require_once 'php/functions.php';


$mysqli->query("SET FOREIGN_KEY_CHECKS = 0") or die("ERROR(".__LINE__."): ".$mysqli->error);
$mysqli->query("TRUNCATE TABLE `Annotation`") or die("ERROR(".__LINE__."): ".$mysqli->error);
$mysqli->query("TRUNCATE TABLE `Class`") or die("ERROR(".__LINE__."): ".$mysqli->error);
$mysqli->query("TRUNCATE TABLE `Data`") or die("ERROR(".__LINE__."): ".$mysqli->error);
$mysqli->query("TRUNCATE TABLE `Group`") or die("ERROR(".__LINE__."): ".$mysqli->error);
// $mysqli->query("TRUNCATE TABLE `Publication`") or die("ERROR(".__LINE__."): ".$mysqli->error);
$mysqli->query("TRUNCATE TABLE `Relation`") or die("ERROR(".__LINE__."): ".$mysqli->error);
$mysqli->query("TRUNCATE TABLE `SubClass`") or die("ERROR(".__LINE__."): ".$mysqli->error);
// $mysqli->query("TRUNCATE TABLE `Token`") or die("ERROR(".__LINE__."): ".$mysqli->error);
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1") or die("ERROR(".__LINE__."): ".$mysqli->error);

echo "truncated\n<br />";

$mysqli->query("START TRANSACTION") or die("ERROR(".__LINE__."): ".$mysqli->error);

print_r("inserting... classes".PHP_EOL);
foreach ($classes as $class) {
    if($stmt = $mysqli->prepare("INSERT INTO `Class` (`Name`, `IndividualName`, `Description`) VALUES(?, ?, ?);")) {
        $individualName = $class["individualName"] == "true";
        $stmt->bind_param("sis", $class["class"], $individualName, $class["description"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
print_r("groups".PHP_EOL);
foreach ($groups as $group) {
    if($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $group["group"]);
        $stmt->execute() or die("ERROR: ".$mysqli->error);
        $stmt->bind_result($groupId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    if($stmt = $mysqli->prepare("INSERT INTO `Group` (`Group`, `Heading`, `Order`) VALUES(?, ?, ?);")) {
        $stmt->bind_param("isi", $groupId, $group["heading"], $group["order"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
print_r("relations".PHP_EOL);
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

    if($stmt = $mysqli->prepare("INSERT INTO `Relation` (`Domain`, `Relation`, `Range`, `From`, `To`, `DataProperty`, `MergedName`) VALUES(?, ?, ?, ?, ?, ?, ?);")) {
        if (!empty($relation["mergedName"])) {
            $relation["mergedName"] = substr($relation["mergedName"], 0, 60);
        }
        $stmt->bind_param("isissis", $domainId, $relation["relation"], $rangeId, $relation["from"], $relation["to"], $relation["isDataTypeProperty"], $relation["mergedName"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
print_r("subclasses".PHP_EOL);
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

    if($stmt = $mysqli->prepare("INSERT INTO `SubClass` (`SuperClass`, `SubClass`) VALUES(?, ?);")) {
        $stmt->bind_param("ii", $superclassId, $subclassId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}
$mysqli->query("COMMIT");
echo "all done<br />";

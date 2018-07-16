<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}
header('Content-type:application/json;charset=utf-8');

require_once "../php/functions.php";

if (isset($_POST["relationId"])) {
    $relations = parseRelations();

    if ($stmt = $mysqli->prepare("SELECT `Range` FROM `Relation` WHERE `Id` = ?;")) {
        $stmt->bind_param("i", $_POST["relationId"]);
        $stmt->execute() or die("ERROR(" . __LINE__ . "): " . $mysqli->error);
        $stmt->bind_result($rangeId);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(" . __LINE__ . "): " . $mysqli->error);
} else {
    $rangeId = $_POST["classId"];
}

$parsedSubClasses = $subClasses;
$subClassIds = getSubclasses($rangeId);
$subClasses = array();

$query = "SELECT `Id`, `Name` FROM `Class` WHERE `Id` = ?".str_repeat(" OR `Id` = ?", sizeof($subClassIds)-1)." ORDER BY `Name`";
if ($stmt = $mysqli->prepare($query)) {
    $params = array();
    for ($i = 0; $i < sizeof($subClassIds); ++$i) {
        $params[] = &$subClassIds[$i];
    }
    call_user_func_array(array($stmt, "bind_param"), array_merge(array(str_repeat("i", sizeof($subClassIds))), $params));
    $stmt->execute();
    $stmt->bind_result($subClassId, $subClass);
    while($stmt->fetch()) {
        $subClasses[] = array("id" => $subClassId, "name" => $subClass,
            "subclasses" => !isset($parsedSubClasses[$subClassId]) ? 0 : sizeof($parsedSubClasses[$subClassId]));
    }
    $stmt->close();
}

echo json_encode(utf8ize($subClasses), JSON_PRETTY_PRINT);

<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";

$overwrite = isset($_POST["overwrite"]);

$manualstate = (!empty($_POST['removed'])) ? 0 : 1;

echo "converting ".$_POST["dataId"]." to ".$_POST["classId"];
if($stmt = $mysqli->prepare("UPDATE `Data` SET ManuallySet = ?, `classId`= ? WHERE `Id`= ?")) {
    $stmt->bind_param("iii", $manualstate, $_POST["classId"], $_POST["dataId"]);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
} else die($mysqli->error);

if ($overwrite) {
    if ($stmt = $mysqli->prepare("UPDATE `Data` SET ManuallySet = ?, `AnnotationId`= NULL WHERE `Id`= ?")) {
        $stmt->bind_param("ii", $manualstate, $_POST["dataId"]);
        $stmt->execute() or die("ERROR: " . $mysqli->error);
    } else die($mysqli->error);
}


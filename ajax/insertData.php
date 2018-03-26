<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid method');
}

require_once "../php/constants.php";

if (!is_numeric($_POST['annotationId']) || !is_numeric($_POST['dataId'])) {
    die("Argument invalid (non numeric identifier)");
}

echo htmlentities($_POST["annotationId"])."\n";
echo htmlentities($_POST["dataId"]);

if($stmt = $mysqli->prepare("UPDATE `Data` SET ManuallySet = 1, `AnnotationId`= (SELECT `Id` FROM `Annotation` WHERE `Index` = ? LIMIT 1) WHERE `Id`= ?")) {
    $stmt->bind_param("ii", $_POST["annotationId"], $_POST["dataId"]);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
    $stmt->close();
    return "Data Bound";
}
return $mysqli->error;

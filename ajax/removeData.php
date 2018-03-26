<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";

if($stmt = $mysqli->prepare("DELETE FROM `Data` WHERE `Id`= ? ")) {
    $stmt->bind_param("i", $_POST["dataId"]);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
}
//if($stmt = $mysqli->prepare("UPDATE `Data` SET `AnnotationId` = NULL WHERE `id`= ? ")) {
//    $stmt->bind_param("i", $_POST["dataId"]);
//    $stmt->execute() or die("ERROR: ".$mysqli->error);
//}
//if($stmt = $mysqli->prepare("UPDATE `Data` SET `ClassId` = (SELECT `Range` FROM `Relation` JOIN `Data` ON Relation.Id = Data.RelationId WHERE Data.Id = ?) WHERE `Id`= ? ")) {
//    $stmt->bind_param("ii", $_POST["dataId"], $_POST["dataId"]);
//    $stmt->execute() or die("ERROR: ".$mysqli->error);
//}

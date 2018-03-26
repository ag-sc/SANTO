<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";

if($stmt = $mysqli->prepare("UPDATE `Data` SET `AnnotationId`= NULL, ManuallySet = 0 WHERE `Id`= ?")) {
    $stmt->bind_param("i", $_POST["dataId"]);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
    return "Data Removed";
} else {
    return $mysqli->error;
}

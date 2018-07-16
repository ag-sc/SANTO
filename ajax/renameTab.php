<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";

if($stmt = $mysqli->prepare("UPDATE `Data` SET `Name`= ? WHERE `Id`= ?")) {
    $name = $_POST["name"];
    if (empty($name) || strlen($name) === 0)
        return;
    $stmt->bind_param("si", $_POST["name"], $_POST["dataId"]);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
}
echo $mysqli->error;

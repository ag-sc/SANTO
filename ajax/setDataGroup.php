<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";

if($stmt = $mysqli->prepare("UPDATE `Data` SET `DataGroup`= ? WHERE `Id`= ?")) {
    $groupId = $_POST["groupId"] != -1 ? $_POST["groupId"] : null;
    $stmt->bind_param("ii", $groupId, $_POST["dataId"]);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
    return "Data Bound";
}
return $mysqli->error;

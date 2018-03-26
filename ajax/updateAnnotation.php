<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";
require_once("../php/database.php");


if($stmt = $mysqli->prepare("UPDATE `Annotation` SET `Onset`=?, `Offset`=?, `Text`=? WHERE `Id`= ?")) {
    $stmt->bind_param("iisi", $_POST["onset"], $_POST["offset"], $_POST['text'], $_POST["id"]);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
    return "Data Bound";
}
return $mysqli->error;

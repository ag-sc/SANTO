<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";

if ($stmt = $mysqli->prepare("UPDATE User_Publication SET `Ready` = ? WHERE UserId = ? AND PublicationId = ?")) {
    $stmt->bind_param("iii", $_POST["ready"], $_SESSION['user'], $_SESSION['document']);
    $stmt->execute() or die("ERROR: " . $mysqli->error);
    echo $_POST["ready"], ", ", $_SESSION['user'], ", ", $_SESSION['document'];
} else die($mysqli->error);
$_SESSION["ready"] = $_POST["ready"];


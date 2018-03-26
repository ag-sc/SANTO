<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";

if ($stmt = $mysqli->prepare("INSERT INTO `Data` (`ClassId`, `Parent`, `RelationId`, `User`, `PublicationId`) VALUES (?, ?, ?, ?, ?);")) {
    $stmt->bind_param("iiiii", $_POST["class"], $_POST["parent"], $_POST["relation"], $_SESSION['user'], $_SESSION['document']);
    $stmt->execute();
    $stmt->free_result();
} else die($mysqli->error);

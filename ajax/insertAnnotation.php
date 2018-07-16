<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

if ($_POST['annotation'] == -1) return;
if ($_POST['sentence'] == -1) return;
if ($_POST['endToken'] < $_POST['startToken']) return;

if (isset($_POST['description']) && $_POST['description'] != "")
    $description = $_POST['description'];
else
    $description = null;

require_once "../php/constants.php";

$class = $_POST['annotation'];

if (isset($_POST['index']) && $_POST['index'] != -1) {
    $index = $_POST['index'];
} else if ($stmt = $mysqli->prepare("SELECT Id from Annotation ORDER BY Id DESC LIMIT 1")) {
    $stmt->execute() or die("ERROR: ".$mysqli->error);
    $result = $stmt->get_result();
    $arr = $result->fetch_row();
    $index = $arr[0] + 1; // TODO do something reliable here
} else die($mysqli->error);


if($stmt = $mysqli->prepare("INSERT INTO `Annotation` (`PublicationId`, `Index`, `Class`, `Sentence`, `Onset`, `Offset`, `Text`, `User`, `annometa`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
    $stmt->bind_param("isiiiisis", $_SESSION['document'], $index, $class, $_POST['sentence'], $_POST['startToken'], $_POST['endToken'], $_POST['text'], $_SESSION['user'], $description);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
} else die($mysqli->error);


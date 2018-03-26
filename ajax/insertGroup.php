<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

header('Content-Type: application/json');

require_once "../php/functions.php";

$return = $_POST;
$return["success"] = false;

if (!$stmt = $mysqli->prepare("INSERT INTO `Data` (`ClassId`, `User`, `PublicationId`) VALUES(?, ?, ?)")) {
    die();
}
$stmt->bind_param("iii", $_POST["classId"], $_SESSION['user'], $_SESSION["document"]);
$stmt->execute() or die();
$return["id"] = $mysqli->insert_id;
$stmt->close();

if ($stmt = $mysqli->prepare("SELECT `Description` FROM Class WHERE Id = ?")) {
    $stmt->bind_param("i", $_POST["classId"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $r = $result->fetch_row();
    $return["description"] = $r[0];
} else {
    echo json_encode($return);
}

$return["success"] = true;
echo json_encode($return);

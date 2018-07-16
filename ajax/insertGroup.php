<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return utf8_encode($d);
    }
    return $d;
}

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
    echo json_encode(utf8ize($return));
}

$return["success"] = true;
echo json_encode(utf8ize($return));

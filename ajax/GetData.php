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

$dataId = isset($_POST["dataId"]) ? $_POST["dataId"] : $_GET["dataId"];

$query = "SELECT * FROM Data WHERE `id` = ?";
if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param("i", $dataId);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(utf8ize($result->fetch_all(MYSQLI_ASSOC)), JSON_PRETTY_PRINT);
}

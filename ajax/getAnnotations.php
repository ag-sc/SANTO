<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

header('Content-Type: application/json');

require_once "../php/functions.php";

if (isset($_POST["superclass"])) {
    $classes = getSubclassesName($_POST["superclass"]);
    array_multisort($classes);
    echo json_encode($classes);
    return;
}

$query = "SELECT `Id`, `Name`, `Description` FROM `Class` ORDER BY `Name` ASC";
if ($stmt = $mysqli->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
} else die($mysqli->error);

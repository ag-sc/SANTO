<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

header('Content-Type: application/json');

require_once "../php/functions.php";
require_once "../php/database.php";
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

if (isset($_POST["superclass"])) {
    $classes = getSubclassesName($_POST["superclass"]);
    array_multisort($classes);
    echo json_encode(utf8ize($classes));
    return;
}

$query = "SELECT `Id`, `Name`, `Description` FROM `Class` ORDER BY `Name` ASC";

if ($stmt = Database::prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $allres = $result->fetch_all(MYSQLI_ASSOC);
    $allres = utf8ize($allres);
    echo json_encode($allres, JSON_PRETTY_PRINT);
} else die($mysqli->error);

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

require_once "../php/functions.php";

global $mysqli;
if($stmt = $mysqli->prepare("SELECT UserId AS Id, Mail, Ready FROM User_Publication JOIN User U ON User_Publication.UserId = U.Id WHERE PublicationId = (?) AND U.Id != 1 ")) {
    $stmt->bind_param("i", $_SESSION['document']);
    $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    $result = $stmt->get_result();
    $arr = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else die("ERROR(".__LINE__."): ".$mysqli->error);

echo json_encode(utf8ize($arr), JSON_PRETTY_PRINT);

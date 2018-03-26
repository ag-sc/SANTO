<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";
require_once("../php/publication.php");

$activepub = null;
if (isset($_SESSION['document'])) {
    $activepub = Publications::byId($_SESSION['document']);
}

$activepub->setReadyCuration(Users::loginUser(), $_POST['ready']);
$_SESSION["ready"] = $_POST["ready"];

?>

<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

if (isset($_POST['showAll']))
    $_SESSION['showAll'] = $_POST['showAll'] == "true";
if (isset($_POST['showPredefined']))
    $_SESSION['showPredefined'] = $_POST['showPredefined'] == 'true';

var_dump($_SESSION);

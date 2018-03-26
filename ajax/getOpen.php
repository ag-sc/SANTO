<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

session_start();
echo isset($_SESSION['opened'][$_POST["dataId"]]) ? $_SESSION['opened'][$_POST["dataId"]] : 0;

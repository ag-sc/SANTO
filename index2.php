<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once("php/publication.php");
$activepub = null;
if (isset($_SESSION['document'])) {
    $activepub = Publications::byId($_SESSION['document']);
}

function action_list() {
    $actions = array();
    foreach(glob(__DIR__."/blocks/*.inc.php") as $includefile) {
        $actions[] = str_replace(__DIR__."/blocks/", "", str_replace(".inc.php", "", $includefile));
    }
    return $actions;
}
function action_allowed($action) {
    return in_array($action, action_list());
}
function action_include($action) {
    if (!action_allowed($action)) {
        echo "Forbidden";
    } else {
        include(__DIR__."/blocks/".$action.".inc.php");
    }
}

global $active_action;
$active_action = "none";
if (isset($_GET) && !empty($_GET["action"]) and action_allowed($_GET["action"])) {
    $active_action = $_GET["action"];
} else if (isset($_POST) && !empty($_POST["action"]) and action_allowed($_POST["action"])) {
    $active_action = $_POST["action"];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // handle data processing for the given action, render view if action id contains ".ui"
    if (strpos($active_action, ".ui") === false) {
        action_include($active_action);
    } else {
    include("blocks/header.php");
    action_include($active_action);
    include("blocks/footer.php");
    }
} else {
    // render the view part of the action
    include("blocks/header.php");
    action_include($active_action);
    include("blocks/footer.php");
}
?>

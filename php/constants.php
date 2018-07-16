<?php

require_once("configuration.php");

define("DB_HOST", Configuration::instance()->get("database", "host"));
define("DB_USER", Configuration::instance()->get("database", "user"));
define("DB_PW", Configuration::instance()->get("database", "password"));
define("DB_SCHEMA", Configuration::instance()->get("database", "schema"));

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_SCHEMA);
$mysqli->set_charset("utf8");
$pepper = Configuration::instance()->get("crypto", "pepper");
?>

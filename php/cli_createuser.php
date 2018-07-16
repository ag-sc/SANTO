<?php
if (count($argv) < 3) {
    die("Error: Syntax is php_createuser.php mail password\n");
}

$mail = $argv[1];
$password = $argv[2];
if (empty($mail) || empty($password)){
    die("empty mail or pw\n");
}
$curator = false;
if (sizeof($argv) > 2 && !empty($argv[3]) && $argv[3] == '1') {
    $curator = true;
}
echo "registering user $mail $password\n";
require_once("functions.php");
registerUser($mail, $password, $curator)

?>

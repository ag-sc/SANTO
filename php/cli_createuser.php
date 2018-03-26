<?php
if (count($argv) < 3) {
    die("Error: Syntax is php_createuser.php mail password\n");
}

$mail = $argv[1];
$password = $argv[2];
if (empty($mail) || empty($password)){
    die("empty mail or pw\n");
}
echo "registering user $mail $password\n";
require_once("functions.php");
registerUser($mail, $password)

?>

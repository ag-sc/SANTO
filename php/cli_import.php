<?php
require_once("publication.php");
if (count($argv) < 2) {
    die("Missing filename");
}
$target = $argv[1];
echo "trying to import from $target".PHP_EOL;
$action = $argv[2];

$actions = array("--import", "--skiptokens", "--metadata");

if (!empty($action)) {
    if ($action  === '--skiptokens') {
        Publications::bulkinsert($target, true);
    } else if ($action === '--metadata') {
        Publications::insertmeta($target);
    } else if ($action === '--import') {
        Publications::bulkinsert($target, false);
    } else {
        echo "Unknown action: $action (allowed: ".implode(", ", $actions).")".PHP_EOL;
    }
} else {
    echo "No action parameter. Allowed: ".implode(", ", $actions).")".PHP_EOL;
}

echo "all done.".PHP_EOL;
?>

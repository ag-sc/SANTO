<?php
require_once("publication.php");
if (count($argv) < 2) {
    die("Missing filename");
}
$target = $argv[1];
echo "trying to import from $target".PHP_EOL;

Publications::bulkinsert($target);

echo "all done.".PHP_EOL;
?>

<?php
if (count($argv) < 2) {
    die("Error: Syntax is php_export.php target_file\n");
}

require_once("publication.php");
require_once("configuration.php");
$config = Configuration::instance();

if (empty($config->get("misc", "baseuri"))) {
    die("missing configuration option \"baseuri\" in section [misc]");
}
$baseuri = "http://localhost/".$config->get("misc", "baseuri")."/";

$targetfile = $argv[1];
echo "exporting to - $targetfile".PHP_EOL;

$allpub_count = count(Publications::all());
$counter = 0;

if (!file_exists($targetfile)) {
    mkdir($targetfile) or die("Failed to create target directory");
}

function parseheader($header) {
    $query = "Content-disposition: ";
    foreach($header as $h) {
        if (substr($h, 0, strlen($query)) === $query) {
            $h = str_replace("Content-disposition: attachment; filename=\"", "", $h);
            $h = substr($h, 0, strlen($h) - 1);
            return $h;
        }
    }
    return null;
}

foreach(Publications::all() as $pub) {
    $counter++;
    echo "Exporting [".$counter."/".$allpub_count."] ".$pub.PHP_EOL;
    $docuri = $baseuri."export.php?publication=".$pub->id."&output=document";
    $docexport = file_get_contents($docuri);
    $dlfile = $targetfile."/".parseheader($http_response_header);
    file_put_contents($dlfile, $docexport);
    echo "\t<$dlfile> written".PHP_EOL;
    
    foreach ($pub->users() as $annotator) {
        echo "\tUser: $annotator".PHP_EOL;
        $annouri = $baseuri."rdf.php?publication=".$pub->id."&output=annotation&user=".$annotator->mail;
        $docexport = file_get_contents($annouri);
        $dlfile = $targetfile."/".parseheader($http_response_header);
        file_put_contents($dlfile, $docexport);
        echo "\t<$dlfile> written".PHP_EOL;

        $rdfuri = $baseuri."rdf.php?publication=".$pub->id."&user=".$annotator->mail;
        $docexport = file_get_contents($rdfuri);
        $dlfile = $targetfile."/".parseheader($http_response_header);
        file_put_contents($dlfile, $docexport);
        echo "\t<$dlfile> written".PHP_EOL;
    }
}

echo "export done - $targetfile".PHP_EOL;



?>

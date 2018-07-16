<?php
die("disabled");
header('Content-type:application/json;charset=utf-8');

define("domainClass", 0);
define("relation", 0);
define("rangeClass", 0);
define("from", 1);
define("to", 2);
define("isDataTypeProperty", 3);
define("superClass", 0);
define("subClass", 1);
define("properties", "properties");
define("subClasses", "subClasses");

$database = "anno";

$relations = array();
$csvFile = file("data/scio_v_41_relations.csv");
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') {
        $keys = str_getcsv(ltrim($line, '#'), "\t");
        array_shift($keys);
        array_shift($keys);
        continue;
    }
    $csvLine = str_getcsv($line, "\t");
    $key = array_shift($csvLine);
    $relations[$key][] = $csvLine;
}
if (!isset($keys)) return null;
$superClasses = array();
$csvFile = file("data/scio_v_41_subclasses.csv");
foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') continue;
    $csvLine = str_getcsv($line, "\t");
    $superClasses[$csvLine[subClass]] = $csvLine[superClass];
}
$topClasses = $superClasses;
$classes = array();
$csvFile = file("data/scio_v_41_classes.csv");
sort($csvFile);

foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') continue;
    if (!isset($keys)) return null;
    $csvLine = str_getcsv($line, "\t");
    $hasSuperclass = array_key_exists($csvLine[domainClass], $superClasses);
    for ($class = $csvLine[domainClass];
         array_key_exists($class, $superClasses);
         $class = $superClasses[$class]);
    $topClasses[$csvLine[domainClass]] = $class;
    if (!array_key_exists($class, $classes))
        $classes[$class] = array(properties => array(), subClasses => array());
    if (array_key_exists($csvLine[domainClass], $relations)) {
        $data = array();
        foreach ($relations[$csvLine[domainClass]] as $relation) {
            $key = array_shift($relation);
            $data[$key] = array_combine($keys, $relation);
        }
        $classes[$class][properties] = array_merge($classes[$class][properties], $data);
    }
    $classes[$class][subClasses][] = "'".$csvLine[domainClass]."'";
}



echo json_encode($classes, JSON_PRETTY_PRINT);

foreach ($classes as $name => $class)
    if (sizeof($class[properties]) == 0 && sizeof($class[subClasses]) == 1)
        unset($classes[$name]);

$subQueries = array();
foreach ($classes as $name => $class) {
    $query = "CREATE TABLE IF NOT EXISTS `$database`.`$name` (\n";
    $query .= "    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT";
    if (sizeof($class[subClasses]) > 1) {
        $query .= ",\n    `type` enum(".implode(",", $class[subClasses]).")";
    }
    foreach ($class[properties] as $rowName => $property) {
        $query .= ",\n    `$rowName` ";
        $rangeClass = $property["rangeClass"];
        $topClass = $topClasses[$rangeClass];
        if ($property["isDataTypeProperty"] && !array_key_exists($class, $classes)) {
            $query .= "TEXT";
        } else {
            $query .= "INT UNSIGNED";
            $subQuery = "CREATE TABLE IF NOT EXISTS `$database`.`$name" . "_" . "$rowName` (";
            $subQuery .= "\n    `$name` INT UNSIGNED NOT NULL";
            $subQuery .= ",\n    `$rangeClass` INT UNSIGNED NOT NULL";
            if ($property["isDataTypeProperty"])
                $subQuery .= ",\n    `value` TEXT NOT NULL";
            $subQuery .= ",\n    INDEX `fk_$name" . "_$rowName"."_1` (`$name` ASC)";
            $subQuery .= ",\n    INDEX `fk_$name" . "_$rowName"."_2` (`$rangeClass` ASC)";
            $subQuery .= ",\n    CONSTRAINT `fkc_$name" . "_$rowName"."_1` FOREIGN KEY (`$name`)";
            $subQuery .= "\n        REFERENCES `$database`.`$name` (`id`) ON DELETE CASCADE ON UPDATE CASCADE";
            $subQuery .= ",\n    CONSTRAINT `fkc_$name" . "_$rowName"."_2` FOREIGN KEY (`$rangeClass`)";
            $subQuery .= "\n        REFERENCES `$database`.`$topClass` (`id`) ON DELETE CASCADE ON UPDATE CASCADE";
            $subQuery .= "\n);";
            $subQueries[] = $subQuery;
        }
    }

    $query .= ",\n    PRIMARY KEY (`id`), UNIQUE INDEX `pk_id` (`id` ASC)\n);";
    echo "$query\n\n";
}
foreach ($subQueries as $subQuery) {
    echo "$subQuery\n\n";
}
return;

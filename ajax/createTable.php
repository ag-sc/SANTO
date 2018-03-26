<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

require_once("../php/constants.php");
require_once("../php/functions.php");

header('Content-Type: application/json');

$relations = parseRelations();
function createAnnotationTree($top, $depth = 1)
{
    global $relations;
    $data = array();
    if (!isset($relations[$top])) return;
    foreach ($relations[$top] as $relation)
        if (isset($relations[$relation["rangeClass"]]))
            $data[] = array("title" => $relation["relation"], "children" => createAnnotationTree($relation["rangeClass"], $depth+1));
        else
            $data[] = array("title" => $relation["relation"]);
    return $data;
}
echo json_encode(createAnnotationTree($_GET["group"]), JSON_PRETTY_PRINT);

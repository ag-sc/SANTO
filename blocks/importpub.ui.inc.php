<?php

require_once "php/constants.php";
require_once "php/functions.php";
require_once ("plugins/EasyRdf.php");

if($stmt = Database::prepare("SELECT Id, Mail FROM `User` WHERE `Id` != 1")) {
    $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error());
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else die("ERROR(".__LINE__."): ".Database::error());

?>

<h1>Import publication</h1>
<form action="index2.php?action=importpub.ui" id="form" name="import" method="post" accept-charset="utf-8" enctype="multipart/form-data">
    <table>
        <tr>
            <td><label for="name">Name</label></td>
            <td><input type="text" name="name" id="name"></td>
        </tr>
        <tr>
            <td><label for="tokens">Publication<span style="color: red">*</span></label></td>
            <td><input type="file" accept="text/csv" name="tokens" id="tokens" required></td>
        </tr>
        <tr>
            <td><label for="annotations">Annotations</label></td>
            <td><input type="file" accept=".annodb" name="annotations" id="annotations"/></td>
        </tr>
        <tr>
            <td><label for="rdf">RDF</label></td>
            <td><input type="file" accept=".n-triples" name="rdf" id="rdf"/></td>
        </tr>
        <tr>
            <td><label for="users">Users</label></td>
            <td><select name="users[]" id="users" multiple><?php
                    foreach ($users as $value) {
                        echo "<option value='{$value['Id']}'>{$value['Mail']}</option>";
                    }
                    ?></select></td>
        </tr>
        <tr>
            <td><input id="submitType" type="hidden" name="submitType"/></td>
        </tr>
        <tr>
            <td><input type="submit" value="Import"  onclick="(function() {
                        document.getElementById('submitType').value='importpub';
                })()" /></td>
        </tr>
    </table>
</form>

<?php


function get_class_name_id($rdf, $name) {
    $type_str = "http://www.w3.org/1999/02/22-rdf-syntax-ns#type";
    if (!array_key_exists($name, $rdf)
        || !array_key_exists($type_str, $rdf[$name])
        || !sizeof($rdf[$name][$type_str])
        || !array_key_exists("value", $rdf[$name][$type_str][0]))
        return null;

    $class_name = substr($rdf[$name][$type_str][0]["value"], strlen("http://psink.de/scio/"));
    if ($stmt = Database::prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
        $stmt->bind_param("s", $class_name);
        $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
        $stmt->bind_result($class_id);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(" . __LINE__ . "): " . Database::error());

    return array("name" => $class_name, "id" => $class_id, "rdf" => $name);
}

function get_relation_name_id($relation_rdf, $domain_id) {
    $relation_name = substr($relation_rdf, strlen("http://psink.de/scio/"));
    foreach (getSuperclasses($domain_id) as $superclass_id) {
        if ($stmt = Database::prepare("SELECT `Id`, `Range`
                                        FROM `Relation`
                                        WHERE `Relation` = ? && `Domain` = ?;")) {
            $stmt->bind_param("si", $relation_name, $superclass_id);
            $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
            $stmt->bind_result($relaltion_id, $range_id);
            $stmt->fetch();
            $stmt->close();
        } else die("ERROR(" . __LINE__ . "): " . Database::error());

        if ($relaltion_id) {
            return array("name" => $relation_name, "id" => $relaltion_id, "rdf" => $relation_rdf, "range" => $range_id);
        }
    }
    return null;
}

if (isset($_FILES['tokens'])) {
    $name = $_POST['name'];
    if ($name == "") {
        $filename = $_FILES['tokens']['name'];
        $name = substr($filename, 0, strrpos($filename, '.'));
        $name = str_replace("_admin", "", $name);
    }


    if ($_FILES['tokens']['tmp_name'] == "")
        return;

    Database::db()->begin_transaction();


    if ($stmt = Database::prepare("INSERT INTO `Publication` (`FileName`, `Name`) VALUES(?, ?)")) {
        $stmt->bind_param("ss", $_FILES['tokens']['name'], $name);
        $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
        $stmt->close();
    } else die("ERROR(" . __LINE__ . "): " . Database::error());
    $publicationId = Database::db()->insert_id;

    if (isset($_POST["users"])) {
        foreach ($_POST["users"] as $user) {
            if ($stmt = Database::prepare("INSERT IGNORE INTO `User_Publication` (`UserId`, `PublicationId`) VALUES(?, ?)")) {
                $stmt->bind_param("ii", $user, $publicationId);
                $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                $stmt->close();
            } else die("ERROR(" . __LINE__ . "): " . Database::error());
        }
    }

    $csvFile = file($_FILES['tokens']['tmp_name']);
    foreach ($csvFile as $line) {
        if (trim($line)[0] == '#') continue;
        $token = array_combine(array(
            "doc_id",
            "sentence",
            "sentence_token_position",
            "doc_token_position",
            "sentence_char_onset",
            "sentence_char_offset",
            "onset",
            "offset",
            "text"), str_getcsv($line, ", "));

        if ($stmt = Database::prepare("INSERT INTO `Token` (`PublicationId`, `Text`, `Onset`, `Offset`, `Sentence`, `Number`) VALUES(?, ?, ?, ?, ?, ?);")) {
            $stmt->bind_param("isiiii", $publicationId, $token["text"], $token["onset"], $token["offset"], $token["sentence"], $token["doc_token_position"]);
            $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
            $stmt->close();
        } else die("ERROR(" . __LINE__ . "): " . Database::error());
    }


    $annotation_data = array();

    if (isset($_FILES['annotations']) && $_FILES['annotations']['tmp_name'] != "") {
        if ($stmt = Database::prepare("SELECT MAX(`Index`) FROM Annotation;")) {
            $stmt->execute() or die("ERROR: " . Database::error());
            $result = $stmt->get_result();
            $arr = $result->fetch_row();
            $nextIndex = intval($arr[0]);
            $stmt->close();
        } else die(Database::error());

        $csvFile = file($_FILES['annotations']['tmp_name']);
        foreach ($csvFile as $line) {
            if (trim($line)[0] == '#') continue;
            $token = array_combine(array(
                "annotation_id",
                "class",
                "onset",
                "offset",
                "text",
                "meta",
                "instances",), str_getcsv($line, ","));

            $token["class"] = trim($token["class"]);
            $token["onset"] = trim($token["onset"]);
            $token["offset"] = trim($token["offset"]);
            $token["text"] = trim($token["text"]);
            $token["meta"] = trim($token["meta"]);
            $token["instances"] = trim($token["instances"]);

            if ($token["meta"] == "")
                $token["meta"] = null;

            if ($stmt = Database::prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
                $stmt->bind_param("s", $token["class"]);
                $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                $stmt->bind_result($class);
                $stmt->fetch();
                $stmt->close();
            } else die("ERROR(" . __LINE__ . "): " . Database::error());

            if ($stmt = Database::prepare("SELECT `Number`, `Sentence` FROM `Token` WHERE `Onset` = ? AND `PublicationId` = ?;")) {
                $stmt->bind_param("ii", $token["onset"], $publicationId);
                $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                $stmt->bind_result($onset, $sentence);
                $stmt->fetch();
                $stmt->close();
            } else die("ERROR(" . __LINE__ . "): " . Database::error());

            if ($stmt = Database::prepare("SELECT `Number` FROM `Token` WHERE `Offset` = ? AND `PublicationId` = ?;")) {
                $stmt->bind_param("ii", $token["offset"], $publicationId);
                $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                $stmt->bind_result($offset);
                $stmt->fetch();
                $stmt->close();
            } else die("ERROR(" . __LINE__ . "): " . Database::error());

            if ($stmt = Database::prepare("INSERT INTO `Annotation` (`PublicationId`, `Index`, `Class`, `Sentence`, `Onset`, `Offset`, `Text`, `User`, `annometa`) VALUES(?, ?, ?, ?, ?, ?, ?, 1, ?);")) {
                $stmt->bind_param("iiiiiisi", $publicationId, $token["annotation_id"], $class, $sentence, $onset, $offset, $token["text"], $token["meta"]);
                $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                $stmt->close();
            } else die("ERROR(" . __LINE__ . "): " . Database::error());

            if ($token["instances"] != "") {
                $instances = explode(",", $token["instances"]);
                foreach ($instances as $instance) {
                    preg_match('/<(.*)> <(.*)> <(.*)>/', $instance, $matches);
                    if ($matches[1] == "null")
                        $path = $matches[3].'/'.$matches[2];
                    else
                        $path = $matches[1].'/'.$matches[2];
//                    echo "<pre>";
//                    print_r($matches);
//                    echo "</pre>";
                    if (!array_key_exists($path, $annotation_data)) {
                        $annotation_data[$path] = array();
                    }
                    $annotation_data[$path][] = Database::db()->insert_id;
                }
            }
        }
    }
    $path = $matches[1].'/'.$matches[2];
//    echo "<pre>";
//    print_r($annotation_data);
//    echo "</pre>";

    if (isset($_FILES["rdf"]) && $_FILES['rdf']['tmp_name'] != "") {
        $foaf = new EasyRdf_Graph("http://psink.de/scio/");
        $foaf->parseFile($_FILES['rdf']['tmp_name'], null, "http://psink.de/scio/");
        $rdf = $foaf->toRdfPhp();

        $data = array();
        $dependencies = array();
        $data_groups = array();

        $groups = parseGroups();
        foreach ($rdf as $domain => $relations) {

            $domain = get_class_name_id($rdf, $domain);
            if (!$domain)
                continue;



            foreach (getSuperclasses($domain["id"]) as $domain_id) {
                if (array_key_exists($domain_id, $groups)) {
                    $path = $domain["rdf"].'/http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
                    if (array_key_exists($path, $annotation_data)) {
                        foreach ($annotation_data[$path] as $annotation) {
                            $annotation_id = $annotation;
                        }
                    } else {
                        $annotation_id = null;
                    }
                    if ($stmt = Database::prepare("INSERT INTO `Data` (`ClassId`, `User`, `PublicationId`, `ManuallySet`, `AnnotationId`) VALUES(?, 1, ?, 1, ?);")) {
                        $stmt->bind_param("iii", $domain_id, $publicationId, $annotation_id);
                        $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                        $stmt->close();
                    } else die("ERROR(" . __LINE__ . "): " . Database::error());

                    $dependencies[$domain["rdf"]] = Database::db()->insert_id;
                    $data_groups[$domain["rdf"]] = Database::db()->insert_id;
                    break;
                }
            }
            foreach ($relations as $relation => $ranges) {
                if ($relation == "http://www.w3.org/1999/02/22-rdf-syntax-ns#type")
                    continue;

                $relation = get_relation_name_id($relation, $domain["id"]);
                if (!$relation)
                    continue;

                foreach ($ranges as $range) {
//                    echo "<pre>";
                    $path = $domain["rdf"].'/'.$relation["rdf"];
                    if ($range["type"] == "uri") {
                        $range = get_class_name_id($rdf, $range["value"]);
                        if (!$range)
                            continue;
//                        print_r($domain);
//                        print_r($relation);
//                        print_r($range);

                        if (array_key_exists($path, $annotation_data)) {
                            foreach ($annotation_data[$path] as $annotation) {
                                $data[] = array("domain" => $domain["rdf"], "range" => $range["rdf"], "class_id" => $range["id"], "relation_id" => $relation["id"], "user" => 1, "manually_set" => true, "name" => null, "annotation_id" => $annotation);
                            }
                            unset($annotation_data[$path]);
                        } else {
                            $data[] = array("domain" => $domain["rdf"], "range" => $range["rdf"], "class_id" => $range["id"], "relation_id" => $relation["id"], "user" => 1, "manually_set" => true, "name" => null, "annotation_id" => null);
                        }
                    } else {
                        $path = $domain["rdf"].'/'.$relation["rdf"];
                        if (array_key_exists($path, $annotation_data)) {
                            foreach ($annotation_data[$path] as $annotation) {
                                $data[] = array("domain" => $domain["rdf"], "range" => null, "class_id" => $relation["range"], "relation_id" => $relation["id"], "user" => 1, "manually_set" => true, "name" => null, "annotation_id" => $annotation);
                            }
                            unset($annotation_data[$path]);
                        }
                    }
//                    echo "</pre>";
                }
            }
        }

//        echo "<pre>";
//        print_r($annotation_data);
//        echo "</pre>";

        $runs = 0;
        while (sizeof($data) != 0) {
            foreach ($data as $key => $datum) {
                if (array_key_exists($datum["domain"], $dependencies)) {
                    $parent = $dependencies[$datum["domain"]];
                    if (array_key_exists($datum["range"], $data_groups)) {
                        $data_group = $data_groups[$datum["range"]];
                    }

                    if ($stmt = Database::prepare("INSERT INTO `Data` (`ClassId`, `Parent`, `AnnotationId`, `RelationId`, `DataGroup`, `Name`, `User`, `PublicationId`, `ManuallySet`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?);")) {
                        $stmt->bind_param("iiiiisiii", $datum["class_id"], $parent, $datum["annotation_id"], $datum["relation_id"], $data_group, $datum["name"], $datum["user"], $publicationId, $datum["manually_set"]);
                        $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                        $stmt->close();

                        $dependencies[$datum["range"]] = Database::db()->insert_id;

                    } else die("ERROR(" . __LINE__ . "): " . Database::error());
                    unset($data[$key]);
                }
            }
            $runs++;
            if ($runs > 50) {
                echo "WARNING: Exceeded iterations</br>";
                break;
            }
        }

    }

    Database::db()->commit();

    echo "Publication " . $name . " imported</br>";
}

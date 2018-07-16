<?php
require_once("publication.php");

$affected = array();
if ($stmt = Database::prepare("SELECT * FROM `Annotation` WHERE `Text` LIKE '';")) {
    $stmt->execute() or die("ERROR(".__LINE__.")");
    if ($result = $stmt->get_result()) {
        while ($row = $result->fetch_assoc()) {
            $id = $row["Id"];
            $affected[] = $row;
        }
        $result->close();
    }
    $stmt->close();
}

$emptytext = array();
$annotexts = array();
print_r("retrieving token texts\n");
foreach($affected as $annodata) {
    $annoid = $annodata["Id"];
    
    $tokens = array();
    if ($stmt = Database::prepare("SELECT * FROM `Token` WHERE `PublicationId` = ? AND `Sentence` = ? AND `Token`.`Number` >= ? AND `Token`.`Number` <= ?;")) {
        $stmt->bind_param("iiii", $annodata["PublicationId"], $annodata["Sentence"], $annodata["Onset"], $annodata["Offset"]);
        $stmt->execute() or die("ERROR(".__LINE__.")");
        if ($result = $stmt->get_result()) {
            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }
            $result->close();
        }
        $stmt->close();
    }

    $prevtoken = null;
    $annotext = "";
    foreach($tokens as $token) {
        if ($prevtoken != null) {
            if ($token["Onset"] > $prevtoken["Offset"]) {
                $annotext .= " ";
            }
        }
        if (!empty($token["Text"])) {
            $annotext .= $token["Text"];
        }
        $prevtoken = $token;
    }

    if (empty($annotext)) {
        $emptytext[] = $annoid;
        continue;
    }
    $annotexts[$annoid] = $annotext;
}

foreach($annotexts as $annoid => $annotext) {
    if (!empty($annoid) && !empty($annotext)) {
        print_r("setting $annoid => '$annotext'\n");
        if ($ustmt = Database::prepare("UPDATE `Annotation` SET Text = ? WHERE Id = ? LIMIT 1")) {
            $ustmt->bind_param("si", $annotext, $annoid);
            $ustmt->execute() or die("ERROR(".__LINE__."): ".Database::error().": ".$annoid." => ".$annotext);
            $ustmt->close();
        }
    }
}

print_r("Affected annotations: ".sizeof($affected).PHP_EOL);
print_r("Annotations without fix (no token text): ".sizeof($emptytext).PHP_EOL);
print_r("all done.".PHP_EOL);
?>

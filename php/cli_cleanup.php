<?php
require_once("publication.php");

$required = array();
if ($stmt = Database::prepare("SELECT * FROM `Annotation`")) {
    $stmt->execute() or die("ERROR(".__LINE__.")");
    if ($result = $stmt->get_result()) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['Class'])) {
                $required[] = $row['Class'];
            }
        }
        $result->close();
    }
    $stmt->close();
}

print_r("req classes ".sizeof($required).PHP_EOL);

if ($stmt = Database::prepare("SELECT * FROM `Group`")) {
    $stmt->execute() or die("ERROR(".__LINE__.")");
    if ($result = $stmt->get_result()) {
        while ($row = $result->fetch_assoc()) {
            if(!empty($row["Group"])) {
                $required[] = $row["Group"];
            }
        }
        $result->close();
    }
    $stmt->close();
}

print_r("req classes ".sizeof($required).PHP_EOL);

$required = array_unique($required);
print_r("unique req classes ".sizeof($required).PHP_EOL);

$iter = 0;
$prevsize = sizeof($required);
do {
    $required = array_unique($required);
    print_r("iter $iter size ".sizeof($required).PHP_EOL);

    foreach ($required as $classId) {
        if ($stmt = Database::prepare("SELECT * FROM `Relation` WHERE Relation.`Range` = ? OR Relation.`Domain` = ?")) {
            $stmt->bind_param("ii", $classId, $classId);
            $stmt->execute() or die("ERROR(".__LINE__.")");
            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['Domain'])) {
                        $required[] = $row['Domain'];
                    }
                    if (!empty($row['Range'])) {
                        $required[] = $row['Range'];
                    }
                }
                $result->close();
            }
            $stmt->close();
        }
    }    

    $required = array_unique($required);
    if (sizeof($required) === $prevsize) {
        break;
    }
    $prevsize = sizeof($required);
    $iter++;
} while(true);

$allclasses = array();
if ($stmt = Database::prepare("SELECT * FROM `Class`")) {
    $stmt->execute() or die("ERROR(".__LINE__.")");
    if ($result = $stmt->get_result()) {
        while ($row = $result->fetch_assoc()) {
            $allclasses[] = $row['Id'];
        }
        $result->close();
    }
    $stmt->close();
}

$unnecessary = array_diff($allclasses, $required);
print_r("required: ".sizeof($required)."unnecessary: ".sizeof($unnecessary)." of ".sizeof($allclasses).PHP_EOL);
foreach($unnecessary as $classId) {
    print_r("deleting $classId");
    if ($stmt = Database::prepare("DELETE FROM `Class` WHERE `Id` = ? LIMIT 1")) {
        $stmt->bind_param("i", $classId);
        $stmt->execute() or die("ERROR(".__LINE__.")");
        $stmt->close();
    }
}
print_r("all done");
print_r("required: ".sizeof($required)."unnecessary: ".sizeof($unnecessary)." of ".sizeof($allclasses).PHP_EOL);

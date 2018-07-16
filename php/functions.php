<?php
require_once("constants.php");
require_once("user.php");
require_once("database.php");

function parse($file, $multimap = false) {
    $csvFile = file($file);
    $data = array();
    foreach ($csvFile as $line) {
        if ($line[0] == '#') {
            $names = str_getcsv(ltrim($line, "#"), "\t");
            continue;
        }
        $csv_line = str_getcsv($line, "\t");
        if (sizeof($csv_line) == 1) {
            $data[] = $csv_line[0];
            continue;
        }
        $csv_line = array_combine($names, $csv_line);
        $key = array_shift($csv_line);
        if ($multimap)
            $data[$key][] = $csv_line;
        else
            $data[$key] = $csv_line;
    }
    return $data;
}

function parseGroups()
{
    $query = <<<QUERY
        SELECT `Class`.`Id`, `Class`.`Name`, `Heading` FROM `Group` 
            JOIN `Class` 
                ON `Class`.`Id` = `Group`  ORDER BY `Order` ASC
QUERY;
    global $mysqli;
    $groups = array();
    if ($result = Database::query($query)) {
        while($row = $result->fetch_row())
            $groups[$row[0]] = array("name" => $row[1], "heading" => $row[2]);
        $result->free();
    }
    return $groups;
}

$reversedRelations = array();
function parseRelations()
{
    $query = <<<QUERY
        SELECT `Domain`.`Id` AS `DomainId`, `Domain`.`Name` as `Domain`, `Relation`, `Range`.`Id` AS `RangeId`, `Range`.`Name` AS `Range`, `From`, `To`, `DataProperty` FROM Relation
            JOIN `Class` AS `Domain`
                ON `Relation`.`Domain` = `Domain`.`Id`
            JOIN `Class` AS `Range`
                ON `Relation`.`Range` = `Range`.`Id`;
QUERY;
    global $mysqli, $reversedRelations;
    $relations = array();
    if ($result = Database::query($query)) {
        while($row = $result->fetch_row()) {
            $reversedRelations[$row[3]] = $row[0];
            $relations[$row[0]][] = array("domain" => $row[1], "relation" => $row[2], "rangeId" => $row[3], "range" => $row[4], "from" => $row[5], "to" => $row[6], "dataType" => boolval($row[7]));
        }
        $result->free();
    }
    return $relations;
}
parseRelations();

$subClasses = array();
$superClasses = array();
function parseSubClasses()
{
    $query = <<<QUERY
        SELECT `Super`.Name AS `SuperClass`, `Super`.`Id` AS `SuperClassId`, `Sub`.Name AS `SubClass`, `Sub`.`Id` AS `SubClassId`
        FROM SubClass
	        JOIN `Class` AS `Sub`
		        ON `SubClass`.`SubClass` = `Sub`.`Id`
	        JOIN `Class` AS `Super`
		        ON `SubClass`.`SuperClass` = `Super`.`Id`
QUERY;
    global $subClasses, $superClasses;
    $subClasses = array();
    $superClasses = array();
    if ($result = Database::query($query)) {
        while($row = $result->fetch_row()) {
            $subClasses[$row[1]][] = array("superClass" => $row[0], "subClass" => $row[2], "superClassId" => $row[1], "subClassId" => $row[3]);
            $superClasses[$row[3]][] = array("superClass" => $row[0], "subClass" => $row[2], "superClassId" => $row[1], "subClassId" => $row[3]);
        }
        $result->free();
    }
    return $subClasses;
}

function find_group($classId) {
    var_dump(getSuperSuperClass($classId));
    if ($stmt = Database::prepare("SELECT Id FROM `Group` WHERE `Group`.`Group` = ?")) {
        $super = getSuperSuperclass($classId);
        $stmt->bind_param("i", $super);
        $stmt->execute();
        if ($f = $stmt->fetch()) {
            $stmt->close();
            return $f;
        }
        $stmt->close();
        return $f;
    }
}

function parseClasses()
{
    global $mysqli;
    $classes = array();
    if ($result = Database::query("SELECT `Class`.`Name` AS `Class` FROM`Class`")) {
        while($row = $result->fetch_row())
            $classes[] = $row[0];
        $result->free();
    }
    return $classes;
};


parseSubClasses();

function getSubclasses($top) {
    global $subClasses;
    $ret = array($top);
    if (isset($subClasses[$top]))
        foreach ($subClasses[$top] as $subClass) {
            $ret = array_merge($ret, getSubclasses($subClass["subClassId"]));
        }
    return $ret;
}
function getSubclassesName($top) {
    global $mysqli;
    $subclasses = getSubclasses($top);
    $ret = [];
    foreach ($subclasses as $subclass) {
        if ($stmt = $mysqli->prepare("SELECT Name, Description, `Id` FROM Class WHERE Id = ? ORDER BY `Name` ASC")) {
            $stmt->bind_param("i", $subclass);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            $ret[$subclass] = $row;
            $stmt->close();
        }
    }
    return $ret;
}

function getSuperclasses($top)
{
    global $superClasses;
    $ret = array($top);
    if (isset($superClasses[$top])) foreach ($superClasses[$top] as $superClass) {
        $ret = array_merge($ret, getSuperclasses($superClass["superClassId"]));
    }
    return $ret;
}

function getSuperSuperClass($current)
{
    global $superClasses;
    if (isset($superClasses[$current])) foreach ($superClasses[$current] as $superClass) {
        $current = getSuperSuperClass($superClass["superClassId"]);
    }
    return $current;
}
//var_dump($reversedRelations);
function getReversedRelations($classId) {
    global $reversedRelations;
    echo $classId;
    $superClasses = getSuperclasses($classId);
    $classId = end($superClasses);
//    while(isset($superClasses[$classId])) {
//        $classId = $superClasses[$classId];
//        $superClasses = getSuperclasses($classId);
//    }
    echo $classId;
    $ret = array($classId => $classId);
    if (isset($reversedRelations[$classId]) && !array_key_exists($classId, $ret)) {
//        echo "$classId\n";
        $ret = array_merge($ret, getReversedRelations($reversedRelations[$classId]));
    }
//    var_dump($ret);
    return $ret;
}

$groupAssociations = parseAssociations();

function getGroup($classId, $groups, $visited = null) {
    foreach ($groups as $group) {
        if ($classId == $group["Group"])
            return $group["Id"];
    }
    if (empty($visited)) {
        $visited = array();
    }
    if (in_array($classId, $visited)) {
        return null;
    }

    $visited[] = $classId;
    $superclass = null;
    if ($stmt = Database::prepare("SELECT `SuperClass` FROM `SubClass` WHERE `SubClass` = ?")) {
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 1) {
            foreach ($result->fetch_assoc() as $row) {
                foreach ($groups as $group) {
                    if ($row["SuperClass"] == $group["Group"])
                        return $group["Group"];
                }
            }
            $stmt->close();
            return null;
        }
        if ($result->num_rows == 1) {
            $superclass = $result->fetch_assoc()["SuperClass"];
        }
        $stmt->close();
    }
    $relation = null;
    if ($stmt = Database::prepare("SELECT `Domain` FROM `Relation` WHERE `Range` = ?")) {
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 1) {
            foreach ($result->fetch_assoc() as $row) {
                foreach ($groups as $group) {
                    if ($row["Domain"] == $group["Group"])
                        return $group["Group"];
                }
            }
            $stmt->close();
            return null;
        }
        if ($result->num_rows == 1) {
            $superclass = $result->fetch_assoc()["Domain"];
        }
        $stmt->close();
    }
    foreach ($groups as $group) {
        if ($superclass && $superclass == $group["Group"]
            || $relation && $relation == $group["Group"])
            return $group["Id"];
    }
    if ($relation && !$superclass) {
        return getGroup($relation, $groups, $visited);
    }
    if (!$relation && $superclass) {
        return getGroup($superclass, $groups, $visited);
    }
    return null;
}

function parseAssociations()
{
    $groups = Database::query("SELECT `Group`, `Id` FROM `Group`")->fetch_all(MYSQLI_ASSOC);
//    foreach ($groups as $group) {
//        return (int)$group["Group"];
//    }
//    return $groups;

    $classes = array();
    if ($result = Database::query("SELECT `Id`, `Name` FROM `Class`")) {
        while($row = $result->fetch_assoc())
            $classes[$row["Name"]] = $row["Id"];
        $result->free();
    } else die(Database::error());

    $associations = array();
    foreach ($classes as $name => $classId) {
        $associations[$classId] = (int)getGroup($classId, $groups);
    }
    return $associations;
}

function generateColor($classId) {
    $groups = parseGroups();
    $groups[296] = $groups[7] = true;
    $r = parseRelations();
    foreach ($r[7] as $domain => $relation) {
        $groups[$relation["rangeId"]] = true;
    }
//    var_dump($groups);
    $relations = getReversedRelations($classId);
//    var_dump($relations);
    $i = 0;
    $hue = 0;
    $found = false;
    foreach ($groups as $groupId => $group) {
        ++$i;
        if (array_key_exists($groupId, $relations)) {
            $hue = 360/(sizeof($groups)-1)*$i;
            $found = true;
//            break;
        }
    }
//    var_dump($relations);
    if ($found) {
        $saturation = 50 + sizeof($relations) * 5;
        $light = 90 - sizeof($relations) * 10;
    } else {
        return "#888888";
    }
    return "hsl($hue, $saturation%, $light%)";
}

function ensureDefaultUser() {
    global $mysqli;
    Database::query('INSERT IGNORE INTO `User` (`Id`, `Mail`, `Password`) VALUES(1, "admin", "$2y$12$qXacYz7tb9xXul96YovcTeDea44n4R5H2zyOEgdoV6W54WjEyLvoW")');
}

function loginUser($mail, $password) {
    global $mysqli, $pepper;
    $user = null;
    if($stmt = $mysqli->prepare("SELECT `Id`, `Password` FROM `User` WHERE `Mail` = ?;")) {
        $stmt->bind_param("s", $mail);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($user, $hash);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    if (password_verify($password.$pepper, $hash)) {
        $_SESSION['user'] = $user;
        $_SESSION['admin'] = Users::byId($user)->isCurator();
        $_SESSION['opened'] = array();
    }
}

function getUser() {
    global $mysqli;
    if($stmt = $mysqli->prepare("SELECT `Mail` FROM `User` WHERE `Id` = ?;")) {
        $stmt->bind_param("s", $_SESSION['user']);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($mail);
        $stmt->fetch();
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    return $mail;
}

function registerUser($mail, $password, $curator = false) {
    global $mysqli, $pepper;

    if ($curator !== false) {
        $curator = 1;
    } else {
        $curator = 0;
    }

    $hash = password_hash($password.$pepper, PASSWORD_BCRYPT, array('cost' => 12));
    if($stmt = $mysqli->prepare("INSERT INTO `User` (`Mail`, `Password`, IsCurator) VALUES(?, ?, ?)")) {
        $stmt->bind_param("ssi", $mail, $hash, $curator);
        if ($stmt->execute()) {

            $_SESSION['loggedIn'] = true;
            $_SESSION['user'] = $mail;
            $_SESSION['opened'] = array();
        } else {
            echo "$mail already exist.";
        }
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);

    $_SESSION['user'] = $mysqli->insert_id;


    return true;
}

<?php
require_once 'php/constants.php';

//$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
//$mysqli->query("TRUNCATE TABLE `Publication`");
//$mysqli->query("TRUNCATE TABLE `Token`");
//$mysqli->query("TRUNCATE TABLE `Annotation`");
//$mysqli->query("TRUNCATE TABLE `Data`");
//$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

$publicationId = $_POST["publication"];
//$publication = trim(file_get_contents($_FILES['publication']['tmp_name']));

//$file = basename($_FILES['publication']['name']);
//var_dump($_POST);
//$name =

foreach ($_POST["users"] as $user) {
    if($stmt = $mysqli->prepare("INSERT IGNORE INTO `User_Publication` (`UserId`, `PublicationId`) VALUES(?, ?)")) {
        $stmt->bind_param("ii", $user, $publicationId);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
}

if ($_FILES['annotations']["name"] == "")
    die();

$mysqli->query("START TRANSACTION");
$csvFile = file($_FILES['annotations']['tmp_name']);

$annotationIndices = array();

foreach ($csvFile as $line) {
    if (trim($line)[0] == '#') continue;
    $annotation = array_combine(array("index", "class", "onset", "offset", "text"), str_getcsv($line, "\t"));
    $annotationIndices[$annotation["index"]][] = $annotation;
}

if ($stmt = $mysqli->prepare("SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'anno' AND TABLE_NAME = 'Annotation';")) {
    $stmt->execute() or die("ERROR: ".$mysqli->error);
    $result = $stmt->get_result();
    $arr = $result->fetch_row();
    $nextIndex = intval($arr[0]);
    $stmt->close();
} else die($mysqli->error);

foreach ($annotationIndices as $index => $annotations) {
    foreach ($annotations as $i => $annotation) {
        if ($stmt = $mysqli->prepare("SELECT `Id` FROM `Class` WHERE `Name` = ?;")) {
            $stmt->bind_param("s", $annotation["class"]);
            $stmt->execute() or die("ERROR(" . __LINE__ . "): " . $mysqli->error);
            $stmt->bind_result($annotationIndices[$index][$i]["classId"]);
            $stmt->fetch();
            $stmt->close();
        } else die("ERROR(" . __LINE__ . "): " . $mysqli->error);

        if ($stmt = $mysqli->prepare("SELECT `Number`, `Sentence` FROM `Token` WHERE `Onset` = ? AND `PublicationId` = ?;")) {
            $stmt->bind_param("ii", $annotation["onset"], $publicationId);
            $stmt->execute() or die("ERROR(" . __LINE__ . "): " . $mysqli->error);
            $stmt->bind_result($annotationIndices[$index][$i]["tokenOnset"], $annotationIndices[$index][$i]["sentenceA"]);
            $stmt->fetch();
            $stmt->close();
        } else die("ERROR(" . __LINE__ . "): " . $mysqli->error);

        if ($stmt = $mysqli->prepare("SELECT `Number` FROM `Token` WHERE `Offset` = ? AND `PublicationId` = ?;")) {
            $stmt->bind_param("ii", $annotation["offset"], $publicationId);
            $stmt->execute() or die("ERROR(" . __LINE__ . "): " . $mysqli->error);
            $stmt->bind_result($annotationIndices[$index][$i]["tokenOffset"]);
            $stmt->fetch();
            $stmt->close();
        } else die("ERROR(" . __LINE__ . "): " . $mysqli->error);
    }

}

foreach ($_POST["users"] as $user) {
    foreach ($annotationIndices as $index => $annotations) {
        foreach ($annotations as $annotation) {
            var_dump($annotation);
            echo "INSERT IGNORE INTO Annotation ($publicationId, $nextIndex, {$annotation["classId"]}, {$annotation["sentenceA"]}, {$annotation["tokenOnset"]}, {$annotation["tokenOffset"]}, {$annotation["text"]}, $user<br>";
            if ($stmt = $mysqli->prepare("INSERT IGNORE INTO `Annotation` (`PublicationId`, `Index`, `Class`, `Sentence`, `Onset`, `Offset`, `Text`, `User`) VALUES(?, ?, ?, ?, ?, ?, ?, ?);")) {
                $stmt->bind_param("iiiiiisi", $publicationId, $nextIndex, $annotation["classId"], $annotation["sentenceA"], $annotation["tokenOnset"], $annotation["tokenOffset"], $annotation["text"], $user);
                $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error.": ".$annotation["class"]);
                $stmt->close();
            } else die("ERROR(" . __LINE__ . "): " . $mysqli->error);
        }
        ++$nextIndex;
    }
}

$mysqli->query("COMMIT");

die();

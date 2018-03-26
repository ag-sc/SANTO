<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once "../php/constants.php";
require_once "../php/user.php";
$userid = Users::loginUser()->id;

if ($stmt = $mysqli->prepare("SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'anno' AND TABLE_NAME = 'Annotation';")) {
    $stmt->execute() or die("ERROR: " . $mysqli->error);
    $result = $stmt->get_result();
    $arr = $result->fetch_row();
    $nextIndex = intval($arr[0]);
    $stmt->close();
} else die($mysqli->error);

foreach ($_POST["annotationId"] as $annotationId => $ignored) {
    if ($stmt = $mysqli->prepare("SELECT * FROM Annotation WHERE `Index` = ? AND PublicationId = ?")) {
        $stmt->bind_param("si", $annotationId, $_SESSION["document"]);
        $stmt->execute() or die("ERROR: " . $mysqli->error);
        $result = $stmt->get_result();
        $annotations = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else die($mysqli->error);

    var_dump($annotations);

    foreach ($annotations as $annotation) {
        if ($annotation["Reference"] != null)
            continue;

        if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM Annotation WHERE User = ? AND Reference = ?")) {
            $stmt->bind_param("ii", $userid, $annotation["Id"]);
            $stmt->execute() or die("ERROR: " . $mysqli->error);
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            $count = $row[0];
            $stmt->close();
        } else die($mysqli->error);
        if ($count > 0)
            continue;

        $copyQuery = <<<QUERY
INSERT IGNORE INTO `Annotation` (PublicationId, `Index`, Class, Sentence, Onset, Offset, Text, User, Reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
QUERY;
        if ($stmt = $mysqli->prepare($copyQuery)) {
            $stmt->bind_param("iiiiiisii",
                $annotation["PublicationId"],
                $nextIndex,
                $annotation["Class"],
                $annotation["Sentence"],
                $annotation["Onset"],
                $annotation["Offset"],
                $annotation["Text"],
                $userid,
                $annotation["Id"]);
            $stmt->execute() or die("ERROR: " . $mysqli->error);
            $stmt->close();
        } else die($mysqli->error);
    }
    ++$nextIndex;
}

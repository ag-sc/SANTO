<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

require_once("../php/constants.php");

header('Content-Type: application/json');

function duplicate($id, $parent = null) {
    global $mysqli;
    $query = "INSERT INTO `Data` (`ClassId`, `Parent`, `AnnotationId`, `RelationId`, `DataGroup`, `Name`, `User`, `PublicationId`)
                SELECT `ClassId`, `Parent`, `AnnotationId`, `RelationId`, `DataGroup`, `Name`, `User`, `PublicationId` FROM `Data`
                WHERE `Id` = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $insertId = $mysqli->insert_id;

    if ($parent) {
        if($stmt = $mysqli->prepare("UPDATE `Data` SET `PARENT`= ? WHERE `Id` = ?")) {
            $stmt->bind_param("ii", $parent, $insertId);
            $stmt->execute();
        }
    }


    $query = "SELECT `Id` FROM `Data` WHERE `Parent` = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultSet = $stmt->get_result();
    $dataSet = $resultSet->fetch_all(MYSQLI_ASSOC);
    $stmt->free_result();

    foreach ($dataSet as $data)
        duplicate($data["Id"], $insertId);

    return $insertId;
}

$newDataId = duplicate($_POST["dataId"]);

if (isset($_POST["classId"])) {
    if($stmt = $mysqli->prepare("SELECT `Description` FROM Class WHERE Id = ?")) {
        $stmt->bind_param("i", $_POST["classId"]);
        $stmt->execute();
        $resultSet = $stmt->get_result();
        $r = $resultSet->fetch_row();
        $description = $r[0];
    }
}

echo json_encode(array("newDataId" => $newDataId, "description" => isset($description) ? $description : null));

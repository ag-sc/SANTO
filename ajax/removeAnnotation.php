<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

if ($_POST['index'] == -1) return;

require_once "../php/constants.php";
require_once "../php/database.php";

$resdata = array();
$resdata["annos"] = array();

if($stmt = $mysqli->prepare("SELECT `User` FROM `Annotation` WHERE `Index` = ? AND `PublicationId` = ?")) {
    $stmt->bind_param("si", $_POST['index'], $_SESSION['document']);
    $stmt->execute() or die("ERROR: ".$mysqli->error);
    $stmt->bind_result($user);
    $stmt->fetch();
    $stmt->close();
} else die($mysqli->error);

if ($_SESSION['user'] == $user) {
    $deleted_ids = array();
    if ($stmt = Database::prepare("SELECT Id FROM `Annotation` WHERE `Index` = ? AND `PublicationId` = ?")) {
        $stmt->bind_param("si", $_POST['index'], $_SESSION['document']);
        $stmt->execute() or die("ERROR: " . $mysqli->error);
        if ($result = $stmt->get_result()) {
            while ($row = $result->fetch_assoc()) {
                $deleted_ids[] = $row['Id'];
            }
            $result->close();
        }
        $stmt->close();
    }
    $delete_ids = array();
    foreach($deleted_ids as $anno_id) {
        #echo "Getting data entries for anno #$anno_id\n";
        $resdata['annos'][] = $anno_id;
        if ($stmt = Database::prepare("SELECT id FROM `Data` WHERE AnnotationId = ? AND `PublicationId` = ?")) {
            $stmt->bind_param("ii", $anno_id, $_SESSION['document']);
            $stmt->execute() or die("ERROR: " . $mysqli->error);
            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $delete_ids[] = $row['id'];
                }
                $result->close();
            }
            $stmt->close();
        }
    }
    if ($stmt = Database::prepare("DELETE FROM `Annotation` WHERE `Index` = ? AND `PublicationId` = ?")) {
        $stmt->bind_param("si", $_POST['index'], $_SESSION['document']);
        $stmt->execute() or die("ERROR: " . $mysqli->error);
        $stmt->close();
    } else die($mysqli->error);
    if (count($delete_ids) > 0) {
        foreach($delete_ids as $data_id) {
            # echo "Deleting entries for user=$user dataid=$data_id\n";
            $resdata['dataids'][] = $data_id;
            if ($stmt = Database::prepare("DELETE FROM Data WHERE User = ? AND id = ? AND PublicationId = ?")) {
                $stmt->bind_param("iii", $user, $data_id, $_SESSION['document']);
                $stmt->execute() or die("ERROR: " . $mysqli->error);
                $stmt->close();
            }
        }
    }
}

echo json_encode($resdata);

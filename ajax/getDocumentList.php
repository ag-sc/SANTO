<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

require_once "../php/constants.php";

if (!$_SESSION['admin']) {
    $query = "SELECT `Id`, `FileName`, `Name`, `Ready` FROM `Publication` JOIN `User_Publication` ON Publication.Id = PublicationId WHERE `UserId` = ? ORDER BY `FileName`";

    if($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("i", $_SESSION["user"]);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $result = $stmt->get_result();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
} else
    $result = $mysqli->query("SELECT `Id`, `FileName`, `Name` FROM `Publication`");
if ($result) {
    while($row = $result->fetch_assoc()) { ?>
        <option value="<?=$row["Id"]?>" <?php if (isset($_SESSION['document']) && $row["Id"] == $_SESSION['document']) echo 'selected="selected"'; ?>>
            <?= isset($row["Name"]) ? $row["Name"] : $row["FileName"] ?> <?= isset($row["Ready"]) && $row["Ready"] ? "(finished)" : "(unfinished)"?></option>
    <?php }

    $result->free();
}?>


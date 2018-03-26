<?php

require_once "php/functions.php";

global $mysqli;
if($stmt = $mysqli->prepare("SELECT Id, Mail FROM `User` WHERE `Id` != 1")) {
    $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else die("ERROR(".__LINE__."): ".$mysqli->error);

if($stmt = $mysqli->prepare("SELECT Id, `Name` FROM `Publication`")) {
    $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    $result = $stmt->get_result();
    $publications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else die("ERROR(".__LINE__."): ".$mysqli->error);

?>

<!DOCTYPE html>
<html>
    <script src="plugins/jquery-3.1.0.min.js"></script>

    <script src="plugins/chosen/chosen.jquery.min.js"></script>
    <script src="plugins/chosen/chosen.proto.min.js"></script>
    <link href="plugins/chosen/chosen.min.css" rel="stylesheet" type="text/css">
</head>
<body>

<form action="InsertPublication.php" method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <td><label for="name">Name</label></td>
            <td><input type="text" name="name" id="name"></td>
        </tr>
        <tr>
            <td><label for="tokens">Tokens*</label></td>
            <td><input type="file" accept="text/csv" name="tokens" id="tokens" required></td>
        </tr>
    </table>
    <input type="submit" value="Insert Annotation" name="submit">
</form>
<br>
<form action="InsertAnnotation.php" method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <td><label for="publication">Publication*</label></td>
        <td><select name="publication" id="publication" required><?php
                foreach ($publications as $value) {
                    echo "<option value='{$value['Id']}'>{$value['Name']}</option>";
                }
                ?></select></td>
        </tr>
        <tr>
            <td><label for="annotations">Annotations</label></td>
            <td><input type="file" accept=".annodb" name="annotations" id="annotations"></td>
        </tr>
        <tr>
            <td><label for="users">Users*</label></td>
            <td><select name="users[]" id="users" multiple required><?php
                    foreach ($users as $value) {
                        echo "<option value='{$value['Id']}'>{$value['Mail']}</option>";
                    }
                    ?></select></td>
        </tr>
    </table>
    <input type="submit" value="Insert Annotation" name="submit">
</form>
<script>
    $("#users").chosen({
        search_contains: true,
        display_selected_options: false,
        placeholder_text_multiple: "Select users to associate",
        width: "400px"
    });
</script>
</body>
</html>

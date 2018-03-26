<?php
global $active_action;

function endsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length === 0 || 
        (substr($haystack, -$length) === $needle);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once("php/publication.php");
    require_once("php/user.php");
    include("header.php");
    echo "<pre>\n";
    $target = "files/";
    $target = $target.basename($_FILES["datafile"]["name"]);
    $target_ext = strtolower(pathinfo($target,PATHINFO_EXTENSION));

    if ($target_ext !== "zip") {
        die("invalid file format");
    }

    // move to files directory
    move_uploaded_file($_FILES["datafile"]["tmp_name"], $target);
    
    Publications::bulkinsert($target);

    echo "</pre><br /><a href=\"index2.php?action=bulkinsert\">back</a>";
    include("footer.php");

    exit();
}
?><h1>Bulk Insert</h2>
<form action="index2.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="<?= $active_action ?>" />
<label for="tokens">ZIP file</label> 
<input type="file" name="datafile" required>
<input type="submit" value="Upload" class="ui-button ui-widget ui-corner-all" />
</form>


<?php
global $activepub;
if ($_SESSION['admin'] || empty($activepub)) {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newname = $_POST['newname'];
    if ($activepub->rename($newname)) {
        $activepub = Publications::byId($_POST['document']);
    } else {
        echo "<div class=\"ui-error\">Unknown error when renaming publication</div>";
    }
}
?>
<div style="width: 60%; margin: 0 auto;">
<h1>Rename publication</h1>
<form action="index2.php?action=renamepub.ui" method="post" encoding="application/x-www-form-urlencoded">
<input type="hidden" name="action" value="renamepub" />
<input type="hidden" name="document" value="<?= $activepub->id ?>" />
<div>
    <label for="newname">New name:</label> <input class="ui-widget" name="newname" type="text" required value="<?= $activepub->name ?>" placeholder="<?= $activepub->name ?>" />
</div>
<input type=reset value="Reset" class="ui-button" /> <input type="submit" value="Change" class="ui-button" />

</form>

</div>

<?php
} else {
?>
    <h1>Forbidden</h1>
    <p>Only curators can change publication details.</p>
<?php
}
?>

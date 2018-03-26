<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if (!isset($_SESSION["document"])) return "";

require_once("../php/constants.php");
require_once("../php/functions.php");

$groups = parseGroups();
?>

<div id="annotation-accordion">
<?php foreach ($groups as $id => $group) { ?>
    <h3><?=$group["heading"]?></h3>
    <div class='accordion-div' classId='<?=$id?>' className='<?=$group["name"]?>'></div>
<?php } ?>
</div>

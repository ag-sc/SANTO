<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    if (!isset($_SESSION['user']) || !isset($_SESSION['admin'])) {
        header("Location: login.php");
        exit;
    }

    if (isset($_POST['document'])) {
        $_SESSION['document'] = $_POST['document'];
        header("Location: index.php");
        exit();
    }

    if (empty($_SESSION['document'])) {
        header("Location: index2.php?action=selectpub");
        exit();
    }
    require_once("php/functions.php");
    require_once("php/configuration.php");
    require_once("php/publication.php");
    require_once("php/user.php");
    
    if (!isset($_SESSION['mode'])) {
        $_SESSION['mode'] = 3;
    }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Annotation Interface</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="plugins/jquery-3.1.0.min.js"></script>
    <script defer src="plugins/fontawesome-all.min.js"></script>

    <script src="plugins/ui/jquery-ui.min.js"></script>
    <link href="plugins/ui/jquery-ui.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/ui/jquery-ui.structure.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/ui/jquery-ui.theme.min.css" rel="stylesheet" type="text/css">

    <script src="plugins/chosen/chosen.jquery.min.js"></script>
    <script src="plugins/chosen/chosen.proto.min.js"></script>
    <link href="plugins/chosen/chosen.min.css" rel="stylesheet" type="text/css">

    <script src="plugins/contextMenu/jquery.contextMenu.min.js"></script>
    <link href="plugins/contextMenu/jquery.contextMenu.min.css" rel="stylesheet" type="text/css">

    <script src="plugins/fancytree/jquery.fancytree-all.min.js"></script>
    <link href="plugins/fancytree/skin-lion/ui.fancytree.min.css" rel="stylesheet" type="text/css">

    <link type="text/css" rel="stylesheet" href="plugins/jquery.dropdown.css" />
    <script type="text/javascript" src="plugins/jquery.dropdown.js"></script>

<!--    <script type="text/javascript" src="plugins/tooltipster/js/tooltipster.bundle.min.js"></script>-->
<!--    <link rel="stylesheet" type="text/css" href="plugins/tooltipster/css/tooltipster.bundle.min.css" />-->

    <script src="js/script.js"></script>
    <?php if ($_SESSION['admin']) { ?>
        <link href="css/kurator.css" rel="stylesheet">
    <?php } else { ?>
        <link href="css/user.css" rel="stylesheet">
    <?php } ?>
    <link href="css/left.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<?php
    $activepub = null;
    if (isset($_SESSION['document'])) {
        $activepub = Publications::byId($_SESSION['document']);
    }
?>
    <?php if ($_SESSION['admin']) { ?>
    <section class="left-half">
    <?php } else { ?>
    <section>
    <?php } ?>
        <div id="header">
            <form method="post" accept-charset="utf-8" id="logoutform">
                <div class="ui-controlgroup ui-controlgroup-horizontal login-bar toolbar-text">
                    <a href="index2.php?action=selectpub"><img src="css/images/logo_small.png" alt="psink" id="header_logo" /></a>
                    <span class="version"><?= htmlentities(Configuration::instance()->get("ontology", "name")); ?></span>
                    <span><i class="fas fa-user"></i> <?php echo getUser() ?></span>
                    <button name="logout" value="logout" class="logout-btn ui-controlgroup-item ui-button" onclick="document.getElementById('logoutform').submit();"><i class="fas fa-power-off"></i> Logout</button>
                </div>
             <?php Publications::renderSelection(); ?>
            <!-- select id="document" class="form-control" name="document" onchange="this.form.submit()" style="width: 40%">
                <?php if (!array_key_exists('document', $_SESSION)) { ?>
                    <option selected="selected">None selected</option>
                <?php }
                    $query = "SELECT Publication.`Id`, `FileName`, `Name`, `Ready`, `ReadyCuration` FROM `Publication` JOIN `User_Publication` ON Publication.Id = PublicationId WHERE `UserId` = ? ORDER BY `FileName` ASC";

                    if($stmt = $mysqli->prepare($query)) {
                        $stmt->bind_param("i", $_SESSION["user"]);
                        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
                        $result = $stmt->get_result();
                    } else die("ERROR(".__LINE__."): ".$mysqli->error);

                    while($row = $result->fetch_assoc()) {
                        $ready = $row["Ready"];
                        $readyCuration = $row["ReadyCuration"];
                        $name = isset($row["Name"]) ? $row["Name"] : $row["FileName"];
                        $selected = isset($_SESSION['document']) && $row["Id"] == $_SESSION['document'];?>
                        <option ready="<?=$ready?>" text="<?=$name?>" value="<?=$row["Id"]?>" <?php if ($selected) echo 'selected="selected"'; ?> finishable="1">
                        <?php if ($_SESSION['mode'] == 3) { ?>
                        <?= $name ?> (<?= $ready ? "annotated" : "not annotated" ?>, <?= $readyCuration ? "curated" : "not curated" ?>)</option>
                        <?php } else { ?>
                            <?= $name ?> <?= $ready ? "(finished)" : "(unfinished)" ?></option>
                        <?php } ?>
                    <?php }

                    $result->free();
                /* } else { // admin view, todo: add counts
                    $result = $mysqli->query("SELECT `Id`, `FileName`, `Name` FROM `Publication`");

                    while($row = $result->fetch_assoc()) {
                        $ready = $row["Ready"];
                        $name = isset($row["Name"]) ? $row["Name"] : $row["FileName"];
                        $selected = isset($_SESSION['document']) && $row["Id"] == $_SESSION['document'];?>
                        <option text="<?=$name?>" value="<?=$row["Id"]?>" <?php if ($selected) echo 'selected="selected"'; ?> finishable="0">
                            <?= $name ?></option>
                    <?php }

                    $result->free();
                    } */
                ?>
            </select> -->
            <div id="toolbar" class="ui-controlgroup ui-controlgroup-horizontal">
                <a class="ui-controlgroup-item" id='toolbar-add-anno' href="#" title="Adds a new annotation to the selected range. If an annotation has already been selected, it is extended to a discontinuous annotation [RETURN]">Add annotation</a>
                <a class="ui-controlgroup-item" id='toolbar-delete-anno' href="#" title="Deletes the selected annotation [DEL]">Delete annotation</a>
                <a class="ui-controlgroup-item" id='toolbar-grow-anno-left' href="#" title="Expands the selected annotation to the left [CTRL+Left]">Expand annotation LEFT</a>
                <a  class="ui-controlgroup-item" id='toolbar-grow-anno-right' href="#" title="Expands the selected annotation to the right [CTRL+Right]">Expand annotation RIGHT</a>
                <a  class="ui-controlgroup-item" id='toolbar-shrink-anno-left' href="#" title="Shrinks the selected annotation from the left [CTRL+ALT+Right]">Shrink annotation LEFT</a>
                <a  class="ui-controlgroup-item" id='toolbar-shrink-anno-right' href="#" title="Shrinks the selected annotation from the right [CTRL+ALT+Left]">Shrink annotation RIGHT</a>
                <?php if ($activepub) {

                    $query = "SELECT `Ready` FROM `Publication` JOIN `User_Publication` ON Publication.Id = PublicationId WHERE `UserId` = ? AND `Publication`.Id = ?";
                    if($stmt = $mysqli->prepare($query)) {
                        $stmt->bind_param("ii", $_SESSION["user"], $_SESSION["document"]);
                        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
                        $result = $stmt->get_result();
                    } else die("ERROR(".__LINE__."): ".$mysqli->error);
                    $ready = false;
                    while($row = $result->fetch_assoc()) {
                        $ready = $row["Ready"];
                    }
                    $result->free();
                    $checked = $ready ? "checked='checked'" : "";
                    ?>
                    <!--                    <form id="mode-form">-->
                    <input type="checkbox" class="ui-controlgroup-item"  id="DocumentReady" title="Marks this document ready to be curated" <?=$checked?>>
                    <label for="DocumentReady" title="Marks this document ready to be curated">Done</label>
                    <!--                    </form>-->
                <?php } ?>
            </div>
            <div id="toolbar-kurator" class="ui-controlgroup ui-controlgroup-horizontal">
                <a class="ui-controlgroup-item"  id='toolbar-accept-anno' href="#" title="Takes over the selected annotations [ENTER]">Accept annotation</a>
                <a class="ui-controlgroup-item"  id='toolbar-unaccept-anno' href="#" title="Unaccept the selected annotations [DEL]">Unaccept annotation</a>
                <a class="ui-controlgroup-item"  id='toolbar-select-all' href="#" title="Select all annotations [CTRL+A]">Select all</a>
                <a class="ui-controlgroup-item"  id='toolbar-deselect-all' href="#" title="Deselect all annotations [ESCAPE]">Deselect all</a>
                <?php if ($activepub) {
                    $checked = $activepub->isReadyCuration(Users::loginUser()) ? "checked='checked'" : "";
                    ?>
                    <!--                    <form id="mode-form">-->
                    <input type="checkbox" class="ui-controlgroup-item"  id="DocumentReadyCuration" title="Marks the curation for this document as done" <?=$checked?>>
                    <label for="DocumentReadyCuration" title="Marks the curation for this document as done">Done</label>
                    <!--                    </form>-->
                <?php } ?>
            </div>
            <div id="toolbar-slotfilling" class="ui-controlgroup ui-controlgroup-horizontal">
                <?php if ($activepub) {
                    $checked = $activepub->isReadySlotFilling(Users::loginUser()) ? "checked='checked'" : "";
?>
                    <!--                    <form id="mode-form">-->
                    <!--div id="toolbar-slotfilling-slotuser" class="ui-controlgroup-item">
                        <select name="showSlotData" id="showSlotData" data-placeholder="Select users" multiple></select>
                        <label for="showSlotData">Slot data for </label>
                    </div-->
                    <input type="checkbox" class="ui-controlgroup-item"  id="DocumentReadySlotFilling" title="Marks the slot filling for this document as done" <?=$checked?>>
                    <label for="DocumentReadySlotFilling" title="Marks the slot filling for this document as done">Done</label>
                    <!--                    </form>-->
                <?php } ?>
            </div>
        </form>
        <?php
            if (!isset($_SESSION['mode']))
                $_SESSION['mode'] = 3;
        ?>
        <?php if ($_SESSION['admin']) { ?>
        <form id="mode-form" class="ui-helper-clearfix" style="display: block; float: left; width: 100%;">
                <label>Mode: </label>
                <input type="radio" name="mode" id="curator" value="3" <?= $_SESSION['mode'] == 3 ? "checked" : ""?>>
                <label for="curator">Curator</label>
                <input type="radio" name="mode" id="annotation" value="2" <?= $_SESSION['mode'] == 2 ? "checked" : ""?>>
                <label for="annotation">Annotation</label>
                <input type="radio" name="mode" id="slot-filling" value="1" <?= $_SESSION['mode'] == 1 ? "checked" : ""?>>
                <label for="slot-filling">Slotfilling</label>
                <select name="users" id="shownUsersSelect" data-placeholder="Select users" multiple></select>
                <label for="shownUsersSelect">Show user annotations: </label>
        </form>
        <?php } ?>
    </div>
    <span class="context-menu"></span>
    <menu id="context-menu" style="display:none" class="showcase"></menu>
    <span id="context-menu-title" style="display:none"></span>
    <div id="content"><article id="text"></article></div>
</section>

<?php if ($_SESSION['admin']) { ?>
<section id="annotation-list" class="right-half no-select" style="cursor: default">
</section>
<?php } ?>

<div id="dialog" title="Rename Tab">
    <form>
        <fieldset>
            <label for="name">Name</label>
            <input autocomplete="off" type="text" name="name" id="name">
            <input type="submit" tabindex="-1" value="Rename">
        </fieldset>
    </form>
</div>
<div id="insertAnnotationDialog" title="Insert Annotation" startToken="-1" endToken="-1" sentence="-1" index="-1">
    <form>
        <fieldset>
            <label for="annotationText">Text</label>
            <input type="text" tabindex="-1" name="annotationText" id="annotationText" readonly/><br><br>
            <select name="annotation" tabindex="0" id="annotation" data-placeholder="Select annotation"></select><br><br>
            <label for="description">Description</label>
            <input type="text" tabindex="1" name="description" id="description" /><br><br>
            <input type="submit" tabindex="2" value="Insert" />
            </fieldset>
        </form>
    </div>
</body>
</html>

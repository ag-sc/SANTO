<?php
    require_once("php/user.php");
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

<!--    <script type="text/javascript" src="plugins/tooltipster/js/tooltipster.bundle.min.js"></script>-->
<!--    <link rel="stylesheet" type="text/css" href="plugins/tooltipster/css/tooltipster.bundle.min.css" />-->

    <script src="js/script.js"></script>
    <?php if ($_SESSION['user'] == 1) { ?>
        <link href="css/kurator.css" rel="stylesheet">
    <?php } else { ?>
        <link href="css/user.css" rel="stylesheet">
    <?php } ?>
    <link href="css/left.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/override.css" rel="stylesheet">
</head>
<body>
<div id="#header">
        <form method="post" action="index.php" enctype="application/x-www-form-urlencoded" accept-charset="utf-8" id="logoutform">
            <div class="ui-controlgroup ui-controlgroup-horizontal login-bar toolbar-text">
                <a href="index2.php?action=selectpub"><img src="css/images/logo_small.png" alt="psink" id="header_logo" /></a>
                <span class="version"><?= htmlentities(Configuration::instance()->get("ontology", "name")); ?></span>
                <span><i class="fas fa-user"></i> <?= Users::loginUser()->mail; ?></span>
                <input type="hidden" name="logout" value="logout" />
                <div style="float:right; padding-left: 2em;">
                    <a class="logout-btn ui-controlgroup-item ui-button" style="" onclick="document.getElementById('logoutform').submit();"><i class="fas fa-power-off"></i> Logout</a>
                    <?php if (!empty($_SESSION['document'])) { ?>
                        <a href="index.php" class="ui-button"><i class="fas fa-arrow-left"></i> Back</a>
                    <?php } ?>
                    <?php if (Users::loginUser()->isCurator()) { ?>
                        <a href="index2.php?action=importpub.ui" class="ui-button"><i class="fas fa-arrow-up"></i> Import</a>
                    <?php } ?>
                </div>
            </div>
        </form>
</div>


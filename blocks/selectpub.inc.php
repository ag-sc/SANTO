<?php
require_once("./php/publication.php");
require_once("./php/user.php");
?>
<div style="width: 80%; margin: 0 auto;">
<h1>Select Publication</h1>
    <div class="pub_select_filter">
    <input type="text" id="pub_select_input" class="ui-widget" placeholder="Search..." data-clear-btn="true" />
</div>
    <ul class="pub_select_container">
<?php
$publist = null;
if (!$_SESSION['admin']) {
    $publist = Publications::all(Users::loginUser());
} else {
    $publist = Publications::all();
}

foreach($publist as $pub) {
?>
    <li class="<?php if (!empty($_SESSION['document']) && $pub->id == $_SESSION['document']) { echo "active_pub"; } ?>"><form id="go_pub_<?= $pub->id?>" action="index.php" method="post" enctype="application/x-www-form-urlencoded">
    <input type="hidden" name="document" value="<?= $pub->id ?>" />
<table class="pub_select">
<tr>
<td class="pub_select_go">
<button class="ui-button" type="submit">
    <span class="fas fa-file-alt"></span> Go 
</button>
</td>
<td class="pub_select_name">
    <a href="#" class="doc_change_link" data-form="go_pub_<?= $pub->id?>"><?= $pub->name ?></a>
</td>

<td class="pub_select_more">
<?php
        foreach ($pub->users() as $annotator) {
            echo '<span class="pub_select_annotator ui-corner-all">';
            echo '<i class="far fa-user"></i> ';
            echo '<span class="pub_select_annotator_name">'.$annotator->mail.'</span> ';
            $style = 'ps_a_notready';
            if ($pub->isReady($annotator)) {
                $style = 'ps_a_ready';
            }
            echo '<a href="#" title="Annotation" class='.$style.'>';
            echo '<i class="fas fa-pencil-alt"></i>';
            echo '</a> ';
            if ($_SESSION['admin'] && $annotator->isCurator()) {
                $style = 'ps_a_notready';
                if ($pub->isReadyCuration($annotator)) {
                    $style = 'ps_a_ready';
                }
                echo '<a href="#" title="Curation" class='.$style.'>';
                echo '<i class="fas fa-align-justify"></i>';
                echo '</a> ';

                $style = 'ps_a_notready';
                if ($pub->isReadySlotFilling($annotator)) {
                    $style = 'ps_a_ready';
                }
                echo '<a href="#" title="Slot Filling" class='.$style.'>';
                echo '<i class="fas fa-database"></i>';
                echo '</a> ';
            }
            echo "</span>";
    }
?>

</td>

</tr>
</table>
</form></li>
<?php
}
?>
</ul>

</div>
<script type="text/javascript">
$(document).ready(function() {
    $("a.doc_change_link").click(function() {
        $("#" + $(this).data("form"))[0].submit();
    });
    var $searchFilter = $("input#pub_select_input");
    $searchFilter.on('input', function() {
        console.log($searchFilter.val());
        var querytext = $searchFilter.val().trim();

        $("ul.pub_select_container li").each(function(idx, elem) {
            var elemtext = $(elem).text().replace(/\s+/g, " ").trim();
            var newstate = 'show';
            if (!querytext || querytext === '') {
                // noop, show;
            } else if (elemtext) {
                if (!elemtext || elemtext === '') {
                    // noop, show
                } else {
                    if (elemtext.indexOf(querytext) === -1) {
                        newstate = "hide";
                    }
                }
            }

            if (newstate === 'hide') {
                $(elem).hide();
            } else {
                $(elem).show();
            }
        });
    });
});
</script>

<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

require_once("../php/constants.php");
require_once("../php/functions.php");

$parsedRelations = parseRelations();

$number = 0;

function hasData($id) {
    global $mysqli;
    if ($id) {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM `Data` WHERE `Id` = ? AND `User` = ? AND (`AnnotationId` IS NOT NULL OR `DataGroup` IS NOT NULL)");
        $stmt->bind_param("ii", $id, $_SESSION['user']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($r = $result->fetch_row())
            return $r[0];
    }
    return false;
}

function createRow($row, $depth, $groups, $id, $dataGroup, $parent, $num, $size) {
    global $mysqli, $parsedRelations, $number;
    # broken: $opened = isset($_SESSION["opened"][$id]) && $_SESSION["opened"][$id] == 1;
    $opened = false;

    if ($id) {
        $stmt = $mysqli->prepare("SELECT `Class`.`Name`, `Class`.`Id`, `Class`.`Description`, Data.ManuallySet FROM `Data` JOIN `Class` ON `ClassId` = `Class`.`Id`  WHERE `Data`.`Id` = ? AND `Data`.User = ? AND `Data`.`PublicationId` = ?");
        $stmt->bind_param("iii", $id, $_SESSION['user'], $_SESSION['document']);
        $stmt->execute();
        $stmt->bind_result($class, $classId, $description, $manuallyset);
        $stmt->fetch();
        $class = $class == "" ? $row["Range"] : $class;
        $classId = $classId == "" ? $row["RangeId"] : $classId;
        $stmt->close();
    } else {
        $class = $row["Range"];
        $classId = $row["RangeId"];
        $manuallyset = 0;
    }
    $valued = $classId != $row["RangeId"] || hasData($id);
    $dropdown = in_array($row["RangeId"], $groups);
//    var_dump($classId, $parsedRelations);
    $collapsible = !$dropdown && (array_key_exists($row["RangeId"], $parsedRelations) || array_key_exists($classId, $parsedRelations));
    $hidden = isset($_POST["hidden"]) ? 'style="display: none"' : ''; ?>
    <tr class="tooltip-able" data-relisdp="<?= $row["DataProperty"] ?>" title="<?=$description?>" from="<?= $row["From"] ?>" to="<?= $row["To"] ?>" dataId="<?= $id ?>" classId="<?= $classId ?>" parent="<?= $parent?>" relationId="<?=$row["Id"]?>"
        data-manualentry="<?= $manuallyset ?>" 
        originalClassId = "<?= $row["RangeId"]?>"
        class='<?= $collapsible ? ($opened ? "collapsed expanded" : "collapsed collapsible") : "" ?>' depth='<?= $depth ?>'>
        <td class='relation' relationId='<?= $row["Id"] ?>' style='padding-left: <?= 2 * $depth ?>em'>
            <div <?= $hidden ?>>
                <span class="icon <?= $collapsible ? ($opened ? "ui-icon ui-icon-circle-arrow-s" : "ui-icon ui-icon-circle-arrow-e") : "" ?>"></span>
                <label <?= !$collapsible ? "style='padding-left: 20px'" : "" ?>><?= $row["MergedName"] . ($size > 1 ? " $num" : "")?></label>
            </div>
        </td>
        <?php if ($row["To"] == 'm') { ?>
        <td class="<?= $valued ? "" : "addFieldTd" ?>">
            <?= $valued
                ? "<span class='addField ui-button-icon ui-icon ui-icon-plus'>+</span>"
                : "<span></span>"
        ?></td>
        <?php } else { echo "<td></td>"; } ?>
        <td class='input'>
            <div <?= $hidden ?>>
                <?php if (!in_array($row["RangeId"], $groups) && $row["Range"] != "Event") {
                    $placeholder = $class;
                    if ($classId != $row["RangeId"]) {
                        if (!empty($row["DataProperty"]) && $row["DataProperty"] == 1) {
                            $placeholder .= " "; #  . $id;
                        } else {
                            $placeholder = $placeholder; 
                        }
                    } else {
                        if (!empty($row["DataProperty"]) && $row["DataProperty"] == 1) {
                           $placeholder = '';
                        } else {
                            if (!empty($manuallyset) && $manuallyset != 0) {
                                $placeholder = $placeholder; # TODO current, only show when manually set
                            } else {
                                $placeholder = '';
                            }
                        }
                    }
                    ?>
                        <input type='text' placeholder='<?=$placeholder?>' data-range="<?= !empty($row['Range']) ? $row['Range'] : "" ?>" data-isdataprop="<?= (!empty($row["DataProperty"]) && $row["DataProperty"] == 1) ? "1" : "0" ?>" data-class="<?= htmlentities($class); ?>" data-instance="<?= htmlentities($class)." ".htmlentities($id); ?>"
                           class='slot_editor accepts-<?= implode(" accepts-", getSubclasses($row["RangeId"])) ?>'
                           readonly='readonly' value='<?php
                    if ($id) {
                        $query = <<<QUERY
    SELECT `Class`.`Name` as `Class`, `Text`, `Annotation`.`Index` as AnnotationIndex
    FROM `Annotation`
        JOIN `Class`
            ON `Annotation`.`Class` = `Class`.`Id`
        WHERE `Annotation`.`Index` = (SELECT `Index` FROM `Annotation` WHERE `Id` = (SELECT `AnnotationId` FROM `Data` WHERE `Id` = ? AND `User` = ? AND `PublicationId` = ?))
QUERY;
                        $stmt = $mysqli->prepare($query);
                        $stmt->bind_param("iii", $id, $_SESSION['user'], $_SESSION['document']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $text = "";
                        $class = null;
                        while ($r = $result->fetch_row()) {
                            $class = $r[0];
                            $text .= $r[1]." ";
                            $annotationIndex = $r[2];
                        }
                        if (isset($class) && !empty($text)) {
                            $valued = true;
                            echo "$class (".trim($text).")";
                        }
                        $stmt->free_result();
                        //$id = $mysqli->insert_id;
                    }
                    ?>' annotation='<?= ($valued && !empty($annotationIndex)) ? $annotationIndex : ""?>' hasvalue="<?= ($valued && !empty($annotationIndex)) ? "1" : "0" ?>">
                <?php
                } else
                {
                    ?>
                        <select id="select_<?= $id ?>" class="sublist_select slot_editor" size="1" data-range="<?= !empty($row['Range']) ? $row['Range'] : "" ?>" data-isdataprop="<?= (!empty($row["DataProperty"]) && $row["DataProperty"] == 1) ? "1" : "0" ?>" data-class="<?= htmlentities($class); ?>" data-instance="<?= htmlentities($class)." ".htmlentities($id); ?>" data-datagroupid="<?= (isset($dataGroup) && $dataGroup != -1) ? $dataGroup : "-1"  ?>" hasvalue="<?= (isset($dataGroup) && $dataGroup != -1) ? "1" : "0"  ?>" onchange='$(function() {
                            var self = $("#select_<?= $id ?>");
                            var tr = self.closest("tr");
                            var tab = tr.closest("table").prev("ul").find("li.active");
                            var loader = tab.find("a.loader");
                            var value = $("#select_<?= $id ?>").val();
                            $.ajax({
                                type: "POST",
                                url: "ajax/setDataGroup.php",
                                data: {"dataId": <?= $id ?>, "groupId": value},
                                success: function() {
                                    self.find("option[value=-1]").remove();
                                    var removeInput = tr.find("td.removeInput").find("span.removeInput");
                                    if (value == -1)
                                        removeInput.hide();
                                    else
                                        removeInput.show();
                                    if(tr.attr("to") != 1) {
                                        refresh();
                                    }
                                }
                            });
                            })'>
                        <?php if (!isset($dataGroup) || $dataGroup == -1) { ?>
                        <option value="-1">Select <?= $row["Range"] ?></option>
                        <?php } ?>
                        <?php $query = <<<QUERY
    SELECT `Class`.`Name`, `Data`.`Id`, (SELECT `Text` FROM `Annotation` WHERE `Id` = `Data`.`AnnotationId`) AS Text, `Data`.`Name`, Data.ManuallySet FROM `Data`
        JOIN `Class` ON `Class`.`Id` = `ClassId`
        WHERE `ClassId` = ? AND `Data`.`Parent` IS null AND `Data`.`User` = ? AND `Data`.`PublicationId` = ?
QUERY;
                        $i = 0;
                        foreach (getSubclasses($row["RangeId"]) as $subClass) {
                            //                        echo "<option>$subClass</option>";
                            $stmt = $mysqli->prepare($query);
                            $stmt->bind_param("iii", $subClass, $_SESSION['user'], $_SESSION['document']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($r = $result->fetch_row()) {
                                    echo "<option " . (($r[1] == $dataGroup) ? "selected=\"selected\" " : "") . "value='" . $r[1] . "' data-manualentry='".$r[4]."'>" . ($r[3] ? $r[3] : ($r[0] . " " . $r[1])) . "</option>";
                            }
                        }
                        ?>
                    </select>
                <?php } ?>
            </div>
        </td>
        <td class='removeInput'>
            <div <?= $hidden ?>>
                <span <?= ($manuallyset || $valued || $num != $size) ? '' : 'style="display: none"' ?> class='removeInput'><i class="fas fa-minus-circle"></i></span>
            </div>
        </td>
    </tr>
    <?php
    if ($opened)
        createSublist($row["RangeId"], null, $id, $depth+1);
    return $valued;
}

function createSublist($classId, $topLevel, $dataId, $depth) {
    global $mysqli;

    $superClasses = getSuperclasses($classId);
    $subClasses = getSubclasses($_POST["topLevelId"]);

    $query = "SELECT `Relation`.`Id` AS `Id`, `Relation`, `Range`.`Id` AS `RangeId`, `Range`.`Name` AS `Range`, `From`, `To`, `MergedName`, Relation.DataProperty AS DataProperty
        FROM `Relation`
        JOIN `Class` AS `Range`
            ON `Relation`.`Range` = `Range`.`Id`
        WHERE `Domain` = ?".str_repeat(" OR `Domain` = ?", sizeof($superClasses)-1)." ORDER BY `Id`";
    if ($stmt = $mysqli->prepare($query)) {
        $params = array();
        for ($i = 0; $i < sizeof($superClasses); ++$i) {
            $params[] = &$superClasses[$i];
        }
        call_user_func_array(array($stmt, "bind_param"), array_merge(array(str_repeat("i", sizeof($superClasses))), $params));
        $stmt->execute();
        $results = $stmt->get_result();
        $relations = $results->fetch_all(MYSQLI_ASSOC);
        $stmt->free_result();
    }



    $query = <<<QUERY
    SELECT `Group` FROM `Group`;
QUERY;
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $groups = array();
    while($group = $result->fetch_assoc()) {
        $groups = array_merge($groups, getSubclasses((int) $group["Group"]));
    }
    $stmt->free_result();

    $valued = false;
    if ($depth == 0 && (sizeof($subClasses) > 1 || sizeof($superClasses) > 1)) {
        if ($stmt = $mysqli->prepare("SELECT `Name`, `Description` FROM `Class` WHERE `Id` = ?;")) {
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $resultSet = $stmt->get_result();
            $result = $resultSet->fetch_assoc();
            $stmt->free_result();
            $class = $result["Name"];
            $description = $result["Description"];
        }
?>
        <tr class="tooltip-able" title="<?=$description?>"  dataId="<?=$dataId?>" classId="<?=$classId?>" data-topLevelId="<?php if (!empty($_POST['topLevelId'])) { htmlentities($_POST['topLevelId']); } ?>" originalClassId="<?= $classId?>" depth='0'>
            <td class='relation' style='padding-left: <?=2*$depth?>em'>
                <div>
                    <label style='padding-left: 20px'>Type</label>
                </div>
            </td>
            <td></td>
            <td class='input'>
                <div>
<?php 
        if ($classId != $_POST['topLevelId']) {
            $valued = true;
        }
?>
    <input type='text' data-classid="<?php echo htmlentities($dataId); ?>" data-tlid="<?php if (!empty($_POST['topLevelId'])) { echo htmlentities($_POST['topLevelId']); } ?>" placeholder='<?php
                    $stmt = $mysqli->prepare("SELECT `Class`.`Name`, Data.ManuallySet FROM `Data` JOIN `Class` ON `ClassId` = `Class`.`Id`  WHERE `Data`.`Id` = ? AND `Data`.`User` = ? AND `Data`.`PublicationId` = ?");
                    $stmt->bind_param("iii", $dataId, $_SESSION['user'], $_SESSION['document']);
                    $stmt->execute();
                    $stmt->bind_result($placeholder, $manuallyset);
                    $stmt->fetch();
                    // echo "BCUR".$placeholder." ".$dataId;
                    if (!empty($manuallyset) && $manuallyset != 0) {
                        echo htmlentities($placeholder);
                    }
                    $stmt->close();
                    ?>' class='topLevel darkplaceholder slot_editor accepts-<?=implode(" accepts-", getSubclasses($_POST["topLevelId"]))?>' readonly='readonly' value='<?php
                    $query = <<<QUERY
    SELECT `Class`.`Name` as `Class`, `Text`, Annotation.Id
    FROM `Annotation`
        JOIN `Class`
            ON `Annotation`.`Class` = `Class`.`Id`
        WHERE `Annotation`.`Id` = (SELECT `AnnotationId` FROM `Data` WHERE `Id` = ? AND `User` = ?)
QUERY;
                    $stmt = $mysqli->prepare($query);
                    $stmt->bind_param("ii", $dataId, $_SESSION['user']);
                    $stmt->execute();
                    $stmt->bind_result($class, $text, $classId);
                    $stmt->fetch();
                    if (isset($class) && isset($text)) {
                        $valued = true;
                        echo "$class ($text)";
                    }
                    $stmt->close();
                    ?>' data-manualentry="<?= $manuallyset ?>">
                </div>
            </td>
            <td class='removeInput'>
                <div>
                    <span <?= ($manuallyset || $valued) ? '' : 'style="display: none"'?> class='removeInput'><i class="fas fa-minus-circle"></i></span>
                </div>
            </td>
        </tr>
        <?php
    }

    foreach ($relations as $relation) {
        if ($stmt = $mysqli->prepare("SELECT `Id`, `ClassId`, `AnnotationId`, `RelationId`, `DataGroup`, ManuallySet FROM `Data` WHERE Parent = ? AND RelationId = ? AND `User` = ? AND `PublicationId` = ?")) {
            $stmt->bind_param("iiii", $dataId, $relation["Id"], $_SESSION['user'], $_SESSION['document']);
            $stmt->execute();
            $results = $stmt->get_result();
            $children = $results->fetch_all(MYSQLI_ASSOC);
            $stmt->free_result();
        } else die($mysqli->error);

        $relation["From"] = ($relation["From"] == "?") ? 1 : $relation["From"];
        $relation["To"] = ($relation["To"] == "?") ? 1 : $relation["To"];

        if (sizeof($children) == 0) {
            $stmt = $mysqli->prepare("INSERT INTO `Data` (`ClassId`, `Parent`, `RelationId`, `User`, `PublicationId`) VALUES (?, ?, ?, ?, ?);");
            $stmt->bind_param("iiiii", $relation["RangeId"], $dataId, $relation["Id"], $_SESSION['user'], $_SESSION['document']);
            $stmt->execute();
            $stmt->free_result();

            createRow($relation, $depth, $groups, $mysqli->insert_id, -1, $dataId, 1, 1);
            continue;
        }
        $i = 0;
        foreach ($children as $child)
            createRow($relation, $depth, $groups, $child["Id"], $child["DataGroup"], $dataId, ++$i, sizeof($children));
    }
}

createSublist($_POST["classId"], isset($_POST["topLevel"]) ? $_POST["topLevel"] : null, $_POST["dataId"], isset($_POST["depth"]) ? $_POST["depth"] : 0);

?>
<script>
    $(function () {
        $('input').hover(function(e) {
            $(".highlight-at-"+$(e.target).closest("tr").attr("classId")).addClass("annotation-label-hover");
        }, function(e) {
            $(".highlight-at-"+$(e.target).closest("tr").attr("classId")).removeClass("annotation-label-hover");
        });
    });
</script>

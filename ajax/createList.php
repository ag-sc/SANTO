<?php

require_once("../php/user.php");
Users::ensureActiveLogin();

require_once "../php/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid method');
}

if(!isset($_POST["classId"])) die("Fatal error: No class set");
$classId = $_POST["classId"];

$subClasses = getSubclasses($_POST["classId"]);
$query = "SELECT `Id`, (SELECT `Text` FROM `Annotation` WHERE `Id` = `Data`.`AnnotationId`) AS Text, `ClassId`, `Name` FROM `Data` WHERE (`ClassId` = ?".str_repeat(" OR `ClassId` = ?", sizeof($subClasses)-1).") AND `Parent` IS NULL AND `User` = {$_SESSION['user']} AND `PublicationId` = {$_SESSION['document']}";
if ($stmt = $mysqli->prepare($query)) {
    $params = array();
    for ($i = 0; $i < sizeof($subClasses); ++$i) {
        $params[] = &$subClasses[$i];
    }
    call_user_func_array(array($stmt, "bind_param"), array_merge(array(str_repeat("i", sizeof($subClasses))), $params));
    $stmt->execute();
    $resultSet = $stmt->get_result();
    $dataSet = $resultSet->fetch_all(MYSQLI_ASSOC);
    $stmt->free_result();
} else {
    die($mysqli->error);
}
if ($stmt = $mysqli->prepare("SELECT `Name`, Description FROM `Class` WHERE `Id` = ?;")) {
    $stmt->bind_param("i", $_POST["classId"]);
    $stmt->execute();
    $resultSet = $stmt->get_result();
    $result = $resultSet->fetch_assoc();
    $stmt->free_result();
    $class = $result["Name"];
    $description = $result["Description"];
}
$tabCounter = 0;
?>


<ul>
    <?php foreach ($dataSet as $data) { ?>
    <li tabId="<?=$data["Id"]?>" class="tabs">
        <a title="<?=$description?>" class="loader" onclick="loadTab(<?=$data["Id"]?>, <?=$data["ClassId"]?>)">
            <?= $data["Name"] ? $data["Name"] : ($class." ".$data["Id"])?></a>
        <a title="Rename tab" onclick="renameTabDefault(<?=$data["Id"]?>)" class='rename_tab'><i class="fas fa-pencil-alt"></i></a>
        <a title="Remove tab" onclick="removeTab(<?=$data["Id"]?>)" class='remove_tab'><i class="fas fa-minus-circle"></i></a>
        <a title="Duplicate tab" onclick="duplicateTab(<?=$data["Id"]?>, <?=$data["ClassId"]?>)" class='duplicate_tab'><i class="fas fa-clone"></i></a>
    </li>
    <?php } ?>
    <li class="add_tab not-sortable">add</li>
    <li class="tab-spinner not-sortable" style="display: none"></li>
</ul>
<br style="clear:both" />
<table style="border-collapse: collapse" class="annotation-list">
    <tbody id="tab-<?=$classId?>">
    </tbody>
</table>
<script>
function updatePlaceholder(idx, elem) {
    elem = $(elem);
    if (elem.hasClass("topLevel")) {
        return; // already handled server-side
    }

    if (!elem.hasClass("darkplaceholder")) {
        elem.addClass("darkplaceholder");
    }
}

    function updateHierarchy(data, response) {
        if (!data) {
            return;
        }
        var $container = $("li.active[tabid]").parent().parent();
        $container.find("tr input.slot_editor").each(updatePlaceholder);
    }

    var tabCounter = <?=$tabCounter?>;
    var ctrlPressed = false;
    var contextMenuMetaData = [];
    function loadTab(id, classId) {
        var reqdata = {"dataId": id, "classId": classId, "topLevel": true, "topLevelId": $(".ui-accordion-content-active").attr("classId")};
        $.ajax({
            url: "ajax/createSubList.php",
            data: reqdata,
            type: "POST",
            cache: false,
            success: function(response) {
                $("#tab-<?=$classId?>").html(response);
                $("li[tabid]").removeClass("active");
                $("li[tabid="+id+"]").addClass("active");
                updateHierarchy(reqdata, response);
            }
        });
    }

    function removeTab(id) {
        if (!confirm("Are you sure?")) {
            return false;
        }
        var self = this;
        $.ajax({
            url: "ajax/removeTab.php",
            data: {"dataId": id},
            type: "POST",
            cache: false,
            success: function() {
                var wasactive = $("[tabId="+id+"]").hasClass("active");
                var target = null;
                if (wasactive) {
                    target = $("[tabId="+id+"]").parents("div:first").children("table.annotation-list")
                }
                $("[tabId="+id+"]").remove();
                if (wasactive) {
                    target.children("tbody").empty();
                }
            }
        });
    }
    function duplicateTab(dataId, classId) {
        var self = $(".add_tab");
        $.ajax({
            url: "ajax/duplicateTab.php",
            data: {"dataId": dataId, "classId": classId},
            type: "POST",
            cache: false,
            success: function(data) {
                var newDataId = data.newDataId;
                var description = data.description;
                var tabs = self.closest(".annotation-tabs");

                var li = "<li title=\""+description+"\" tabId=\""+newDataId+"\" class=\"tabs\">" +
                    "  <a class=\"loader\" onclick=\"loadTab("+newDataId+", "+classId+")\"><?=$class?> " + newDataId + "</a>" +
                    "  <a title=\"Rename tab\" onclick=\"renameTabDefault("+newDataId+")\" class='rename_tab'><i class=\"fas fa-pencil-alt\"></i></a>" +
                    "  <a title=\"Remove tab\" onclick=\"removeTab("+newDataId+")\" class='remove_tab'><i class=\"fas fa-minus-circle\"></i></a>" +
                    "  <a title=\"Duplicate tab\" onclick=\"duplicateTab("+newDataId+", "+classId+")\" class='duplicate_tab'><i class=\"fas fa-clone\"></i></a>" +
                    "</li>";
                self.before(li);
                self.prev().contextmenu(renameTab);
                loadTab(newDataId, classId);
                renameTabId(newDataId, "<?=$class?> "+newDataId);
            }
        });
    }
    function renameTab(e) {
        e.preventDefault();
        var target = $(e.currentTarget);
        renameTabId(target.attr("tabId"), target.find(".loader").text());
    }
    function renameTabDefault(id) {
        var target = $(".tabs[tabid="+id+"]");
        renameTabId(id, target.find(".loader").text());
    }
    function renameTabId(id, text) {
        var dialog = $("#dialog");
        dialog.attr("dataId", id);
        dialog.find("input[type=text]").val(text.trim());
        dialog.dialog( "open" );
    }

    $(function () {
        $('.tabs').contextmenu(renameTab);
        $(".add_tab").button({
            icon: "ui-icon-plus",
            showLabel: false
        }).on("click", function () {
            var self = $(this);
            ++tabCounter;
            $.ajax({
                url: 'ajax/insertGroup.php',
                data: {"classId": <?=$classId?>},
                type: 'POST',
                cache: false,
                dataType: "json",
                success: function(data)
                {
                    if (data["success"]) {
                        var tabs = self.closest(".annotation-tabs");

                        var li = "<li title=\""+data["description"]+"\" tabId=\""+data["id"]+"\" class=\"tabs\">" +
                            "  <a class=\"loader\" onclick=\"loadTab("+data["id"]+", <?=$classId?>)\"><?=$class?> " + data["id"] + "</a>" +
                            "  <a title=\"Rename tab\" onclick=\"renameTabDefault("+data["id"]+")\" class='rename_tab'><i class=\"fas fa-pencil-alt\"></i></a>" +
                            "  <a title=\"Remove tab\" onclick=\"removeTab("+data["id"]+")\" class='remove_tab'><i class=\"fas fa-minus-circle\"></i></a>" +
                            "  <a title=\"Duplicate tab\" onclick=\"duplicateTab("+data["id"]+", <?=$classId?>)\" class='duplicate_tab'><i class=\"fas fa-clone\"></i></a>" +
                            "</li>";
                        self.before(li);
                        self.prev().contextmenu(renameTab);
                        loadTab(data["id"], <?=$classId?>);
                        renameTabId(data["id"], "<?=$class?> "+data["id"]);
                        // var dialog = $( "#dialog" );
                        // dialog.attr("dataId", data["id"]);
                        // dialog.attr("")
                        // dialog.dialog( "open" );
                    }
                }
            });
        });

        $('.annotation-list').on('click', 'span.removeInput', function (e) {
            var self = $(this);
            var tr = self.closest("tr");
            if (!confirm("Are you sure?")) {
                return false;
            }
            var input = tr.find(".input input");
            if (!input || !input.hasClass("topLevel")) {
                $.ajax({
                    url: "ajax/removeField.php",
                    data: {
                        "dataId": tr.attr("dataId"),
                    },
                    type: "POST",
                    success: function () {
                        refresh();
                    }
                });
            }

            var tab = tr.closest("table").prevAll("ul:first").find("li.active");
            var loader = tab.find("a.loader");

            if (tr.find("select").length) {
                $.ajax({
                    type: "POST",
                    url: "ajax/setDataGroup.php",
                    data: {"dataId": tr.attr("dataId"), "groupId": -1},
                    success: function() {
                        loader.click();
                    }
                });
            } else {
                var input = tr.find(".input input");
                if (input.hasClass("topLevel")) {
                    loader.text(tr.closest("div.accordion-div").attr("className") + " " + ++tabCounter);
                    var classId = tr.closest("div.accordion-div").attr("classId");

                    $.ajax({
                        type: "POST",
                        url: "ajax/removeDataAnnotation.php",
                        data: {"dataId": tr.attr("dataId")},
                        success: function (result) {
                            input.val("");
                            loader.attr("onclick", "loadTab(" + tab.attr("tabId") + ", " + classId + ")");
                            refresh();
                        }
                    });
                    $.ajax({
                        type: "POST",
                        url: "ajax/updateClass.php",
                        data: {"dataId": tr.attr("dataId"), "classId": classId, "removed": 1}
                    });
                } else {
                    $.ajax({
                        url: 'ajax/removeData.php',
                        data: {"dataId": tr.attr("dataId")},
                        type: 'POST',
                        cache: false,
                        success: function (data) {
                            input.val("");
                            tr.find(".icon").click();
                            self.hide();
                            refresh();

                        }
                    });
                }
            }
            e.stopPropagation();
        }).on('click', 'input:text', function (e) {
            if (e.ctrlKey) {
                var annotation = $(this).attr("annotation");
                if (annotation == 0)
                    return;
                var target = $(".annotation-label[annotation="+$(this).attr("annotation")+"]");
                console.log(target);
                var content = $("#content");
                content.animate({
                    scrollTop: target.offset().top - content.offset().top + content.scrollTop()
                });
                return;
            }
            var tr = $(this).closest("tr");
            var relation = tr.find(".relation");
            var classId = null;
            if (relation.is("[relationId]"))
                var data = {"relationId": relation.attr("relationId")};
            else {
                classId =  tr.closest("div").attr("classId");
                var data = {"classId": classId};
            }
            var self = $(this);
            var dataId = tr.attr("dataId");
            var reqdata = data;
            $.ajax({
                url: "ajax/GetSubclassesByRelation.php",
                data: data,
                type: "POST",
                cache: true,
                success: function (data) {
                    var menu = $("#context-menu");
                    menu.empty();
                    $("#context-menu-title").text(relation.find('label').text());
                    var contextMenuItems = [];
                    var tab = tr.closest("table").prev("ul").find("li.active");
                    var tabId = tab.find("a.loader").parent("li").attr("tabid");

                    var annotation = false;
                    $.each($(".highlight-at-"+tr.attr("originalClassId")), function(index, element) {
                        if (!annotation) {
                            menu.append("<menuitem label='Annotations'/>");
                            annotation = true;
                        }
                        var anno = $(element);
                        var onset = parseInt(anno.attr("onset"));
                        var offset = parseInt(anno.attr("offset"));
                        var sentence = parseInt(anno.attr("sentence"));
                        var annotationId = parseInt(anno.attr("annotation"));
                        var content = "";
                        // var onset = parseInt(label.attr(""))
                        $(".token").filter(function () {
                            return parseInt($(this).attr("sentence")) === sentence;
                        }).filter(function () {
                            return parseInt($(this).attr("token")) >= onset;
                        }).filter(function () {
                            return parseInt($(this).attr("token")) <= offset;
                        }).each(function () {
                            content += $(this).text()+" ";
                        });
                        contextMenuMetaData.push(annotationId);
                        contextMenuItems[String(annotationId)] = {name: String(anno.text()+" ("+content+")")};
                        menu.append("<menuitem label='"+anno.text()+" ("+content.substr(0, content.length-1)+")' data='"+dataId+"' annotation='"+annotationId+"' classId='" + anno.attr("classid") + "' />");
                    });
                    if (data.length > 0) {
                        if (!($(tr).data('relisdp') && $(tr).data('relisdp') == 1)) {
                            menu.append("<menuitem label='Classes'/>");
                            var inp = null;
                            if (tr.find("input[type=text]") && tr.find("input[type=text]").hasClass("topLevel")) {
                                inp = tr.find("input[type=text]");
                                if (inp && inp.data("tlid")) {
                                    inp = inp.data("tlid");
                                } else {
                                    inp = null;
                                }
                            }
                            for (var index = 0, l = data.length; index < l; index++) {
                                var element = data[index];
                                contextMenuItems[element.name] = {name: element.name};
                                var originalClassID = null;
                                if ($(relation) && $(relation).parent() && $(relation).parent().attr('originalclassid')) {
                                    originalClassID = $(relation).parent().attr('originalclassid');
                                }
                                if (inp) { //top level
                                    if (!inp || inp != element.id) {
                                        menu.append("<menuitem label='"  + element.name + "' data='"+dataId+"' elementId='"+element.id+"' />");
                                    } else {
                                        menu.append("<menuitem label='"  + element.name + "' data='"+dataId+"' elementId='"+element.id+"' root='rootclass' />");
                                    }
                                } else {
                                    if (!originalClassID || originalClassID != element.id) {
                                        menu.append("<menuitem label='"  + element.name + "' data='"+dataId+"' elementId='"+element.id+"' />");
                                    } else {
                                        menu.append("<menuitem label='"  + element.name + "' data='"+dataId+"' elementId='"+element.id+"' root='rootclass' />");
                                    }
                                }
                            };
                        } else {
                            if (!annotation) {
                                menu.append("<menuitem label='Please create an annotation'/>");
                            }
                        }
                    }

                    if (menu.children().length > 0)
                        menu.contextMenu({
                            x: self.offset().left ,y: self.offset().top
                        });
                }
            });
        }).on('click', '.icon', function (e) {
            var tr = $(this).closest("tr");
            var findChildren = function (tr) {
                return tr.nextUntil($('tr').filter(function () {
                    return $(this).attr('depth') <= tr.attr("depth");
                }));
            };

            if (tr.hasClass('expanded')) {
                var children = findChildren(tr);
                children.find("div").slideUp({"always": function () {
                    children.remove();
                }})
                tr.removeClass('expanded').addClass('collapsed');
                tr.find(".icon").removeClass('ui-icon-circle-arrow-s').addClass('ui-icon-circle-arrow-e');

                $.ajax({url: "ajax/setOpen.php",data: {"dataId": tr.attr("dataId"),"open": 0},type: "POST"});
            } else {
                var reqdata = {
                        "dataId": tr.attr("dataId"),
                        "classId": tr.attr("classId"),
                        "depth": parseInt(tr.attr("depth")) + 1,
                        "hidden": 1,
                        "topLevelId": $(".ui-accordion-content-active").attr("classId")
                    };
                $.ajax({
                    url: "ajax/createSubList.php",
                    data: reqdata,
                    type: "POST",
                    cache: false,
                    success: function (data) {
                        $(data).find("div").hide();
                        tr.after(data);
                        findChildren(tr).find("div").slideDown();
                        tr.removeClass('collapsed').addClass('expanded');
                        tr.find(".icon").removeClass('ui-icon-circle-arrow-e').addClass('ui-icon-circle-arrow-s');
                        $.ajax({url: "ajax/setOpen.php",data: {"dataId": tr.attr("dataId"),"open": 1},type: "POST"});
                        updateHierarchy(reqdata, data);
                    }
                });
            }
        }).on('click', 'span.addField', function () {
            var tr = $(this).closest("tr");
            $.ajax({
                url: "ajax/addField.php",
                data: {
                    "class": tr.attr("originalClassId"),
                    "parent": tr.attr("parent"),
                    "relation": tr.attr("relationId")
                },
                type: "POST",
                success: function () {
                    refresh();
                }
            });
        })
        // .on('click', 'span.removeField', function () {
        //     var tr = $(this).closest("tr");
        //     $.ajax({
        //         url: "ajax/removeField.php",
        //         data: {
        //             "dataId": tr.attr("dataId"),
        //         },
        //         type: "POST",
        //         success: function () {
        //             refresh();
        //         }
        //     });
        // });
    });
    $(document).keydown(function(event){
        if(event.which=="17")
            ctrlPressed = true;
    });

    $(document).keyup(function(){
        ctrlPressed = false;
    });
</script>

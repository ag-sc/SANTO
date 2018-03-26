function refresh() {
    $("li.active").find("a.loader:visible").click();
}

function insertAnnotation(dataId, annotationId, classId) {
    $.ajax({
        type: "POST",
        url: "ajax/insertData.php",
        data: {"dataId": dataId, "annotationId": annotationId},
        success: function () {
            refresh();
        }
    });
    $.ajax({
        url: "ajax/updateClass.php",
        data: {"dataId": dataId, "classId": classId},
        type: "POST",
        cache: false,
        success: function() {
            var loader = $("li.tabs.active:visible").find("a.loader");
            if ($(".annotation-list:visible").find("tr[dataId="+dataId+"]").find("input").hasClass("topLevel"))
                loader.attr("onclick", "loadTab("+loader.closest("li.tabs").attr("tabId")+", "+classId+")").click();
            refresh();
        }
    });
}

function select_user_color(user) {
    console.log(user);
    switch (user) {
        case "3": return "red";
        case "4": return "blue";
        case "5": return "darkgreen";
        case "6": return "darkorange";
        case "7": return "aqua";
        case "8": return "brown";
        case "9": return "indigo";
        case "10": return "gray";
        default: return "black";
    }
}

function update_user_colors() {
    var chosen = $("#shownUsersSelect_chosen");
    var select = $("#shownUsersSelect");
    chosen.find("li.search-choice").each(function () {
        var index = $(this).find("a").attr("data-option-array-index");
        var user = select.find("option:nth-child("+(parseInt(index)+1)+")").val();
        $(this).css({"border-color":select_user_color(user)});
        console.log($(this).find("span").text(), user, select_user_color(user));
    });
    console.log(chosen, select);
}

function getSelectionText() {
    var text = "";
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
}

function modeChange() {
    var selectedMode = $('input[name=mode]:checked').val();

    if (selectedMode) {
        // console.log("triggering modeChange", {"mode": selectedMode});
        $.ajax({
            type: "POST",
            url: "ajax/changeMode.php",
            data: {"mode": selectedMode}
        });
    }

    modeApply();
}

function modeApply() {
    // console.log("triggering modeApply k=" + kurator() + " a=" + annotator() + " s=" + filler());
    if (kurator()) {
        $("#annotation-list").hide();
        $("label[for=shownUsersSelect]").show();
        // $("#shownUsersSelect").show();
        $("#shownUsersSelect_chosen").show();
        $("#shownUsersSelect_chosen").css("max-width", "100%");
        $("#shownUsersSelect_chosen").css("float: left");
        $(".left-half").css("right", "0%");
        $("#toolbar").hide();
        $("#toolbar-kurator").show();
        $("#toolbar-slotfilling").hide();
    } else if (annotator()) {
        $("#annotation-list").hide();
        // $("#shownUsersSelect").hide();
        $("label[for=shownUsersSelect]").hide();
        $("#shownUsersSelect_chosen").hide();
        $(".left-half").css("right", "0%");
        $("#toolbar").show();
        $("#toolbar-kurator").hide();
        $("#toolbar-slotfilling").hide();
    } else if (filler()) {
        $("#annotation-list").show();
        // $("#shownUsersSelect").hide();
        $("label[for=shownUsersSelect]").hide();
        $("#shownUsersSelect_chosen").hide();
        $(".left-half").css("right", "50%");
        $("#toolbar").hide();
        $("#toolbar-kurator").hide();
        $("#toolbar-slotfilling").show();
    }
    loadText();
    refresh();
}

function kurator() {
    return $('input[name=mode]:checked').val() == 3;
}
function filler() {
    return $('input[name=mode]:checked').val() == 1;
}
function annotator() {
    return !kurator() && !filler();
}

function loadText(async) {
    if (typeof async === 'undefined') { async = true; }
    // $("#DocumentReady").prop('checked', $('#document').find("option:selected").attr("ready"));
    // console.log($('#document').find("option:selected").attr("ready"));
    $.ajax({
        type: "GET",
        url: "ajax/loadText.php",
        async: async,
        data: {"users": kurator() ? $("#shownUsersSelect").val() : []},
        success: function (result) {
            var width = $("#text").html(result).width();
            var labels = $(".annotation-label");

            $(".annotation-label").mouseenter(function () {
                var sentence = parseInt($(this).attr("sentence"));
                var onset = parseInt($(this).attr("onset"));
                var offset = parseInt($(this).attr("offset"));
                $(".token").filter(function () {
                    return parseInt($(this).attr("sentence")) === sentence;
                }).filter(function () {
                    return parseInt($(this).attr("token")) >= onset;
                }).filter(function () {
                    return parseInt($(this).attr("token")) <= offset;
                }).addClass("annotation-label-hover");
                $(".accepts-" + $(this).attr("classId")).addClass("annotation-label-hover");
            }).mouseleave(function () {
                var sentence = parseInt($(this).attr("sentence"));
                var onset = parseInt($(this).attr("onset"));
                var offset = parseInt($(this).attr("offset"));
                $(".token").filter(function () {
                    return parseInt($(this).attr("sentence")) === sentence;
                }).filter(function () {
                    return parseInt($(this).attr("token")) >= onset;
                }).filter(function () {
                    return parseInt($(this).attr("token")) <= offset;
                }).removeClass("annotation-label-hover");
                $(".accepts-" + $(this).attr("classId")).removeClass("annotation-label-hover");
            }).mousedown(function (event) {
                var mode = $('input[name=mode]:checked', '#mode-form').val();
                if (mode == 1) {
                    var annotation = $(this).attr("classId");
                    var annotationId = $(this).attr("annotation");

                    var input = $(".accepts-" + annotation + ":visible");
                    if (input.length == 1) {
                        insertAnnotation(input.closest("tr").attr("dataId"), annotationId, annotation);
                    } else if (input.length > 1) {
                        var menu = $("#context-menu");
                        menu.empty();
                        input.each(function () {
                            var label = $(this).closest("tr").find("label").text();

                            menu.append("<menuitem label='" + label + "' "
                                + "onclick='insertAnnotation("+$(this).closest("tr").attr("dataId")+", \"" + annotationId + "\", "+annotation+")' "
                                + "class='annotation-selector' annotationId='"+annotationId+"'"
                                + "/>");
                        });
                        $('.context-menu').contextMenu({x: $(this).offset().left, y: $(this).offset().top+15});
                    }
                } else {
                    var index = $(this).attr("annotation");
                    if (annotator())
                        $(".selected:not([annotation="+index+"])").removeClass("selected");
                    $('.annotation-label[annotation='+index+']').toggleClass("selected");
                }
            });

            // console.log($('#document').find("option:selected").attr("ready"));
        }
    });
}

function getSelectedAnnotation() {
    var index = null;
    $(".selected").each(function () {
        var idx = $(this).attr("annotation");
        if (index && idx != index)
            return null;
        index = idx;
    });
    return index;
}

function setDocumentReadyStatus(ready) {
    $.ajax({
        type: "POST",
        url: "ajax/changeDocumentReadyStatus.php",
        data: {"ready": ready},
        success: function (value) {
            $("#DocumentReady").checked(ready);
        }
    });
}

function setDocumentReadyCurationStatus(ready) {
    $.ajax({
        type: "POST",
        url: "ajax/changeDocumentReadyCurationStatus.php",
        data: {"ready": ready},
        success: function (value) {
            $("#DocumentReadyCuration").checked(ready);
        }
    });
}

function setDocumentReadySlotFillingStatus(ready) {
    $.ajax({
        type: "POST",
        url: "ajax/changeDocumentReadySlotFillingStatus.php",
        data: {"ready": ready},
        success: function (value) {
            $("#DocumentReadySlotFilling").checked(ready);
        }
    });
}

function handleAnnotationGrowShrink(alt, left, right) {
    var selected = $(".selected");
    if (selected.length != 1) return;
    selected = selected.first();
    var onset = parseInt(selected.attr("onset"));
    var offset = parseInt(selected.attr("offset"));
    if (!alt) {
        if (left)
            onset -= 1;
        if (right)
            offset += 1;
    } else {
        if (left)
            offset -= 1;
        if (right)
            onset += 1;
    }

    var sentence = parseInt(selected.attr('sentence'));
    var onsetExists = $('.token[sentence=' + sentence + '][token=' + onset + ']').length;
    var offsetExists = $('.token[sentence=' + sentence + '][token=' + offset + ']').length;

    var annotation = selected.attr("annotation");

    if (onset <= offset && onsetExists && offsetExists)
        tokens = selected.parents(".sentence");
        var text = '';
        for (var id = onset; id <= offset; ++id) {
            var token = tokens.find('#token-' + id);
            if (id > onset)
                text += ' '.repeat(token.attr("whitespaces"));
            text += tokens.find('#token-' + id).text();
        }
        $.ajax({
            type: "POST",
            url: "ajax/updateAnnotation.php",
            data: {"onset": onset, "offset": offset, "id": selected.attr("annotationId"), "text": text || ""},
            success: function () {
                loadText(false);
                $(".annotation-label[annotation=" + annotation + "]").addClass("selected");
            }
        });
}

function growAnnotationLeft() {
    handleAnnotationGrowShrink(false, true, false);
}
function growAnnotationRight() {
    handleAnnotationGrowShrink(false, false, true);
}
function shrinkAnnotationLeft() {
    handleAnnotationGrowShrink(true, false, true);
}
function shrinkAnnotationRight() {
    handleAnnotationGrowShrink(true, true, false);
}
function addAnnotation() {
    if (window.getSelection) {
        var sel = window.getSelection();
        if (sel.rangeCount) {
            var startContainer = $(sel.getRangeAt(0).startContainer);
            var endContainer = $(sel.getRangeAt(sel.rangeCount - 1).endContainer);

            var sentence = startContainer.parents(".sentence");
            if (sentence[0] != endContainer.parents(".sentence")[0]) {
                alert("Cannot annotate across multiple sentences!");
                return;
            }
            var sentenceId = sentence.find('.line-number').text();

            var startToken = startContainer.parent(".token");
            if (startToken.length)
                var startTokenId = parseInt(startToken.attr("token"));
            else
                var startTokenId = parseInt(startContainer.parent(".whitespace").attr("token-right"));

            var endToken = endContainer.parent(".token");
            if (endToken.length)
                var endTokenId = parseInt(endToken.attr("token"));
            else
                var endTokenId = parseInt(endContainer.parent(".whitespace").attr("token-left"));

            if (!startTokenId || !endTokenId)
                return;
            var text = '';
            for (var id = startTokenId; id <= endTokenId; ++id) {
                var token = sentence.find('#token-' + id);
                if (id > startTokenId)
                    text += ' '.repeat(token.attr("whitespaces"));
                text += sentence.find('#token-' + id).text();
            }

            var index = null;
            var annotationClass = null;
            var insert = true;
            $(".selected").each(function () {
                var annotator = $(this).attr("annotator");
                var idx = $(this).attr("annotation");
                if (index && idx != index) {
                    insert = false;
                    return;
                }
                index = idx;
                annotationClass = $(this).attr("classId");
            });
            if (!insert) return;
            if (index && annotationClass) {
                $.ajax({
                    type: "POST",
                    url: "ajax/insertAnnotation.php",
                    data: {
                        "annotation": annotationClass,
                        "startToken": startTokenId,
                        "endToken": endTokenId,
                        "index": index,
                        "sentence": sentenceId,
                        "text": text
                    },
                    success: function () {
                        loadText();
                    }
                });
            } else {
                var dialog = $("#insertAnnotationDialog");
                dialog.find('#annotationText').val(text);
                dialog.attr('startToken', startTokenId);
                dialog.attr('endToken', endTokenId);
                dialog.attr('sentence', sentenceId);
                $(".token[sentence=" + sentenceId + "]").filter(function () {
                    return parseInt($(this).attr("token")) >= startTokenId && parseInt($(this).attr("token")) <= endTokenId;
                }).addClass("currentAnnotation");
                $(".whitespace[sentence=" + sentenceId + "]").filter(function () {
                    return parseInt($(this).attr("token-left")) >= startTokenId && parseInt($(this).attr("token-right")) <= endTokenId;
                }).addClass("currentAnnotation");
                dialog.dialog("open");
            }
        }
    }
}

function changeActiveAnnotation() {
    console.log("changeActiveAnnotation");
    var selectedMode = $('input[name=mode]:checked').val();
    var isCurationMode = selectedMode == 3;
    if (isCurationMode) {
        alert("Cannot change annotations in curation mode.\nPlease switch to annotator mode first.");
        return false;
    }
    var annotation = getSelectedAnnotation();
    var selectedAnnotation = $(".annotation-label[annotation=" + annotation + "]");
    var annotation_data = {
        "id": annotation,
        "classid": selectedAnnotation.attr("classid"),
        "onset": selectedAnnotation.attr("onset"),
        "offset": selectedAnnotation.attr("offset"),
        "sentence": selectedAnnotation.attr("sentence"),
        "index": selectedAnnotation.attr("index")
    };

    var affected_tokens = $(".token[sentence=" + annotation_data.sentence+ "]").filter(function () {
        return parseInt($(this).attr("token")) >= annotation_data.onset && parseInt($(this).attr("token")) <= annotation_data.offset;
    });
    affected_tokens.addClass("currentAnnotation");
    $(".whitespace[sentence=" + annotation_data.sentence + "]").filter(function () {
        return parseInt($(this).attr("token-left")) >= annotation_data.onset&& parseInt($(this).attr("token-right")) <= annotation_data.offset;
    }).addClass("currentAnnotation");

    var dialog = $("#insertAnnotationDialog");
    var text = affected_tokens.map(function() { return $(this).text(); }).get().join(" ")
    dialog.find('#annotationText').val(text);
    dialog.attr('startToken', annotation_data.onset);
    dialog.attr('endToken', annotation_data.offset);
    dialog.attr('sentence', annotation_data.sentence);
    dialog.on( "submit", function( event ) {
        $.ajax({
            type: "POST",
            url: "ajax/removeAnnotation.php",
            data: {"index": annotation}
        }).done(function(res) {
            console.log("res", res);
            if (!res) {
                return;
            }
            res = JSON.parse(res);

            var affectedSlots = false;
            if (res && res.dataids && res.dataids.length > 0) {
                affectedSlots = true;
            }

            if (affectedSlots) {
                alert("Warning: The annotation has been removed from previously assigned slots.");
            }
        });
    })
    dialog.dialog("open");
}

function deleteAnnotation(cb) {
    if (!cb) {
        // default callback: reload text
        cb = loadText;
    }
    var containsOwnUserAnnotations = false;
    
    var selectedMode = $('input[name=mode]:checked').val();
    var isCurationMode = selectedMode == 3;
    if (isCurationMode) {
        $(".selected").each(function() {
            var $this = $(this);
            if ($this.data("bycuruser") && $this.data("bycuruser") == '1' && !$this.attr("reference")) {
                containsOwnUserAnnotations = true;
            }
        });
    }

    if (containsOwnUserAnnotations) {
        alert("Annotations by the current user cannot be unaccepted.\nPlease remove them in the annotation mode.");
        return;
    }
    var ajax = [];
    $(".selected").each(function () {
        ajax.push($.ajax({
            type: "POST",
            url: "ajax/removeAnnotation.php",
            data: {"index": $(this).attr("annotation")}
        }));
    });
    $.when.apply($, ajax).done(cb);
}

function acceptAnnotation() {
    var ids = {};
    $(".selected").each(function () {
        ids[$(this).attr("annotation")] = true;
    });
    $.ajax({
        type: "POST",
        url: "ajax/changeAnnotator.php",
        data: {"annotationId": ids},
        success: function (value) {
            loadText();
        }
    });
}

$(document).ready(function () {
    modeChange();
    $("#toolbar").find("a").button({"ui-button": ""});
    $("#toolbar").find("a").removeClass("ui-corner-all");
    $("#toolbar").find("a").removeClass("ui-widget");
    $("#toolbar-kurator").find("a").button({"ui-button": ""});
    $("#toolbar-kurator").find("a").removeClass("ui-corner-all");
    $("#toolbar-kurator").find("a").removeClass("ui-widget");
    $("input[type=checkbox]").checkboxradio();
    $("#toolbar").find("label.ui-checkboxradio-label").removeClass("ui-corner-all").removeClass("ui-widget");
    $("#toolbar-kurator").find("label.ui-checkboxradio-label").removeClass("ui-corner-all").removeClass("ui-widget");
    $("#toolbar-slotfilling").find("label.ui-checkboxradio-label").removeClass("ui-corner-all").removeClass("ui-widget");
    var ctrlPressed = false;

    $.contextMenu.types.annotation = function(item, opt, root) {
        // console.log(item, opt, root);
        // this === item.$node

        $('<span>'+item.customName+'<ul>')
            .appendTo(this)
            .on('mouseenter', function () {
                var item = $("#context-menu").find(":nth-child("+(parseInt($(this).parent().index())+1)+")");
                $(".annotation-label[annotation="+item.attr("annotation")+"]").addClass("annotation-label-hover");
            })
            .on('mouseleave', function () {
                var item = $("#context-menu").find(":nth-child("+(parseInt($(this).parent().index())+1)+")");
                $(".annotation-label[annotation="+item.attr("annotation")+"]").removeClass("annotation-label-hover");
            })
            .on('click', function() {
                var item = $("#context-menu").find(":nth-child("+(parseInt($(this).parent().index())+1)+")");
                var annotationId = item.attr("annotation");
                var classId = item.attr("classId");
                if (ctrlPressed) {
                    var target = $(".annotation-label[annotation="+annotationId+"]");
                    var content = $("#content");
                    content.animate({
                        scrollTop: target.offset().top - content.offset().top + content.scrollTop()
                    });
                } else {
                    insertAnnotation(item.attr("data"), annotationId, classId);
                    root.$menu.trigger('contextmenu:hide');
                }
            });

        this.addClass('annotation').on('contextmenu:focus', function(e) {
            // setup some awesome stuff
        });
    };
    $.contextMenu({
        selector: '#context-menu',
        trigger: 'none',
        build: function ($trigger, e) {
            var items = {};
            var itemNumber = 0;
            $($trigger).children().each(function () {
                label = $(this).attr('label');
                var element = $(this).attr("elementId");
                var data = $(this).attr("data");
                if (typeof data !== typeof undefined && data !== false) {
                    if (typeof element !== typeof undefined && element !== false) {
                        items[itemNumber++] = {name: label, callback: function (key, options) {
                            var item = $("#context-menu").find(":nth-child("+(parseInt(key)+1)+")");
                            var dataId = item.attr('data');
                            var classId = item.attr('elementId');
                            $.ajax({
                                url: "ajax/updateClass.php",
                                data: {"dataId": dataId, "classId": classId, "overwrite": true},
                                type: "POST",
                                cache: false,
                                success: function() {
                                    var loader = $("li.tabs.active:visible").find("a.loader");
                                    if ($(".annotation-list:visible").find("tr[dataId="+dataId+"]").find("input").hasClass("topLevel"))
                                        loader.attr("onclick", "loadTab("+loader.closest("li.tabs").attr("tabId")+", "+classId+")").click();
                                    refresh();
                                }
                            });
                        }};
                        if ($(this).attr('root')) {
                            items[itemNumber - 1].className = 'hierarchy_rootclass';
                        }
                    } else {
                        items[itemNumber++] = {type: "annotation", customName: label};
                    }
                } else
                    items[itemNumber++] = {name: label, disabled: true};
            });
            return { items: items };
        }
    });

    $("#toolbar-grow-anno-left").on("click", growAnnotationLeft);
    $("#toolbar-grow-anno-right").on("click", growAnnotationRight);
    $("#toolbar-shrink-anno-left").on("click", shrinkAnnotationLeft);
    $("#toolbar-shrink-anno-right").on("click", shrinkAnnotationRight);
    $("#toolbar-add-anno").on("click", addAnnotation);
    $("#toolbar-delete-anno").on("click", deleteAnnotation);
    $("#toolbar-accept-anno").on("click", acceptAnnotation);
    $("#toolbar-unaccept-anno").on("click", deleteAnnotation);
    var selectAll = function selectAll() {
        var selectedMode = $('input[name=mode]:checked').val();
        var isCurationMode = selectedMode == 3;
        if (!isCurationMode) {
            $(".annotation-label").addClass("selected");
        } else {
            $(".annotation-label").removeClass("selected");

            $(".annotation-label").each(function() {
                var $this = $(this);
                if ($this.data("bycuruser") && $this.data("bycuruser") == '1') {
                    return;
                }
                $this.addClass("selected");
            })
        }
    }
    $("#toolbar-select-all").on("click", selectAll);
    $("#toolbar-deselect-all").on("click", function () {
        $(".annotation-label.selected").removeClass("selected");
    });

    $("body").keyup(function(event){
        if(event.which=="17")
            ctrlPressed = false;
    });
    $("body").keydown(function (event) {
        if(event.which=="17")
            ctrlPressed = true;
        var annotation = getSelectedAnnotation();
        var left = event.key == "ArrowLeft";
        var right = event.key == "ArrowRight";
        if (annotator()) {
            if (right && annotation && !event.ctrlKey) {
                var selectedAnnotation = $(".annotation-label[annotation=" + annotation + "]");
                var element;
                var content = $('#content');
                var index = parseInt(selectedAnnotation.attr("index"));
                if (event.key == "ArrowRight") {
                    var maxIndex = null;
                    $('.annotation-label').each(function () {
                        var value = parseInt($(this).attr('index'));
                        maxIndex = (value > maxIndex) ? value : maxIndex;
                    });
                    if (index < maxIndex) {
                        selectedAnnotation.removeClass("selected");
                        element = $(".annotation-label[index=" + (index + 1) + "]");
                        element.addClass("selected");
                        var scroll = content.height() / 2 - element.closest(".sentence").offset().top + content.offset().top;
                        console.log(scroll);
                        if (scroll < 0)
                            content.animate({scrollTop: '+=' + (-scroll) + 'px'}, 0);
                        else
                            content.animate({scrollTop: '-=' + scroll + 'px'}, 0);
                    }
                }
            } else if (left && annotation && !event.ctrlKey) {
                var selectedAnnotation = $(".annotation-label[annotation=" + annotation + "]");
                var element;
                var content = $('#content');
                var index = parseInt(selectedAnnotation.attr("index"));
                if (index > 0) {
                    selectedAnnotation.removeClass("selected");
                    element = $(".annotation-label[index=" + (index - 1) + "]");
                    element.addClass("selected");
                    var scroll = content.height() / 2 - element.closest(".sentence").offset().top + content.offset().top;
                    console.log(scroll);
                    if (scroll < 0)
                        content.animate({scrollTop: '+=' + (-scroll) + 'px'}, 0);
                    else
                        content.animate({scrollTop: '-=' + scroll + 'px'}, 0);
                }
            } else if (left || right && event.ctrlKey) {
                handleAnnotationGrowShrink(event.altKey, left, right);
            } else if (event.key == "Delete") {
                deleteAnnotation();
            } else if (event.key == "Enter") {
                addAnnotation();
            }
            if (event.key === "c") {
                changeActiveAnnotation();
            }
        } else if (kurator()) {
            if (event.key == "Delete") {
                deleteAnnotation();
            }
            if (event.ctrlKey && event.key === "a") {
                selectAll();
                event.preventDefault();
            } if (event.key == "Enter") {
                acceptAnnotation();
            } else if (left) {
                if ($(".selected").length == 0)
                    $(".annotation-label:first").addClass("selected");
                var annotation = $(".annotation-label.selected:first");
                var user = annotation.attr("annotator");
                var index = parseInt(annotation.attr("index"));
                if (event.ctrlKey && $(".selected").length > 1) {
                    $(".annotation-label.selected:last").removeClass("selected")
                } else if (index > 0) {
                    if (!event.ctrlKey)
                        $(".selected").removeClass("selected");
                    var element = $(".annotation-label").filter(function () {
                        return $(this).attr("annotator") === user;
                    }).filter(function () {
                        return $(this).attr("index") < index;
                    }).last();

                    element.addClass("selected");
                    var content = $('#content');
                    var scroll = content.height() / 2 - element.closest(".sentence").offset().top + content.offset().top;
                    console.log(scroll);
                    if (scroll < 0)
                        content.animate({scrollTop: '+=' + (-scroll) + 'px'}, 0);
                    else
                        content.animate({scrollTop: '-=' + scroll + 'px'}, 0);
                }
            } else if (right) {
                if ($(".selected").length == 0)
                    $(".annotation-label:first").addClass("selected");
                var annotation = $(".annotation-label.selected:last");
                var user = annotation.attr("annotator");
                var index = parseInt(annotation.attr("index"));
                if (index < $(".annotation-label:last").attr("index")) {
                    if (!event.ctrlKey)
                        $(".selected").removeClass("selected");
                    var element = $(".annotation-label").filter(function () {
                        return $(this).attr("annotator") === user;
                    }).filter(function () {
                        return $(this).attr("index") > index;
                    }).first();

                    element.addClass("selected");
                    var content = $('#content');
                    var scroll = content.height() / 2 - element.closest(".sentence").offset().top + content.offset().top;
                    console.log(scroll);
                    if (scroll < 0)
                        content.animate({scrollTop: '+=' + (-scroll) + 'px'}, 0);
                    else
                        content.animate({scrollTop: '-=' + scroll + 'px'}, 0);
                }
            }
        }
        if (annotator() || kurator()) {
            if (event.key == "Escape") {
                $(".selected").each(function () {
                    $(this).removeClass("selected");
                });
            }
        }

    });

    loadText();
    $.ajax({
        type: "GET",
        url: "ajax/getUsers.php",
        dataType: "json",
        success: function (result) {
            var select = $("#shownUsersSelect");
            for (var key in result) {
                select.append("<option value='"+result[key].Id+"'>"+result[key].Mail + (result[key].Ready ? " (finished)" : " (unfinished)") +"</option>");
            }
            select.chosen({
                no_results_text: "No user found",
                search_contains: true,
                display_selected_options: false
            }).change(function () {
                update_user_colors();
                loadText();
            });
            if (!kurator()) {
                $("#shownUsersSelect_chosen").hide();
            }
        }
    });
    $.ajax({
        type: "GET",
        url: "ajax/createListLayout.php",
        success: function (result) {
            $("#annotation-list").html(result);
            $("#annotation-accordion").accordion({
                collapsible: true,
                heightStyle: "fill",
                icons: {
                    header: "ui-icon-circle-arrow-e",
                    activeHeader: "ui-icon-circle-arrow-s"
                },
                active: true,
                beforeActivate: function(e, ui) {
                    ui.newPanel.load('ajax/createList.php', {"classId": ui.newPanel.attr("classId")});
                },
                activate: function (e, ui) {
                    ui.oldPanel.html("<p>loading...</p>");
                }
            });
        }
    });


    $('input[name=mode]').change(function() {
        $(".selected").each(function () {
            $(this).removeClass("selected");
        });
    });

    var dialog = $( "#dialog" );
    dialog.dialog({
        autoOpen: false,
        modal: true,
        show: {
            effect: "blind",
            duration: 100
        }
    }).find( "form" ).on( "submit", function( event ) {
        event.preventDefault();
        var dataId = dialog.attr("dataId");
        var name = dialog.find("input[name=name]").val();
        $.ajax({
            type: "POST",
            url: "ajax/renameTab.php",
            data: {"dataId": dataId, "name": name},
            success: function (value) {
                $("li.tabs[tabId="+dataId+"]").find(".loader").text(name);
            }
        });
        dialog.dialog("close");
    });



    var insertAnnotationDialog = $( "#insertAnnotationDialog" );
    var annotationSelect = insertAnnotationDialog.find('#annotation');
    insertAnnotationDialog.dialog({
        autoOpen: false,
        modal: true,
        show: {
            effect: "blind",
            duration: 100
        },
        close: function () { $(".currentAnnotation").removeClass("currentAnnotation"); }
    }).find( "form" ).on( "submit", function( event ) {
        event.preventDefault();
        var startToken = insertAnnotationDialog.attr('startToken');
        var endToken = insertAnnotationDialog.attr('endToken');
        var sentence = insertAnnotationDialog.attr('sentence');
        var index = insertAnnotationDialog.attr('index');
        var anno;
        $($(this).find("select").get().reverse()).each(function () {
            if (parseInt($(this).val()) !== -1) {
                anno = $(this).val();
                return false;
            }
        });

        $.ajax({
            type: "POST",
            url: "ajax/insertAnnotation.php",
            data: {"annotation": anno, "startToken": startToken, "endToken": endToken, "index": index, "sentence": sentence, "text": insertAnnotationDialog.find('#annotationText').val()},
            success: function () {
                loadText();
            }
        });
        insertAnnotationDialog.dialog("close");
    });
    annotationSelect.append("<option value='-1'></option>");
    $.ajax({
        type: "GET",
        url: "ajax/getAnnotations.php",
        success: function (result) {
            result.forEach(function (val) {
                if (val.Description)
                    annotationSelect.append("<option class='tooltip-able' title='"+val.Description+"' value='"+val.Id+"'>"+val.Name+"</option>");
                else
                    annotationSelect.append("<option value='"+val.Id+"'>"+val.Name+"</option>");
            });
            annotationSelect.chosen({
                width: "270px",
                no_results_text: "No annotation found:",
                search_contains: true
            });
            function onChange(evt, params) {
                $(evt.target).nextAll("select").remove();
                $(evt.target).next().nextAll("div").remove();
                $.ajax({
                    type: "POST",
                    url: "ajax/getAnnotations.php",
                    data: {superclass: params.selected},
                    success: function (result) {
                        console.log("result",result);
                        if (Object.keys(result).length <= 1)
                            return;
                        var newContainer = insertAnnotationDialog.find("div.chosen-container:last").after(
                            "<select name='annotation' data-placeholder='Select subclass or annotation'></select>").next();
                        var first = true;
                        newContainer.append("<option value='-1'></option>");
                        console.log(result);
                        $.each(result, function (key, val) {
                            if (key === params.selected) {
                                first = false;
                                return;
                            }
                            if (val[1])
                                newContainer.append("<option class='tooltip-able' title='"+val[1]+"' value='"+val[2]+"'>"+val[0]+"</option>");
                            else
                                newContainer.append("<option value='"+val[2]+"'>"+val[0]+"</option>");
                        });
                        newContainer.chosen({
                            width: "270px",
                            no_results_text: "No annotation found:",
                            search_contains: true
                        });
                        newContainer.next().contextmenu(function (evt) {
                            evt.preventDefault();
                            newContainer.nextAll("select").remove();
                            newContainer.next().nextAll("div").remove();
                            newContainer.val('').trigger("chosen:updated");
                        });
                        newContainer.on("change", onChange);
                        newContainer.trigger("chosen:activate");
                        newContainer.trigger("chosen:open");
                    }
                });
            }
            annotationSelect.on("change", onChange);
            annotationSelect.next().contextmenu(function (evt) {
                evt.preventDefault();
                annotationSelect.nextAll("select").remove();
                annotationSelect.next().nextAll("div").remove();
                annotationSelect.val('').trigger("chosen:updated");
            });

            annotationSelect.trigger("chosen:activate");
        }
    });

    $("input[type=radio][name=mode]").on("change", modeChange);
    $('#DocumentReady').change(function() {
        var checked = this.checked;
        $.ajax({
            type: "POST",
            url: "ajax/changeDocumentReadyStatus.php",
            data: {"ready": checked ? 1 : 0},
            success: function () {
                var doc = $('#document').find("option:selected");
                if (doc.attr("finishable")) {
                    doc.attr("ready", checked ? 1 : 0);
                    console.log(doc.attr("text") + (checked ? " (finished)" : " (unfinished)"));
                    doc.text(doc.attr("text") + (checked ? " (finished)" : " (unfinished)"))
                }
            }
        });
    });
    $('#DocumentReadyCuration').change(function() {
        var checked = this.checked;
        $.ajax({
            type: "POST",
            url: "ajax/changeDocumentReadyCurationStatus.php",
            data: {"ready": checked ? 1 : 0},
            success: function () {
                var doc = $('#document').find("option:selected");
                if (doc.attr("finishable")) {
                    doc.attr("ready", checked ? 1 : 0);
                    console.log(doc.attr("text") + (checked ? " (curated)" : ""));
                    doc.text(doc.attr("text") + (checked ? " (curated)" : ""))
                }
            }
        });
    });
    $('#DocumentReadySlotFilling').change(function() {
        var checked = this.checked;
        $.ajax({
            type: "POST",
            url: "ajax/changeDocumentReadySlotFillingStatus.php",
            data: {"ready": checked ? 1 : 0},
            success: function () {
                var doc = $('#document').find("option:selected");
                if (doc.attr("finishable")) {
                    doc.attr("ready", checked ? 1 : 0);
                    console.log(doc.attr("text") + (checked ? " (slots filled)" : ""));
                    doc.text(doc.attr("text") + (checked ? " (slots filled)" : ""))
                }
            }
        });
    });
});

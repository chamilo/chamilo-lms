<script>
{% if constant('CHAMILO_LOAD_WYSIWYG') %}
    // External plugins not part of the default Ckeditor package.
    var plugins = [
        'asciimath',
        'asciisvg',
        'audio',
        'ckeditor_wiris',
        'dialogui',
        'glossary',
        'leaflet',
        'mapping',
        'maximize',
        'mathjax',
        'oembed',
        'toolbar',
        'toolbarswitch',
        'video',
        'wikilink',
        'wordcount',
        'youtube',
        'flash'
    ];

    plugins.forEach(function(plugin) {
        CKEDITOR.plugins.addExternal(plugin, '{{ _p.web_main ~ 'inc/lib/javascript/ckeditor/plugins/' }}' + plugin + '/');
    });

    /**
     * Function use to load templates in a div
     **/
    var showTemplates = function (ckeditorName) {
        var editorName = 'content';
        if (ckeditorName && ckeditorName.length > 0) {
            editorName = ckeditorName;
        }
        CKEDITOR.editorConfig(CKEDITOR.config);
        CKEDITOR.loadTemplates(CKEDITOR.config.templates_files, function (a) {
            var templatesConfig = CKEDITOR.getTemplates("default");
            var $templatesUL = $("<ul>");
            if (templatesConfig) {
                $.each(templatesConfig.templates, function () {
                    var template = this;
                    var $templateLi = $("<li>");
                    var templateHTML = "<img src=\"" + templatesConfig.imagesPath + template.image + "\" ><div>";
                    templateHTML += "<b>" + template.title + "</b>";

                    if (template.description) {
                        templateHTML += "<div class=description>" + template.description + "</div>";
                    }
                    templateHTML += "</div>";

                    $("<a>", {
                        href: "#",
                        html: templateHTML,
                        click: function (e) {
                            e.preventDefault();
                            if (CKEDITOR.instances[editorName]) {
                                CKEDITOR.instances[editorName].setData(template.html, function () {
                                    this.checkDirty();
                                });
                            }
                        }
                    }).appendTo($templateLi);
                    $templatesUL.append($templateLi);
                });
            }
            $templatesUL.appendTo("#frmModel");
        });
    };
{% endif %}


function doneResizing() {
    var widhtWindow = $(window).width();
    if ((widhtWindow>=1024) && (widhtWindow>=768)) {
        $("#profileCollapse").addClass("in");
        $("#courseCollapse").addClass("in");
        $("#skillsCollapse").addClass("in");
        $("#sn-sidebar-collapse").addClass("in");
        $("#user_image_block").removeClass("text-muted");
    } else {
        $("#profileCollapse").removeClass("in");
        $("#courseCollapse").removeClass("in");
        $("#skillsCollapse").removeClass("in");
        $("#sn-avatar-one").removeClass("in");
        $("#user_image_block").addClass("text-muted");
    }
};

$(document).ready(function() {
    $("#open-view-list").click(function(){
        $("#student-list-work").fadeIn(300);
    });
    $("#closed-view-list").click(function(){
        $("#student-list-work").fadeOut(300);
    });
    check_brand();
    var id;
    $(window).resize(function() {
        clearTimeout(id);
        id = setTimeout(doneResizing, 200);
    });

    // Removes the yellow input in Chrome
    if (navigator.userAgent.toLowerCase().indexOf("chrome") >= 0) {
        $(window).load(function(){
            $('input:-webkit-autofill').each(function(){
                var text = $(this).val();
                var name = $(this).attr('name');
                $(this).after(this.outerHTML).remove();
                $('input[name=' + name + ']').val(text);
            });
        });
    }

    $(".accordion_jquery").accordion({
        autoHeight: false,
        active: false, // all items closed by default
        collapsible: true,
        header: ".accordion-heading"
    });

    // Global popup
    $('body').on('click', 'a.ajax', function(e) {
        e.preventDefault();

        var contentUrl = this.href,
                loadModalContent = $.get(contentUrl),
                self = $(this);

        $.when(loadModalContent).done(function(modalContent) {
            var modalDialog = $('#global-modal').find('.modal-dialog'),
                    modalSize = self.data('size') || get_url_params(contentUrl, 'modal_size'),
                    modalWidth = self.data('width') || get_url_params(contentUrl, 'width'),
                    modalTitle = self.data('title') || ' ';

            modalDialog.removeClass('modal-lg modal-sm').css('width', '');

            if (modalSize) {
                switch (modalSize) {
                    case 'lg':
                        modalDialog.addClass('modal-lg');
                        break;
                    case 'sm':
                        modalDialog.addClass('modal-sm');
                        break;
                }
            } else if (modalWidth) {
                modalDialog.css('width', modalWidth + 'px');
            }

            $('#global-modal').find('.modal-title').text(modalTitle);
            $('#global-modal').find('.modal-body').html(modalContent);
            $('#global-modal').modal('show');
        });
    });

    $('a.expand-image').on('click', function(e) {
        e.preventDefault();
        var title = $(this).attr('title');
        var image = new Image();
        image.onload = function() {
            if (title) {
                $('#expand-image-modal').find('.modal-title').text(title);
            } else {
                $('#expand-image-modal').find('.modal-title').html('&nbsp;');
            }

            $('#expand-image-modal').find('.modal-body').html(image);
            $('#expand-image-modal').modal({
                show: true
            });
        };
        image.src = this.href;
    });

    // Global confirmation
    $('.popup-confirmation').on('click', function() {
        showConfirmationPopup(this);
        return false;
    });

    // old jquery.menu.js
    $('#navigation a').stop().animate({
        'marginLeft':'50px'
    },1000);

    $('#navigation div').hover(
        function () {
            $('a',$(this)).stop().animate({
                'marginLeft':'1px'
            },200);
        },
        function () {
            $('a',$(this)).stop().animate({
                'marginLeft':'50px'
            },200);
        }
    );

    /* Make responsive image maps */
    $('map').imageMapResize();

    jQuery.fn.filterByText = function(textbox) {
        return this.each(function() {
            var select = this;
            var options = [];
            $(select).find('option').each(function() {
                options.push({value: $(this).val(), text: $(this).text()});
            });
            $(select).data('options', options);

            $(textbox).bind('change keyup', function() {
                var options = $(select).empty().data('options');
                var search = $.trim($(this).val());
                var regex = new RegExp(search,"gi");

                $.each(options, function(i) {
                    var option = options[i];
                    if(option.text.match(regex) !== null) {
                        $(select).append(
                                $('<option>').text(option.text).val(option.value)
                        );
                    }
                });
            });
        });
    };
    $(".black-shadow").mouseenter(function() {
        $(this).addClass('hovered-course');
    }).mouseleave(function() {
         $(this).removeClass('hovered-course');
    });
});

$(window).resize(function() {
    check_brand();
});

$(document).scroll(function() {
    var valor = $('body').outerHeight() - 700;
    if ($(this).scrollTop() > 100) {
        $('.bottom_actions').addClass('bottom_actions_fixed');
    } else {
        $('.bottom_actions').removeClass('bottom_actions_fixed');
    }

    if ($(this).scrollTop() > valor) {
        $('.bottom_actions').removeClass('bottom_actions_fixed');
    } else {
        $('.bottom_actions').addClass('bottom_actions_fixed');
    }

    //Exercise warning fixed at the top
    var fixed =  $("#exercise_clock_warning");
    if (fixed.length) {
        if (!fixed.attr('data-top')) {
            // If already fixed, then do nothing
            if (fixed.hasClass('subnav-fixed')) return;
            // Remember top position
            var offset = fixed.offset();
            fixed.attr('data-top', offset.top);
            fixed.css('width', '100%');
        }

        if (fixed.attr('data-top') - fixed.outerHeight() <= $(this).scrollTop()) {
            fixed.addClass('navbar-fixed-top');
            fixed.css('width', '100%');
        } else {
            fixed.removeClass('navbar-fixed-top');
            fixed.css('width', '100%');
        }
    }

    // Admin -> Settings toolbar.
    if ($('body').width() > 959) {
        if ($('.new_actions').length) {
            if (!$('.new_actions').attr('data-top')) {
                // If already fixed, then do nothing
                if ($('.new_actions').hasClass('new_actions-fixed')) return;
                // Remember top position
                var offset = $('.new_actions').offset();

                var more_top = 0;
                if ($('.subnav').hasClass('new_actions-fixed')) {
                    more_top = 50;
                }
                $('.new_actions').attr('data-top', offset.top + more_top);
            }
            // Check if the height is enough before fixing the icons menu (or otherwise removing it)
            // Added a 30px offset otherwise sometimes the menu plays ping-pong when scrolling to
            // the bottom of the page on short pages.
            if ($('.new_actions').attr('data-top') - $('.new_actions').outerHeight() <= $(this).scrollTop() + 30) {
                $('.new_actions').addClass('new_actions-fixed');
            } else {
                $('.new_actions').removeClass('new_actions-fixed');
            }
        }
    }
});

function get_url_params(q, attribute) {
    var vars;
    var hash;
    if (q != undefined) {
        q = q.split('&');
        for(var i = 0; i < q.length; i++){
            hash = q[i].split('=');
            if (hash[0] == attribute) {
                return hash[1];
            }
        }
    }
}

function check_brand() {
    if ($('.subnav').length) {
        if ($(window).width() >= 969) {
            $('.subnav .brand').hide();
        } else {
            $('.subnav .brand').show();
        }
    }
}

function showConfirmationPopup(obj, urlParam) {
    if (urlParam) {
        url = urlParam
    } else {
        url = obj.href;
    }

    var dialog  = $("#dialog");
    if ($("#dialog").length == 0) {
        dialog  = $('<div id="dialog" style="display:none">{{ "ConfirmYourChoice" | get_lang }} </div>').appendTo('body');
    }

    var width_value = 350;
    var height_value = 150;
    var resizable_value = true;

    var new_param = get_url_params(url, 'width');
    if (new_param) {
        width_value = new_param;
    }

    var new_param = get_url_params(url, 'height')
    if (new_param) {
        height_value = new_param;
    }

    var new_param = get_url_params(url, 'resizable');
    if (new_param) {
        resizable_value = new_param;
    }

    // Show dialog
    dialog.dialog({
        modal       : true,
        width       : width_value,
        height      : height_value,
        resizable   : resizable_value,
        buttons: [
            {
                text: '{{ 'Yes' | get_lang }}',
                click: function() {
                    window.location = url;
                },
                icons:{
                    primary:'ui-icon-locked'
                }
            },
            {
                text: '{{ 'No' | get_lang }}',
                click: function() { $(this).dialog("close"); },
                icons:{
                    primary:'ui-icon-locked'
                }
            }
        ]
    });
    // prevent the browser to follow the link
    return false;
}

function setCheckbox(value, table_id) {
    checkboxes = $("#"+table_id+" input:checkbox");
    $.each(checkboxes, function(index, checkbox) {
        checkbox.checked = value;
        if (value) {
            $(checkbox).parentsUntil("tr").parent().addClass("row_selected");
        } else {
            $(checkbox).parentsUntil("tr").parent().removeClass("row_selected");
        }
    });
    return false;
}

function action_click(element, table_id) {
    d = $("#"+table_id);
    if (!confirm('{{ "ConfirmYourChoice"|get_lang }}')) {
        return false;
    } else {
        var action =$(element).attr("data-action");
        $('#'+table_id+' input[name="action"] ').attr("value", action);
        d.submit();
        return false;
    }
}

/**
 * Generic function to replace the deprecated jQuery toggle function
 * @param inId          : id of block to hide / unhide
 * @param inIdTxt       : id of the button
 * @param inTxtHide     : text one of the button
 * @param inTxtUnhide   : text two of the button
 * @todo : allow to detect if text is from a button or from a <a>
 */
function hideUnhide(inId, inIdTxt, inTxtHide, inTxtUnhide)
{
    if ($('#'+inId).css("display") == "none") {
        $('#'+inId).show(400);
        $('#'+inIdTxt).attr("value", inTxtUnhide);
    } else {
        $('#'+inId).hide(400);
        $('#'+inIdTxt).attr("value", inTxtHide);
    }
}

function expandColumnToogle(buttonSelector, col1Info, col2Info)
{
    $(buttonSelector).on('click', function (e) {
        e.preventDefault();

        col1Info = $.extend({
            selector: '',
            width: 4
        }, col1Info);
        col2Info = $.extend({
            selector: '',
            width: 8
        }, col2Info);

        if (!col1Info.selector || !col2Info.selector) {
            return;
        }

        var col1 = $(col1Info.selector),
            col2 = $(col2Info.selector);

        $('#expand').toggleClass('hide');
        $('#contract').toggleClass('hide');

        if (col2.is('.col-md-' + col2Info.width)) {
            col2.removeClass('col-md-' + col2Info.width).addClass('col-md-12');
            col1.removeClass('col-md-' + col1Info.width).addClass('hide');

            return;
        }

        col2.removeClass('col-md-12').addClass('col-md-' + col2Info.width);
        col1.removeClass('hide').addClass('col-md-' + col1Info.width);
    });
}
</script>

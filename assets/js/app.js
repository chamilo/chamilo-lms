/* For licensing terms, see /license.txt */

// Load symfony routes in order to use it in a js
const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';

Routing.setRoutingData(routes);

require('./vendor');
require('./main');

// @todo rework url naming
const homePublicUrl = Routing.generate('home') + 'public/';
const legacyIndex = Routing.generate('legacy_index');
const mainUrl = Routing.generate('web.main');
const webAjax = Routing.generate('web.ajax');

/*console.log(homePublicUrl);
console.log(legacyIndex);
console.log(mainUrl);
console.log(webAjax);*/

var ajax_url = webAjax + 'chat.ajax.php';
var online_button = '<img src="' + homePublicUrl + 'img/statusonline.png">';
var offline_button = '<img src="' + homePublicUrl + 'img/statusoffline.png">';
/*var connect_lang = '{{ "ChatConnected"|get_lang }}';
var disconnect_lang = '{{ "ChatDisconnected"|get_lang }}';*/

var connect_lang = 'ChatConnected';
var disconnect_lang = 'ChatDisconnected';

$(function() {
    var webCidReq = '&cidReq=' + $('body').attr('data-course-code');
    window.webCidReq = webCidReq;

    $("#menu_courses").click(function(){
        return false;
    });
    $("#menu_social").click(function(){
        return false;
    });
    $("#menu_administrator").click(function(){
        return false;
    });

    var isInCourse = $("body").data("in-course");
    if (isInCourse == true) {
        var courseCode = $("body").data("course-code");
        var logOutUrl = webAjax + 'course.ajax.php?a=course_logout&cidReq=' + courseCode;
        function courseLogout() {
            $.ajax({
                async: false,
                url: logOutUrl,
                success: function (data) {
                    return 1;
                }
            });
        }
        addMainEvent(window, 'unload', courseLogout ,false);
    }
    $("#open-view-list").click(function(){
        $("#student-list-work").fadeIn(300);
    });
    $("#closed-view-list").click(function(){
        $("#student-list-work").fadeOut(300);
    });

    // Removes the yellow input in Chrome
    if (navigator.userAgent.toLowerCase().indexOf("chrome") >= 0) {
        $(window).on("load", function () {
            $('input:-webkit-autofill').each(function(){
                var text = $(this).val();
                var name = $(this).attr('name');
                $(this).after(this.outerHTML).remove();
                $('input[name=' + name + ']').val(text);
            });
        });
    }



    // Start modals
    // class='ajax' loads a page in a modal
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

            if (modalSize && modalSize.length != 0) {
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

    $('#global-modal').on('hidden.bs.modal', function () {
        jQuery(".embed-responsive").find('iframe').remove();
    });

    // Expands an image modal
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

    // Delete modal
    $('#confirm-delete').on('show.bs.modal', function(e) {
        $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
        //var message = '{{ 'AreYouSureToDeleteJS' | get_lang }}: <strong>' + $(e.relatedTarget).data('item-title') + '</strong>';
        var message = 'AreYouSureToDeleteJS : <strong>' + $(e.relatedTarget).data('item-title') + '</strong>';

        if ($(e.relatedTarget).data('item-question')) {
            message = $(e.relatedTarget).data('item-question');
        }

        $('.debug-url').html(message);
    });
    // End modals

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

    $("[data-toggle=popover]").each(function(i, obj) {
        $(this).popover({
            html: true,
            content: function() {
                var id = $(this).attr('id')
                return $('#popover-content-' + id).html();
            }
        });
    });

    $('.scrollbar-inner').scrollbar();

    var locale = $('html').attr('lang');
    // Date time settings.
    moment.locale(locale);
    $.datepicker.setDefaults($.datepicker.regional[locale]);
    $.datepicker.regional["local"] = $.datepicker.regional[locale];

    // Fix old calls of "inc/lib/mediaplayer/player.swf" and convert to <audio> tag, then rendered by media element js
    // see BT#13405
    $('embed').each( function () {
        var flashVars = $(this).attr('flashvars');
        if (flashVars && flashVars.indexOf("file") == -1) {
            var audioId = Math.floor( Math.random()*99999 );
            flashVars = flashVars.replace('&autostart=false', '');
            flashVars = flashVars.replace('&autostart=true', '');
            var audioDiv = '<audio id="'+audioId+'" controls="controls" style="width:400px;" width:"400px;" src="'+flashVars+'" ><source src="'+flashVars+'" type="audio/mp3"  ></source></audio>';
            $(this).hide();
            $(this).after(audioDiv);
        }
    });

    // Chosen select
    $(".chzn-select").chosen({
        disable_search_threshold: 10,
        /*no_results_text: '{{ 'SearchNoResultsFound' | get_lang | escape('js') }}',
        placeholder_text_multiple: '{{ 'SelectSomeOptions' | get_lang | escape('js') }}',
        placeholder_text_single: '{{ 'SelectAnOption' | get_lang | escape('js') }}',*/
        width: "100%"
    });

    // Bootstrap tabs.
    $('.tab_wrapper .nav a').on('click', function (e) {
        e.preventDefault()
        $(this).tab('show')
    });

    // Fixes bug when loading links inside a tab.
    $('.tab_wrapper .tab-pane a').unbind();

    /**
     * Advanced options
     * Usage
     * <a id="link" href="url">Advanced</a>
     * <div id="link_options">
     *     hidden content :)
     * </div>
     * */
    $(".advanced_options").on("click", function (event) {
        event.preventDefault();
        var id = $(this).attr('id') + '_options';

        $("#" + id).toggle();
        if($("#card-container").height()>700){
            $("#card-container").css("height","auto");
        } else {
            $("#card-container").css("height","100vh");
        }

        if($("#column-left").hasClass("col-md-12")){
            $("#column-left").removeClass('col-md-12');
            $("#column-right").removeClass('col-md-12');
            $("#column-right").addClass('col-md-4');
            $("#column-left").addClass('col-md-8');
        }else{
            $("#column-left").removeClass('col-md-8');
            $("#column-right").removeClass('col-md-4');
            $("#column-left").addClass('col-md-12');
            $("#column-right").addClass('col-md-12');
        };

        if($("#preview_course_add_course").length >= 0){
            $("#preview_course_add_course").toggle();
        }

    });

    /**
     * <a class="advanced_options_open" href="http://" rel="div_id">Open</a>
     * <a class="advanced_options_close" href="http://" rel="div_id">Close</a>
     * <div id="div_id">Div content</div>
     * */
    $(".advanced_options_open").on("click", function (event) {
        event.preventDefault();
        var id = $(this).attr('rel');
        $("#" + id).show();
    });

    $(".advanced_options_close").on("click", function (event) {
        event.preventDefault();
        var id = $(this).attr('rel');
        $("#" + id).hide();
    });

    // Adv multi-select search input.
    $('.select_class_filter').each( function () {
        var inputId = $(this).attr('id');
        inputId = inputId.replace('-filter', '');
        $("#" + inputId).filterByText($("#" + inputId + "-filter"));
    });

    // Mediaelement
    //if ( {{ show_media_element }} == 1) {
    //     $('video:not(.skip), audio:not(.skip)').mediaelementplayer({
    //         pluginPath: _p.web + 'web/assets/mediaelement/build/',
    //         //renderers: ['html5', 'flash_video', 'native_flv'],
    //         features: ['{{ video_features }}'],
    //         success: function(mediaElement, originalNode, instance) {
    //         },
    //         vrPath: _p.web + 'web/assets/vrview/build/vrview.js'
    //     });
    //}

    // Table highlight.
    $("form .data_table input:checkbox").click(function () {
        if ($(this).is(":checked")) {
            $(this).parentsUntil("tr").parent().addClass("row_selected");
        } else {
            $(this).parentsUntil("tr").parent().removeClass("row_selected");
        }
    });

    // Tool tip (in exercises)
    var tip_options = {
        placement: 'right'
    };
    $('.boot-tooltip').tooltip(tip_options);
    // var more = '{{ 'SeeMore' | get_lang | escape('js') }}';
    // var close = '{{ 'Close' | get_lang | escape('js') }}';

    var more = 'see more';
    var close = 'close';
    // readmore dont work with jquery3
    // $('.list-teachers').readmore({
    //     speed: 75,
    //     moreLink: '<a href="#">' + more + '</a>',
    //     lessLink: '<a href="#">' + close + '</a>',
    //     collapsedHeight: 35,
    //     blockCSS: 'display: block; width: 100%;'
    // });

    $('.star-rating li a').on('click', function(event) {
        var id = $(this).parents('ul').attr('id');
        //$('#vote_label2_' + id).html("{{'Loading'|get_lang}}");
        $('#vote_label2_' + id).html("loading");
        $.ajax({
            url: $(this).attr('data-link'),
            success: function(data) {
                $("#rating_wrapper_"+id).html(data);
                if (data == 'added') {
                    //$('#vote_label2_' + id).html("{{'Saved'|get_lang}}");
                }
                if (data == 'updated') {
                    //$('#vote_label2_' + id).html("{{'Saved'|get_lang}}");
                }
            }
        });
    });

    $("#notifications").load(webAjax + "online.ajax.php?a=get_users_online");
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

function setCheckbox(value, table_id) {
    var checkboxes = $("#"+table_id+" input:checkbox");
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
    var d = $("#"+table_id);
    if (!confirm('ConfirmYourChoice')) {
    //if (!confirm('{{ "ConfirmYourChoice"|get_lang }}')) {
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
function hideUnhide(inId, inIdTxt, inTxtHide, inTxtUnhide) {
    if ($('#'+inId).css("display") == "none") {
        $('#'+inId).show(400);
        $('#'+inIdTxt).attr("value", inTxtUnhide);
    } else {
        $('#'+inId).hide(400);
        $('#'+inIdTxt).attr("value", inTxtHide);
    }
}

function expandColumnToogle(buttonSelector, col1Info, col2Info) {
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

// Load ckeditor plugins
if (typeof CKEDITOR !== 'undefined') {
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
        'flash',
        'inserthtml',
        'image2_chamilo'
    ];

    plugins.forEach(function (plugin) {
        CKEDITOR.plugins.addExternal(
            plugin,
            mainUrl + 'inc/lib/javascript/ckeditor/plugins/' + plugin + '/'
        );
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
}

function addMainEvent(elm, evType, fn, useCapture)
{
    if (elm.addEventListener) {
        elm.addEventListener(evType, fn, useCapture);
        return true;
    } else if (elm.attachEvent) {
        elm.attachEvent('on' + evType, fn);
    } else {
        elm['on'+evType] = fn;
    }
}

function copyTextToClipBoard(elementId)
{
    /* Get the text field */
    var copyText = document.getElementById(elementId);

    /* Select the text field */
    copyText.select();

    /* Copy the text inside the text field */
    document.execCommand('copy');
}

// Expose functions to be use inside chamilo.
// @todo check if there's a better way to expose functions.
window.expandColumnToogle = expandColumnToogle;
window.get_url_params = get_url_params;
window.setCheckbox = setCheckbox;
window.action_click = action_click;
window.hideUnhide = hideUnhide;
window.addMainEvent = addMainEvent;
window.showTemplates = showTemplates;

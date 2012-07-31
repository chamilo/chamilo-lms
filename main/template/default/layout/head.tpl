<meta charset="{{ system_charset }}" />
<link href="http://www.chamilo.org/documentation.php" rel="help" />
<link href="http://www.chamilo.org/team.php" rel="author" />
<link href="http://www.chamilo.org" rel="copyright" />
{{ prefetch}}
{{ favico }}
<link rel="apple-touch-icon" href="{{ _p.web}}apple-touch-icon.png" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="Generator" content="{{ _s.software_name }} {{ _s.system_version|slice(0,1) }}" />
{#  Use the latest engine in ie8/ie9 or use google chrome engine if available  #}
{#  Improve usability in portal devices #}
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!--[if ie]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
<title>{{ title_string }}</title>
<style type="text/css" media="screen">
/*<![CDATA[*/
{{ css_style }}
/*]]>*/
</style>
<style type="text/css" media="print">
/*<![CDATA[*/
{{ css_style_print }}
/*]]>*/
</style>
<script>
//<![CDATA[
// This is a patch for the "__flash__removeCallback" bug, see FS#4378.
{% raw %}
if ((navigator.userAgent.toLowerCase().indexOf('msie') != -1 ) && ( navigator.userAgent.toLowerCase().indexOf('opera') == -1 )) {
    window.attachEvent( 'onunload', function() {
            window['__flash__removeCallback'] = function ( instance, name ) {
                try {
                    if ( instance ) {
                        instance[name] = null ;
                    }
                } catch ( flashEx ) {
                }
            } ;
    });
}
{% endraw %}
//]]>

/* Global chat variables */
var ajax_url        = '{{ _p.web_ajax }}chat.ajax.php';
var online_button   = '{{ online_button }}';
var offline_button  = '{{ offline_button }}';
var connect_lang    = '{{ "ChatConnected"|get_lang }}';
var disconnect_lang = '{{ "ChatDisconnected"|get_lang }}';
</script>

{{ js_file_to_string}}
{{ css_file_to_string}}
{{ extra_headers}}

<script>
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
        if ($(window).width() > 959) {
            $('.subnav .brand').hide();
        } else {
            $('.subnav .brand').show();
        }
    }
}


$(window).resize(function() {
    check_brand();
});

$(document).scroll(function() {

    // Top bar scroll effect
    if ($('body').width() > 959) {   
        if ($('.subnav').length) {
            if (!$('.subnav').attr('data-top')) {
                // If already fixed, then do nothing
                if ($('.subnav').hasClass('subnav-fixed')) return;
                // Remember top position
                var offset = $('.subnav').offset();
                $('.subnav').attr('data-top', offset.top);
            }

            if ($('.subnav').attr('data-top') - $('.subnav').outerHeight() <= $(this).scrollTop()) {
                $('.subnav').addClass('subnav-fixed');
            } else {
                $('.subnav').removeClass('subnav-fixed');
            }
            //$('.subnav .brand').show();
        }
    } else {
        //$('.subnav .brand').hide();
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
            fixed.addClass('subnav-fixed');
            fixed.css('width', '100%');
        } else {
            fixed.removeClass('subnav-fixed');
            fixed.css('width', '200px');
        }
    }

    //Admin -> Settings toolbar
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

            if ($('.new_actions').attr('data-top') - $('.new_actions').outerHeight() <= $(this).scrollTop()) {
                $('.new_actions').addClass('new_actions-fixed');
            } else {
                $('.new_actions').removeClass('new_actions-fixed');
            }
        }
    }

    //Bottom actions
    if ($('.bottom_actions').length) {
        if (!$('.bottom_actions').attr('data-top')) {
            // If already fixed, then do nothing
            if ($('.bottom_actions').hasClass('bottom_actions_fixed')) return;

            // Remember top position
            var offset = $('.bottom_actions').offset();
            $('.bottom_actions').attr('data-top', offset.top);
        }

        if ($('.bottom_actions').attr('data-top') > $('body').outerHeight()) {
            if ( ($('.bottom_actions').attr('data-top') - $('body').outerHeight() - $('.bottom_actions').outerHeight()) >= $(this).scrollTop()) {
                $('.bottom_actions').addClass('bottom_actions_fixed');
                $('.bottom_actions').css("width", "100%");
            } else {
                $('.bottom_actions').css("width", "");
                $('.bottom_actions').removeClass('bottom_actions_fixed');
            }
        } else {
            if ( ($('.bottom_actions').attr('data-top') -  $('.bottom_actions').outerHeight()) <= $(this).scrollTop()) {
                $('.bottom_actions').addClass('bottom_actions_fixed');
                $('.bottom_actions').css("width", "100%");
            } else {
                $('.bottom_actions').removeClass('bottom_actions_fixed');
                $('.bottom_actions').css("width", "");
            }
        }
    }
}); 
    
    
/*
function isScrolledIntoView(elem) {
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}*/


$(function() {
    
    check_brand();

    //Removes the yellow input in Chrome
    if (navigator.userAgent.toLowerCase().indexOf("chrome") >= 0) {
        $(window).load(function(){
            $('input:-webkit-autofill').each(function(){
                var text = $(this).val();
                var name = $(this).attr('name');
                console.log(name);
                $(this).after(this.outerHTML).remove();
                $('input[name=' + name + ']').val(text);
            });
        });
    }

    //Fixes buttons to the new btn class
    if (!$('#button').hasClass('btn')) {
        $("button").addClass('btn');
    }


    //Dropdown effect
    $('.dropdown-toggle').dropdown();

    //Responsive effect
    $(".collapse").collapse();

    //Global popup
    $('.ajax').on('click', function() {
            var url     = this.href;
            var dialog  = $("#dialog");
            if ($("#dialog").length == 0) {
                dialog  = $('<div id="dialog" style="display:none"></div>').appendTo('body');
            }

            width_value = 580;
            height_value = 450;
            resizable_value = true;

            new_param = get_url_params(url, 'width');
            if (new_param) {
                width_value = new_param;
            }

            new_param = get_url_params(url, 'height')
            if (new_param) {
                height_value = new_param;
            }

            new_param = get_url_params(url, 'resizable');
            if (new_param) {
                resizable_value = new_param;
            }

            // load remote content
            dialog.load(
                            url,
                            {},
                            function(responseText, textStatus, XMLHttpRequest) {
                                    dialog.dialog({
                                            modal       : true,
                                            width       : width_value,
                                            height      : height_value,
                                            resizable   : resizable_value
                                    });
            });
            //prevent the browser to follow the link
            return false;
    });

    //old jquery.menu.js
    $('#navigation a').stop().animate({
        'marginLeft':'50px'
    },1000);
    
    $('#navigation > li').hover(
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

    /*
    $(".td_actions").hide();

    $(".td_actions").parent('tr').mouseover(function() {
       $(".td_actions").show();
    });

    $(".td_actions").parent('tr').mouseout(function() {
        $(".td_actions").hide();
    });*/
});
</script>
{{ header_extra_content }}

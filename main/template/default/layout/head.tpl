<!--  head start -->
<link href="http://www.chamilo.org/documentation.php" rel="Help" />
<link href="http://www.chamilo.org/team.php" rel="Author" />
<link href="http://www.chamilo.org" rel="Copyright" />
<!-- <link rel="top"		href="{$_p.web_main}index.php" title="" />
<link rel="courses" href="{$_p.web_main}auth/courses.php" title="{"OtherCourses"|get_lang}"/>
<link rel="profil"  href="{$_p.web_main}auth/profile.php" title="{"ModifyProfile"|get_lang}"/> -->

<meta name="Generator" content="{$_s.software_name} {$_s.system_version|substr:0:1}" />
<meta charset="{$system_charset}" />
<meta http-equiv="content-language" content="{$document_language}">
		
<title>{$title_string}</title>

<style type="text/css" media="screen, projection">
	/*<![CDATA[*/
	{$css_style}
	/*]]>*/
</style>
<style type="text/css" media="print">
	/*<![CDATA[*/
	{$css_style_print}
	/*]]>*/
</style>
<script type="text/javascript">
//<![CDATA[
// This is a patch for the "__flash__removeCallback" bug, see FS#4378.
{literal}
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
{/literal}
//]]>

/* Global chat variables */
var ajax_url        = '{$_p.web_ajax}chat.ajax.php';
var online_button   = '{$online_button}';
var offline_button  = '{$offline_button}';
var	connect_lang    = '{"ChatConnected"|get_lang}';
var	disconnect_lang = '{"ChatDisconnected"|get_lang}';

</script>

{$js_file_to_string}
{$css_file_to_string}
{$extra_headers}
{$favico}

<script type="text/javascript">

$(document).scroll(function() {
    // If has not activated (has no attribute "data-top"

    if($('body').width() > 959) {
    if (!$('.subnav').attr('data-top')) {
        // If already fixed, then do nothing
        if ($('.subnav').hasClass('subnav-fixed')) return;
        // Remember top position
        var offset = $('.subnav').offset()
        $('.subnav').attr('data-top', offset.top);
    }

    if ($('.subnav').attr('data-top') - $('.subnav').outerHeight() <= $(this).scrollTop())
        $('.subnav').addClass('subnav-fixed');
    else
        $('.subnav').removeClass('subnav-fixed');
    }
});


$(document).ready(function() {       
	$('.dropdown-toggle').dropdown();   
    $(".collapse").collapse();
    
	$('.ajax').on('click', function() {
		var url     = this.href;
		var dialog  = $("#dialog");
		if ($("#dialog").length == 0) {
			dialog  = $('<div id="dialog" style="display:hidden"></div>').appendTo('body');
		}

		// load remote content
		dialog.load(
				url,                    
				{},
				function(responseText, textStatus, XMLHttpRequest) {
					dialog.dialog({
						modal	: true, 
						width	: 540, 
						height	: 400        
					});	                    
		});
		//prevent the browser to follow the link
		return false;
	});
});
</script>
{$header_extra_content}
<!--  head end-->
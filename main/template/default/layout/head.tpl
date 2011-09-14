<!--  head start -->
<link href="http://www.chamilo.org/documentation.php" rel="Help" />
<link href="http://www.chamilo.org/team.php" rel="Author" />
<link href="http://www.chamilo.org" rel="Copyright" />
<link rel="top" href="{$_p.web_main}index.php" title="" />
<link rel="courses" href="{$_p.web_main}auth/courses.php" title="{"OtherCourses"|get_lang}"/>
<link rel="profil" href="{$_p.web_main}auth/profile.php" title="{"ModifyProfile"|get_lang}"/>

<meta http-equiv="Content-Type" content="text/html; charset={$system_charset}" />
<meta name="Generator" content="{$_s.software_name} {$_s.system_version|substr:0:1}" />
		
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
{literal}
<script type="text/javascript">
//<![CDATA[
// This is a patch for the "__flash__removeCallback" bug, see FS#4378.
if ( ( navigator.userAgent.toLowerCase().indexOf('msie') != -1 ) && ( navigator.userAgent.toLowerCase().indexOf( 'opera' ) == -1 ) ) {
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
//]]>
</script>
{/literal}
{$js_file_to_string}
{$css_file_to_string}
{$extra_headers}
{$favico}

{$header_extra_content}
<!--  head end-->
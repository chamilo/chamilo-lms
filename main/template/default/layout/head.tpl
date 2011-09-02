<title>{$title_string}</title>

<style type="text/css" media="screen, projection">
	/*<![CDATA[*/
	{$css_style}
	?>
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
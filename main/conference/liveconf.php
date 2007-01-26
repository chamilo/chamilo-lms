<?php
/*
 * Created on 8 nov. 06
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 include("../inc/global.inc.php");
api_protect_course_script();
//$nameTool = get_lang('conference');
//Display::display_header($nameTool);

if($_GET['type'] == 'classroom')
{
	$visio_link = api_get_setting('service_visio','visioclassroom_url');
}
else if($_GET['type'] == 'conference')
{
	$visio_link = api_get_setting('service_visio','visioconference_url');
}


?>
<span align="center">
<iframe frameborder="0" scrolling="no" width="100%" height="100%" src="<?php echo $visio_link ?>"></iframe>
</span>
<?php 
//Display::display_footer();
?>

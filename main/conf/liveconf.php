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


$visio_link = api_get_path(WEB_PATH).api_get_setting('service_visio','url');

?>
<span align="center">
<iframe frameborder="0" scrolling="no" width="100%" height="600" src="<?php echo $visio_link ?>"></iframe>
</span>
<?php 
//Display::display_footer();
?>

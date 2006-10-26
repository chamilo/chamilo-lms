<?php
/*
 * Created on 25 oct. 06
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
$langFile='admin';

include('../inc/global.inc.php');

$nameTool = get_lang('Visioconf');
Display::display_header($nameTool);
?>
<div id="main_visio" align="center">
	<div id="extension_content_visio" style="display:block" class="accordion_content">		
		<?php echo get_lang('VisioconfDescription') ?><br /><br />
		<table width="100%">
			<tr>
				<td>
					<img src="<?php echo api_get_path(WEB_IMG_PATH).'screenshot_conf.jpg' ?>" />
				</td>
				<td align="center" width="50%" style="color: red">
					<?php echo get_lang('ExtensionActivedButNotYetOperational') ?>
				</td>
			</tr>
		</table>
	</div>
</div>

<?php

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>

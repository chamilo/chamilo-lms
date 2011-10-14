<?php
/* For licensing terms, see /license.txt */

// name of the language file that needs to be included
$language_file = array ('admin','courses', 'index');

// including necessary files
require_once 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'system_announcements.lib.php';

$tool_name = get_lang('SystemAnnouncements');
$htmlHeadXtra[] = api_get_jquery_libraries_js(array('bxslider'));
$htmlHeadXtra[] ='
<script type="text/javascript">
$(document).ready(function(){
	$("#slider").bxSlider({
		infiniteLoop	: true,
		auto			: true,
		pager			: true,
		autoHover		: true,
		pause			: 10000
	});
});
</script>';
Display::display_header($tool_name);

if (api_is_platform_admin()) {
	echo '<div class="actions">';
	echo '<a href="'.api_get_path(WEB_PATH).'main/admin/system_announcements.php">'.Display::return_icon('edit.png', get_lang('EditSystemAnnouncement'), array(), 32).'</a>';
	echo '</div>';
}

$start = isset($_GET['start']) ? (int)$_GET['start'] : $start = 0;
SystemAnnouncementManager ::display_announcements_slider($visibility);
Display::display_footer();
<?php
/* For licensing terms, see /chamilo_license.txt */

// name of the language file that needs to be included
$language_file= 'messages';
$cidReset= true;
require_once '../inc/global.inc.php';
api_block_anonymous_users();
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

if (isset($_GET['id_send']) || isset($_GET['id'])) {
	if (isset($_GET['rs'])) {
		$interbreadcrumb[]= array (
			'url' => '#',
			'name' => get_lang('Messages')
		);
		$interbreadcrumb[]= array (
				'url' => '../social/'.$_SESSION['social_dest'].'?#remote-tab-2',
				'name' => get_lang('SocialNetwork')
		);
		$interbreadcrumb[]= array (
			'url' => 'inbox.php',
			'name' => get_lang('Inbox')
		);
		$interbreadcrumb[]= array (
			'url' => 'outbox.php',
			'name' => get_lang('Outbox')
		);
	} else {
	$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang('Messages')
	);
	$interbreadcrumb[]= array (
		'url' => 'inbox.php',
		'name' => get_lang('Inbox')
	);
	$interbreadcrumb[]= array (
		'url' => 'outbox.php',
		'name' => get_lang('Outbox')
	);
	}
}
/*
==============================================================================
		HEADER
==============================================================================
*/
$request=api_is_xml_http_request();
if ($request===false) {
	Display::display_header('');
}
//api_display_tool_title(api_xml_http_response_encode(get_lang('ReadMessage')));
if (isset($_GET['id_send'])) {
	MessageManager::show_message_box_sent();
} else {
	MessageManager::show_message_box();
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
if ($request===false) {
	Display::display_footer();
}
?>
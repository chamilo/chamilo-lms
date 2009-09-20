<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * @todo can't this be moved to a different file so that we can delete this file?
 * 		 Is this still in use? If not, then it should be removed or maybe offered as an extension
 */
/**
==============================================================================
* Deletes the web-chat request form the user table
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'index';

// including necessary files
include_once './main/inc/global.inc.php';

// table definitions
$track_user_table = Database::get_main_table(TABLE_MAIN_USER);
if (isset($_user['user_id']) && $_user['user_id'] != '') {
	$_user['user_id'] = intval($_user['user_id']);
	$sql = "update $track_user_table set chatcall_user_id = '', chatcall_date = '', chatcall_text='DENIED' where (user_id = ".$_user['user_id'].")";
	$result = Database::query($sql, __FILE__, __LINE__);
}

Display::display_header();

$message = get_lang('RequestDenied').'<br /><br /><a href="javascript: history.back();">'.get_lang('Back').'</a>';
Display::display_normal_message($message);

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display::display_footer();

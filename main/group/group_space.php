<?php
/* For licensing terms, see /license.txt */

/**
 * This script shows the group space for one specific group, possibly displaying
 * a list of users in the group, subscribe or unsubscribe option, tutors...
 *
 * @package chamilo.group
 * @todo	Display error message if no group ID specified
 */

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = 'group';

require_once '../inc/global.inc.php';

/*	Libraries & config files */

require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
require_once api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php';

/*	MAIN CODE */

$current_group = GroupManager :: get_group_properties($_SESSION['_gid']);
if (!is_array($current_group) ) {
	//display some error message
}
$this_section = SECTION_COURSES;
$nameTools = get_lang('GroupSpace');
$interbreadcrumb[] = array ('url' => 'group.php', 'name' => get_lang('Groups'));

/*	Ensure all private groups // Juan Carlos Raña Trabado */

$forums_of_groups = get_forums_of_group($current_group['id']);

$forum_state_public = 0;
if (is_array($forums_of_groups)) {
	foreach ($forums_of_groups as $key => $value) {
		if($value['forum_group_public_private'] == 'public') {
			$forum_state_public = 1;
		}
	}
}

if ($current_group['doc_state'] != 1 && $current_group['calendar_state'] != 1 && $current_group['work_state'] != 1 && $current_group['announcements_state'] != 1 && $current_group['wiki_state'] != 1 && $current_group['chat_state'] != 1 && $forum_state_public != 1) {
	if (!api_is_allowed_to_edit(null,true) && !GroupManager :: is_user_in_group($_user['user_id'], $current_group['id'])) {
		echo api_not_allowed($print_headers);
	}
}

/*	Header */

Display::display_header($nameTools.' '.stripslashes($current_group['name']), 'Group');

/*	Introduction section (editable by course admin) */

Display::display_introduction_section(group_space_.$_SESSION['_gid']);

/*	Actions and Action links */

/*
 * User wants to register in this group
 */
if (!empty($_GET['selfReg']) && GroupManager :: is_self_registration_allowed($_SESSION['_user']['user_id'], $current_group['id'])) {
	GroupManager :: subscribe_users($_SESSION['_user']['user_id'], $current_group['id']);
	Display :: display_normal_message(get_lang('GroupNowMember'));
}

/*
 * User wants to unregister from this group
 */
if (!empty($_GET['selfUnReg']) && GroupManager :: is_self_unregistration_allowed($_SESSION['_user']['user_id'], $current_group['id'])) {
	GroupManager :: unsubscribe_users($_SESSION['_user']['user_id'], $current_group['id']);
	Display::display_normal_message(get_lang('StudentDeletesHimself'));
}
echo '<div class="actions">';
echo '<a href="group.php">'.Display::return_icon('back.png',get_lang('BackToGroupList'),'','32').'</a>';
/*
 * Edit the group
 */
if (api_is_allowed_to_edit(false, true) or GroupManager :: is_tutor($_user['user_id'])) {
	$my_origin = isset($origin) ? $origin : '';
	echo '<a href="group_edit.php?origin=$my_origin">'.Display::return_icon('settings.png', get_lang('EditGroup'),'','32').'</a>';
}

/*
 * Register to group
 */
if (GroupManager :: is_self_registration_allowed($_SESSION['_user']['user_id'], $current_group['id'])) {
	echo '<a href="'.api_get_self().'?selfReg=1&amp;group_id='.$current_group['id'].'" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."'".')) return false;">'.Display::return_icon('groupadd.gif').get_lang("RegIntoGroup").'</a>';
}

/*
 * Unregister from group
 */
if (GroupManager :: is_self_unregistration_allowed($_SESSION['_user']['user_id'], $current_group['id'])) {
	echo '<a href="'.api_get_self().'?selfUnReg=1" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."'".')) return false;">'.Display::return_icon('group_delete.gif').get_lang("StudentUnsubscribe").'</a>';
}
echo '&nbsp;</div>';

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'show_msg':
			Display::display_normal_message(Security::remove_XSS($_GET['msg']));
			break;
	}
}

/*	Main Display Area */

$course_code = $_course['sysCode'];
$is_course_member = CourseManager :: is_user_subscribed_in_real_or_linked_course($_SESSION['_user']['user_id'], $course_code);

/*
 * Group title and comment
 */
//api_display_tool_title($nameTools.' '.stripslashes($current_group['name']));

if (!empty($current_group['description'])) {
	echo '<blockquote>'.stripslashes($current_group['description']).'</blockquote>';
}

/*
 * Group Tools
 */
// If the user is subscribed to the group or the user is a tutor of the group then
if (api_is_allowed_to_edit(false, true) OR GroupManager :: is_user_in_group($_SESSION['_user']['user_id'], $current_group['id'])) {
	echo '<ul>';
	$tools = '';
	// Link to the forum of this group
	$forums_of_groups = get_forums_of_group($current_group['id']);
	if (is_array($forums_of_groups)) {
		if ($current_group['forum_state'] != TOOL_NOT_AVAILABLE ) {
			foreach ($forums_of_groups as $key => $value) {
				if ($value['forum_group_public_private'] == 'public' || (/*!empty($user_subscribe_to_current_group) && */ $value['forum_group_public_private'] == 'private') || !empty($user_is_tutor) || api_is_allowed_to_edit(false, true)) {
					$tools .= '<li style="display:inline; margin:5px;"><a href="../forum/viewforum.php?forum='.$value['forum_id'].'&gidReq='.Security::remove_XSS($current_group['id']).'&amp;origin=group">'.Display::return_icon('forum.png', get_lang('Forum').': '.$value['forum_title'] , array(), 32).'</a></li>';
				}
			}
		}
	}
	if ($current_group['doc_state'] != TOOL_NOT_AVAILABLE ) {
		// Link to the documents area of this group
		$tools .= '<li style="display:inline; margin:5px;" ><a href="../document/document.php?'.api_get_cidreq().'&amp;gidReq='.$current_group['id'].'">'.Display::return_icon('folder.png', get_lang('GroupDocument'), array(), 32).'</a></li>';
	}
	if ($current_group['calendar_state'] != TOOL_NOT_AVAILABLE) {
		// Link to a group-specific part of agenda
		$tools .= '<li style="display:inline; margin:5px;"><a href="../calendar/agenda.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'&amp;group='.$current_group['id'].'&amp;acces=0">'.Display::return_icon('agenda.png', get_lang('GroupCalendar'), array(), 32).'</a></li>';
	}
	if ($current_group['work_state'] != TOOL_NOT_AVAILABLE) {
		// Link to the works area of this group
		$tools .= '<li style="display:inline; margin:5px;" ><a href="../work/work.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'">'.Display::return_icon('work.png', get_lang('GroupWork'), array(), 32).'</a></li>';
	}
	if ($current_group['announcements_state'] != TOOL_NOT_AVAILABLE) {
		// Link to a group-specific part of announcements
		$tools .= '<li style="display:inline; margin:5px;"><a href="../announcements/announcements.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'">'.Display::return_icon('announce.png', get_lang('GroupAnnouncements'), array(), 32).'</a></li>';
	}
	if ($current_group['wiki_state'] != TOOL_NOT_AVAILABLE) {
		// Link to the wiki area of this group
		$tools .= '<li style="display:inline; margin:5px;"><a href="../wiki/index.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'&amp;action=show&amp;title=index&amp;session_id='.api_get_session_id().'&amp;group_id='.$current_group['id'].'">'.Display::return_icon('wiki.png', get_lang('GroupWiki'), array(), 32).'</a></li>';
	}
	if ($current_group['chat_state'] != TOOL_NOT_AVAILABLE) {
		// Link to the chat area of this group
		if (api_get_course_setting('allow_open_chat_window')) {
			$tools .= "<li style=\"display:inline; margin:5px;\"><a href=\"javascript: void(0);\" onclick=\"window.open('../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."','window_chat_group_".$_SESSION['_cid']."_".$_SESSION['_gid']."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no') \" >".Display::return_icon('chat.png', get_lang('Chat'),'',32)."</a></li>";
		} else {
			$tools .= "<li style=\"display:inline; margin:5px;\"><a href=\"../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."\">".Display::return_icon('chat.png', get_lang('Chat'), array(), 32)."</a></li>";
		}
	}
echo '</ul>';
	echo '<div class="actions-message" style="margin-bottom:4px;"><b>'.get_lang('Tools').'</b></div>';
	if (!empty($tools)) {
		echo '<div style="margin-left:5px; margin-bottom:4px; margin-top:4px;">'.$tools.'</div>';
	}

} else {
	echo '<ul>';
	
	$tools = '';
	// Link to the forum of this group
	$forums_of_groups = get_forums_of_group($current_group['id']);
	if (is_array($forums_of_groups)) {
		if ( $current_group['forum_state'] == TOOL_PUBLIC ) {
			foreach ($forums_of_groups as $key => $value) {
				if ($value['forum_group_public_private'] == 'public' ) {
					$tools.= '<li style="display:inline; margin:5px;"><a href="../forum/viewforum.php?forum='.$value['forum_id'].'&gidReq='.Security::remove_XSS($current_group['id']).'&amp;origin=group">'.Display::return_icon('forum.png', get_lang('GroupForum'), array(), 32).'</a></li>';
				}
			}
		}
	}
	if ($current_group['doc_state'] == TOOL_PUBLIC) {
		// Link to the documents area of this group
		$tools .= '<li style="display:inline; margin:5px;"><a href="../document/document.php?'.api_get_cidreq().'&amp;gidReq='.$current_group['id'].'&amp;origin='.$origin.'">'.Display::return_icon('folder.png', get_lang('GroupDocument'), array(), 32).'</a></li>';
	}
	if ($current_group['calendar_state'] == TOOL_PUBLIC) {
		// Link to a group-specific part of agenda
		$tools .= '<li style="display:inline; margin:5px;"><a href="../calendar/agenda.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'&amp;group='.$current_group['id'].'">'.Display::return_icon('agenda.png', get_lang('GroupCalendar'), array(), 32).'</a></li>';
	}
	if ($current_group['work_state'] == TOOL_PUBLIC) {
		// Link to the works area of this group
		$tools .= '<li style="display:inline; margin:5px;"><a href="../work/work.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'">'.Display::return_icon('work.png', get_lang('GroupWork'), array(), 32).'</a></li>';
	}
	if ($current_group['announcements_state'] == TOOL_PUBLIC) {
		// Link to a group-specific part of announcements
		$tools .= '<li style="display:inline; margin:5px;"><a href="../announcements/announcements.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'&amp;group='.$current_group['id'].'">'.Display::return_icon('announce.png', get_lang('GroupAnnouncements'), array(), 32).'</a></li>';
	}
	if ($current_group['wiki_state'] == TOOL_PUBLIC) {
		// Link to the wiki area of this group
		$tools .= '<li style="display:inline; margin:5px;"><a href="../wiki/index.php?'.api_get_cidreq().'&amp;toolgroup='.$current_group['id'].'&amp;action=show&amp;title=index&amp;session_id='.api_get_session_id().'&amp;group_id='.$current_group['id'].'">'.Display::return_icon('wiki.png', get_lang('GroupWiki'), array(), 32).'</a></li>';		
	}
	if ($current_group['chat_state'] == TOOL_PUBLIC ) {
		// Link to the chat area of this group
		if (api_get_course_setting('allow_open_chat_window')) {
			$tools .= "<li style=\"display:inline; margin:5px;\"><a href=\"javascript: void(0);\" onclick=\"window.open('../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."','window_chat_group_".$_SESSION['_cid']."_".$_SESSION['_gid']."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no') \" >".Display::return_icon('chat.png', get_lang("Chat"), array(), 32)."</a></li>";
		} else {
			$tools .= "<li style=\"display:inline; margin:5px;\"><a href=\"../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."\">".Display::return_icon('chat.png', get_lang("Chat"), array(), 32)."</a></li>";
		}
	}

	echo '</ul>';

	echo '<div class="actions-message" style="margin-bottom:4px;"><b>'.get_lang('Tools').'</b></div>';
	if (!empty($tools)) {
		echo '<div style="margin-left:5px; margin-bottom:4px; margin-top:4px;">'.$tools.'</div>';
	}
}

/*
 * List all the tutors of the current group
 */
$tutors = GroupManager::get_subscribed_tutors($current_group['id']);
$tutor_info = '';
if (count($tutors) == 0) {
	$tutor_info = get_lang('GroupNoneMasc');
} else {
	isset($origin)?$my_origin = $origin:$my_origin='';
	foreach($tutors as $index => $tutor) {
		$image_path = UserManager::get_user_picture_path_by_id($tutor['user_id'], 'web', false, true);
		$image_repository = $image_path['dir'];
		$existing_image = $image_path['file'];
		$photo= '<img src="'.$image_repository.$existing_image.'" align="absbottom" alt="'.api_get_person_name($tutor['firstname'], $tutor['lastname']).'" width="32" height="32" title="'.api_get_person_name($tutor['firstname'], $tutor['lastname']).'" />';
		$tutor_info .= '<div style="margin-bottom: 5px;"><a href="../user/userInfo.php?origin='.$my_origin.'&amp;uInfo='.$tutor['user_id'].'">'.$photo.'&nbsp;'.api_get_person_name($tutor['firstname'], $tutor['lastname']).'</a></div>';
	}
}

echo '<div class="actions-message" style="margin-bottom:4px;style="margin:4px;"><b>'.get_lang('GroupTutors').'</b></div>';
if (!empty($tutor_info)) {
	echo '<div style="margin-left:5px;">'.$tutor_info.'</div>';
}
echo '<br />';

/*
 * List all the members of the current group
 */
echo '<b>'.get_lang("GroupMembers").'</b>';

$table = new SortableTable('group_users', 'get_number_of_group_users', 'get_group_user_data', (api_is_western_name_order() xor api_sort_by_first_name()) ? 2 : 1);
$my_cidreq = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$my_origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';
$my_gidreq = isset($_GET['gidReq']) ? Security::remove_XSS($_GET['gidReq']) : '';
$parameters = array('cidReq' => $my_cidreq, 'origin'=> $my_origin, 'gidReq' => $my_gidreq);
$table->set_additional_parameters($parameters);
$table->set_header(0, '');
if (api_is_western_name_order()) {
	$table->set_header(1, get_lang('FirstName'));
	$table->set_header(2, get_lang('LastName'));
} else {
	$table->set_header(1, get_lang('LastName'));
	$table->set_header(2, get_lang('FirstName'));
}

if (api_get_setting('show_email_addresses') == 'true') {
	$table->set_header(3, get_lang('Email'));
	$table->set_column_filter(3, 'email_filter');
} else {
	if (api_is_allowed_to_edit() == 'true') {
		$table->set_header(3, get_lang('Email'));
		$table->set_column_filter(3, 'email_filter');
	}
}
//the order of these calls is important
$table->set_column_filter(1, 'user_name_filter');
$table->set_column_filter(2, 'user_name_filter');
$table->set_column_filter(0, 'user_icon_filter');
$table->display();

/**
 * Get the number of subscribed users to the group
 *
 * @return integer
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_number_of_group_users() {
	global $current_group;

	// Database table definition
	$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);

	// Query
	$sql = "SELECT count(id) AS number_of_users
				FROM ".$table_group_user."
				WHERE group_id='".Database::escape_string($current_group['id'])."'";
	$result = Database::query($sql);
	$return = Database::fetch_array($result,'ASSOC');
	return $return['number_of_users'];
}

/**
 * Get the details of the users in a group
 *
 * @param integer $from starting row
 * @param integer $number_of_items number of items to be displayed
 * @param integer $column sorting colum
 * @param integer $direction sorting direction
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_group_user_data($from, $number_of_items, $column, $direction) {
	global $current_group;

	// Database table definition
	$table_group_user 	= Database :: get_course_table(TABLE_GROUP_USER);
	$table_user 		= Database :: get_main_table(TABLE_MAIN_USER);

	// Query
	if (api_get_setting('show_email_addresses') == 'true') {
		$sql = "SELECT
					user.user_id 	AS col0,
				".(api_is_western_name_order() ?
				"user.firstname 	AS col1,
				user.lastname 	AS col2,"
				:
				"user.lastname 	AS col1,
				user.firstname 	AS col2,"
				)."
					user.email		AS col3
					FROM ".$table_user." user, ".$table_group_user." group_rel_user
					WHERE group_rel_user.user_id = user.user_id
					AND group_rel_user.group_id = '".Database::escape_string($current_group['id'])."'";
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
	} else {
		if (api_is_allowed_to_edit()) {
			$sql = "SELECT
						user.user_id 	AS col0,
						".(api_is_western_name_order() ?
						"user.firstname 	AS col1,
						user.lastname 	AS col2,"
						:
						"user.lastname 	AS col1,
						user.firstname 	AS col2,"
						)."
						user.email		AS col3
						FROM ".$table_user." user, ".$table_group_user." group_rel_user
						WHERE group_rel_user.user_id = user.user_id
						AND group_rel_user.group_id = '".Database::escape_string($current_group['id'])."'";
			$sql .= " ORDER BY col$column $direction ";
			$sql .= " LIMIT $from,$number_of_items";
		} else {
			$sql = "SELECT
						user.user_id 	AS col0,
						". (api_is_western_name_order() ?
						"user.firstname 	AS col1,
						user.lastname 	AS col2 "
						:
						"user.lastname 	AS col1,
						user.firstname 	AS col2 "
						)."
						FROM ".$table_user." user, ".$table_group_user." group_rel_user
						WHERE group_rel_user.user_id = user.user_id
						AND group_rel_user.group_id = '".Database::escape_string($current_group['id'])."'";
			$sql .= " ORDER BY col$column $direction ";
			$sql .= " LIMIT $from,$number_of_items";
		}
	}

	$return = array();
	$result = Database::query($sql);
	while ($row = Database::fetch_row($result)) {
		$return[] = $row;
	}
	return $return;
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function email_filter($email) {
	return Display :: encrypted_mailto_link($email, $email);
}

/**
 * Display a user icon that links to the user page
 *
 * @param integer $user_id the id of the user
 * @return html code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function user_icon_filter($user_id) {
	global $origin;
	$userinfo = Database::get_user_info_from_id($user_id);
	$image_path = UserManager::get_user_picture_path_by_id($user_id, 'web', false, true);
	$image_repository = $image_path['dir'];
	$existing_image = $image_path['file'];
	$photo = '<center><img src="'.$image_repository.$existing_image.'" alt="'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'"  width="22" height="22" title="'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'" /></center>';
	return '<a href="../user/userInfo.php?origin='.$origin.'&amp;uInfo='.$user_id.'">'.$photo;
}
/**
 * Return user profile link around the given user name.
 * 
 * The parameters use a trick of the sorteable table, where the first param is
 * the original value of the column 
 * @param   string  User name (value of the column at the time of calling)
 * @param   string  URL parameters
 * @param   array   Row of the "sortable table" as it is at the time of function call - we extract the user ID from there
 * @return  string  HTML link
 */
function user_name_filter($name, $url_params, $row) {
    global $origin;
	return '<a href="../user/userInfo.php?uInfo='.$row[0].'&amp;'.$url_params.'">'.$name.'</a>';
}

// Footer
$orig = isset($origin) ? $origin : '';
if ($orig != 'learnpath') {
	Display::display_footer();
}

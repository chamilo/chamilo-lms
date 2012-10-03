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
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

/*	Libraries & config files */

require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
require_once api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php';

/*	MAIN CODE */

$group_id = api_get_group_id();
$user_id = api_get_user_id();

$current_group = GroupManager :: get_group_properties($group_id);

if (empty($current_group)) {
	api_not_allowed();
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('GroupSpace');
$interbreadcrumb[] = array ('url' => 'group.php', 'name' => get_lang('Groups'));

/*	Ensure all private groups // Juan Carlos Raña Trabado */

$forums_of_groups = get_forums_of_group($current_group['id']);

$forum_state_public = 0;
if (is_array($forums_of_groups)) {
	foreach ($forums_of_groups as $key => $value) {
		if ($value['forum_group_public_private'] == 'public') {
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

Display::display_header($nameTools.' '.Security::remove_XSS($current_group['name']), 'Group');

/*	Introduction section (editable by course admin) */

Display::display_introduction_section(TOOL_GROUP);

/*	Actions and Action links */

/*
 * User wants to register in this group
 */
if (!empty($_GET['selfReg']) && GroupManager :: is_self_registration_allowed($user_id, $current_group['id'])) {
	GroupManager :: subscribe_users($user_id, $current_group['id']);
	Display :: display_normal_message(get_lang('GroupNowMember'));
}

/*
 * User wants to unregister from this group
 */
if (!empty($_GET['selfUnReg']) && GroupManager :: is_self_unregistration_allowed($user_id, $current_group['id'])) {
	GroupManager :: unsubscribe_users($user_id, $current_group['id']);
	Display::display_normal_message(get_lang('StudentDeletesHimself'));
}
echo '<div class="actions">';
echo '<a href="group.php">'.Display::return_icon('back.png',get_lang('BackToGroupList'),'',ICON_SIZE_MEDIUM).'</a>';

/*
 * Register to group
 */
$subscribe_group = '';
if (GroupManager :: is_self_registration_allowed($user_id, $current_group['id'])) {
	$subscribe_group = '<a class="btn" href="'.api_get_self().'?selfReg=1&amp;group_id='.$current_group['id'].'" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."'".')) return false;">'.get_lang("RegIntoGroup").'</a>';
}

/*
 * Unregister from group
 */
$unsubscribe_group = '';
if (GroupManager :: is_self_unregistration_allowed($user_id, $current_group['id'])) {
	$unsubscribe_group = '<a class="btn" href="'.api_get_self().'?selfUnReg=1" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."'".')) return false;">'.get_lang("StudentUnsubscribe").'</a>';
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
$is_course_member = CourseManager :: is_user_subscribed_in_real_or_linked_course(api_get_user_id(), $course_code);

 /*
 * Edit the group
 */
$edit_url = '';
if (api_is_allowed_to_edit(false, true) or GroupManager :: is_tutor_of_group(api_get_user_id(), api_get_group_id())) {
    $my_origin = isset($origin) ? $origin : '';
    $edit_url =  '<a href="group_edit.php?cidReq='.  api_get_course_id().'&origin='.$my_origin.'&gidReq='.api_get_group_id().'">'.Display::return_icon('edit.png', get_lang('EditGroup'),'',ICON_SIZE_SMALL).'</a>';
}

echo Display::page_header(Security::remove_XSS($current_group['name']).' '.$edit_url.' '.$subscribe_group.' '.$unsubscribe_group);

if (!empty($current_group['description'])) {
	echo '<p>'.Security::remove_XSS($current_group['description']).'</p>';
}

/*
 * Group Tools
 */
// If the user is subscribed to the group or the user is a tutor of the group then
if (api_is_allowed_to_edit(false, true) OR GroupManager :: is_user_in_group(api_get_user_id(), $current_group['id'])) {	
	$actions_array = array();
	// Link to the forum of this group
	$forums_of_groups = get_forums_of_group($current_group['id']);
    
	if (is_array($forums_of_groups)) {
		if ($current_group['forum_state'] != GroupManager::TOOL_NOT_AVAILABLE ) {
			foreach ($forums_of_groups as $key => $value) {
				if ($value['forum_group_public_private'] == 'public' || (/*!empty($user_subscribe_to_current_group) && */ $value['forum_group_public_private'] == 'private') || !empty($user_is_tutor) || api_is_allowed_to_edit(false, true)) {
                    $actions_array[] = array(
                        'url' => '../forum/viewforum.php?forum='.$value['forum_id'].'&gidReq='.Security::remove_XSS($current_group['id']).'&amp;origin=group',
                        'content' => Display::return_icon('forum.png', get_lang('Forum').': '.$value['forum_title'] , array(), 32)
                     );
				}
			}
		}
	}
	if ($current_group['doc_state'] != GroupManager::TOOL_NOT_AVAILABLE ) {
		// Link to the documents area of this group		
        $actions_array[] = array(
                        'url' => '../document/document.php?'.api_get_cidreq(),
                        'content' => Display::return_icon('folder.png', get_lang('GroupDocument'), array(), 32)
                     );
	}
	if ($current_group['calendar_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
		// Link to a group-specific part of agenda
        $actions_array[] = array(
                        'url' => '../calendar/agenda.php?'.api_get_cidreq(),
                        'content' => Display::return_icon('agenda.png', get_lang('GroupCalendar'), array(), 32)
        );
	}
	if ($current_group['work_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
		// Link to the works area of this group
		 $actions_array[] = array(
                        'url' => '../work/work.php?'.api_get_cidreq(),
                        'content' => Display::return_icon('work.png', get_lang('GroupWork'), array(), 32)
         );
         
	}
	if ($current_group['announcements_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
		// Link to a group-specific part of announcements
        $actions_array[] = array(
                        'url' => '../announcements/announcements.php?'.api_get_cidreq(),
                        'content' => Display::return_icon('announce.png', get_lang('GroupAnnouncements'), array(), 32)
        );
	}
    
	if ($current_group['wiki_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
		// Link to the wiki area of this group
        $actions_array[] = array(
                        'url' => '../wiki/index.php?'.api_get_cidreq().'&amp;action=show&amp;title=index&amp;session_id='.api_get_session_id().'&amp;group_id='.$current_group['id'],
                        'content' => Display::return_icon('wiki.png', get_lang('GroupWiki'), array(), 32)
        );		
	}
	if ($current_group['chat_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
		// Link to the chat area of this group
		if (api_get_course_setting('allow_open_chat_window')) {
            $actions_array[] = array(
                        'url' => "javascript: void(0);\" onclick=\"window.open('../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."','window_chat_group_".$_SESSION['_cid']."_".$_SESSION['_gid']."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no') \"",
                        'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32)
            );
		} else {
            $actions_array[] = array(
                        'url' => "../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id'],
                        'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32)
            );
		}
	}
    
	if (!empty($actions_array)) {
        echo Display::page_subheader(get_lang('Tools'));
		echo Display::actions($actions_array);
	}

} else {
	$actions_array = array();
    
	// Link to the forum of this group
	$forums_of_groups = get_forums_of_group($current_group['id']);
	if (is_array($forums_of_groups)) {
		if ( $current_group['forum_state'] == GroupManager::TOOL_PUBLIC ) {
			foreach ($forums_of_groups as $key => $value) {
				if ($value['forum_group_public_private'] == 'public' ) {					                    
                    $actions_array[] = array(
                        'url' => '../forum/viewforum.php?cidReq='.api_get_course_id().'&forum='.$value['forum_id'].'&gidReq='.Security::remove_XSS($current_group['id']).'&amp;origin=group',
                        'content' => Display::return_icon('forum.png', get_lang('GroupForum'), array(), ICON_SIZE_MEDIUM)
                    );                     
				}
			}
		}
	}
	if ($current_group['doc_state'] == GroupManager::TOOL_PUBLIC) {
		// Link to the documents area of this group
        $actions_array[] = array(
                        'url' => '../document/document.php?cidReq='.api_get_course_id().'&amp;origin='.$origin,
                        'content' => Display::return_icon('folder.png', get_lang('GroupDocument'), array(), ICON_SIZE_MEDIUM)
        );
	}
	if ($current_group['calendar_state'] == GroupManager::TOOL_PUBLIC) {
		// Link to a group-specific part of agenda
        $actions_array[] = array(
                        'url' => '../calendar/agenda.php?'.api_get_cidreq(),
                        'content' => Display::return_icon('agenda.png', get_lang('GroupCalendar'), array(), ICON_SIZE_MEDIUM)
        );
        
	}
	if ($current_group['work_state'] == GroupManager::TOOL_PUBLIC) {
		// Link to the works area of this group
		$actions_array[] = array(
                        'url' => '../work/work.php?'.api_get_cidreq(),
                        'content' => Display::return_icon('work.png', get_lang('GroupWork'), array(), ICON_SIZE_MEDIUM)
         );
	}
	if ($current_group['announcements_state'] == GroupManager::TOOL_PUBLIC) {
		// Link to a group-specific part of announcements
		$actions_array[] = array(
                        'url' => '../announcements/announcements.php?'.api_get_cidreq(),
                        'content' => Display::return_icon('announce.png', get_lang('GroupAnnouncements'), array(), ICON_SIZE_MEDIUM)
        );
	}
	if ($current_group['wiki_state'] == GroupManager::TOOL_PUBLIC) {
		// Link to the wiki area of this group
		$actions_array[] = array(
                        'url' => '../wiki/index.php?'.api_get_cidreq().'&amp;action=show&amp;title=index&amp;session_id='.api_get_session_id().'&amp;group_id='.$current_group['id'],
                        'content' => Display::return_icon('wiki.png', get_lang('GroupWiki'), array(), 32)
        );
	}
	if ($current_group['chat_state'] == GroupManager::TOOL_PUBLIC ) {
		// Link to the chat area of this group
		if (api_get_course_setting('allow_open_chat_window')) {
            $actions_array[] = array(
                        'url' => "javascript: void(0);\" onclick=\"window.open('../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."','window_chat_group_".$_SESSION['_cid']."_".$_SESSION['_gid']."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no') \"",
                        'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32)
            );
		} else {
            $actions_array[] = array(
                        'url' => "../chat/chat.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id'],
                        'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32)
            );
		}
	}
	if (!empty($actions_array)) {
        echo Display::page_subheader(get_lang('Tools'));
		echo Display::actions($actions_array);
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
	    $tab_user_info = Database::get_user_info_from_id($tutor['user_id']);
	    $username = api_htmlentities(sprintf(get_lang('LoginX'), $tab_user_info['username']), ENT_QUOTES);
		$image_path = UserManager::get_user_picture_path_by_id($tutor['user_id'], 'web', false, true);
		$image_repository = $image_path['dir'];
		$existing_image = $image_path['file'];
		$photo= '<img src="'.$image_repository.$existing_image.'" align="absbottom" alt="'.api_get_person_name($tutor['firstname'], $tutor['lastname']).'" width="32" height="32" title="'.api_get_person_name($tutor['firstname'], $tutor['lastname']).'" />';
		$tutor_info .= '<a href="../user/userInfo.php?origin='.$my_origin.'&amp;uInfo='.$tutor['user_id'].'">'.$photo.'&nbsp;'.Display::tag('span', api_get_person_name($tutor['firstname'], $tutor['lastname']), array('title'=>$username)).'</a>';
	}
}

echo Display::page_subheader(get_lang('GroupTutors'));
if (!empty($tutor_info)) {
	echo $tutor_info;
}
echo '<br />';

/*
 * List all the members of the current group
 */
echo Display::page_subheader(get_lang('GroupMembers'));

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
    $course_id = api_get_course_int_id();

	// Database table definition
	$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);

	// Query
	$sql = "SELECT count(id) AS number_of_users FROM ".$table_group_user."
				WHERE c_id = $course_id AND group_id='".Database::escape_string($current_group['id'])."'";
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
	
	$course_id = api_get_course_int_id();

	// Query
	if (api_get_setting('show_email_addresses') == 'true') {
		$sql = "SELECT user.user_id 	AS col0,
				".(api_is_western_name_order() ?
				"user.firstname 	AS col1,
				user.lastname 	AS col2,"
				:
				"user.lastname 	AS col1,
				user.firstname 	AS col2,"
				)."
				user.email		AS col3
				FROM ".$table_user." user, ".$table_group_user." group_rel_user
				WHERE group_rel_user.c_id = $course_id AND group_rel_user.user_id = user.user_id
				AND group_rel_user.group_id = '".Database::escape_string($current_group['id'])."'";
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
	} else {
		if (api_is_allowed_to_edit()) {
			$sql = "SELECT DISTINCT
						u.user_id 	AS col0,
						".(api_is_western_name_order() ?
						"u.firstname 	AS col1,
						u.lastname 	AS col2,"
						:
						"u.lastname 	AS col1,
						u.firstname 	AS col2,"
						)."
						u.email		AS col3
						FROM ".$table_user." u INNER JOIN ".$table_group_user." gu ON (gu.user_id = u.user_id) AND gu.c_id = $course_id
						WHERE gu.group_id = '".Database::escape_string($current_group['id'])."'";
			$sql .= " ORDER BY col$column $direction ";
			$sql .= " LIMIT $from,$number_of_items";            
		} else {
			$sql = "SELECT DISTINCT
						user.user_id 	AS col0,
						". (api_is_western_name_order() ?
						"user.firstname 	AS col1,
						user.lastname 	AS col2 "
						:
						"user.lastname 	AS col1,
						user.firstname 	AS col2 "
						)."
						FROM ".$table_user." user, ".$table_group_user." group_rel_user
						WHERE group_rel_user.c_id = $course_id AND  group_rel_user.user_id = user.user_id
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
    $tab_user_info = Database::get_user_info_from_id($row[0]);
    $username = api_htmlentities(sprintf(get_lang('LoginX'), $tab_user_info['username']), ENT_QUOTES);	
    return '<a href="../user/userInfo.php?uInfo='.$row[0].'&amp;'.$url_params.'" title="'.$username.'">'.$name.'</a>';
}

// Footer
$orig = isset($origin) ? $origin : '';
if ($orig != 'learnpath') {
	Display::display_footer();
}

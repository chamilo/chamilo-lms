<?php
/* For licensing terms, see /license.txt */

/**
 * This script shows the group space for one specific group, possibly displaying
 * a list of users in the group, subscribe or unsubscribe option, tutors...
 *
 * @package chamilo.group
 * @todo	Display error message if no group ID specified
 */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

/*	Libraries & config files */
require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
require_once api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php';

/*	MAIN CODE */
$group_id = api_get_group_id();
$user_id = api_get_user_id();
$current_group = GroupManager::get_group_properties($group_id);
if (empty($current_group)) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('GroupSpace');
$interbreadcrumb[] = array('url' => 'group.php?'.api_get_cidreq(), 'name' => get_lang('Groups'));

/*	Ensure all private groups // Juan Carlos Raña Trabado */

$forums_of_groups = get_forums_of_group($current_group);

if (!GroupManager::userHasAccessToBrowse($user_id, $current_group, api_get_session_id())) {
    api_not_allowed(true);
}

/*	Actions and Action links */

/*
 * User wants to register in this group
 */
if (!empty($_GET['selfReg']) &&
    GroupManager :: is_self_registration_allowed($user_id, $current_group)
) {
    GroupManager :: subscribe_users($user_id, $current_group);
    Display::addFlash(Display::return_message(get_lang('GroupNowMember'), 'normal'));
}

/*
 * User wants to unregister from this group
 */
if (!empty($_GET['selfUnReg']) &&
    GroupManager :: is_self_unregistration_allowed($user_id, $current_group)
) {
    GroupManager::unsubscribe_users($user_id, $current_group);
    Display::addFlash(Display::return_message(get_lang('StudentDeletesHimself'), 'normal'));
}

Display::display_header($nameTools.' '.Security::remove_XSS($current_group['name']), 'Group');

/*	Introduction section (editable by course admin) */
Display::display_introduction_section(TOOL_GROUP);

echo '<div class="actions">';
echo '<a href="group.php">'.
    Display::return_icon('back.png', get_lang('BackToGroupList'), '', ICON_SIZE_MEDIUM).
    '</a>';

/*
 * Register to group
 */
$subscribe_group = '';
if (GroupManager :: is_self_registration_allowed($user_id, $current_group)) {
    $subscribe_group = '<a class="btn btn-default" href="'.api_get_self().'?selfReg=1&group_id='.$current_group['id'].'" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."'".')) return false;">'.
        get_lang("RegIntoGroup").'</a>';
}

/*
 * Unregister from group
 */
$unsubscribe_group = '';
if (GroupManager :: is_self_unregistration_allowed($user_id, $current_group)) {
    $unsubscribe_group = '<a class="btn btn-default" href="'.api_get_self().'?selfUnReg=1" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."'".')) return false;">'.
        get_lang("StudentUnsubscribe").'</a>';
}
echo '&nbsp;</div>';

/*	Main Display Area */

$edit_url = '';
if (api_is_allowed_to_edit(false, true) ||
    GroupManager::is_tutor_of_group(api_get_user_id(), $current_group)
) {
    $edit_url = '<a href="'.api_get_path(WEB_CODE_PATH).'group/settings.php?'.api_get_cidreq().'">'.
        Display::return_icon('edit.png', get_lang('EditGroup'), '', ICON_SIZE_SMALL).'</a>';
}

echo Display::page_header(
    Security::remove_XSS($current_group['name']).' '.$edit_url.' '.$subscribe_group.' '.$unsubscribe_group
);

if (!empty($current_group['description'])) {
    echo '<p>'.Security::remove_XSS($current_group['description']).'</p>';
}

/*
 * Group Tools
 */

// If the user is subscribed to the group or the user is a tutor of the group then
if (api_is_allowed_to_edit(false, true) ||
    GroupManager::is_user_in_group(api_get_user_id(), $current_group)
) {
    $actions_array = array();
    // Link to the forum of this group
    $forums_of_groups = get_forums_of_group($current_group);

    if (is_array($forums_of_groups)) {
        if ($current_group['forum_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
            foreach ($forums_of_groups as $key => $value) {
                if ($value['forum_group_public_private'] == 'public' ||
                    ($value['forum_group_public_private'] == 'private') ||
                    !empty($user_is_tutor) ||
                    api_is_allowed_to_edit(false, true)
                ) {

                    $actions_array[] = array(
                        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.$value['forum_id'].'&'.api_get_cidreq().'&origin=group',
                        'content' => Display::return_icon(
                            'forum.png',
                            get_lang('Forum').': '.$value['forum_title'],
                            array(),
                            32
                        ),
                    );
                }
            }
        }
    }

    if ($current_group['doc_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
        // Link to the documents area of this group
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq(),
            'content' => Display::return_icon('folder.png', get_lang('GroupDocument'), array(), 32)
        );
    }

    if ($current_group['calendar_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
        $groupFilter = null;
        if (!empty($group_id)) {
            $groupFilter = "&type=course&user_id=GROUP:$group_id";
        }
        // Link to a group-specific part of agenda
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?'.api_get_cidreq().$groupFilter,
            'content' => Display::return_icon('agenda.png', get_lang('GroupCalendar'), array(), 32)
        );
    }

    if ($current_group['work_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
        // Link to the works area of this group
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'content' => Display::return_icon('work.png', get_lang('GroupWork'), array(), 32)
        );

    }
    if ($current_group['announcements_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
        // Link to a group-specific part of announcements
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'content' => Display::return_icon('announce.png', get_lang('GroupAnnouncements'), array(), 32)
        );
    }

    if ($current_group['wiki_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
        // Link to the wiki area of this group
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'wiki/index.php?'.api_get_cidreq().'&action=show&title=index&session_id='.api_get_session_id().'&group_id='.$current_group['id'],
            'content' => Display::return_icon('wiki.png', get_lang('GroupWiki'), array(), 32)
        );
    }

    if ($current_group['chat_state'] != GroupManager::TOOL_NOT_AVAILABLE) {
        // Link to the chat area of this group
        if (api_get_course_setting('allow_open_chat_window')) {
            $actions_array[] = array(
                'url' => "javascript: void(0);",
                'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32),
                'url_attributes' => array(
                    'onclick' => " window.open('../chat/chat.php?".api_get_cidreq()."&toolgroup=".$current_group['id']."','window_chat_group_".api_get_course_id()."_".api_get_group_id()."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no')"
                )
            );
        } else {
            $actions_array[] = array(
                'url' => api_get_path(WEB_CODE_PATH)."chat/chat.php?".api_get_cidreq()."&toolgroup=".$current_group['id'],
                'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32)
            );
        }
    }

    $enabled = api_get_plugin_setting('bbb', 'tool_enable');
    if ($enabled === 'true') {
        $bbb = new bbb();
        if ($bbb->hasGroupSupport()) {
            $actions_array[] = array(
                'url' => api_get_path(WEB_PLUGIN_PATH)."bbb/start.php?".api_get_cidreq(),
                'content' => Display::return_icon('bbb.png', get_lang('VideoConference'), array(), 32)
            );
        }
    }

    if (!empty($actions_array)) {
        echo Display::actions($actions_array);
    }
} else {
    $actions_array = array();

    // Link to the forum of this group
    $forums_of_groups = get_forums_of_group($current_group);

    if (is_array($forums_of_groups)) {
        if ($current_group['forum_state'] == GroupManager::TOOL_PUBLIC) {
            foreach ($forums_of_groups as $key => $value) {
                if ($value['forum_group_public_private'] == 'public') {
                    $actions_array[] = array(
                        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?cidReq='.api_get_course_id().'&forum='.$value['forum_id'].'&gidReq='.Security::remove_XSS($current_group['id']).'&origin=group',
                        'content' => Display::return_icon('forum.png', get_lang('GroupForum'), array(), ICON_SIZE_MEDIUM)
                    );
                }
            }
        }
    }

    if ($current_group['doc_state'] == GroupManager::TOOL_PUBLIC) {
        // Link to the documents area of this group
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq(),
            'content' => Display::return_icon('folder.png', get_lang('GroupDocument'), array(), ICON_SIZE_MEDIUM)
        );
    }

    if ($current_group['calendar_state'] == GroupManager::TOOL_PUBLIC) {
        // Link to a group-specific part of agenda
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'calendar/agenda.php?'.api_get_cidreq(),
            'content' => Display::return_icon('agenda.png', get_lang('GroupCalendar'), array(), ICON_SIZE_MEDIUM)
        );
    }

    if ($current_group['work_state'] == GroupManager::TOOL_PUBLIC) {
        // Link to the works area of this group
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'content' => Display::return_icon('work.png', get_lang('GroupWork'), array(), ICON_SIZE_MEDIUM)
        );
    }

    if ($current_group['announcements_state'] == GroupManager::TOOL_PUBLIC) {
        // Link to a group-specific part of announcements
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'content' => Display::return_icon('announce.png', get_lang('GroupAnnouncements'), array(), ICON_SIZE_MEDIUM)
        );
    }

    if ($current_group['wiki_state'] == GroupManager::TOOL_PUBLIC) {
        // Link to the wiki area of this group
        $actions_array[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'wiki/index.php?'.api_get_cidreq().'&action=show&title=index&session_id='.api_get_session_id().'&group_id='.$current_group['id'],
            'content' => Display::return_icon('wiki.png', get_lang('GroupWiki'), array(), 32)
        );
    }

    if ($current_group['chat_state'] == GroupManager::TOOL_PUBLIC) {
        // Link to the chat area of this group
        if (api_get_course_setting('allow_open_chat_window')) {
            $actions_array[] = array(
                'url' => "javascript: void(0);\" onclick=\"window.open('../chat/chat.php?".api_get_cidreq()."&toolgroup=".$current_group['id']."','window_chat_group_".api_get_course_id()."_".api_get_group_id()."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no') \"",
                'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32)
            );
        } else {
            $actions_array[] = array(
                'url' => api_get_path(WEB_CODE_PATH)."chat/chat.php?".api_get_cidreq()."&toolgroup=".$current_group['id'],
                'content' => Display::return_icon('chat.png', get_lang('Chat'), array(), 32)
            );
        }
    }

    if (!empty($actions_array)) {
        echo Display::actions($actions_array);
    }
}

/*
 * List all the tutors of the current group
 */
$tutors = GroupManager::get_subscribed_tutors($current_group);

$tutor_info = '';
if (count($tutors) == 0) {
    $tutor_info = get_lang('GroupNoneMasc');
} else {
    $tutor_info .= '<ul class="thumbnails">';
    foreach ($tutors as $index => $tutor) {
        $userInfo = api_get_user_info($tutor['user_id']);
        $username = api_htmlentities(sprintf(get_lang('LoginX'), $userInfo['username']), ENT_QUOTES);
        $completeName = $userInfo['complete_name'];
        $photo = '<img src="'.$userInfo['avatar'].'" alt="'.$completeName.'" width="32" height="32" title="'.$completeName.'" />';
        $tutor_info .= '<li>';
        $tutor_info .= Display::url(
            $userInfo['complete_name'],
            $userInfo['profile_url']
        );
        $tutor_info .= '</li>';
    }
    $tutor_info .= '</ul>';
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

$table = new SortableTable(
    'group_users',
    'get_number_of_group_users',
    'get_group_user_data',
    (api_is_western_name_order() xor api_sort_by_first_name()) ? 2 : 1
);
$origin = api_get_origin();
$my_cidreq = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$my_gidreq = isset($_GET['gidReq']) ? Security::remove_XSS($_GET['gidReq']) : '';
$parameters = array('cidReq' => $my_cidreq, 'origin'=> $origin, 'gidReq' => $my_gidreq);
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
//$table->set_column_filter(1, 'user_name_filter');
//$table->set_column_filter(2, 'user_name_filter');
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
function get_number_of_group_users()
{
    $groupInfo = GroupManager::get_group_properties(api_get_group_id());
    $course_id = api_get_course_int_id();

    if (empty($groupInfo) || empty($course_id)) {
        return 0;
    }

    // Database table definition
    $table = Database::get_course_table(TABLE_GROUP_USER);

    // Query
    $sql = "SELECT count(iid) AS number_of_users
            FROM $table
            WHERE 
                c_id = $course_id AND 
                group_id = '".intval($groupInfo['iid'])."'";
    $result = Database::query($sql);
    $return = Database::fetch_array($result, 'ASSOC');

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
function get_group_user_data($from, $number_of_items, $column, $direction)
{
    $groupInfo = GroupManager::get_group_properties(api_get_group_id());
    $course_id = api_get_course_int_id();

    if (empty($groupInfo) || empty($course_id)) {
        return 0;
    }

    // Database table definition
    $table_group_user = Database::get_course_table(TABLE_GROUP_USER);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
    $tableGroup = Database::get_course_table(TABLE_GROUP);

    // Query
    if (api_get_setting('show_email_addresses') === 'true') {
        $sql = "SELECT user.id 	AS col0,
				".(api_is_western_name_order() ?
                "user.firstname 	AS col1,
				user.lastname 	AS col2,"
                :
                "user.lastname 	AS col1,
				user.firstname 	AS col2,"
            )."
				user.email		AS col3
				FROM $table_user user INNER JOIN 
				$table_group_user group_rel_user
				ON (group_rel_user.user_id = user.id)
				INNER JOIN $tableGroup g
				ON (group_rel_user.group_id = g.id)
				WHERE 
				    group_rel_user.c_id = $course_id AND 
				    g.iid = '".$groupInfo['iid']."'
                ORDER BY col$column $direction 
                LIMIT $from, $number_of_items";
    } else {
        if (api_is_allowed_to_edit()) {
            $sql = "SELECT DISTINCT
                        u.id AS col0,
                        ".(api_is_western_name_order() ?
                        "u.firstname 	AS col1,
                            u.lastname 	AS col2,"
                        :
                        "u.lastname 	AS col1,
                        u.firstname 	AS col2,")."
                        u.email		AS col3
                    FROM $table_user u 
                    INNER JOIN $table_group_user gu 
                    ON (gu.user_id = u.id)
                    INNER JOIN $tableGroup g
				    ON (gu.group_id = g.id)
                    WHERE 
                        g.iid = '".$groupInfo['iid']."' AND 
                        gu.c_id = $course_id
                    ORDER BY col$column $direction 
                    LIMIT $from, $number_of_items";
        } else {
            $sql = "SELECT DISTINCT
						user.id 	AS col0,
						". (api_is_western_name_order() ?
                    "user.firstname 	AS col1,
						user.lastname 	AS col2 "
                    :
                    "user.lastname 	AS col1,
						user.firstname 	AS col2 "
                    )."
                    FROM $table_user user 
                    INNER JOIN $table_group_user group_rel_user
                    ON (group_rel_user.user_id = user.id)
                    INNER JOIN $tableGroup g
                    ON (group_rel_user.group_id = g.id)
                    WHERE 
                        g.iid = '".$groupInfo['iid']."' AND 
                        group_rel_user.c_id = $course_id AND  
                        group_rel_user.user_id = user.id AND 
                        g.iid = '".$groupInfo['iid']."'
                    ORDER BY col$column $direction 
                    LIMIT $from, $number_of_items";
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
function email_filter($email)
{
    return Display :: encrypted_mailto_link($email, $email);
}

/**
 * Display a user icon that links to the user page
 *
 * @param integer $user_id the id of the user
 * @return string code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function user_icon_filter($user_id)
{
    $userInfo = api_get_user_info($user_id);
    $photo = '<img src="'.$userInfo['avatar'].'" alt="'.$userInfo['complete_name'].'" width="22" height="22" title="'.$userInfo['complete_name'].'" />';
    return Display::url($photo, $userInfo['profile_url']);
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
function user_name_filter($name, $url_params, $row)
{
    $userInfo = api_get_user_info($row[0]);
    return UserManager::getUserProfileLink($userInfo);
}

if ($origin != 'learnpath') {
    Display::display_footer();
}

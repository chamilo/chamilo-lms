<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

/**
 * This script shows the group space for one specific group, possibly displaying
 * a list of users in the group, subscribe or unsubscribe option, tutors...
 *
 * @todo    Display error message if no group ID specified
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true, false, 'group');

$group_id = api_get_group_id();
$user_id = api_get_user_id();
$groupEntity = null;
if (!empty($group_id)) {
    $groupEntity = api_get_group_entity($group_id);
}

if (null === $groupEntity) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('Group area');
$interbreadcrumb[] = [
    'url' => 'group.php?'.api_get_cidreq(),
    'name' => get_lang('Groups'),
];

/*	Ensure all private groups // Juan Carlos Raña Trabado */
$forums = get_forums_of_group($groupEntity);
if (!GroupManager::userHasAccessToBrowse($user_id, $groupEntity, api_get_session_id())) {
    api_not_allowed(true);
}

/*
 * User wants to register in this group
 */
if (!empty($_GET['selfReg']) &&
    GroupManager::is_self_registration_allowed($user_id, $groupEntity)
) {
    GroupManager::subscribeUsers($user_id, $groupEntity);
    Display::addFlash(Display::return_message(get_lang('You are now a member of this group.')));
}

/*
 * User wants to unregister from this group
 */
if (!empty($_GET['selfUnReg']) &&
    GroupManager::is_self_unregistration_allowed($user_id, $groupEntity)
) {
    GroupManager::unsubscribeUsers($user_id, $groupEntity);
    Display::addFlash(
        Display::return_message(get_lang('You\'re now unsubscribed.'), 'normal')
    );
}

Display::display_header(
    $nameTools.' '.Security::remove_XSS($groupEntity->getName()),
    'Group'
);

Display::display_introduction_section(TOOL_GROUP);

$actions = '<a href="'.api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('Back to Groups list'),
        '',
        ICON_SIZE_MEDIUM
    ).
    '</a>';

$confirmationMessage = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));

// Register to group.
$subscribe_group = '';
if (GroupManager::is_self_registration_allowed($user_id, $groupEntity)) {
    $subscribe_group = '<a
            class="btn btn-default"
            href="'.api_get_self().'?selfReg=1&group_id='.$group_id.'"
            onclick="javascript: if(!confirm('."'".$confirmationMessage."'".')) return false;">'.
        get_lang('Add me to this group').
    '</a>';
}

// Unregister from group.
$unsubscribe_group = '';
if (GroupManager::is_self_unregistration_allowed($user_id, $groupEntity)) {
    $unsubscribe_group = '<a
        class="btn btn-default" href="'.api_get_self().'?selfUnReg=1"
        onclick="javascript: if(!confirm('."'".$confirmationMessage."'".')) return false;">'.
        get_lang('Unsubscribe me from this group.').'</a>';
}

echo Display::toolbarAction('toolbar', [$actions]);

$edit_url = '';
if (api_is_allowed_to_edit(false, true) ||
    GroupManager::isTutorOfGroup($user_id, $groupEntity)
) {
    $edit_url = '<a
        href="'.api_get_path(WEB_CODE_PATH).'group/settings.php?'.api_get_cidreq().'">'.
        Display::return_icon('edit.png', get_lang('Edit this group'), '', ICON_SIZE_SMALL).
        '</a>';
}

echo Display::page_header(
    Security::remove_XSS($groupEntity->getName()).' '.$edit_url.' '.$subscribe_group.' '.$unsubscribe_group
);

if (!empty($groupEntity->getDescription())) {
    echo '<p>'.Security::remove_XSS($groupEntity->getDescription()).'</p>';
}

if (api_is_allowed_to_edit(false, true) ||
    GroupManager::userHasAccessToBrowse($user_id, $groupEntity, api_get_session_id())
) {
    $actions_array = [];
    if (is_array($forums)) {
        if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getForumState()) {
            foreach ($forums as $forum) {
                if ('public' === $forum->getForumGroupPublicPrivate() ||
                    ('private' === $forum->getForumGroupPublicPrivate()) ||
                    !empty($user_is_tutor) ||
                    api_is_allowed_to_edit(false, true)
                ) {
                    $actions_array[] = [
                        'url' => api_get_path(WEB_CODE_PATH).
                            'forum/viewforum.php?forum='.$forum->getIid().'&'.api_get_cidreq().'&origin=group',
                        'content' => Display::return_icon(
                            'forum.png',
                            get_lang('Forum').': '.$forum->getForumTitle(),
                            [],
                            32
                        ),
                    ];
                }
            }
        }
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getDocState()) {
        // Link to the documents area of this group
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq(),
            'content' => Display::return_icon('folder.png', get_lang('Documents'), [], 32),
        ];
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getCalendarState()) {
        $groupFilter = '';
        if (!empty($group_id)) {
            $groupFilter = "&type=course&user_id=GROUP:$group_id";
        }
        // Link to a group-specific part of agenda
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?'.api_get_cidreq().$groupFilter,
            'content' => Display::return_icon('agenda.png', get_lang('Agenda'), [], 32),
        ];
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getWorkState()) {
        // Link to the works area of this group
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'content' => Display::return_icon('work.png', get_lang('Assignments'), [], 32),
        ];
    }
    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getAnnouncementsState()) {
        // Link to a group-specific part of announcements
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'content' => Display::return_icon('announce.png', get_lang('Announcements'), [], 32),
        ];
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getWikiState()) {
        // Link to the wiki area of this group
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).
                'wiki/index.php?'.api_get_cidreq().'&action=show&title=index&sid='.api_get_session_id().'&group_id='.$groupEntity->getIid(),
            'content' => Display::return_icon('wiki.png', get_lang('Wiki'), [], 32),
        ];
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getChatState()) {
        // Link to the chat area of this group
        if (api_get_course_setting('allow_open_chat_window')) {
            $actions_array[] = [
                'url' => 'javascript: void(0);',
                'content' => Display::return_icon('chat.png', get_lang('Chat'), [], 32),
                'url_attributes' => [
                    'onclick' => " window.open('../chat/chat.php?".api_get_cidreq().'&toolgroup='.$groupEntity->getIid()."','window_chat_group_".api_get_course_id().'_'.api_get_group_id()."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no')",
                ],
            ];
        } else {
            $actions_array[] = [
                'url' => api_get_path(WEB_CODE_PATH).'chat/chat.php?'.api_get_cidreq().'&gid='.$groupEntity->getIid(),
                'content' => Display::return_icon('chat.png', get_lang('Chat'), [], 32),
            ];
        }
    }

    $enabled = api_get_plugin_setting('bbb', 'tool_enable');
    if ('true' === $enabled) {
        $bbb = new bbb();
        if ($bbb->hasGroupSupport()) {
            $actions_array[] = [
                'url' => api_get_path(WEB_PLUGIN_PATH).'bbb/start.php?'.api_get_cidreq(),
                'content' => Display::return_icon('bbb.png', get_lang('Videoconference'), [], 32),
            ];
        }
    }

    $enabled = api_get_plugin_setting('zoom', 'tool_enable');
    if ('true' === $enabled) {
        $actions_array[] = [
            'url' => api_get_path(WEB_PLUGIN_PATH).'zoom/start.php?'.api_get_cidreq(),
            'content' => Display::return_icon('bbb.png', get_lang('VideoConference'), [], 32),
        ];
    }

    if (!empty($actions_array)) {
        echo Display::actions($actions_array);
    }
} else {
    $actions_array = [];
    if (is_array($forums)) {
        if (GroupManager::TOOL_PUBLIC == $groupEntity->getForumState()) {
            foreach ($forums as $forum) {
                if ('public' === $forum->getForumGroupPublicPrivate()) {
                    $actions_array[] = [
                        'url' => api_get_path(WEB_CODE_PATH).
                            'forum/viewforum.php?cid='.api_get_course_int_id().
                            '&forum='.$forum->getIid().'&gid='.$groupEntity->getIid().'&origin=group',
                        'content' => Display::return_icon(
                            'forum.png',
                            get_lang('Group Forum'),
                            [],
                            ICON_SIZE_MEDIUM
                        ),
                    ];
                }
            }
        }
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getDocState()) {
        // Link to the documents area of this group
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq(),
            'content' => Display::return_icon('folder.png', get_lang('Documents'), [], ICON_SIZE_MEDIUM),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getCalendarState()) {
        $groupFilter = '';
        if (!empty($group_id)) {
            $groupFilter = "&type=course&user_id=GROUP:$group_id";
        }
        // Link to a group-specific part of agenda
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?'.api_get_cidreq().$groupFilter,
            'content' => Display::return_icon('agenda.png', get_lang('Agenda'), [], 32),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getWorkState()) {
        // Link to the works area of this group
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'content' => Display::return_icon('work.png', get_lang('Assignments'), [], ICON_SIZE_MEDIUM),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getAnnouncementsState()) {
        // Link to a group-specific part of announcements
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'content' => Display::return_icon('announce.png', get_lang('Announcements'), [], ICON_SIZE_MEDIUM),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getWikiState()) {
        // Link to the wiki area of this group
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'wiki/index.php?'.
                api_get_cidreq().'&action=show&title=index&sid='.api_get_session_id().'&gid='.$group_id,
            'content' => Display::return_icon('wiki.png', get_lang('Wiki'), [], 32),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getChatState()) {
        // Link to the chat area of this group
        if (api_get_course_setting('allow_open_chat_window')) {
            $actions_array[] = [
                'url' => "javascript: void(0);\" onclick=\"window.open('../chat/chat.php?".api_get_cidreq().'&toolgroup='.$group_id."','window_chat_group_".api_get_course_id().'_'.api_get_group_id()."','height=380, width=625, left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no') \"",
                'content' => Display::return_icon('chat.png', get_lang('Chat'), [], 32),
            ];
        } else {
            $actions_array[] = [
                'url' => api_get_path(WEB_CODE_PATH).'chat/chat.php?'.api_get_cidreq().'&toolgroup='.$group_id,
                'content' => Display::return_icon('chat.png', get_lang('Chat'), [], 32),
            ];
        }
    }

    if (!empty($actions_array)) {
        echo Display::actions($actions_array);
    }
}

/*
 * List all the tutors of the current group
 */
$tutors = $groupEntity->getTutors();
$userRepo = Container::getUserRepository();
$tutor_info = '';
if (0 == count($tutors)) {
    $tutor_info = get_lang('(none)');
} else {
    $tutor_info .= '<ul class="thumbnails">';
    foreach ($tutors as $tutor) {
        $user = $tutor->getUser();
        $username = api_htmlentities(sprintf(get_lang('Login: %s'), $user->getUsername()), ENT_QUOTES);
        $completeName = UserManager::formatUserFullName($user);
        $avatar = Container::getIllustrationRepository()->getIllustrationUrl($user);
        $photo = '<img src="'.$avatar.'" alt="'.$completeName.'" width="32" height="32" title="'.$completeName.'" />';
        $tutor_info .= '<li>';
        //$tutor_info .= $userInfo['complete_name_with_message_link'];
        $tutor_info .= $photo.$completeName;
        $tutor_info .= '</li>';
    }
    $tutor_info .= '</ul>';
}

echo Display::page_subheader(get_lang('Coaches'));
if (!empty($tutor_info)) {
    echo $tutor_info;
}
echo '<br />';

/*
 * List all the members of the current group
 */
echo Display::page_subheader(get_lang('Group members'));

$table = new SortableTable(
    'group_users',
    'get_number_of_group_users',
    'get_group_user_data',
    api_is_western_name_order() xor api_sort_by_first_name() ? 2 : 1
);
$origin = api_get_origin();
$my_cidreq = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
$my_gidreq = isset($_GET['gid']) ? (int) $_GET['gid'] : 0;
$parameters = ['cid' => $my_cidreq, 'origin' => $origin, 'gid' => $my_gidreq];
$table->set_additional_parameters($parameters);
$table->set_header(0, '');

if (api_is_western_name_order()) {
    $table->set_header(1, get_lang('First name'));
    $table->set_header(2, get_lang('Last name'));
} else {
    $table->set_header(1, get_lang('Last name'));
    $table->set_header(2, get_lang('First name'));
}

if ('true' === api_get_setting('show_email_addresses') || api_is_allowed_to_edit()) {
    $table->set_header(3, get_lang('e-mail'));
    $table->set_column_filter(3, 'email_filter');
    $table->set_header(4, get_lang('active'));
    $table->set_column_filter(4, 'activeFilter');
} else {
    $table->set_header(3, get_lang('active'));
    $table->set_column_filter(3, 'activeFilter');
}

$table->display();

/**
 * Get the number of subscribed users to the group.
 *
 * @return int
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
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
                group_id = '".(int) ($groupInfo['iid'])."'";
    $result = Database::query($sql);
    $return = Database::fetch_array($result, 'ASSOC');

    return $return['number_of_users'];
}

/**
 * Get the details of the users in a group.
 *
 * @param int $from            starting row
 * @param int $number_of_items number of items to be displayed
 * @param int $column          sorting colum
 * @param int $direction       sorting direction
 *
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version April 2008
 */
function get_group_user_data($from, $number_of_items, $column, $direction)
{
    $groupInfo = GroupManager::get_group_properties(api_get_group_id());
    $course_id = api_get_course_int_id();
    $column = (int) $column;

    if (empty($groupInfo) || empty($course_id)) {
        return 0;
    }

    // Database table definition
    $table_group_user = Database::get_course_table(TABLE_GROUP_USER);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
    $tableGroup = Database::get_course_table(TABLE_GROUP);

    // Query
    if ('true' === api_get_setting('show_email_addresses')) {
        $sql = 'SELECT user.id 	AS col0,
				'.(
            api_is_western_name_order() ?
                'user.firstname 	AS col1,
				user.lastname 	AS col2,'
                :
                'user.lastname 	AS col1,
				user.firstname 	AS col2,'
            )."
				user.email		AS col3
				, user.active AS col4
				FROM $table_user user
				INNER JOIN $table_group_user group_rel_user
				ON (group_rel_user.user_id = user.id)
				INNER JOIN $tableGroup g
				ON (group_rel_user.group_id = g.iid)
				WHERE
				    group_rel_user.c_id = $course_id AND
				    g.iid = '".$groupInfo['iid']."'
                ORDER BY col$column $direction
                LIMIT $from, $number_of_items";
    } else {
        if (api_is_allowed_to_edit()) {
            $sql = 'SELECT DISTINCT
                        u.id AS col0,
                        '.(api_is_western_name_order() ?
                        'u.firstname 	AS col1,
                            u.lastname 	AS col2,'
                        :
                        'u.lastname 	AS col1,
                        u.firstname 	AS col2,')."
                        u.email		AS col3
                        , u.active AS col4
                    FROM $table_user u
                    INNER JOIN $table_group_user gu
                    ON (gu.user_id = u.id)
                    INNER JOIN $tableGroup g
				    ON (gu.group_id = g.iid)
                    WHERE
                        g.iid = '".$groupInfo['iid']."' AND
                        gu.c_id = $course_id
                    ORDER BY col$column $direction
                    LIMIT $from, $number_of_items";
        } else {
            $sql = 'SELECT DISTINCT
						user.id 	AS col0,
						'.(
                api_is_western_name_order() ?
                    'user.firstname 	AS col1,
						user.lastname 	AS col2 '
                    :
                    'user.lastname 	AS col1,
						user.firstname 	AS col2 '
                    )."
                    , user.active AS col3
                    FROM $table_user user
                    INNER JOIN $table_group_user group_rel_user
                    ON (group_rel_user.user_id = user.id)
                    INNER JOIN $tableGroup g
                    ON (group_rel_user.group_id = g.iid)
                    WHERE
                        g.iid = '".$groupInfo['iid']."' AND
                        group_rel_user.c_id = $course_id AND
                        group_rel_user.user_id = user.id AND
                        g.iid = '".$groupInfo['iid']."'
                    ORDER BY col$column $direction
                    LIMIT $from, $number_of_items";
        }
    }

    $return = [];
    $result = Database::query($sql);
    while ($row = Database::fetch_row($result)) {
        $user = api_get_user_entity($row[0]);
        $avatar = Container::getIllustrationRepository()->getIllustrationUrl($user);
        $photo = '<img src="'.$avatar.'" width="22" height="22" />';
        $row[0] = $photo;
        $return[] = $row;
    }

    return $return;
}

/**
 * Returns a mailto-link.
 *
 * @param string $email An email-address
 *
 * @return string HTML-code with a mailto-link
 */
function email_filter($email)
{
    return Display::encrypted_mailto_link($email, $email);
}

function activeFilter($isActive)
{
    if ($isActive) {
        return Display::return_icon('accept.png', get_lang('active'), [], ICON_SIZE_TINY);
    }

    return Display::return_icon('error.png', get_lang('inactive'), [], ICON_SIZE_TINY);
}

if ('learnpath' != $origin) {
    Display::display_footer();
}

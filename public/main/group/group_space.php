<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * This script shows the group space for one specific group.
 */
require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true, false, 'group');

$group_id = api_get_group_id();
$user_id = api_get_user_id();
$courseId = api_get_course_int_id();

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

/* Ensure all private groups */
$forums = get_forums_of_group($groupEntity);

if (!GroupManager::userHasAccessToBrowse($user_id, $groupEntity, api_get_session_id())) {
    api_not_allowed(true);
}

$user_is_tutor = GroupManager::isTutorOfGroup($user_id, $groupEntity);
$can_edit = api_is_allowed_to_edit(false, true) || $user_is_tutor;

/*
 * User wants to register in this group
 */
if (!empty($_GET['selfReg']) && GroupManager::is_self_registration_allowed($user_id, $groupEntity)) {
    GroupManager::subscribeUsers($user_id, $groupEntity);
    Display::addFlash(Display::return_message(get_lang('You are now a member of this group.')));
}

/*
 * User wants to unregister from this group
 */
if (!empty($_GET['selfUnReg']) && GroupManager::is_self_unregistration_allowed($user_id, $groupEntity)) {
    GroupManager::unsubscribeUsers($user_id, $groupEntity);
    Display::addFlash(Display::return_message(get_lang('You\'re now unsubscribed.'), 'normal'));
}

$htmlHeadXtra[] = '<style>
/* Make legacy tables look cleaner inside Tailwind-like cards */
.data_table,
.data_table table {
  width: 100%;
}
.thumbnails { list-style: none; padding-left: 0; margin: 0; }
</style>';

Display::display_header(
    $nameTools.' '.Security::remove_XSS($groupEntity->getTitle()),
    'Group'
);

Display::display_introduction_section(TOOL_GROUP);

$confirmationMessage = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));

$backUrl = api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq();

$subscribeUrl = api_get_self().'?'.api_get_cidreq().'&selfReg=1&group_id='.(int) $group_id;
$unsubscribeUrl = api_get_self().'?'.api_get_cidreq().'&selfUnReg=1&group_id='.(int) $group_id;

$editUrl = api_get_path(WEB_CODE_PATH).'group/settings.php?'.api_get_cidreq();

// Build header actions
$actionsHtml = '<div class="flex flex-wrap items-center gap-2">';

$actionsHtml .= '<a href="'.Security::remove_XSS($backUrl).'"
    class="inline-flex items-center gap-2 rounded-md border border-gray-25 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-20">';
$actionsHtml .= Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Back'));
$actionsHtml .= '<span>'.sprintf(get_lang('Back to %s'), get_lang('Group list')).'</span>';
$actionsHtml .= '</a>';

if ($can_edit) {
    $actionsHtml .= '<a href="'.Security::remove_XSS($editUrl).'"
        class="inline-flex items-center gap-2 rounded-md border border-gray-25 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-20">';
    $actionsHtml .= Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit this group'));
    $actionsHtml .= '<span>'.get_lang('Edit this group').'</span>';
    $actionsHtml .= '</a>';
}

if (GroupManager::is_self_registration_allowed($user_id, $groupEntity)) {
    $actionsHtml .= '<a href="'.Security::remove_XSS($subscribeUrl).'"
        onclick="javascript: if(!confirm(\''.$confirmationMessage.'\')) return false;"
        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-white shadow-sm hover:opacity-90">';
    $actionsHtml .= Display::getMdiIcon('account-plus', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Add me to this group'));
    $actionsHtml .= '<span>'.get_lang('Add me to this group').'</span>';
    $actionsHtml .= '</a>';
}

if (GroupManager::is_self_unregistration_allowed($user_id, $groupEntity)) {
    $actionsHtml .= '<a href="'.Security::remove_XSS($unsubscribeUrl).'"
        onclick="javascript: if(!confirm(\''.$confirmationMessage.'\')) return false;"
        class="inline-flex items-center gap-2 rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50">';
    $actionsHtml .= Display::getMdiIcon('account-remove', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Unsubscribe me from this group.'));
    $actionsHtml .= '<span>'.get_lang('Unsubscribe me from this group.').'</span>';
    $actionsHtml .= '</a>';
}

$actionsHtml .= '</div>';

echo '<div class="mx-auto wd-full px-4 sm:px-6 lg:px-8">';
echo '<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">';
echo '<div>';
echo '<h1 class="text-2xl font-semibold text-gray-900">'.Security::remove_XSS($groupEntity->getTitle()).'</h1>';

if (!empty($groupEntity->getDescription())) {
    echo '<p class="mt-2 text-sm text-gray-600">'.Security::remove_XSS($groupEntity->getDescription()).'</p>';
}
echo '</div>';
echo $actionsHtml;
echo '</div>';

// Tabs (space/settings/members/tutors)
echo GroupManager::renderGroupTabs('space');

/**
 * Tool shortcuts
 */
$actions_array = [];

$hasBrowseAccess = $can_edit || GroupManager::userHasAccessToBrowse($user_id, $groupEntity, api_get_session_id());

if ($hasBrowseAccess) {
    if (is_array($forums) && GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getForumState()) {
        foreach ($forums as $forum) {
            if ('public' === $forum->getForumGroupPublicPrivate() ||
                'private' === $forum->getForumGroupPublicPrivate() ||
                $user_is_tutor ||
                api_is_allowed_to_edit(false, true)
            ) {
                $actions_array[] = [
                    'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.$forum->getIid().'&'.api_get_cidreq().'&origin=group',
                    'icon' => Display::getMdiIcon(ToolIcon::FORUM, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Forum')),
                    'label' => get_lang('Forum'),
                    'subtitle' => Security::remove_XSS($forum->getTitle()),
                ];
            }
        }
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getDocState()) {
        $params = ['toolName' => 'document', 'cid' => $courseId];
        $url = Container::getRouter()->generate('chamilo_core_course_redirect_tool', $params).'?'.api_get_cidreq();
        $actions_array[] = [
            'url' => $url,
            'icon' => Display::getMdiIcon(ToolIcon::DOCUMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Documents')),
            'label' => get_lang('Documents'),
        ];
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getCalendarState()) {
        $params = ['toolName' => 'agenda', 'cid' => $courseId];
        $url = Container::getRouter()->generate('chamilo_core_course_redirect_tool', $params).'?'.api_get_cidreq();
        $actions_array[] = [
            'url' => $url,
            'icon' => Display::getMdiIcon(ToolIcon::AGENDA, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Agenda')),
            'label' => get_lang('Agenda'),
        ];
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getWorkState()) {
        $params = ['toolName' => 'student_publication', 'cid' => $courseId];
        $url = Container::getRouter()->generate('chamilo_core_course_redirect_tool', $params).'?'.api_get_cidreq();
        $actions_array[] = [
            'url' => $url,
            'icon' => Display::getMdiIcon(ToolIcon::ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assignments')),
            'label' => get_lang('Assignments'),
        ];
    }

    if (GroupManager::TOOL_NOT_AVAILABLE != $groupEntity->getAnnouncementsState()) {
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'icon' => Display::getMdiIcon(ToolIcon::ANNOUNCEMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Announcements')),
            'label' => get_lang('Announcements'),
        ];
    }

    // Plugins (BBB/Zoom)
    $pluginRepo = Container::getPluginRepository();

    $plugin = $pluginRepo->findOneByTitle('bbb');
    $pluginConfiguration = $plugin?->getConfigurationsByAccessUrl(Container::getAccessUrlUtil()->getCurrent());
    $isInstalled = $plugin && $plugin->isInstalled();
    $isEnabled = $plugin && $pluginConfiguration && $pluginConfiguration->isActive();

    if ($isInstalled && $isEnabled) {
        $bbb = new Bbb();
        if ($bbb->hasGroupSupport()) {
            $actions_array[] = [
                'url' => api_get_path(WEB_PLUGIN_PATH).'Bbb/start.php?'.api_get_cidreq(),
                'icon' => Display::getMdiIcon(ToolIcon::VIDEOCONFERENCE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Videoconference')),
                'label' => get_lang('Videoconference'),
                'subtitle' => 'BBB',
            ];
        }
    }

    $plugin = $pluginRepo->findOneByTitle('zoom');
    $pluginConfiguration = $plugin?->getConfigurationsByAccessUrl(Container::getAccessUrlUtil()->getCurrent());
    $isInstalled = $plugin && $plugin->isInstalled();
    $isEnabled = $plugin && $pluginConfiguration && $pluginConfiguration->isActive();

    if ($isInstalled && $isEnabled) {
        $actions_array[] = [
            'url' => api_get_path(WEB_PLUGIN_PATH).'zoom/start.php?'.api_get_cidreq(),
            'content' => Display::getMdiIcon(ToolIcon::VIDEOCONFERENCE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Videoconference')),
        ];
    }
} else {
    // Public-only access
    if (is_array($forums) && GroupManager::TOOL_PUBLIC == $groupEntity->getForumState()) {
        foreach ($forums as $forum) {
            if ('public' === $forum->getForumGroupPublicPrivate()) {
                $actions_array[] = [
                    'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?cid='.api_get_course_int_id().'&forum='.$forum->getIid().'&gid='.$groupEntity->getIid().'&origin=group',
                    'content' => Display::getMdiIcon(ToolIcon::FORUM, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Group Forum')),
                ];
            }
        }
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getDocState()) {
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq(),
            'content' => Display::getMdiIcon(ToolIcon::DOCUMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Documents')),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getCalendarState()) {
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?'.api_get_cidreq(),
            'content' => Display::getMdiIcon(ToolIcon::AGENDA, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Agenda')),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getWorkState()) {
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
            'content' => Display::getMdiIcon(ToolIcon::ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assignments')),
        ];
    }

    if (GroupManager::TOOL_PUBLIC == $groupEntity->getAnnouncementsState()) {
        $actions_array[] = [
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'content' => Display::getMdiIcon(ToolIcon::ANNOUNCEMENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Announcements')),
        ];
    }
}

// Render tool shortcuts as cards
// Render tool shortcuts as cards (icon + label)
if (!empty($actions_array)) {
    echo '<div class="rounded-2xl border border-gray-50 bg-white p-6 shadow-sm">';
    echo '<h2 class="text-base font-semibold text-gray-900">'.get_lang('Tools').'</h2>';
    echo '<p class="mt-1 text-sm text-gray-600">'.get_lang('Access the group tools and resources').'</p>';

    echo '<div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">';
    foreach ($actions_array as $item) {
        $url = $item['url'] ?? '#';
        $icon = $item['icon'] ?? ($item['content'] ?? '');
        $label = $item['label'] ?? '';
        $subtitle = $item['subtitle'] ?? '';

        $attrs = '';
        if (!empty($item['url_attributes']) && is_array($item['url_attributes'])) {
            foreach ($item['url_attributes'] as $k => $v) {
                $attrs .= ' '.Security::remove_XSS($k).'="'.Security::remove_XSS($v).'"';
            }
        }

        echo '<a href="'.Security::remove_XSS($url).'" '.$attrs.'
            class="group flex items-center gap-3 rounded-xl border border-gray-50 bg-gray-20 px-4 py-3 hover:bg-white hover:shadow-sm">';

        echo '<div class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-50 bg-white">';
        echo $icon;
        echo '</div>';

        echo '<div class="min-w-0">';
        if (!empty($label)) {
            echo '<div class="truncate text-sm font-medium text-gray-900">'.$label.'</div>';
        }
        if (!empty($subtitle)) {
            echo '<div class="truncate text-xs text-gray-600">'.$subtitle.'</div>';
        }
        echo '</div>';

        echo '</a>';
    }
    echo '</div>';
    echo '</div>';
}

/**
 * Coaches
 */
$tutors = $groupEntity->getTutors();

echo '<div class="mt-8 rounded-2xl border border-gray-50 bg-white p-6 shadow-sm">';
echo '<h2 class="text-base font-semibold text-gray-900">'.get_lang('Coaches').'</h2>';

if (0 == count($tutors)) {
    echo '<p class="mt-3 text-sm text-gray-600">'.get_lang('(none)').'</p>';
} else {
    echo '<ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">';
    foreach ($tutors as $tutor) {
        $user = $tutor->getUser();
        $completeName = UserManager::formatUserFullName($user);
        $avatar = Container::getIllustrationRepository()->getIllustrationUrl($user);

        echo '<li class="flex items-center gap-3 rounded-xl border border-gray-50 bg-gray-20 p-3">';
        echo '<img src="'.Security::remove_XSS($avatar).'"
            alt="'.Security::remove_XSS($completeName).'"
            class="h-10 w-10 rounded-full object-cover" />';
        echo '<div class="min-w-0">';
        echo '<div class="truncate text-sm font-medium text-gray-900">'.Security::remove_XSS($completeName).'</div>';
        echo '<div class="truncate text-xs text-gray-600">'.Security::remove_XSS($user->getUsername()).'</div>';
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';
}
echo '</div>';

/**
 * Group members table (keeps SortableTable)
 */
echo '<div class="mt-8 rounded-2xl border border-gray-50 bg-white p-6 shadow-sm">';
echo '<div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">';
echo '<div>';
echo '<h2 class="text-base font-semibold text-gray-900">'.get_lang('Group members').'</h2>';
echo '<p class="mt-1 text-sm text-gray-600">'.sprintf(get_lang('%s members'), (int) get_number_of_group_users()).'</p>';
echo '</div>';
echo '</div>';

echo '<div class="mt-4">';

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
    $table->set_header(3, get_lang('E-mail'));
    $table->set_column_filter(3, 'email_filter');
    $table->set_header(4, get_lang('active'));
    $table->set_column_filter(4, 'activeFilter');
} else {
    $table->set_header(3, get_lang('active'));
    $table->set_column_filter(3, 'activeFilter');
}

$table->display();

echo '</div>';
echo '</div>'; // members card
echo '</div>'; // container

/**
 * Get the number of subscribed users to the group.
 */
function get_number_of_group_users()
{
    $groupInfo = GroupManager::get_group_properties(api_get_group_id());
    $course_id = api_get_course_int_id();

    if (empty($groupInfo) || empty($course_id)) {
        return 0;
    }

    $table = Database::get_course_table(TABLE_GROUP_USER);

    $sql = "SELECT count(iid) AS number_of_users
            FROM $table
            WHERE
                c_id = $course_id AND
                group_id = '".(int) ($groupInfo['iid'])."'";
    $result = Database::query($sql);
    $return = Database::fetch_assoc($result);

    return (int) ($return['number_of_users'] ?? 0);
}

/**
 * Get the details of the users in a group.
 */
function get_group_user_data($from, $number_of_items, $column, $direction)
{
    $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;
    $groupInfo = GroupManager::get_group_properties(api_get_group_id());
    $course_id = api_get_course_int_id();
    $column = (int) $column;

    if (empty($groupInfo) || empty($course_id)) {
        return 0;
    }

    $table_group_user = Database::get_course_table(TABLE_GROUP_USER);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
    $tableGroup = Database::get_course_table(TABLE_GROUP);

    if ('true' === api_get_setting('show_email_addresses')) {
        $sql = 'SELECT user.id AS col0,
                '.(api_is_western_name_order()
                ? 'user.firstname AS col1, user.lastname AS col2,'
                : 'user.lastname AS col1, user.firstname AS col2,')."
                user.email AS col3,
                user.active AS col4
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
                    '.(api_is_western_name_order()
                    ? 'u.firstname AS col1, u.lastname AS col2,'
                    : 'u.lastname AS col1, u.firstname AS col2,')."
                    u.email AS col3,
                    u.active AS col4
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
                    user.id AS col0,
                    '.(api_is_western_name_order()
                    ? 'user.firstname AS col1, user.lastname AS col2 '
                    : 'user.lastname AS col1, user.firstname AS col2 ')."
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
        $row[0] = '<img src="'.Security::remove_XSS($avatar).'" width="22" height="22" alt="" />';
        $return[] = $row;
    }

    return $return;
}

function email_filter($email)
{
    return Display::encrypted_mailto_link($email, $email);
}

function activeFilter($isActive)
{
    if ($isActive) {
        return Display::getMdiIcon('toggle-switch', 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('active'));
    }

    return Display::getMdiIcon('toggle-switch-off', 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('inactive'));
}

if ('learnpath' != api_get_origin()) {
    Display::display_footer();
}

<?php
/* For licensing terms, see /license.txt */
/**
 *	Main page for the group module.
 *	This script displays the general group settings,
 *	and a list of groups with buttons to view, edit...
 *
 *	@author Thomas Depraetere, Hugues Peeters, Christophe Gesche: initial versions
 *	@author Bert Vanderkimpen, improved self-unsubscribe for cvs
 *	@author Patrick Cool, show group comment under the group name
 *	@author Roan Embrechts, initial self-unsubscribe code, code cleaning, virtual course support
 *	@author Bart Mollet, code cleaning, use of Display-library, list of courseAdmin-tools, use of GroupManager
 *	@package chamilo.group
 */
/**
 * INIT SECTION
 */

// Name of the language file that needs to be included
$language_file = 'group';

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$nameTools = get_lang('GroupOverview');

/*	Libraries */

include_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
include_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'export_all':
            $data = array();

            $data[] = array(
                'category',
                'group',
                'description',
                'announcements_state',
                'calendar_state',
                'chat_state',
                'doc_state',
                'forum_state',
                'work_state',
                'wiki_state',
                'max_student',
                'self_reg_allowed',
                'self_unreg_allowed',
                'groups_per_user'
            );

            $groupCategories = array();
            $categories = GroupManager::get_categories();

            foreach ($categories as $categoryInfo) {
                $data[] = array(
                    $categoryInfo['title'],
                    null,
                    $categoryInfo['description'],
                    $categoryInfo['announcements_state'],
                    $categoryInfo['calendar_state'],
                    $categoryInfo['chat_state'],
                    $categoryInfo['doc_state'],
                    $categoryInfo['forum_state'],
                    $categoryInfo['work_state'],
                    $categoryInfo['wiki_state'],
                    $categoryInfo['max_student'],
                    $categoryInfo['self_reg_allowed'],
                    $categoryInfo['self_unreg_allowed'],
                    $categoryInfo['groups_per_user']
                );
            }

            $groups = GroupManager::get_group_list();

            foreach ($groups as $index => $groupInfo) {
                $categoryTitle = null;
                $categoryInfo = GroupManager::get_category($groupInfo['category_id']);
                $groupSettings = GroupManager::get_group_properties($groupInfo['id']);
                if (!empty($categoryInfo)) {
                    $categoryTitle = $categoryInfo['title'];
                }

                $data[] = array(
                    $categoryTitle,
                    $groupSettings['name'],
                    $groupSettings['description'],
                    $groupSettings['announcements_state'],
                    $groupSettings['calendar_state'],
                    $groupSettings['chat_state'],
                    $groupSettings['doc_state'],
                    $groupSettings['forum_state'],
                    $groupSettings['work_state'],
                    $groupSettings['wiki_state'],
                    $groupSettings['maximum_number_of_students'],
                    $groupSettings['self_registration_allowed'],
                    $groupSettings['self_unregistration_allowed'],
                );
            }

            Export::export_table_csv($data);
            exit;

            break;
        case 'export':
            $groupId = isset($_GET['id']) ? intval($_GET['id']) : null;
            $groups = GroupManager::get_group_list();

            $data = array();
            foreach ($groups as $index => $group) {
                if (!empty($groupId)) {
                    if ($group['id'] != $groupId) {
                        continue;
                    }
                }
                $users = GroupManager::get_users($group['id']);
                foreach ($users as $index => $user) {
                    $row = array();
                    $user = api_get_user_info($user);
                    $row[] = $group['name'];
                    $row[] = $user['official_code'];
                    $row[] = $user['lastName'];
                    $row[] = $user['firstName'];
                    $data[] = $row;
                }
            }
            switch ($_GET['type']) {
                case 'csv':
                    Export::export_table_csv($data);
                    exit;
                case 'xls':
                    Export::export_table_xls($data);
                    exit;
            }
            break;
	}
}

/*	Header */

$interbreadcrumb[] = array('url' => 'group.php', 'name' => get_lang('Groups'));
if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
    // So we are not in learnpath tool
    if (!$is_allowed_in_course) {
        api_not_allowed(true);
    }
    if (!api_is_allowed_to_edit(false, true)) {
        api_not_allowed(true);
    } else {
        Display::display_header($nameTools, 'Group');
    }
} else {
?> <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CSS_PATH); ?>default.css" /> <?php
}

// Action links
echo '<div class="actions">';
    echo '<a href="group_creation.php?'.api_get_cidreq().'">'.
        Display::return_icon('new_group.png', get_lang('NewGroupCreate'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '<a href="group.php?'.api_get_cidreq().'">'.
        Display::return_icon('group.png', get_lang('Groups'),'',ICON_SIZE_MEDIUM).'</a>';
    if (api_get_setting('allow_group_categories') == 'true') {
        echo '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.
            Display::return_icon('new_folder.png', get_lang('AddCategory'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.
            Display::return_icon('settings.png', get_lang('PropModify'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    echo '<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=xls">'.
        Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$categories = GroupManager::get_categories();

foreach ($categories as $index => $category) {
    if (api_get_setting('allow_group_categories') == 'true') {
        echo '<h2>'.$category['title'].'</h2>';
    }
    $groups = GroupManager::get_group_list($category['id']);
    echo '<ul>';
    if (!empty($groups)) {
        foreach ($groups as $index => $group) {
            echo '<li>';
            echo Display::tag('h3', Security::remove_XSS($group['name']));
            echo '<ul>';
            $users = GroupManager::get_users($group['id']);
            if (!empty($users)) {
                foreach ($users as $index => $user) {
                    $user_info = api_get_user_info($user);
                    $username = api_htmlentities(sprintf(get_lang('LoginX'), $user_info['username']), ENT_QUOTES);
                    echo '<li title="'.$username.'">'.api_get_person_name($user_info['firstName'], $user_info['lastName']).'</li>';
                }
            }
            echo '</ul>';
            echo '</li>';
        }
    }
    echo '</ul>';
}

if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
    Display::display_footer();
}

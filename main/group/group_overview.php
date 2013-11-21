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
$language_file = array('group', 'admin');

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$nameTools = get_lang('GroupOverview');
$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();

/*	Libraries */
include_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
include_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'export_all':
            $data = GroupManager::exportCategoriesAndGroupsToArray();
            Export::export_table_csv($data);
            exit;
            break;
        case 'export_pdf':
            $content = GroupManager::getOverview($courseId, $keyword);
            $pdf = new PDF();
            $extra = '<div style="text-align:center"><h2>'.get_lang('GroupList').'</h2></div>';
            $extra .= '<strong>'.get_lang('Course').': </strong>'.$courseInfo['title'].' ('.$courseInfo['code'].')';

            $content = $extra.$content;
            $pdf->content_to_pdf($content, null, null, api_get_course_id());
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
                    break;
                case 'xls':
                    if (!empty($data)) {
                        Export::export_table_xls($data);
                        exit;
                    }
                    break;
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
        // Tool introduction
        Display::display_introduction_section(TOOL_GROUP);
    }
} else {
?> <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CSS_PATH); ?>default.css" /> <?php
}

// Action links
echo '<div class="actions">';
    echo '<a href="group_creation.php?'.api_get_cidreq().'">'.
        Display::return_icon('new_group.png', get_lang('NewGroupCreate'), '', ICON_SIZE_MEDIUM).'</a>';

    if (api_get_setting('allow_group_categories') == 'true') {
        echo '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.
            Display::return_icon('new_folder.png', get_lang('AddCategory'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.
            Display::return_icon('settings.png', get_lang('PropModify'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    echo  '<a href="import.php?'.api_get_cidreq().'&action=import">'.
        Display::return_icon('import_csv.png', get_lang('Import'), '', ICON_SIZE_MEDIUM).'</a>';

    echo  '<a href="group_overview.php?'.api_get_cidreq().'&action=export_all&type=csv">'.
        Display::return_icon('export_csv.png', get_lang('Export'), '', ICON_SIZE_MEDIUM).'</a>';

    echo '<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=xls">'.
    Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a>';

   echo  '<a href="group_overview.php?'.api_get_cidreq().'&action=export_pdf">'.
        Display::return_icon('pdf.png', get_lang('ExportToPDF'), '', ICON_SIZE_MEDIUM).'</a>';

    echo '<a href="group.php?'.api_get_cidreq().'">'.
        Display::return_icon('group.png', get_lang('Groups'),'',ICON_SIZE_MEDIUM).'</a>';


    echo '<a href="../user/user.php?'.api_get_cidreq().'">'.
    Display::return_icon('user.png', get_lang('GoTo').' '.get_lang('Users'), '', ICON_SIZE_MEDIUM).'</a>';

    echo GroupManager::getSearchForm();
echo '</div>';

echo GroupManager::getOverview($courseId, $keyword);

if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
    Display::display_footer();
}

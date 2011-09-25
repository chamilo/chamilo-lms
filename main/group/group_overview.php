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

require '../inc/global.inc.php';
$this_section = SECTION_COURSES;

$nameTools = get_lang('GroupOverview');

/*	Libraries */

include_once api_get_path(LIBRARY_PATH).'course.lib.php';
include_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
include_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'export':
			$groups = GroupManager::get_group_list();
			$data = array();
			foreach ($groups as $index => $group) {
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
echo '<a href="group_creation.php?'.api_get_cidreq().'">'.Display::return_icon('new_group.png', get_lang('NewGroupCreate'),'','32').'</a>';
echo '<a href="group.php?'.api_get_cidreq().'">'.Display::return_icon('group.png', get_lang('Groups'),'','32').'</a>';
if (api_get_setting('allow_group_categories') == 'true') {
	echo '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.Display::return_icon('new_folder.png', get_lang('AddCategory'),'','32').'</a>';
} else {
	//echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.Display::return_icon('edit_group.gif').'&nbsp;'.get_lang('PropModify').'</a>&nbsp;';
	echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.Display::return_icon('settings.png', get_lang('PropModify'),'','32').'</a>';
}
//echo Display::return_icon('csv.gif', get_lang('ExportAsCSV')).'<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=csv">'.get_lang('ExportAsCSV').'</a> ';
echo '<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=xls">'.Display::return_icon('export_excel.png', get_lang('ExportAsXLS'),'','32').'</a>';
echo '</div>';

$categories = GroupManager::get_categories();
foreach ($categories as $index => $category) {
	if (api_get_setting('allow_group_categories') == 'true') {
		echo '<h3>'.$category['title'].'</h3>';
	}
	$groups = GroupManager::get_group_list($category['id']);
	echo '<ul>';
	foreach ($groups as $index => $group) {
		echo '<li>';
		echo stripslashes($group['name']);
		echo '<ul>';
		$users = GroupManager::get_users($group['id']);
		foreach ($users as $index => $user) {
			$user_info = api_get_user_info($user);
			echo '<li>'.api_get_person_name($user_info['firstName'], $user_info['lastName']).'</li>';
		}
		echo '</ul>';
		echo '</li>';
	}
	echo '</ul>';
}

/*	FOOTER */

if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
	Display::display_footer();
}

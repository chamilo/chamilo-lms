<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
$language_file = 'gradebook';
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'sortable_table.class.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/evalform.class.php';

api_block_anonymous_users();
block_students();

$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?','name' => get_lang('Gradebook'));
$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.Security::remove_XSS($_GET['selectcat']),'name' => get_lang('Details'));
$interbreadcrumb[] = array ('url' => 'gradebook_showlog_link.php?visiblelink='.Security::remove_XSS($_GET['visiblelink']).'&amp;selectcat='.Security::remove_XSS($_GET['selectcat']),	'name' => get_lang('GradebookQualifyLog'));
$this_section = SECTION_COURSES;
Display :: display_header('');
echo '<div class="actions">';
api_display_tool_title(get_lang('GradebookQualifyLog'));
echo '</div>';

$t_user     = Database :: get_main_table(TABLE_MAIN_USER);
$t_link_log = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);
$visible_link=Security::remove_XSS($_GET['visiblelink']);
$evaledit   = EvalLink :: load($visible_link);
$sql="SELECT lk.name,lk.description,lk.weight,lk.visible,lk.type,lk.created_at,us.username from ".$t_link_log." lk inner join ".$t_user." us on lk.user_id_log=us.user_id where lk.id_linkeval_log=".$evaledit[0]->get_id()." and lk.type='link';";
$result=Database::query($sql);
$list_info=array();
while ($row=Database::fetch_row($result)) {
	$list_info[]=$row;
}

foreach($list_info as $key => $info_log) {
	$list_info[$key][5]=($info_log[5]) ? api_convert_and_format_date($info_log[5]) : 'N/A';
	$list_info[$key][3]=($info_log[3]==1) ? get_lang('GradebookVisible') : get_lang('GradebookInvisible');
}

$parameters=array('visiblelink'=>Security::remove_XSS($_GET['visiblelink']),'selectcat'=>Security::remove_XSS($_GET['selectcat']));


$table = new SortableTableFromArrayConfig($list_info, 1,20,'gradebooklink');
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('GradebookNameLog'));
$table->set_header(1, get_lang('GradebookDescriptionLog'));
$table->set_header(2, get_lang('GradebookPreviousWeight'));
$table->set_header(3, get_lang('GradebookVisibilityLog'));
$table->set_header(4, get_lang('ResourceType'));
$table->set_header(5, get_lang('Date'));
$table->set_header(6, get_lang('GradebookWhoChangedItLog'));
$table->display();

Display :: display_footer();

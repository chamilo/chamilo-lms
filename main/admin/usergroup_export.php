<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.admin
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = 'admin';

$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

$tool_name = get_lang('Export');

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'usergroups.php', 'name' => get_lang('Classes'));

set_time_limit(0);

$form = new FormValidator('export_users');
$form->addElement('header',  $tool_name);
$form->addElement('style_submit_button', 'submit',get_lang('Export'),'class="save"');

if ($form->validate()) {
    $user_group = new UserGroup;
    $header = array(array('name', 'description'));
	$data = $user_group->get_all_for_export();    
    $data = array_merge($header, $data);    
    $filename = 'export_classes_'.date('Y-m-d_H-i-s');    
    Export::export_table_csv($data,$filename);

}
Display :: display_header($tool_name);
$form->display();
Display :: display_footer();
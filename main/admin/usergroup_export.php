<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.admin
 */
$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$tool_name = get_lang('Export');

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'usergroups.php', 'name' => get_lang('Classes'));

set_time_limit(0);

$form = new FormValidator('export_users');
$form->addElement('header', $tool_name);
$form->addButtonExport(get_lang('Export'));

if ($form->validate()) {
    $userGroup = new UserGroup();
    $header = array(array('id', 'name', 'description', 'users'));
    $data = $userGroup->getDataToExport();
    $data = array_merge($header, $data);
    $filename = 'export_classes_'.api_get_local_time();
    Export::arrayToCsv($data, $filename);
    exit;
}

Display :: display_header($tool_name);
$form->display();
Display::display_footer();

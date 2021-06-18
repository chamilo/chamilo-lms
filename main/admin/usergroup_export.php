<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

$userGroup = new UserGroup();
$userGroup->protectScript();

$tool_name = get_lang('Export');
$interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];

set_time_limit(0);

$form = new FormValidator('export_users');
$form->addElement('header', $tool_name);
$form->addButtonExport(get_lang('Export'));

if ($form->validate()) {
    $header = [['id', 'name', 'description', 'users', 'courses', 'sessions']];
    $data = $userGroup->getDataToExport();
    $data = array_merge($header, $data);
    $filename = 'export_classes_'.api_get_local_time();
    Export::arrayToCsv($data, $filename);
    exit;
}

Display::display_header($tool_name);
$form->display();
Display::display_footer();

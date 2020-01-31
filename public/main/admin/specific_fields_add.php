<?php
/* For licensing terms, see /license.txt */

/**
 * Add form.
 */

// Resetting the course id.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'specific_fields_manager.lib.php';

// section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();
$fieldId = isset($_REQUEST['field_id']) ? intval($_REQUEST['field_id']) : 0;
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];
$interbreadcrumb[] = [
    'url' => 'settings.php?category=Search',
    'name' => get_lang('Configuration settings'),
];
$interbreadcrumb[] = [
    'url' => 'specific_fields.php',
    'name' => get_lang('Specific search fields'),
];

$tool_name = get_lang('Add a specific search field');

if (isset($_GET['action']) && 'edit' === $_GET['action']) {
    $tool_name = get_lang('Edit specific search field');
}
// Create the form
$form = new FormValidator('specific_fields_add');
// Field variable name
$form->addElement('hidden', 'field_id', $fieldId);
$form->addElement('text', 'field_name', get_lang('Field name'));
$form->applyFilter('field_name', 'html_filter');
$form->applyFilter('field_name', 'trim');
$form->addRule('field_name', get_lang('Required field'), 'required');
$form->addRule('field_name', get_lang('Only letters and numbers allowed'), 'username');
$form->addRule('field_name', '', 'maxlength', 20);

// Set default values (only not empty when editing)
$defaults = [];
if ($fieldId) {
    $form_information = get_specific_field_list(['id' => $fieldId]);
    $defaults['field_name'] = $form_information[0]['name'];
}
$form->setDefaults($defaults);
// Submit button
$form->addButtonCreate(get_lang('Add'), 'submit');

// Validate form
if ($form->validate()) {
    $field = $form->exportValues();
    $field_name = $field['field_name'];
    if (is_numeric($field['field_id']) && 0 != $field['field_id'] && !empty($field['field_id'])) {
        edit_specific_field($field['field_id'], $field['field_name']);
        $message = get_lang('Field added.');
    } else {
        $field_id = add_specific_field($field_name);
        $message = get_lang('Field succesfully added');
    }
    header('Location: specific_fields.php?message='.$message);
    //exit ();
}
// Display form
Display::display_header($tool_name);
$form->display();
Display::display_footer();

<?php
/* For licensing terms, see /license.txt */
/**
 * Add form
 * @package chamilo.admin
 */

$language_file[] = 'admin';

// Resetting the course id.
$cidReset = true;

// including necessary libraries
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
include_once $libpath.'specific_fields_manager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';

// section for the tabs
$this_section=SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();

// Database table definitions
$table_admin  = Database :: get_main_table(TABLE_MAIN_ADMIN);
$table_user   = Database :: get_main_table(TABLE_MAIN_USER);
$table_uf     = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
$table_uf_opt = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
$table_uf_val = Database :: get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'settings.php?category=Search', 'name' => get_lang('DokeosConfigSettings'));
$interbreadcrumb[] = array ('url' => 'specific_fields.php', 'name' => get_lang('SpecificSearchFields'));
if ($_GET['action']<>'edit') {
  $tool_name = get_lang('AddSpecificSearchField');
} else {
  $tool_name = get_lang('EditSpecificSearchField');
}
// Create the form
$form = new FormValidator('specific_fields_add');
// Field variable name
$form->addElement('hidden','field_id',(int)$_REQUEST['field_id']);
$form->addElement('text','field_name',get_lang('FieldName'));
$form->applyFilter('field_name','html_filter');
$form->applyFilter('field_name','trim');
$form->addRule('field_name', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('field_name', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
$form->addRule('field_name', '', 'maxlength',20);

// Set default values (only not empty when editing)
$defaults = array();
if (is_numeric($_REQUEST['field_id'])) {
    $form_information = get_specific_field_list(array( 'id' => (int)$_GET['field_id'] ));
    $defaults['field_name'] = $form_information[0]['name'];
}
$form->setDefaults($defaults);
// Submit button
$form->addElement('style_submit_button', 'submit', get_lang('Add'),'class="add"');

// Validate form
if ($form->validate()) {
    $field = $form->exportValues();
    $field_name = $field['field_name'];
    if (is_numeric($field['field_id']) && $field['field_id']<>0 && !empty($field['field_id'])) {
        edit_specific_field($field['field_id'],$field['field_name']);
        $message = get_lang('FieldEdited');
    } else {
        $field_id = add_specific_field($field_name);
        $message = get_lang('FieldAdded');
    }
    header('Location: specific_fields.php?message='.$message);
    //exit ();
}
// Display form
Display::display_header($tool_name);
$form->display();
Display::display_footer();
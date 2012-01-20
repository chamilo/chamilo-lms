<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
/**
 * Code
 */
// Language files that should be included.
$language_file = 'admin';

// Resetting the course id.
$cidReset = true;

// Including some necessary dokeos files.
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions.
api_protect_admin_script();

// Setting breadcrumbs.
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'class_list.php', 'name' => get_lang('Classes'));

// Setting the name of the tool.
$tool_name = get_lang("AddClasses");

$form = new FormValidator('add_class');
$form->add_textfield('name', get_lang('ClassName'));
$form->addElement('style_submit_button', 'submit', get_lang('Ok'), 'class="add"');
if ($form->validate()) {
    $values = $form->exportValues();
    ClassManager::create_class($values['name']);
    header('Location: class_list.php');
}

// Displaying the header.
Display :: display_header($tool_name);

// Displaying the form.
$form->display();

// Displaying the footer.
Display :: display_footer();
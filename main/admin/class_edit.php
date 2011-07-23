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
include '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions.
api_protect_admin_script();

// Setting breadcrumbs.
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'class_list.php', 'name' => get_lang('AdminClasses'));

// Setting the name of the tool.
$tool_name = get_lang('AddClasses');

$tool_name = get_lang('ModifyClassInfo');
$class_id = intval($_GET['idclass']);
$class = ClassManager :: get_class_info($class_id);
$form = new FormValidator('edit_class', 'post', 'class_edit.php?idclass='.$class_id);
$form->add_textfield('name',get_lang('ClassName'));
$form->addElement('style_submit_button', 'submit', get_lang('Ok'), 'class="add"');
$form->setDefaults(array('name'=>$class['name']));
if($form->validate())
{
    $values = $form->exportValues();
    ClassManager :: set_name($values['name'], $class_id);
    header('Location: class_list.php');
}

Display :: display_header($tool_name);
//api_display_tool_title($tool_name);
$form->display();

// Displaying the footer.
Display :: display_footer();

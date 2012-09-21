<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
/**
 * Code
*   This     tool allows platform admins to add classes by uploading a CSV file
* @todo Add some langvars to DLTT
*/

/**
 * Validates imported data.
 */
function validate_data($classes) {
    $errors = array();
     $usergroup = new UserGroup();
    foreach ($classes as $index => $class) {
        // 1. Check wheter ClassName is available.
        if (!isset($class['name']) || strlen(trim($class['name'])) == 0) {
            $class['line'] = $index + 2;
            $class['error'] = get_lang('MissingClassName');
            $errors[] = $class;
        }
        // 2. Check whether class doesn't exist yet.
        else {            
            if ($usergroup->usergroup_exists($class['name'])) {
                $class['line'] = $index + 2;
                $class['error'] = get_lang('ClassNameExists').' <strong>'.$class['ClassName'].'</strong>';
                $errors[] = $class;
            }
        }
    }
    return $errors;
}

/**
 * Save imported class data to database
 */
function save_data($classes) {
    $number_of_added_classes = 0;
    $usergroup = new UserGroup();    
    foreach ($classes as $index => $class) {
        $id = $usergroup->save($class);
        if ($id) {
            $number_of_added_classes++;
        }
    }
    return $number_of_added_classes;
}

// Language files that should be included.
$language_file = array ('admin', 'registration');

// Resetting the course id.
$cidReset = true;

// Including some necessary dokeos files
require_once '../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions.
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'usergroups.php', 'name' => get_lang('Classes'));

// Database Table Definitions

// Setting the name of the tool.
$tool_name = get_lang('ImportClassListCSV');

// Displaying the header.
Display :: display_header($tool_name);

set_time_limit(0);

$form = new FormValidator('import_classes');
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
$group = array();
$group[] = $form->createElement('radio', 'file_type', '', 'CSV (<a href="example_class.csv" target="_blank">'.get_lang('ExampleCSVFile').'</a>)', 'csv');
//$group[] = $form->createElement('radio', 'file_type', null, 'XML (<a href="example.xml" target="_blank">'.get_lang('ExampleXMLFile').'</a>)', 'xml');
$form->addGroup($group, '', get_lang('FileType'), '<br/>');
$form->addElement('style_submit_button', 'submit', get_lang('Import'), 'class="save"');

if ($form->validate()) {
    $classes = Import::csv_to_array($_FILES['import_file']['tmp_name']);
    $errors = validate_data($classes);
    if (count($errors) == 0) {
        $number_of_added_classes = save_data($classes);
        Display::display_normal_message($number_of_added_classes.' '.get_lang('Added'));
    } else {
        $error_message = get_lang('ErrorsWhenImportingFile');
        $error_message .= '<ul>';
        foreach ($errors as $index => $error_class) {
            $error_message .= '<li>'.$error_class['error'].' ('.get_lang('Line').' '.$error_class['line'].')';
            $error_message .= '</li>';
        }
        $error_message .= '</ul>';
        $error_message .= get_lang('Error');
        Display :: display_error_message($error_message, false);
    }
}

$form->display();
?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<pre>
<b>name;description</b>
"User group 1";"Description"
</pre>
<?php

// Displaying the footer.
Display :: display_footer();
<?php
/* For licensing terms, see /license.txt */

/**
 *  This tool allows platform admins to add classes by uploading a CSV file
 *  @todo Add some langvars to DLTT
 * 	@package chamilo.admin
 */

/**
 * Validates imported data.
 */
function validate_data($classes) {
    $errors = array();
    $usergroup = new UserGroup();
    foreach ($classes as $index => $class) {
        // 1. Check of class name is available.
        if (!isset($class['name']) || strlen(trim($class['name'])) == 0) {
            $class['line'] = $index + 2;
            $class['error'] = get_lang('MissingClassName');
            $errors[] = $class;
        } else {
            // 2. Check whether class doesn't exist yet.
            if ($usergroup->usergroup_exists($class['name'])) {
                $class['line'] = $index + 2;
                $class['error'] = get_lang('ClassNameExists') .
                    ': <strong>' .$class['name'] . '</strong>';
                $errors[] = $class;
            }
        }
    }

    return $errors;
}

/**
 * Save imported class data to database
 *
 * @param $classes
 *
 * @return int
 */
function save_data($classes)
{
    $count = 0;
    $usergroup = new UserGroup();
    foreach ($classes as $index => $class) {
        $usersToAdd = isset($class['users']) ? $class['users'] : null;
        unset($class['users']);
        $id = $usergroup->save($class);
        if ($id) {
            if (!empty($usersToAdd)) {
                $usersToAddList = explode(',', $usersToAdd);
                $userIdList = array();
                foreach ($usersToAddList as $username) {
                    $userInfo = api_get_user_info_from_username($username);
                    $userIdList[] = $userInfo['user_id'];
                }
                if (!empty($userIdList)) {
                    $usergroup->subscribe_users_to_usergroup(
                        $id,
                        $userIdList,
                        false
                    );
                }
            }
            $count++;
        }
    }

    return $count;
}

// Resetting the course id.
$cidReset = true;

include '../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions.
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'usergroups.php', 'name' => get_lang('Classes'));

// Database Table Definitions
// Setting the name of the tool.
$tool_name = get_lang('ImportClassListCSV');

// Displaying the header.
Display :: display_header($tool_name);

set_time_limit(0);

$form = new FormValidator('import_classes');
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
$group = array();
$group[] = $form->createElement(
    'radio',
    'file_type',
    '',
    'CSV (<a href="example_class.csv" target="_blank">' . get_lang('ExampleCSVFile') . '</a>)',
    'csv'
);
$form->addGroup($group, '', get_lang('FileType'), null);
$form->addButtonImport(get_lang('Import'));

if ($form->validate()) {
    $classes = Import::csvToArray($_FILES['import_file']['tmp_name']);
    $errors = validate_data($classes);
    if (count($errors) == 0) {
        $number_of_added_classes = save_data($classes);
        Display::display_normal_message($number_of_added_classes . ' ' . get_lang('Added'));
    } else {
        $error_message = get_lang('ErrorsWhenImportingFile');
        $error_message .= '<ul>';
        foreach ($errors as $index => $error_class) {
            $error_message .= '<li>' . $error_class['error'] . ' (' . get_lang('Line') . ' ' . $error_class['line'] . ')';
            $error_message .= '</li>';
        }
        $error_message .= '</ul>';
        $error_message .= get_lang('Error');
        Display :: display_error_message($error_message, false);
    }
}

$form->display();
?>
<p><?php echo get_lang('CSVMustLookLike') . ' (' . get_lang('MandatoryFields') . ')'; ?> :</p>

<pre>
<b>name;description;</b>users
"User group 1";"Description";admin,username1,username2
</pre>
<?php
// Displaying the footer.
Display :: display_footer();

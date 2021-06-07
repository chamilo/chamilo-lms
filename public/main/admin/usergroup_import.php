<?php
/* For licensing terms, see /license.txt */

/**
 *  This tool allows platform admins to add classes by uploading a CSV file.
 *
 * @todo Add some langvars to DLTT
 */

/**
 * Validates imported data.
 */
function validate_data($classes)
{
    $errors = [];
    $usergroup = new UserGroupModel();
    foreach ($classes as $index => $class) {
        // 1. Check of class name is available.
        if (!isset($class['name']) || 0 == strlen(trim($class['name']))) {
            $class['line'] = $index + 2;
            $class['error'] = get_lang('Missing class name');
            $errors[] = $class;
        } else {
            // 2. Check whether class doesn't exist yet.
            if ($usergroup->usergroup_exists($class['name'])) {
                $class['line'] = $index + 2;
                $class['error'] = get_lang('Class name exists').
                    ': <strong>'.$class['name'].'</strong>';
                $errors[] = $class;
            }
        }
    }

    return $errors;
}

/**
 * Save imported class data to database.
 *
 * @param $classes
 *
 * @return int
 */
function save_data($classes)
{
    $count = 0;
    $usergroup = new UserGroupModel();
    foreach ($classes as $index => $class) {
        $usersToAdd = isset($class['users']) ? $class['users'] : null;
        unset($class['users']);
        $id = $usergroup->save($class);
        if ($id) {
            if (!empty($usersToAdd)) {
                $usersToAddList = explode(',', $usersToAdd);
                $userIdList = [];
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

require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

$usergroup = new UserGroupModel();
$usergroup->protectScript();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];

// Setting the name of the tool.
$tool_name = get_lang('Import class list via CSV');

set_time_limit(0);

$form = new FormValidator('import_classes');
$form->addElement('file', 'import_file', get_lang('CSV file import location'));
$group = [];
$group[] = $form->createElement(
    'radio',
    'file_type',
    '',
    'CSV (<a href="example_class.csv" target="_blank" download>'.get_lang('Example CSV file').'</a>)',
    'csv'
);
$form->addGroup($group, '', get_lang('File type'), null);
$form->addButtonImport(get_lang('Import'));

if ($form->validate()) {
    $classes = Import::csvToArray($_FILES['import_file']['tmp_name']);
    $errors = validate_data($classes);
    if (0 == count($errors)) {
        $number_of_added_classes = save_data($classes);
        Display::addFlash(Display::return_message($number_of_added_classes.' '.get_lang('Added'), 'normal'));
    } else {
        $error_message = get_lang('Errors when importing file');
        $error_message .= '<ul>';
        foreach ($errors as $index => $error_class) {
            $error_message .= '<li>'.$error_class['error'].' ('.get_lang('Line').' '.$error_class['line'].')';
            $error_message .= '</li>';
        }
        $error_message .= '</ul>';
        $error_message .= get_lang('Error');
        Display::addFlash(Display::return_message($error_message, 'error', false));
    }
}

// Displaying the header.
Display::display_header($tool_name);

$form->display();
?>
<p><?php echo get_lang('The CSV file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').')'; ?> :</p>

<pre>
<b>name;description;</b>users
"User group 1";"Description";admin,username1,username2
</pre>
<?php
// Displaying the footer.
Display::display_footer();

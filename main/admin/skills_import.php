<?php
/* For licensing terms, see /license.txt */
/**
 * This tool allows platform admins to add skills by uploading a CSV or XML file.
 *
 * @documentation Some interesting basic skills can be found in the "Skills"
 * section here: http://en.wikipedia.org/wiki/Personal_knowledge_management
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Validate the imported data.
 *
 * @param $skills
 *
 * @return array
 */
function validate_data($skills)
{
    $errors = [];
    // 1. Check if mandatory fields are set.
    $mandatory_fields = ['id', 'parent_id', 'name'];
    foreach ($skills as $index => $skill) {
        foreach ($mandatory_fields as $field) {
            if (empty($skill[$field])) {
                $skill['error'] = get_lang(ucfirst($field).'Mandatory');
                $errors[] = $skill;
            }
        }
        // 2. Check skill ID is not empty
        if (!isset($skill['id']) || empty($skill['id'])) {
            $skill['error'] = get_lang('SkillImportNoID');
            $errors[] = $skill;
        }
        // 3. Check skill Parent
        if (!isset($skill['parent_id'])) {
            $skill['error'] = get_lang('SkillImportNoParent');
            $errors[] = $skill;
        }
        // 4. Check skill Name
        if (!isset($skill['name'])) {
            $skill['error'] = get_lang('SkillImportNoName');
            $errors[] = $skill;
        }
    }

    return $errors;
}

/**
 * Save the imported data.
 *
 * @param   array   List of users
 *
 * @uses \global variable $inserted_in_course,
 * which returns the list of courses the user was inserted in
 */
function save_data($skills)
{
    if (is_array($skills)) {
        $parents = [];
        $urlId = api_get_current_access_url_id();
        foreach ($skills as $index => $skill) {
            if (isset($parents[$skill['parent_id']])) {
                $skill['parent_id'] = $parents[$skill['parent_id']];
            } else {
                $skill['parent_id'] = 1;
            }
            if (empty($skill['access_url_id'])) {
                $skill['access_url_id'] = $urlId;
            }
            $skill['a'] = 'add';
            $saved_id = $skill['id'];
            $skill['id'] = null;
            $oskill = new Skill();
            $skill_id = $oskill->add($skill);
            $parents[$saved_id] = $skill_id;
        }
    }
}

/**
 * Read the CSV-file.
 *
 * @param string $file Path to the CSV-file
 *
 * @return array All userinformation read from the file
 */
function parse_csv_data($file)
{
    $skills = Import::csvToArray($file);
    foreach ($skills as $index => $skill) {
        $skills[$index] = $skill;
    }

    return $skills;
}

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

$tool_name = get_lang('ImportSkillsListCSV');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('PlatformAdmin')];

set_time_limit(0);
$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', true);
$user_id_error = [];
$error_message = '';

if (!empty($_POST['formSent']) && $_FILES['import_file']['size'] !== 0) {
    $file_type = $_POST['file_type'];
    Security::clear_token();
    $tok = Security::get_token();
    $allowed_file_mimetype = ['csv'];
    $error_kind_file = false;
    $error_message = '';

    $ext_import_file = substr($_FILES['import_file']['name'], (strrpos($_FILES['import_file']['name'], '.') + 1));

    if (in_array($ext_import_file, $allowed_file_mimetype)) {
        if (strcmp($file_type, 'csv') === 0 && $ext_import_file == $allowed_file_mimetype[0]) {
            $skills = parse_csv_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($skills);
            $error_kind_file = false;
        } else {
            $error_kind_file = true;
        }
    } else {
        $error_kind_file = true;
    }

    // List skill id with error.
    $skills_to_insert = $skill_id_error = [];
    if (is_array($errors)) {
        foreach ($errors as $my_errors) {
            $skill_id_error[] = $my_errors['SkillName'];
        }
    }
    if (is_array($skills)) {
        foreach ($skills as $my_skill) {
            if (isset($my_skill['name']) && !in_array($my_skill['name'], $skill_id_error)) {
                $skills_to_insert[] = $my_skill;
            }
        }
    }

    if (strcmp($file_type, 'csv') === 0) {
        save_data($skills_to_insert);
    } else {
        $error_message = get_lang('YouMustImportAFileAccordingToSelectedOption');
    }

    if (count($errors) > 0) {
        $see_message_import = get_lang('FileImportedJustSkillsThatAreNotRegistered');
    } else {
        $see_message_import = get_lang('FileImported');
    }

    if (count($errors) != 0) {
        $warning_message = '<ul>';
        foreach ($errors as $index => $error_skill) {
            $warning_message .= '<li><b>'.$error_skill['error'].'</b>: ';
            $warning_message .= '<strong>'.$error_skill['SkillName'].'</strong>&nbsp;('.$error_skill['SkillName'].')';
            $warning_message .= '</li>';
        }
        $warning_message .= '</ul>';
    }

    if ($error_kind_file) {
        $error_message = get_lang('YouMustImportAFileAccordingToSelectedOption');
    }
}

$interbreadcrumb[] = ["url" => 'skill_list.php', "name" => get_lang('ManageSkills')];

Display::display_header($tool_name);

if (!empty($error_message)) {
    echo Display::return_message($error_message, 'error');
}
if (!empty($see_message_import)) {
    echo Display::return_message($see_message_import, 'normal');
}

$objSkill = new Skill();
echo $objSkill->getToolBar();

$form = new FormValidator('user_import', 'post', 'skills_import.php');
$form->addElement('header', '', $tool_name);
$form->addElement('hidden', 'formSent');
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$group = [];
$group[] = $form->createElement(
    'radio',
    'file_type',
    '',
    'CSV (<a href="skill_example.csv" target="_blank" download>'.get_lang('ExampleCSVFile').'</a>)',
    'csv'
);
$form->addGroup($group, '', get_lang('FileType'));
$form->addButtonImport(get_lang('Import'));
$defaults['formSent'] = 1;
$defaults['sendMail'] = 0;
$defaults['file_type'] = 'csv';
$form->setDefaults($defaults);
$form->display();

?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<pre>
    <b>id</b>;<b>parent_id</b>;<b>name</b>;<b>description</b>
    <b>2</b>;<b>1</b>;<b>Chamilo Expert</b>;Chamilo is an open source LMS;<br />
</pre>
<?php
Display::display_footer();

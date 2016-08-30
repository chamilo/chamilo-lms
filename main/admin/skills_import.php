<?php
/* For licensing terms, see /license.txt */
/**
 * This tool allows platform admins to add skills by uploading a CSV or XML file
 * @package chamilo.admin
 * @documentation Some interesting basic skills can be found in the "Skills"
 * section here: http://en.wikipedia.org/wiki/Personal_knowledge_management
 */

$cidReset = true;
require_once '../inc/global.inc.php';

/**
 * Validate the imported data.
 * @param $skills
 * @return array
 */
function validate_data($skills)
{
    $errors = array();
    // 1. Check if mandatory fields are set.
    $mandatory_fields = array('id', 'parent_id', 'name');
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
 * Save the imported data
 * @param   array   List of users
 * @return  void
 * @uses global variable $inserted_in_course,
 * which returns the list of courses the user was inserted in
 */
function save_data($skills)
{
    if (is_array($skills)) {
        $parents = array();
        foreach ($skills as $index => $skill) {
            if (isset($parents[$skill['parent_id']])) {
                $skill['parent_id'] = $parents[$skill['parent_id']];
            } else {
                $skill['parent_id'] = 1;
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
 * Read the CSV-file
 * @param string $file Path to the CSV-file
 * @return array All userinformation read from the file
 */
function parse_csv_data($file)
{
	$skills = Import :: csvToArray($file);
	foreach ($skills as $index => $skill) {
		$skills[$index] = $skill;
	}

	return $skills;
}

/**
 * XML-parser: handle start of element
 */
function element_start($parser, $data)
{
	$data = api_utf8_decode($data);
    global $skill;
    global $current_tag;
    switch ($data) {
        case 'Skill':
            $skill = array();
            break;
        default:
            $current_tag = $data;
    }
}

/**
 * XML-parser: handle end of element
 */
function element_end($parser, $data)
{
	$data = api_utf8_decode($data);
    global $skill;
    global $skills;
    global $current_value;
    switch ($data) {
        case 'Skill':
            $skills[] = $skill;
            break;
        default:
            $skill[$data] = $current_value;
            break;
    }
}

/**
 * XML-parser: handle character data
 */
function character_data($parser, $data)
{
	$data = trim(api_utf8_decode($data));
	global $current_value;
	$current_value = $data;
}

/**
 * Read the XML-file
 * @param string $file Path to the XML-file
 * @return array All userinformation read from the file
 */
function parse_xml_data($file)
{
    global $current_tag;
    global $current_value;
    global $skill;
    global $skills;
    $skills = array();
    $parser = xml_parser_create('UTF-8');
    xml_set_element_handler($parser, 'element_start', 'element_end');
    xml_set_character_data_handler($parser, 'character_data');
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
    xml_parse($parser, api_utf8_encode_xml(file_get_contents($file)));
    xml_parser_free($parser);

	return $skills;
}

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

$tool_name = get_lang('ImportSkillsListCSV');
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

set_time_limit(0);
$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', true);
$user_id_error = array();
$error_message = '';

if (!empty($_POST['formSent']) && $_FILES['import_file']['size'] !== 0) {
    $file_type = $_POST['file_type'];
    Security::clear_token();
    $tok = Security::get_token();
    $allowed_file_mimetype = array('csv','xml');
    $error_kind_file = false;
    $error_message = '';

    $ext_import_file = substr($_FILES['import_file']['name'], (strrpos($_FILES['import_file']['name'],'.')+1));

    if (in_array($ext_import_file,$allowed_file_mimetype)) {
        if (strcmp($file_type, 'csv') === 0 && $ext_import_file == $allowed_file_mimetype[0]) {
            $skills	= parse_csv_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($skills);
            $error_kind_file = false;
        } elseif (strcmp($file_type, 'xml') === 0 && $ext_import_file == $allowed_file_mimetype[1]) {
            $skills = parse_xml_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($skills);
            $error_kind_file = false;
        } else {
            $error_kind_file = true;
        }
    } else {
        $error_kind_file = true;
    }

    // List skill id with error.
    $skills_to_insert = $skill_id_error = array();
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
    } elseif (strcmp($file_type, 'xml') === 0) {
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

$interbreadcrumb[] = array ("url" => 'skill_list.php', "name" => get_lang('ManageSkills'));

Display :: display_header($tool_name);

if (!empty($error_message)) {
    Display::display_error_message($error_message);
}
if (!empty($see_message_import)) {
    Display::display_normal_message($see_message_import);
}

$toolbar = Display::toolbarButton(
    get_lang('ManageSkills'),
    api_get_path(WEB_CODE_PATH) . 'admin/skill_list.php',
    'list',
    'success',
    ['title' => get_lang('CreateSkill')]
);
$toolbar .= '&nbsp;&nbsp;';
$toolbar .= Display::toolbarButton(
    get_lang('SkillsWheel'),
    api_get_path(WEB_CODE_PATH) . 'admin/skills_wheel.php',
    'bullseye',
    'primary',
    ['title' => get_lang('CreateSkill')]
);
$toolbar .= '&nbsp;&nbsp;';
$toolbar .= Display::toolbarButton(
    get_lang('BadgesManagement'),
    api_get_path(WEB_CODE_PATH) . 'admin/skill_badge_list.php',
    'shield',
    'warning',
    ['title' => get_lang('BadgesManagement')]
);
$toolbar .= '<br /><br />';

echo $toolbar;

$form = new FormValidator('user_import','post','skills_import.php');
$form->addElement('header', '', $tool_name);
$form->addElement('hidden', 'formSent');
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$group = array();
$group[] = $form->createElement(
    'radio',
    'file_type',
    '',
    'CSV (<a href="skill_example.csv" target="_blank">'.get_lang('ExampleCSVFile').'</a>)',
    'csv'
);
$form->addGroup($group, '', get_lang('FileType'));
$form->addButtonImport(get_lang('Import'));
$defaults['formSent'] = 1;
$defaults['sendMail'] = 0;
$defaults['file_type'] = 'csv';
$form->setDefaults($defaults);
$form->display();

$list = array();
$list_reponse = array();
$result_xml = '';
$i = 0;
$count_fields = count($extra_fields);
if ($count_fields > 0) {
    foreach ($extra_fields as $extra) {
        $list[] = $extra[1];
        $list_reponse[] = 'xxx';
        $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $result_xml .= $spaces.'&lt;'.$extra[1].'&gt;xxx&lt;/'.$extra[1].'&gt;';
        if ($i != $count_fields - 1) {
            $result_xml .= '<br/>';
        }
        $i++;
    }
}
?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<pre>
    <b>id</b>;<b>parent_id</b>;<b>name</b>;<b>description</b>
    <b>2</b>;<b>1</b>;<b>Chamilo Expert</b>;Chamilo is an open source LMS;<br />
</pre>
<?php
Display :: display_footer();

<?php
/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to create courses by uploading a CSV file
 * Copyright (c) 2005 Bart Mollet <bart.mollet@hogent.be>.
 */

/**
 * Validates imported data.
 *
 * @param array $courses
 *
 * @return array
 */
function validate_courses_data($courses)
{
    $errors = [];
    $coursecodes = [];
    foreach ($courses as $index => $course) {
        $course['line'] = $index + 1;

        // 1. Check whether mandatory fields are set.
        $mandatory_fields = ['Code', 'Title', 'CourseCategory'];
        foreach ($mandatory_fields as $field) {
            if (empty($course[$field])) {
                $course['error'] = get_lang($field.'Mandatory');
                $errors[] = $course;
            }
        }

        // 2. Check current course code.
        if (!empty($course['Code'])) {
            // 2.1 Check whether code has been already used by this CVS-file.
            if (isset($coursecodes[$course['Code']])) {
                $course['error'] = get_lang('CodeTwiceInFile');
                $errors[] = $course;
            } else {
                // 2.2 Check whether course code has been occupied.
                $courseInfo = api_get_course_info($course['Code']);
                if (!empty($courseInfo)) {
                    $course['error'] = get_lang('CodeExists');
                    $errors[] = $course;
                }
            }
            $coursecodes[$course['Code']] = 1;
        }

        // 3. Check whether teacher exists.
        $teacherList = getTeacherListInArray($course['Teacher']);

        if (!empty($teacherList)) {
            foreach ($teacherList as $teacher) {
                $teacherInfo = api_get_user_info_from_username($teacher);
                if (empty($teacherInfo)) {
                    $course['error'] = get_lang('UnknownTeacher').' ('.$teacher.')';
                    $errors[] = $course;
                }
            }
        }

        if (!empty($course['CourseCategory'])) {
            $categoryInfo = CourseCategory::getCategory($course['CourseCategory']);
            if (empty($categoryInfo)) {
                CourseCategory::addNode(
                    $course['CourseCategory'],
                    $course['CourseCategoryName'] ? $course['CourseCategoryName'] : $course['CourseCategory'],
                    'TRUE',
                    null
                );
            }
        } else {
            $course['error'] = get_lang('NoCourseCategorySupplied');
            $errors[] = $course;
        }
    }

    return $errors;
}

/**
 * Get the teacher list.
 *
 * @param array $teachers
 *
 * @return array
 */
function getTeacherListInArray($teachers)
{
    if (!empty($teachers)) {
        return explode('|', $teachers);
    }

    return [];
}

/**
 * Saves imported data.
 *
 * @param array $courses List of courses
 */
function save_courses_data($courses)
{
    $msg = '';
    foreach ($courses as $course) {
        $course_language = $course['Language'];
        $teachers = getTeacherListInArray($course['Teacher']);
        $teacherList = [];
        $creatorId = api_get_user_id();

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $teacherInfo = api_get_user_info_from_username($teacher);
                if (!empty($teacherInfo)) {
                    $teacherList[] = $teacherInfo;
                }
            }
        }

        $params = [];
        $params['title'] = $course['Title'];
        $params['wanted_code'] = $course['Code'];
        $params['tutor_name'] = null;
        $params['course_category'] = $course['CourseCategory'];
        $params['course_language'] = $course_language;
        $params['user_id'] = $creatorId;
        $addMeAsTeacher = isset($_POST['add_me_as_teacher']) ? $_POST['add_me_as_teacher'] : false;
        $params['add_user_as_teacher'] = $addMeAsTeacher;

        // Check if there is a course template stated for this course. In that case, we check if that code exists in DB:
        if (array_key_exists('CourseTemplate', $course) && $course['CourseTemplate'] != '') {
            $result = Database::fetch_array(
                Database::query(
                    "SELECT id as real_id FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '".Database::escape_string($course['CourseTemplate'])."'"
                ),
                'ASSOC'
            );
            if (count($result) && array_key_exists('real_id', $result)) {
                $params['course_template'] = $result['real_id'];
            }
        }

        $courseInfo = CourseManager::create_course($params);

        if (!empty($courseInfo)) {
            if (!empty($teacherList)) {
                foreach ($teacherList as $teacher) {
                    CourseManager::subscribeUser(
                        $teacher['user_id'],
                        $courseInfo['code'],
                        COURSEMANAGER
                    );
                }
            }
            $msg .= '<a href="'.api_get_path(WEB_COURSE_PATH).$courseInfo['directory'].'/">
                    '.$courseInfo['title'].'</a> '.get_lang('Created').'<br />';
        }
        // Check if necessary to clone tools' first page from the original course to the imported course:
        if (array_key_exists('CloneHomepageTools', $course) && $course['CloneHomepageTools'] == 'true' && array_key_exists('course_template', $params)) {
            $results = Database::store_result(
                Database::query(
                    "SELECT * FROM ".Database::get_course_table(TABLE_TOOL_LIST)."
                    WHERE c_id = ".$params['course_template']
                ),
                'ASSOC'
            );
            if (count($results)) {
                foreach ($results as $row) {
                    Database::update(
                        Database::get_course_table(TABLE_TOOL_LIST),
                        ['visibility' => $row['visibility']],
                        ['c_id = ? and name = ?' => [$courseInfo['real_id'], $row['name']]]
                    );
                }
            }
        }
    }

    if (!empty($msg)) {
        echo Display::return_message($msg, 'normal', false);
    }
}

/**
 * Read the CSV-file.
 *
 * @param string $file Path to the CSV-file
 *
 * @return array All course-information read from the file
 */
function parse_csv_courses_data($file)
{
    return Import::csv_reader($file);
}

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;

if (isset($extAuthSource) && is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('ImportCourses').' CSV';

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

set_time_limit(0);
Display::display_header($tool_name);

if (isset($_POST['formSent']) && $_POST['formSent']) {
    if (empty($_FILES['import_file']['tmp_name'])) {
        $error_message = get_lang('UplUploadFailed');
        echo Display::return_message($error_message, 'error', false);
    } else {
        $allowed_file_mimetype = ['csv'];

        $ext_import_file = substr($_FILES['import_file']['name'], (strrpos($_FILES['import_file']['name'], '.') + 1));

        if (!in_array($ext_import_file, $allowed_file_mimetype)) {
            echo Display::return_message(get_lang('YouMustImportAFileAccordingToSelectedOption'), 'error');
        } else {
            $courses = parse_csv_courses_data($_FILES['import_file']['tmp_name']);

            $errors = validate_courses_data($courses);
            if (count($errors) == 0) {
                save_courses_data($courses);
            }
        }
    }
}

if (isset($errors) && count($errors) != 0) {
    $error_message = '<ul>';
    foreach ($errors as $index => $error_course) {
        $error_message .= '<li>'.get_lang('Line').' '.$error_course['line'].': <strong>'.$error_course['error'].'</strong>: ';
        $error_message .= get_lang('Course').': '.$error_course['Title'].' ('.$error_course['Code'].')';
        $error_message .= '</li>';
    }
    $error_message .= '</ul>';
    echo Display::return_message($error_message, 'error', false);
}

$form = new FormValidator(
    'import',
    'post',
    api_get_self(),
    null,
    ['enctype' => 'multipart/form-data']
);
$form->addHeader($tool_name);
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
$form->addElement('checkbox', 'add_me_as_teacher', null, get_lang('AddMeAsTeacherInCourses'));
$form->addButtonImport(get_lang('Import'), 'save');
$form->addElement('hidden', 'formSent', 1);
$form->display();

?>
<div style="clear: both;"></div>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<strong>Code</strong>;<strong>Title</strong>;<strong>CourseCategory</strong>;<strong>CourseCategoryName</strong>;Teacher;Language;CourseTemplate;CloneHomepageTools
BIO0015;Biology;BIO;Science;teacher1;english;TEMPLATE1;true
BIO0016;Maths;MATH;Engineerng;teacher2|teacher3;english;;
BIO0017;Language;LANG;;;english;;
</pre>
    </blockquote>

<?php
Display::display_footer();

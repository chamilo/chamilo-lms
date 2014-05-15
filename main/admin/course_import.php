<?php
/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to create courses by uploading a CSV file
 * Copyright (c) 2005 Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.admin
 */

/**
 * Validates imported data.
 */
function validate_data($courses)
{
    $errors = array ();
    $coursecodes = array ();
    foreach ($courses as $index => $course) {
        $course['line'] = $index +1;

        // 1. Check whether mandatory fields are set.
        $mandatory_fields = array ('Code', 'Title', 'CourseCategory');
        foreach ($mandatory_fields as $field) {
            if (!isset($course[$field]) || strlen($course[$field]) == 0) {
                $course['error'] = get_lang($field.'Mandatory');
                $errors[] = $course;
            }
        }

        // 2. Check current course code.
        if (isset ($course['Code']) && strlen($course['Code']) != 0) {
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
                } else {
                    if ($teacherInfo['status'] != COURSEMANAGER) {
                        $course['error'] = get_lang('UserIsNotATeacher').' ('.$teacher.')';
                        $errors[] = $course;
                    }
                }
            }
        }

        // 4. Check whether course category exists.
        if (isset($course['CourseCategory']) && strlen($course['CourseCategory']) != 0) {
            require_once api_get_path(LIBRARY_PATH).'course_category.lib.php';
            $categoryInfo = getCategory($course['CourseCategory']);
            if (empty($categoryInfo)) {
                //@todo this is so bad even all lang variables are wrong ...
                $course['error'] = get_lang('UnkownCategoryCourseCode').' ('.$course['CourseCategory'].')';
                $errors[] = $course;
            }
        }
    }
    return $errors;
}

function getTeacherListInArray($teachers)
{
    if (!empty($teachers)) {
        return explode('|', $teachers);
    }
    return array();
}

/**
 * Saves imported data.
 * @param array   List of courses
 */
function save_data($courses)
{
    $msg = '';
    foreach ($courses as $course) {
        $course_language = api_get_valid_language($course['Language']);
        $teachers = getTeacherListInArray($course['Teacher']);
        $teacherList = array();
        $creatorId = api_get_user_id();

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $teacherInfo = api_get_user_info_from_username($teacher);
                if (!empty($teacherInfo)) {
                    $teacherList[] = $teacherInfo;
                }
            }
        }

        $params = array();
        $params['title']            = $course['Title'];
        $params['wanted_code']      = $course['Code'];
        $params['tutor_name']       = null;
        $params['course_category']  = $course['CourseCategory'];
        $params['course_language']  = $course_language;
        $params['user_id']          = $creatorId;

        $course_info = CourseManager::create_course($params);

        if (!empty($course_info)) {
            if (!empty($teacherList)) {
                foreach ($teacherList as $teacher) {
                    CourseManager::add_user_to_course($teacher['user_id'], $course_info['code'], COURSEMANAGER);
                }
            }
            $msg .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/">
                    '.$course_info['title'].'</a> '.get_lang('Created').'<br />';
        }
    }

    if (!empty($msg)) {
        Display::display_normal_message($msg, false);
    }
}

/**
 * Read the CSV-file
 * @param string $file Path to the CSV-file
 * @return array All course-information read from the file
 */
function parse_csv_data($file)
{
    $courses = Import::csv_to_array($file);
    return $courses;
}

$language_file = array('admin', 'registration','create_course', 'document');

$cidReset = true;

require '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';
$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;

if (is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('ImportCourses').' CSV';

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

set_time_limit(0);
Display :: display_header($tool_name);

if ($_POST['formSent']) {
    if (empty($_FILES['import_file']['tmp_name'])) {
        $error_message = get_lang('UplUploadFailed');
        Display :: display_error_message($error_message, false);
    } else {
        $allowed_file_mimetype = array('csv');

        $ext_import_file = substr($_FILES['import_file']['name'], (strrpos($_FILES['import_file']['name'], '.') + 1));

        if (!in_array($ext_import_file, $allowed_file_mimetype)) {
            Display :: display_error_message(get_lang('YouMustImportAFileAccordingToSelectedOption'));
        } else {
            $courses = parse_csv_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($courses);
            if (count($errors) == 0) {
                save_data($courses);
            }
        }
    }
}

if (count($errors) != 0) {
    $error_message = '<ul>';
    foreach ($errors as $index => $error_course) {
        $error_message .= '<li>'.get_lang('Line').' '.$error_course['line'].': <strong>'.$error_course['error'].'</strong>: ';
        $error_message .= get_lang('Course').': '.$error_course['Title'].' ('.$error_course['Code'].')';
        $error_message .= '</li>';
    }
    $error_message .= '</ul>';
    Display :: display_error_message($error_message, false);
}
?>
<form method="post" action="<?php echo api_get_self(); ?>" enctype="multipart/form-data" style="margin: 0px;">
<legend><?php echo $tool_name; ?></legend>
<div class="control-group">
    <label><?php echo get_lang('ImportCSVFileLocation'); ?></label>
    <div class="control">
        <input type="file" name="import_file"/>
    </div>
</div>
<div class="control-group">
    <div class="control">
        <button type="submit" class="save" value="<?php echo get_lang('Import'); ?>"><?php echo get_lang('Import'); ?></button>
    </div>
</div>
<input type="hidden" name="formSent" value="1"/>
</form>
<div style="clear: both;"></div>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<strong>Code</strong>;<strong>Title</strong>;<strong>CourseCategory</strong>;Teacher;Language
BIO0015;Biology;BIO;teacher1;english
BIO0016;Maths;MATH;teacher2|teacher3;english
BIO0017;Language;LANG;;english
</pre>
</blockquote>

<?php
Display :: display_footer();

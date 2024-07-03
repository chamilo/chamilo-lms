<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;

/**
 * Script managing the learnpath upload. To best treat the uploaded file, make sure we can identify it.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
require_once __DIR__.'/../inc/global.inc.php';
api_protect_course_script();
$course_dir = api_get_course_path().'/scorm';
$course_sys_dir = api_get_path(SYS_COURSE_PATH).$course_dir;
if (empty($_POST['current_dir'])) {
    $current_dir = '';
} else {
    $current_dir = api_replace_dangerous_char(trim($_POST['current_dir']));
}
$uncompress = 1;

$allowHtaccess = false;
if (api_get_configuration_value('allow_htaccess_import_from_scorm') && isset($_POST['allow_htaccess'])) {
    $allowHtaccess = true;
}

/*
 * Check the request method in place of a variable from POST
 * because if the file size exceed the maximum file upload
 * size set in php.ini, all variables from POST are cleared !
 */
$user_file = isset($_GET['user_file']) ? $_GET['user_file'] : [];
$user_file = $user_file ? $user_file : [];
$is_error = isset($user_file['error']) ? $user_file['error'] : false;
if (isset($_POST) && $is_error) {
    Display::addFlash(
        Display::return_message(get_lang('UplFileTooBig'))
    );

    return false;
    unset($_FILES['user_file']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && count($_FILES) > 0 && !empty($_FILES['user_file']['name'])) {
    // A file upload has been detected, now deal with the file...
    // Directory creation.
    $stopping_error = false;
    $s = $_FILES['user_file']['name'];

    // Get name of the zip file without the extension.
    $info = pathinfo($s);
    $filename = $info['basename'];
    $extension = $info['extension'];
    $file_base_name = str_replace('.'.$extension, '', $filename);

    $new_dir = api_replace_dangerous_char(trim($file_base_name));
    $type = learnpath::getPackageType($_FILES['user_file']['tmp_name'], $_FILES['user_file']['name']);

    $proximity = 'local';
    if (!empty($_REQUEST['content_proximity'])) {
        $proximity = Database::escape_string($_REQUEST['content_proximity']);
    }

    $maker = 'Scorm';
    if (!empty($_REQUEST['content_maker'])) {
        $maker = Database::escape_string($_REQUEST['content_maker']);
    }

    switch ($type) {
        case 'chamilo':
            $filename = CourseArchiver::importUploadedFile($_FILES['user_file']['tmp_name']);
            if ($filename) {
                $course = CourseArchiver::readCourse($filename, false);
                $courseRestorer = new CourseRestorer($course);
                // FILE_SKIP, FILE_RENAME or FILE_OVERWRITE
                $courseRestorer->set_file_option(FILE_OVERWRITE);
                $courseRestorer->restore('', api_get_session_id());
                Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            }
            break;
        case 'scorm':
            $oScorm = new scorm();
            $manifest = $oScorm->import_package(
                $_FILES['user_file'],
                $current_dir,
                [],
                false,
                null,
                $allowHtaccess
            );
            if (!empty($manifest)) {
                $oScorm->parse_manifest($manifest);
                $oScorm->import_manifest(api_get_course_id(), $_REQUEST['use_max_score']);
                Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            }
            $oScorm->set_proximity($proximity);
            $oScorm->set_maker($maker);
            $oScorm->set_jslib('scorm_api.php');
            break;
        case 'aicc':
            $oAICC = new aicc();
            $config_dir = $oAICC->import_package($_FILES['user_file']);
            if (!empty($config_dir)) {
                $oAICC->parse_config_files($config_dir);
                $oAICC->import_aicc(api_get_course_id());
                Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            }
            $oAICC->set_proximity($proximity);
            $oAICC->set_maker($maker);
            $oAICC->set_jslib('aicc_api.php');
            break;
        case 'oogie':
            require_once 'openoffice_presentation.class.php';
            $take_slide_name = empty($_POST['take_slide_name']) ? false : true;
            $o_ppt = new OpenofficePresentation($take_slide_name);
            $first_item_id = $o_ppt->convert_document($_FILES['user_file'], 'make_lp', $_POST['slide_size']);
            Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            break;
        case 'woogie':
            require_once 'openoffice_text.class.php';
            $split_steps = (empty($_POST['split_steps']) || $_POST['split_steps'] == 'per_page') ? 'per_page' : 'per_chapter';
            $o_doc = new OpenofficeText($split_steps);
            $first_item_id = $o_doc->convert_document($_FILES['user_file']);
            Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            break;
        case '':
        default:
            Display::addFlash(Display::return_message(get_lang('ScormUnknownPackageFormat'), 'warning'));

            return false;
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' || ('bigUpload' === $_REQUEST['from'] && !empty($_REQUEST['name']))) {
    // end if is_uploaded_file
    // If file name given to get in /upload/, try importing this way.
    // A file upload has been detected, now deal with the file...
    // Directory creation.
    $stopping_error = false;

    // When it is used from bigupload input
    if ('bigUpload' === $_REQUEST['from']) {
        if (empty($_REQUEST['name'])) {
            return false;
        }
        $tempName = $_REQUEST['name'];
    } else {
        if (!isset($_POST['file_name'])) {
            return false;
        }
        $tempName = $_POST['file_name'];
    }

    // Escape path with basename so it can only be directly into the archive/ directory.
    $s = api_get_path(SYS_ARCHIVE_PATH).basename($tempName);
    // Get name of the zip file without the extension
    $info = pathinfo($s);
    $filename = $info['basename'];
    $extension = $info['extension'];
    $file_base_name = str_replace('.'.$extension, '', $filename);
    $new_dir = api_replace_dangerous_char(trim($file_base_name));

    $result = learnpath::verify_document_size($s);
    if ($result) {
        Display::addFlash(
            Display::return_message(get_lang('UplFileTooBig'))
        );
    }
    $type = learnpath::getPackageType($s, basename($s));

    switch ($type) {
        case 'scorm':
            $oScorm = new scorm();
            $manifest = $oScorm->import_local_package($s, $current_dir);
            if (!empty($manifest)) {
                $oScorm->parse_manifest($manifest);
                $oScorm->import_manifest(api_get_course_id(), $_REQUEST['use_max_score']);
                Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            }

            $proximity = '';
            if (!empty($_REQUEST['content_proximity'])) {
                $proximity = Database::escape_string($_REQUEST['content_proximity']);
            }
            $maker = '';
            if (!empty($_REQUEST['content_maker'])) {
                $maker = Database::escape_string($_REQUEST['content_maker']);
            }
            $oScorm->set_proximity($proximity);
            $oScorm->set_maker($maker);
            $oScorm->set_jslib('scorm_api.php');
            break;
        case 'aicc':
            $oAICC = new aicc();
            $config_dir = $oAICC->import_local_package($s, $current_dir);
            if (!empty($config_dir)) {
                $oAICC->parse_config_files($config_dir);
                $oAICC->import_aicc(api_get_course_id());
                Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            }
            $proximity = '';
            if (!empty($_REQUEST['content_proximity'])) {
                $proximity = Database::escape_string($_REQUEST['content_proximity']);
            }
            $maker = '';
            if (!empty($_REQUEST['content_maker'])) {
                $maker = Database::escape_string($_REQUEST['content_maker']);
            }
            $oAICC->set_proximity($proximity);
            $oAICC->set_maker($maker);
            $oAICC->set_jslib('aicc_api.php');
            break;
        case '':
        default:
            Display::addFlash(
                Display::return_message(get_lang('ScormUnknownPackageFormat'), 'warning')
            );

            return false;
            break;
    }
}

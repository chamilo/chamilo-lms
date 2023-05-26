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
if (empty($_POST['current_dir'])) {
    $current_dir = '';
} else {
    $current_dir = api_replace_dangerous_char(trim($_POST['current_dir']));
}
$uncompress = 1;

$allowHtaccess = false;
if (('true' === api_get_setting('lp.allow_htaccess_import_from_scorm')) && isset($_POST['allow_htaccess'])) {
    $allowHtaccess = true;
}

/*
 * Check the request method in place of a variable from POST
 * because if the file size exceed the maximum file upload
 * size set in php.ini, all variables from POST are cleared !
 */
$user_file = $_GET['user_file'] ?? [];
$user_file = $user_file ? $user_file : [];
$is_error = $user_file['error'] ?? false;
$em = Database::getManager();

if (isset($_POST) && $is_error) {
    Display::addFlash(
        Display::return_message(get_lang('The file is too big to upload.'))
    );

    return false;
    unset($_FILES['user_file']);
} elseif ('POST' === $_SERVER['REQUEST_METHOD'] && count($_FILES) > 0 && !empty($_FILES['user_file']['name'])) {
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
        $proximity = $_REQUEST['content_proximity'];
    }

    $maker = 'Scorm';
    if (!empty($_REQUEST['content_maker'])) {
        $maker = $_REQUEST['content_maker'];
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
                Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            }
            break;
        case 'scorm':
            $scorm = new scorm();
            $scorm->import_package(
                $_FILES['user_file'],
                $current_dir,
                [],
                false,
                null,
                $allowHtaccess
            );
            if (!empty($scorm->manifestToString)) {
                $scorm->parse_manifest();
                $lp = $scorm->import_manifest(api_get_course_int_id(), $_REQUEST['use_max_score']);
                if ($lp) {
                    $lp
                        ->setContentLocal($proximity)
                        ->setContentMaker($maker)
                    ;
                    $em->persist($lp);
                    $em->flush();
                    Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
                }
            }
            break;
        case 'aicc':
            $oAICC = new aicc();
            //$entity = $oAICC->getEntity();
            $config_dir = $oAICC->import_package($_FILES['user_file']);
            if (!empty($config_dir)) {
                $oAICC->parse_config_files($config_dir);
                $oAICC->import_aicc(api_get_course_id());
                Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            }
            /*$entity
                ->setContentLocal($proximity)
                ->setContentMaker($maker)
                ->setJsLib('aicc_api.php')
            ;
            $em->persist($entity);
            $em->flush();*/
            break;
        case 'oogie':
            $take_slide_name = empty($_POST['take_slide_name']) ? false : true;
            $o_ppt = new OpenofficePresentation($take_slide_name);
            $first_item_id = $o_ppt->convert_document($_FILES['user_file'], 'make_lp', $_POST['slide_size']);
            Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            break;
        case 'woogie':
            $split_steps = empty($_POST['split_steps']) || 'per_page' === $_POST['split_steps'] ? 'per_page' : 'per_chapter';
            $o_doc = new OpenofficeText($split_steps);
            $first_item_id = $o_doc->convert_document($_FILES['user_file']);
            Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            break;
        case '':
        default:
            Display::addFlash(Display::return_message(get_lang('Unknown package format'), 'warning'));

            return false;
            break;
    }
} elseif ('POST' === $_SERVER['REQUEST_METHOD']) {
    // end if is_uploaded_file
    // If file name given to get in /upload/, try importing this way.
    // A file upload has been detected, now deal with the file...
    // Directory creation.
    $stopping_error = false;

    if (!isset($_POST['file_name'])) {
        return false;
    }

    // Escape path with basename so it can only be directly into the archive/ directory.
    $s = api_get_path(SYS_ARCHIVE_PATH).basename($_POST['file_name']);
    // Get name of the zip file without the extension
    $info = pathinfo($s);
    $filename = $info['basename'];
    $extension = $info['extension'];
    $file_base_name = str_replace('.'.$extension, '', $filename);
    $new_dir = api_replace_dangerous_char(trim($file_base_name));

    $result = learnpath::verify_document_size($s);
    if ($result) {
        Display::addFlash(
            Display::return_message(get_lang('The file is too big to upload.'))
        );
    }
    $type = learnpath::getPackageType($s, basename($s));

    switch ($type) {
        case 'scorm':
            $oScorm = new scorm();
            $entity = $oScorm->getEntity();
            $manifest = $oScorm->import_local_package($s, $current_dir);
            if (!empty($manifest)) {
                $oScorm->parse_manifest();
                $oScorm->import_manifest(api_get_course_int_id(), $_REQUEST['use_max_score']);
                Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            }

            $proximity = '';
            if (!empty($_REQUEST['content_proximity'])) {
                $proximity = Database::escape_string($_REQUEST['content_proximity']);
            }
            $maker = '';
            if (!empty($_REQUEST['content_maker'])) {
                $maker = Database::escape_string($_REQUEST['content_maker']);
            }

            $entity
                ->setContentLocal($proximity)
                ->setContentMaker($maker)
                ->setJsLib('scorm_api.php')
            ;
            $em->persist($entity);
            $em->flush();
            break;
        case 'aicc':
            $oAICC = new aicc();
            $entity = $oAICC->getEntity();
            $config_dir = $oAICC->import_local_package($s, $current_dir);
            if (!empty($config_dir)) {
                $oAICC->parse_config_files($config_dir);
                $oAICC->import_aicc(api_get_course_id());
                Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            }
            $proximity = '';
            if (!empty($_REQUEST['content_proximity'])) {
                $proximity = $_REQUEST['content_proximity'];
            }
            $maker = '';
            if (!empty($_REQUEST['content_maker'])) {
                $maker = $_REQUEST['content_maker'];
            }

            $entity
                ->setContentLocal($proximity)
                ->setContentMaker($maker)
                ->setJsLib('aicc_api.php')
            ;
            $em->persist($entity);
            $em->flush();
            break;
        case '':
        default:
            Display::addFlash(
                Display::return_message(get_lang('Unknown package format'), 'warning')
            );

            return false;
            break;
    }
}

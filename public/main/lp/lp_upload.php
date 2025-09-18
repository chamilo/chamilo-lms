<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
 * Check the request method instead of relying on POST variables,
 * because if the uploaded file exceeds php.ini limits, POST is cleared.
 */
$user_file = $_FILES['user_file'] ?? [];
$is_error  = $user_file['error'] ?? false;
$em        = Database::getManager();

if (isset($_POST) && $is_error) {
    // Redirect to the upload screen with an error
    unset($_FILES['user_file']);
    ChamiloHelper::redirectTo(api_get_path(WEB_PATH).'main/upload/index.php?'.api_get_cidreq().'&origin=course&curdirpath=/&tool=learnpath');
}
elseif ('POST' === $_SERVER['REQUEST_METHOD']
    && !empty($_FILES['user_file']['name'])
) {
    // A file upload has been detected, now handle it...
    $s = $_FILES['user_file']['name'];

    // Derive filename info
    $info           = pathinfo($s);
    $filename       = $info['basename'];
    $extension      = $info['extension'] ?? '';
    $file_base_name = str_replace('.'.$extension, '', $filename);

    $new_dir = api_replace_dangerous_char(trim($file_base_name));
    $type    = learnpath::getPackageType(
        $_FILES['user_file']['tmp_name'],
        $_FILES['user_file']['name']
    );

    // Defaults
    $proximity = $_REQUEST['content_proximity'] ?? 'local';
    $maker     = $_REQUEST['content_maker']     ?? 'Scorm';

    switch ($type) {
        case 'chamilo':
            $filename = CourseArchiver::importUploadedFile($_FILES['user_file']['tmp_name']);
            if ($filename) {
                $course         = CourseArchiver::readCourse($filename, false);
                $courseRestorer = new CourseRestorer($course);
                $courseRestorer->set_file_option(FILE_OVERWRITE);
                $courseRestorer->restore('', api_get_session_id());
                Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            }
            break;
        case 'scorm':
            $tmpFile = $_FILES['user_file']['tmp_name'];
            $blocked = learnpath::verify_document_size($tmpFile);
            if ($blocked) {
                ChamiloHelper::redirectTo(api_get_path(WEB_PATH).'main/upload/index.php?'.api_get_cidreq().'&origin=course&curdirpath=/&tool=learnpath');
            }
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
                    $lp->setContentLocal($proximity)->setContentMaker($maker);
                    $em->persist($lp);
                    $em->flush();

                    /** @var CDocumentRepository $docRepo */
                    $docRepo = $em->getRepository(CDocument::class);

                    /** @var Session|null $session */
                    $session = api_get_session_entity();

                    $uploadedZip = new UploadedFile(
                        $_FILES['user_file']['tmp_name'],
                        $_FILES['user_file']['name'],
                        $_FILES['user_file']['type'] ?? null,
                        $_FILES['user_file']['error'] ?? 0,
                        true
                    );

                    // Save under Documents / Learning paths (course/session aware)
                    $docRepo->registerScormZip(api_get_course_entity(), $session, $lp, $uploadedZip);

                    Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
                }
            }
            break;
        case 'aicc':
            $oAICC = new aicc();
            $config_dir = $oAICC->import_package($_FILES['user_file']);
            if (!empty($config_dir)) {
                $oAICC->parse_config_files($config_dir);
                $oAICC->import_aicc(api_get_course_id());
                Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            }
            break;
        case 'oogie':
            $take_slide_name = !empty($_POST['take_slide_name']);
            $o_ppt = new OpenofficePresentation($take_slide_name);
            $o_ppt->convert_document($_FILES['user_file'], 'make_lp', $_POST['slide_size']);
            Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            break;
        case 'woogie':
            $split_steps = (!empty($_POST['split_steps']) && $_POST['split_steps'] === 'per_chapter')
                ? 'per_chapter'
                : 'per_page';
            $o_doc = new OpenofficeText($split_steps);
            $o_doc->convert_document($_FILES['user_file']);
            Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
            break;
        case '':
        default:
            Display::addFlash(Display::return_message(get_lang('Unknown package format'), 'warning'));

            return false;
    }
} elseif ('POST' === $_SERVER['REQUEST_METHOD']) {
    // Fallback: import from an existing file in /upload/ (no $_FILES)

    if (!isset($_POST['file_name'])) {
        return false;
    }

    // Escape path to ensure it only targets /archive/
    $s = api_get_path(SYS_ARCHIVE_PATH).basename($_POST['file_name']);

    // Derive filename info
    $info           = pathinfo($s);
    $filename       = $info['basename'];
    $extension      = $info['extension'] ?? '';
    $file_base_name = str_replace('.'.$extension, '', $filename);
    $new_dir        = api_replace_dangerous_char(trim($file_base_name));

    $result = learnpath::verify_document_size($s);
    if ($result) {
        Display::addFlash(Display::return_message(get_lang('The file is too big to upload.')));
    }
    $type = learnpath::getPackageType($s, basename($s));

    switch ($type) {
        case 'scorm':
            $oScorm = new scorm();

            // Import from local path
            $manifest = $oScorm->import_local_package($s, $current_dir);

            // Make a tmp copy of the ZIP (so we can register it in Documents after unlink)
            $uploadedZip = null;
            if (is_file($s)) {
                $tmpCopy = tempnam(sys_get_temp_dir(), 'scorm_zip_');
                @copy($s, $tmpCopy);
                $uploadedZip = new UploadedFile(
                    $tmpCopy,
                    basename($s),
                    'application/zip',
                    null,
                    true
                );
            }

            // Clean original file from /archive
            if (is_file($s)) {
                unlink($s);
            }

            if (!empty($manifest)) {
                $oScorm->parse_manifest();

                // Create the LP entity (CLp)
                $lp = $oScorm->import_manifest(api_get_course_int_id(), $_REQUEST['use_max_score'] ?? 1);

                if ($lp) {
                    /** @var CDocumentRepository $docRepo */
                    $docRepo = $em->getRepository(CDocument::class);

                    /** @var Session|null $session */
                    $session = api_get_session_entity();

                    // Save under Documents / Learning paths (course/session aware)
                    $docRepo->registerScormZip(api_get_course_entity(), $session, $lp, $uploadedZip);
                    Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
                }
            }
            break;
        case 'aicc':
            $oAICC  = new aicc();
            $entity = $oAICC->getEntity();
            $config_dir = $oAICC->import_local_package($s, $current_dir);

            if (is_file($s)) {
                unlink($s);
            }

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
            // Unknown format: cleanup and warn
            if (is_file($s)) {
                unlink($s);
            }
            Display::addFlash(
                Display::return_message(get_lang('Unknown package format'), 'warning')
            );
            return false;
    }
}

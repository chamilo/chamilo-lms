<?php
/* For licensing terms, see /license.txt */
/**
 * Script managing the learnpath upload. To best treat the uploaded file, make sure we can identify it.
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once '../inc/global.inc.php';
$course_dir = api_get_course_path().'/scorm';
$course_sys_dir = api_get_path(SYS_COURSE_PATH).$course_dir;
if (empty($_POST['current_dir'])) {
    $current_dir = '';
} else {
    $current_dir = api_replace_dangerous_char(trim($_POST['current_dir']));
}
$uncompress = 1;

/*
 * Check the request method in place of a variable from POST
 * because if the file size exceed the maximum file upload
 * size set in php.ini, all variables from POST are cleared !
 */
$user_file = isset($_GET['user_file']) ? $_GET['user_file'] : array();
$user_file = $user_file ? $user_file : array();
$is_error = isset($user_file['error']) ? $user_file['error'] : false;
if (isset($_POST) && $is_error) {
    Display::addFlash(
        Display::return_message(get_lang('UplFileTooBig'))
    );
    return false;
    unset($_FILES['user_file']);
} else if ($_SERVER['REQUEST_METHOD'] == 'POST' && count($_FILES) > 0 && !empty($_FILES['user_file']['name'])) {

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
    $type = learnpath::get_package_type($_FILES['user_file']['tmp_name'], $_FILES['user_file']['name']);

    $proximity = 'local';
    if (!empty($_REQUEST['content_proximity'])) {
        $proximity = Database::escape_string($_REQUEST['content_proximity']);
    }
    $maker = 'Scorm';
    if (!empty($_REQUEST['content_maker'])) {
        $maker = Database::escape_string($_REQUEST['content_maker']);
    }

    switch ($type) {
        case 'scorm':
            $oScorm = new scorm();
            $manifest = $oScorm->import_package($_FILES['user_file'], $current_dir);
            if (!empty($manifest)) {
                $oScorm->parse_manifest($manifest);
                $fixTemplate = api_get_configuration_value('learnpath_fix_xerte_template');
                $proxyPath = api_get_configuration_value('learnpath_proxy_url');
                if ($fixTemplate && !empty($proxyPath)) {
                    // Check organisations:
                    if (isset($oScorm->manifest['organizations'])) {
                        foreach ($oScorm->manifest['organizations'] as $data) {
                            if (strpos(strtolower($data), 'xerte') !== false) {
                                // Check if template.xml exists:
                                $templatePath = str_replace('imsmanifest.xml', 'template.xml', $manifest);
                                if (file_exists($templatePath) && is_file($templatePath)) {
                                    $templateContent = file_get_contents($templatePath);

                                    $find = array(
                                        'href="www.',
                                        'href="https://',
                                        'href="http://',
                                        'url="www.',
                                        'pdfs/download.php?'
                                    );

                                    $replace = array(
                                        'href="http://www.',
                                        'target = "_blank" href="'.$proxyPath.'?type=link&src=https://',
                                        'target = "_blank" href="'.$proxyPath.'?type=link&src=http://',
                                        'url="http://www.',
                                        'pdfs/download.php&'
                                    );
                                    $templateContent = str_replace($find, $replace, $templateContent);
                                    file_put_contents($templatePath, $templateContent);
                                }

                                // Fix link generation:
                                $linkPath = str_replace('imsmanifest.xml', 'models_html5/links.html', $manifest);

                                if (file_exists($linkPath) && is_file($linkPath)) {
                                    $linkContent = file_get_contents($linkPath);
                                    $find = array(
                                        ':this.getAttribute("url")'
                                    );
                                    $replace = array(
                                        ':"'.$proxyPath.'?type=link&src=" + this.getAttribute("url")'
                                    );
                                    $linkContent = str_replace($find, $replace, $linkContent);
                                    file_put_contents($linkPath, $linkContent);
                                }

                                // Fix iframe generation
                                $framePath = str_replace('imsmanifest.xml', 'models_html5/embedDiv.html', $manifest);

                                if (file_exists($framePath) && is_file($framePath)) {
                                    $content = file_get_contents($framePath);
                                    $find = array(
                                        '$iFrameHolder.html(iFrameTag);'
                                    );
                                    $replace = array(
                                        'iFrameTag = \'<a target ="_blank" href="'.$proxyPath.'?type=link&src=\'+ pageSrc + \'">Open website. <img src="'.api_get_path(WEB_CODE_PATH).'img/link-external.png"></a>\'; $iFrameHolder.html(iFrameTag); '
                                    );
                                    $content = str_replace($find, $replace, $content);
                                    file_put_contents($framePath, $content);
                                }

                                // Fix new window generation
                                $newWindowPath = str_replace('imsmanifest.xml', 'models_html5/newWindow.html', $manifest);

                                if (file_exists($newWindowPath) && is_file($newWindowPath)) {
                                    $content = file_get_contents($newWindowPath);
                                    $find = array(
                                        'var src = x_currentPageXML'
                                    );
                                    $replace = array(
                                        'var src = "'.$proxyPath.'?type=link&src=" + x_currentPageXML'
                                    );
                                    $content = str_replace($find, $replace, $content);
                                    file_put_contents($newWindowPath, $content);
                                }
                            }
                        }
                    }
                }

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
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    if ($result == true) {
        Display::addFlash(
            Display::return_message(get_lang('UplFileTooBig'))
        );
    }
    $type = learnpath::get_package_type($s, basename($s));

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

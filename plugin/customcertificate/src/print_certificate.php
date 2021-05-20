<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;

$default = isset($_GET['default']) ? (int) $_GET['default'] : null;

if ($default === 1) {
    $cidReset = true;
}

$course_plugin = 'customcertificate';
require_once __DIR__.'/../config.php';

api_block_anonymous_users();
$plugin = CustomCertificatePlugin::create();
$enable = $plugin->get('enable_plugin_customcertificate') == 'true';
$tblProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
$categoryId = isset($_GET['cat_id']) ? (int) $_GET['cat_id'] : 0;

if (!$enable) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

if ($default == 1) {
    $courseId = 0;
    $courseCode = '';
    $sessionId = 0;
    $enableCourse = false;
    $useDefault = true;
} else {
    $courseId = api_get_course_int_id();
    $courseCode = api_get_course_id();
    $sessionId = api_get_session_id();
    $enableCourse = api_get_course_setting('customcertificate_course_enable') == 1 ? true : false;
    $useDefault = api_get_course_setting('use_certificate_default') == 1 ? true : false;
}

if (empty($courseCode)) {
    $courseCode = isset($_REQUEST['course_code']) ? Database::escape_string($_REQUEST['course_code']) : '';
    $courseInfo = api_get_course_info($courseCode);
    if (!empty($courseInfo)) {
        $courseId = $courseInfo['real_id'];
    }
} else {
    $courseInfo = api_get_course_info($courseCode);
}

if (empty($sessionId)) {
    $sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;
}

$accessUrlId = api_get_current_access_url_id();

$userList = [];
$exportZip = false;
$exportAllInOne = false;
if (empty($_GET['export_all']) && empty($_GET['export_all_in_one'])) {
    if (!isset($_GET['student_id'])) {
        $studentId = api_get_user_id();
    } else {
        $studentId = intval($_GET['student_id']);
    }
    $userList[] = api_get_user_info($studentId);
} else {
    if (!empty($_GET['export_all'])) {
        $exportZip = true;
    }
    if (!empty($_GET['export_all_in_one'])) {
        $exportAllInOne = true;
    }
    $certificate_list = GradebookUtils::get_list_users_certificates($categoryId);
    foreach ($certificate_list as $index => $value) {
        $userList[] = api_get_user_info($value['user_id']);
    }
}

$sessionInfo = [];
if ($sessionId > 0) {
    $sessionInfo = SessionManager::fetch($sessionId);
}

$table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
$useDefault = false;
$path = api_get_path(WEB_UPLOAD_PATH).'certificates/';

// Get info certificate
$infoCertificate = CustomCertificatePlugin::getInfoCertificate($courseId, $sessionId, $accessUrlId);

if (!is_array($infoCertificate)) {
    $infoCertificate = [];
}

if (empty($infoCertificate)) {
    $infoCertificate = CustomCertificatePlugin::getInfoCertificateDefault($accessUrlId);

    if (empty($infoCertificate)) {
        Display::display_header($plugin->get_lang('PrintCertificate'));
        echo Display::return_message($plugin->get_lang('ErrorTemplateCertificate'), 'error');
        Display::display_footer();
        exit;
    } else {
        $useDefault = true;
    }
}

$workSpace = intval(297 - $infoCertificate['margin_left'] - $infoCertificate['margin_right']);
$widthCell = intval($workSpace / 6);
$htmlList = [];

$currentLocalTime = api_get_local_time();

foreach ($userList as $userInfo) {
    $htmlText = '';
    if (!$exportAllInOne) {
        $htmlText = '<html>';
        $htmlText .= '
        <link rel="stylesheet"
            type="text/css"
            href="'.api_get_path(WEB_PLUGIN_PATH).'customcertificate/resources/css/certificate.css">';
        $htmlText .= '
        <link rel="stylesheet"
            type="text/css"
            href="'.api_get_path(WEB_CSS_PATH).'document.css">';
        $htmlText .= '<body>';
    }
    $studentId = $userInfo['user_id'];

    if (empty($infoCertificate['background'])) {
        $htmlText .= '<div class="caraA" style="page-break-before:always; margin:0px; padding:0px;">';
    } else {
        $urlBackground = $path.$infoCertificate['background'];
        $htmlText .= ' <div
        class="caraA"
        style="background-image:url('.$urlBackground.') no-repeat;
        background-image-resize:6; margin:0px; padding:0px;">';
    }

    if (!empty($infoCertificate['logo_left'])) {
        $logoLeft = '
            <img
                style="max-height: 150px; max-width: '.(2 * $widthCell).'mm;"
                src="'.$path.$infoCertificate['logo_left'].'" />';
    } else {
        $logoLeft = '';
    }

    $logoCenter = '';
    if (!empty($infoCertificate['logo_center'])) {
        $logoCenter = '
            <img
                style="max-height: 150px; max-width: '.intval($workSpace - (2 * $widthCell)).'mm;"
                src="'.$path.$infoCertificate['logo_center'].'" />';
    }

    $logoRight = '';
    if (!empty($infoCertificate['logo_right'])) {
        $logoRight = '
            <img
                style="max-height: 150px; max-width: '.(2 * $widthCell).'mm;"
                src="'.$path.$infoCertificate['logo_right'].'" />';
    }

    $htmlText .= '<table
        width="'.$workSpace.'mm"
        style="
            margin-left:'.$infoCertificate['margin_left'].'mm;
            margin-right:'.$infoCertificate['margin_right'].'mm;
        "
        border="0">';
    $htmlText .= '<tr>';
    $htmlText .= '<td style="width:'.intval($workSpace / 3).'mm" class="logo">'.$logoLeft.'</td>';
    $htmlText .= '<td style="width:'.intval($workSpace / 3).'mm; text-align:center;" class="logo">';
    $htmlText .= $logoCenter;
    $htmlText .= '</td>';
    $htmlText .= '<td style="width:'.intval($workSpace / 3).'mm; text-align:right;" class="logo">'.$logoRight.'</td>';
    $htmlText .= '</tr>';
    $htmlText .= '</table>';

    $myContentHtml = $infoCertificate['content_course'];
    $myContentHtml = str_replace(chr(13).chr(10).chr(13).chr(10), chr(13).chr(10), $myContentHtml);

    if (!empty($courseCode)) {
        $allUserInfo = DocumentManager::get_all_info_to_certificate(
            $studentId,
            $courseCode,
            $sessionId,
            false
        );

        $infoToBeReplacedInContentHtml = $allUserInfo[0];
        $infoToReplaceInContentHtml = $allUserInfo[1];
        $myContentHtml = str_replace(
            $infoToBeReplacedInContentHtml,
            $infoToReplaceInContentHtml,
            $myContentHtml
        );
    }

    $startDate = '';
    $endDate = '';
    switch ($infoCertificate['date_change']) {
        case 0:
            if (!empty($sessionInfo['access_start_date'])) {
                $startDate = date("d/m/Y", strtotime(api_get_local_time($sessionInfo['access_start_date'])));
            }
            if (!empty($sessionInfo['access_end_date'])) {
                $endDate = date("d/m/Y", strtotime(api_get_local_time($sessionInfo['access_end_date'])));
            }
            break;
        case 1:
            $startDate = date("d/m/Y", strtotime($infoCertificate['date_start']));
            $endDate = date("d/m/Y", strtotime($infoCertificate['date_end']));
            break;
    }

    $myContentHtml = str_replace(
        '((start_date))',
        $startDate,
        $myContentHtml
    );

    $myContentHtml = str_replace(
        '((end_date))',
        $endDate,
        $myContentHtml
    );

    $dateExpediction = '';
    if ($infoCertificate['type_date_expediction'] != 3) {
        $dateExpediction .= $plugin->get_lang('ExpedictionIn').' '.$infoCertificate['place'];
        if ($infoCertificate['type_date_expediction'] == 1) {
            $dateExpediction .= $plugin->get_lang('to').api_format_date(time(), DATE_FORMAT_LONG);
        } elseif ($infoCertificate['type_date_expediction'] == 2) {
            $dateFormat = $plugin->get_lang('formatDownloadDate');
            if (!empty($infoCertificate['day']) &&
                !empty($infoCertificate['month']) &&
                !empty($infoCertificate['year'])
            ) {
                $dateExpediction .= sprintf(
                    $dateFormat,
                    $infoCertificate['day'],
                    $infoCertificate['month'],
                    $infoCertificate['year']
                );
            } else {
                $dateExpediction .= sprintf(
                    $dateFormat,
                    '......',
                    '....................',
                    '............'
                );
            }
        } elseif ($infoCertificate['type_date_expediction'] == 4) {
            $dateExpediction .= $plugin->get_lang('to').$infoToReplaceInContentHtml[9]; //date_certificate_no_time
        } else {
            if (!empty($sessionInfo)) {
                $dateInfo = api_get_local_time($sessionInfo['access_end_date']);
                $dateExpediction .= $plugin->get_lang('to').api_format_date($dateInfo, DATE_FORMAT_LONG);
            }
        }
    }

    $myContentHtml = str_replace(
        '((date_expediction))',
        $dateExpediction,
        $myContentHtml
    );

    $myContentHtml = strip_tags(
        $myContentHtml,
        '<p><b><strong><table><tr><td><th><tbody><span><i><li><ol><ul>
        <dd><dt><dl><br><hr><img><a><div><h1><h2><h3><h4><h5><h6>'
    );

    $htmlText .= '<div style="
            height: 480px;
            width:'.$workSpace.'mm;
            margin-left:'.$infoCertificate['margin_left'].'mm;
            margin-right:'.$infoCertificate['margin_right'].'mm;
        ">';
    $htmlText .= $myContentHtml;
    $htmlText .= '</div>';

    $htmlText .= '<table
        width="'.$workSpace.'mm"
        style="
            margin-left:'.$infoCertificate['margin_left'].'mm;
            margin-right:'.$infoCertificate['margin_right'].'mm;
        "
        border="0">';

    $htmlText .= '<tr>';
    $htmlText .= '<td colspan="2" class="seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature_text1'])) ? $infoCertificate['signature_text1'] : '').
                '</td>
                <td colspan="2" class="seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature_text2'])) ? $infoCertificate['signature_text2'] : '').
                '</td>
                <td colspan="2" class="seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature_text3'])) ? $infoCertificate['signature_text3'] : '').
                '</td>
                <td colspan="2" class="seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature_text4'])) ? $infoCertificate['signature_text4'] : '').
                '</td>
                <td colspan="4" class="seals" style="width:'.(2 * $widthCell).'mm">
                    '.((!empty($infoCertificate['seal'])) ? $plugin->get_lang('Seal') : '').
                '</td>';
    $htmlText .= '</tr>';
    $htmlText .= '<tr>';
    $htmlText .= '<td colspan="2" class="logo-seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature1']))
                ? '<img style="max-height: 100px; max-width: '.$widthCell.'mm;"
                    src="'.$path.$infoCertificate['signature1'].'" />'
                : '').
                '</td>
                <td colspan="2" class="logo-seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature2']))
                ? '<img style="max-height: 100px; '.$widthCell.'mm;"
                    src="'.$path.$infoCertificate['signature2'].'" />'
                : '').
                '</td>
                <td colspan="2" class="logo-seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature3']))
                ? '<img style="max-height: 100px; '.$widthCell.'mm;"
                    src="'.$path.$infoCertificate['signature3'].'" />'
                : '').
                '</td>
                <td colspan="2" class="logo-seals" style="width:'.$widthCell.'mm">'.
                ((!empty($infoCertificate['signature4']))
                ? '<img style="max-height: 100px; '.$widthCell.'mm;"
                    src="'.$path.$infoCertificate['signature4'].'" />'
                : '').
                '</td>
                <td colspan="4" class="logo-seals" style="width:'.(2 * $widthCell).'mm">'.
                ((!empty($infoCertificate['seal']))
                ? '<img style="max-height: 100px; '.(2 * $widthCell).'mm;"
                    src="'.$path.$infoCertificate['seal'].'" />'
                : '').
                '</td>';
    $htmlText .= '</tr>';
    $htmlText .= '</table>';
    $htmlText .= '</div>';

    // Rear certificate
    if ($infoCertificate['contents_type'] != 3) {
        $htmlText .= '<div class="caraB" style="page-break-before:always; margin:0px; padding:0px;">';
        if ($infoCertificate['contents_type'] == 0) {
            $courseDescription = new CourseDescription();
            $contentDescription = $courseDescription->get_data_by_description_type(3, $courseId, 0);
            $domd = new DOMDocument();
            libxml_use_internal_errors(true);
            if (isset($contentDescription['description_content'])) {
                $domd->loadHTML($contentDescription['description_content']);
            }
            libxml_use_internal_errors(false);
            $domx = new DOMXPath($domd);
            $items = $domx->query("//li[@style]");
            foreach ($items as $item) {
                $item->removeAttribute("style");
            }

            $items = $domx->query("//span[@style]");
            foreach ($items as $item) {
                $item->removeAttribute("style");
            }

            $output = $domd->saveHTML();
            $htmlText .= getIndexFiltered($output);
        }

        if ($infoCertificate['contents_type'] == 1) {
            $items = [];
            $categoriesTempList = learnpath::getCategories($courseId);
            $categoryTest = new CLpCategory();
            $categoryTest->setId(0);
            $categoryTest->setName($plugin->get_lang('WithOutCategory'));
            $categoryTest->setPosition(0);
            $categories = [$categoryTest];

            if (!empty($categoriesTempList)) {
                $categories = array_merge($categories, $categoriesTempList);
            }

            foreach ($categories as $item) {
                $categoryId = $item->getId();

                if (!learnpath::categoryIsVisibleForStudent($item, api_get_user_entity($studentId))) {
                    continue;
                }

                $sql = "SELECT 1
                        FROM $tblProperty
                        WHERE tool = 'learnpath_category'
                        AND ref = $categoryId
                        AND visibility = 0
                        AND (session_id = $sessionId OR session_id IS NULL)";
                $res = Database::query($sql);
                if (Database::num_rows($res) > 0) {
                    continue;
                }

                $list = new LearnpathList(
                    $studentId,
                    $courseCode,
                    $sessionId,
                    null,
                    false,
                    $categoryId
                );

                $flat_list = $list->get_flat_list();

                if (empty($flat_list)) {
                    continue;
                }

                if (count($categories) > 1 && count($flat_list) > 0) {
                    if ($item->getName() != $plugin->get_lang('WithOutCategory')) {
                        $items[] = '<h4 style="margin:0">'.$item->getName().'</h4>';
                    }
                }

                foreach ($flat_list as $learnpath) {
                    $lpId = $learnpath['lp_old_id'];
                    $sql = "SELECT 1
                            FROM $tblProperty
                            WHERE tool = 'learnpath'
                            AND ref = $lpId AND visibility = 0
                            AND (session_id = $sessionId OR session_id IS NULL)";
                    $res = Database::query($sql);
                    if (Database::num_rows($res) > 0) {
                        continue;
                    }
                    $lpName = $learnpath['lp_name'];
                    $items[] = $lpName.'<br>';
                }
                $items[] = '<br>';
            }

            if (count($items) > 0) {
                $htmlText .= '<table width="100%" class="contents-learnpath">';
                $htmlText .= '<tr>';
                $htmlText .= '<td>';
                $i = 0;
                foreach ($items as $value) {
                    if ($i == 50) {
                        $htmlText .= '</td><td>';
                    }
                    $htmlText .= $value;
                    $i++;
                }
                $htmlText .= '</td>';
                $htmlText .= '</tr>';
                $htmlText .= '</table>';
            }
            $htmlText .= '</td></table>';
        }

        if ($infoCertificate['contents_type'] == 2) {
            $htmlText .= '<table width="100%" class="contents-learnpath">';
            $htmlText .= '<tr>';
            $htmlText .= '<td>';
            $myContentHtml = strip_tags(
                $infoCertificate['contents'],
                '<p><b><strong><table><tr><td><th><span><i><li><ol><ul>'.
                '<dd><dt><dl><br><hr><img><a><div><h1><h2><h3><h4><h5><h6>'
            );
            $htmlText .= $myContentHtml;
            $htmlText .= '</td>';
            $htmlText .= '</tr>';
            $htmlText .= '</table>';
        }
        $htmlText .= '</div>';
    }

    if (!$exportAllInOne) {
        $htmlText .= '</body></html>';
    }
    $fileName = 'certificate_'.$courseInfo['code'].'_'.$userInfo['complete_name'].'_'.$currentLocalTime;
    $htmlList[$fileName] = $htmlText;
}

$fileList = [];
$archivePath = api_get_path(SYS_ARCHIVE_PATH).'certificates/';
if (!is_dir($archivePath)) {
    mkdir($archivePath, api_get_permissions_for_new_directories());
}

if ($exportAllInOne) {
    $params = [
        'pdf_title' => 'Certificate',
        'pdf_description' => '',
        'format' => 'A4-L',
        'orientation' => 'L',
        'left' => 15,
        'top' => 15,
        'bottom' => 0,
    ];
    $pdf = new PDF($params['format'], $params['orientation'], $params);

    $contentAllCertificate = '';
    foreach ($htmlList as $fileName => $content) {
        $contentAllCertificate .= $content;
    }

    if (!empty($contentAllCertificate)) {
        $certificateContent = '<html>';
        $certificateContent .= '
        <link rel="stylesheet"
            type="text/css"
            href="'.api_get_path(WEB_PLUGIN_PATH).'customcertificate/resources/css/certificate.css">';
        $certificateContent .= '
        <link rel="stylesheet"
            type="text/css"
            href="'.api_get_path(WEB_CSS_PATH).'document.css">';
        $certificateContent .= '<body>';
        $certificateContent .= $contentAllCertificate;
        $certificateContent .= '</body></html>';

        $pdf->content_to_pdf(
            $certificateContent,
            '',
            'certificate'.date("Y_m_d_His"),
            null,
            'D',
            false,
            null,
            false,
            false,
            false
        );
    }
} else {
    foreach ($htmlList as $fileName => $content) {
        $fileName = api_replace_dangerous_char($fileName);
        $params = [
            'filename' => $fileName,
            'pdf_title' => 'Certificate',
            'pdf_description' => '',
            'format' => 'A4-L',
            'orientation' => 'L',
            'left' => 15,
            'top' => 15,
            'bottom' => 0,
        ];
        $pdf = new PDF($params['format'], $params['orientation'], $params);
        if ($exportZip) {
            $filePath = $archivePath.$fileName.'.pdf';
            $pdf->content_to_pdf($content, '', $fileName, null, 'F', true, $filePath, false, false, false);
            $fileList[] = $filePath;
        } else {
            $pdf->content_to_pdf($content, '', $fileName, null, 'D', false, null, false, false, false);
        }
    }

    if (!empty($fileList)) {
        $zipFile = $archivePath.'certificates_'.api_get_unique_id().'.zip';
        $zipFolder = new PclZip($zipFile);
        foreach ($fileList as $file) {
            $zipFolder->add($file, PCLZIP_OPT_REMOVE_ALL_PATH);
        }
        $name = 'certificates_'.$courseInfo['code'].'_'.$currentLocalTime.'.zip';
        DocumentManager::file_send_for_download($zipFile, true, $name);
        exit;
    }
}

function getIndexFiltered($index)
{
    $txt = strip_tags($index, "<b><strong><i>");
    $txt = str_replace(chr(13).chr(10).chr(13).chr(10), chr(13).chr(10), $txt);
    $lines = explode(chr(13).chr(10), $txt);
    $text1 = '';
    for ($x = 0; $x < 47; $x++) {
        if (isset($lines[$x])) {
            $text1 .= $lines[$x].chr(13).chr(10);
        }
    }

    $text2 = '';
    for ($x = 47; $x < 94; $x++) {
        if (isset($lines[$x])) {
            $text2 .= $lines[$x].chr(13).chr(10);
        }
    }

    $showLeft = str_replace(chr(13).chr(10), "<br/>", $text1);
    $showRight = str_replace(chr(13).chr(10), "<br/>", $text2);
    $result = '<table width="100%">';
    $result .= '<tr>';
    $result .= '<td style="width:50%;vertical-align:top;padding-left:15px; font-size:12px;">'.$showLeft.'</td>';
    $result .= '<td style="vertical-align:top; font-size:12px;">'.$showRight.'</td>';
    $result .= '<tr>';
    $result .= '</table>';

    return $result;
}

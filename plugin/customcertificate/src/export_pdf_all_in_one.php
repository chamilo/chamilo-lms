<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;

$course_plugin = 'customcertificate';
require_once __DIR__.'/../config.php';

api_block_anonymous_users();

/** @var CustomCertificatePlugin $plugin */
$plugin = CustomCertificatePlugin::create();
$enable = $plugin->get('enable_plugin_customcertificate') === 'true';
$tblProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
$tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tblSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tblSessionRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

define('NO_DATE_FILTER', 0);
define('DATE_BEGIN_FILTER', 1);
define('DATE_END_FILTER', 2);
define('ALL_DATE_FILTER', 3);

if (!$enable) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

$currentLocalTime = api_get_local_time();
$accessUrlId = api_get_current_access_url_id();

/*
 * Support single or multiple session_id parameters:
 * - session_id=3
 * - session_id[0]=3&session_id[1]=5
 */
$sessionIds = [];
if (isset($_GET['session_id'])) {
    if (is_array($_GET['session_id'])) {
        foreach ($_GET['session_id'] as $rawId) {
            $id = (int) $rawId;
            if ($id > 0) {
                $sessionIds[] = $id;
            }
        }
    } else {
        $id = (int) $_GET['session_id'];
        if ($id > 0) {
            $sessionIds[] = $id;
        }
    }
}

// Remove duplicates just in case.
$sessionIds = array_values(array_unique($sessionIds));

if (empty($sessionIds)) {
    // No session selected: nothing to export.
    Display::display_header($plugin->get_lang('PrintCertificate'));
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
    Display::display_footer();
    exit;
}

// Date filters (same logic as session_filter.php)
$dateBeginRaw = isset($_GET['date_begin']) ? $_GET['date_begin'] : null;
$dateEndRaw = isset($_GET['date_end']) ? $_GET['date_end'] : null;

$dateBegin = !empty($dateBeginRaw) ? strtotime($dateBeginRaw) : null;
$dateEnd = !empty($dateEndRaw) ? strtotime($dateEndRaw.' 23:59:59') : null;

$filterDate = 0;
if (!empty($dateBegin)) {
    $filterDate += DATE_BEGIN_FILTER;
}
if (!empty($dateEnd)) {
    $filterDate += DATE_END_FILTER;
}

// Multi-URL protection: every session must belong to the current URL.
if (api_is_multiple_url_enabled() && $accessUrlId != -1) {
    foreach ($sessionIds as $sessionId) {
        $result = Database::select(
            '*',
            $tblSessionRelAccessUrl,
            [
                'where' => [
                    'access_url_id = ? AND session_id = ?' => [$accessUrlId, $sessionId],
                ],
            ]
        );

        if (empty($result)) {
            api_not_allowed();
        }
    }
}

$exportAllInOne = isset($_GET['export_pdf']) ? (int) $_GET['export_pdf'] : false;
$exportZip = isset($_GET['export_zip']) ? (int) $_GET['export_zip'] : false;

// Build extra fields filter (same approach as session_filter.php).
$filterCheckList = [];
$extraField = new ExtraField('user');
$extraFieldsAll = $extraField->get_all(['filter = ?' => 1], 'option_order');
foreach ($extraFieldsAll as $field) {
    $paramName = 'extra_'.$field['variable'];
    if (!empty($_GET[$paramName])) {
        $filterCheckList[$field['id']] = $field;
    }
}

// Collect all certificates from all selected sessions and their courses (same logic as session_filter.php).
$certificateList = [];

foreach ($sessionIds as $sessionId) {
    $courseResult = Database::select(
        'c.id, c.code',
        "$tblCourse c INNER JOIN  $tblSessionRelCourse r ON c.id = r.c_id",
        [
            'where' => [
                'r.session_id = ? ' => [$sessionId],
            ],
        ]
    );

    if (empty($courseResult)) {
        continue;
    }

    foreach ($courseResult as $value) {
        $courseId = (int) $value['id'];
        $courseCode = $value['code'];

        // Load gradebook categories for this course + session.
        $cats = Category::load(
            null,
            null,
            $courseCode,
            null,
            null,
            $sessionId,
            'ORDER BY id'
        );

        if (empty($cats)) {
            // First time, try with default category id = 0 (same as session_filter.php).
            $cats = Category::load(
                0,
                null,
                $courseCode,
                null,
                null,
                $sessionId,
                'ORDER BY id'
            );
        }

        if (empty($cats)) {
            // No gradebook category for this course/session.
            continue;
        }

        $selectCat = (int) $cats[0]->get_id();
        if ($selectCat <= 0) {
            continue;
        }

        // Get all certificates for this category.
        $certificateListAux = GradebookUtils::get_list_users_certificates($selectCat);
        if (empty($certificateListAux)) {
            continue;
        }

        foreach ($certificateListAux as $certRow) {
            $createdAt = strtotime(api_get_local_time($certRow['created_at']));

            // Base row with extra metadata.
            $row = $certRow;
            $row['category_id'] = $selectCat;
            $row['c_id'] = $courseId;
            $row['course_code'] = $courseCode;
            $row['session_id'] = $sessionId;

            // Apply date filter (same logic as session_filter.php).
            $include = false;
            switch ($filterDate) {
                case NO_DATE_FILTER:
                    $include = true;
                    break;
                case DATE_BEGIN_FILTER:
                    $include = $createdAt >= $dateBegin;
                    break;
                case DATE_END_FILTER:
                    $include = $createdAt <= $dateEnd;
                    break;
                case ALL_DATE_FILTER:
                    $include = $createdAt >= $dateBegin && $createdAt <= $dateEnd;
                    break;
            }

            if ($include) {
                $certificateList[] = $row;
            }
        }
    }
}

// Apply extra fields filtering to the global certificate list (same idea as session_filter.php).
if (!empty($filterCheckList) && !empty($certificateList)) {
    foreach ($certificateList as $key => $value) {
        foreach ($filterCheckList as $fieldId => $field) {
            $extraFieldValue = new ExtraFieldValue('user');
            $extraFieldValueData = $extraFieldValue->get_values_by_handler_and_field_id(
                $value['user_id'],
                $fieldId
            );

            if (empty($extraFieldValueData)) {
                unset($certificateList[$key]);
                break;
            }

            $paramName = 'extra_'.$field['variable'];

            switch ($field['field_type']) {
                case ExtraField::FIELD_TYPE_TEXT:
                case ExtraField::FIELD_TYPE_ALPHANUMERIC:
                    $filterValue = isset($_GET[$paramName]) ? (string) $_GET[$paramName] : '';
                    if ($filterValue !== '') {
                        $pos = stripos($extraFieldValueData['value'], $filterValue);
                        if ($pos === false) {
                            unset($certificateList[$key]);
                        }
                    }
                    break;
                case ExtraField::FIELD_TYPE_RADIO:
                    $filterValue = '';
                    if (isset($_GET[$paramName][$paramName])) {
                        $filterValue = $_GET[$paramName][$paramName];
                    }
                    if ($extraFieldValueData['value'] != $filterValue) {
                        unset($certificateList[$key]);
                    }
                    break;
                case ExtraField::FIELD_TYPE_SELECT:
                    $filterValue = isset($_GET[$paramName]) ? $_GET[$paramName] : null;
                    if ($filterValue !== null && $extraFieldValueData['value'] != $filterValue) {
                        unset($certificateList[$key]);
                    }
                    break;
            }

            // Stop checking other fields if this one already disqualified the record.
            if (!isset($certificateList[$key])) {
                break;
            }
        }
    }
}

// Re-index the array after unsetting elements.
$certificateList = array_values($certificateList);

// If no certificates pass the filters, show a friendly message.
if (empty($certificateList)) {
    Display::display_header($plugin->get_lang('PrintCertificate'));
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
    Display::display_footer();
    exit;
}

// Build user list + session mapping.
$userList = [];
$sessionInfoPerSession = [];

foreach ($certificateList as $value) {
    $infoUser = api_get_user_info($value['user_id']);
    $infoUser['category_id'] = $value['category_id'];
    $infoUser['c_id'] = $value['c_id'];
    $infoUser['course_code'] = $value['course_code'];
    $infoUser['session_id'] = $value['session_id'];
    $userList[] = $infoUser;
}

// Preload session info for all involved sessions (used in dates).
foreach ($sessionIds as $sessionId) {
    if ($sessionId > 0) {
        $sessionInfoPerSession[$sessionId] = SessionManager::fetch($sessionId);
    }
}

$path = api_get_path(WEB_UPLOAD_PATH).'certificates/';
$htmlList = [];

foreach ($userList as $userInfo) {
    $courseId = $userInfo['c_id'];
    $courseCode = $userInfo['course_code'];
    $studentId = $userInfo['user_id'];
    $sessionId = !empty($userInfo['session_id']) ? (int) $userInfo['session_id'] : 0;

    $courseInfo = api_get_course_info($courseCode);
    $allowCustomCertificateCourse = api_get_course_setting('customcertificate_course_enable', $courseInfo);

    if (!$allowCustomCertificateCourse) {
        // Skip courses where the custom certificate plugin is not enabled at course level.
        continue;
    }

    // Get info certificate for this course + session.
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
        }
    }

    $workSpace = (int) (297 - $infoCertificate['margin_left'] - $infoCertificate['margin_right']);
    $widthCell = (int) ($workSpace / 6);

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
                style="max-height: 150px; max-width: '.(int) ($workSpace - (2 * $widthCell)).'mm;"
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
    $htmlText .= '<td style="width:'.(int) ($workSpace / 3).'mm" class="logo">'.$logoLeft.'</td>';
    $htmlText .= '<td style="width:'.(int) ($workSpace / 3).'mm; text-align:center;" class="logo">';
    $htmlText .= $logoCenter;
    $htmlText .= '</td>';
    $htmlText .= '<td style="width:'.(int) ($workSpace / 3).'mm; text-align:right;" class="logo">'.$logoRight.'</td>';
    $htmlText .= '</tr>';
    $htmlText .= '</table>';

    $allUserInfo = DocumentManager::get_all_info_to_certificate(
        $studentId,
        $courseCode,
        $sessionId,
        false
    );

    $myContentHtml = $infoCertificate['content_course'];
    $myContentHtml = str_replace(chr(13).chr(10).chr(13).chr(10), chr(13).chr(10), $myContentHtml);
    $infoToBeReplacedInContentHtml = $allUserInfo[0];
    $infoToReplaceInContentHtml = $allUserInfo[1];
    $myContentHtml = str_replace(
        $infoToBeReplacedInContentHtml,
        $infoToReplaceInContentHtml,
        $myContentHtml
    );

    // Use session-specific dates when available.
    $currentSessionInfo = isset($sessionInfoPerSession[$sessionId]) ? $sessionInfoPerSession[$sessionId] : [];

    $startDate = '';
    $endDate = '';
    switch ($infoCertificate['date_change']) {
        case 0:
            if (!empty($currentSessionInfo['access_start_date'])) {
                $startDate = date("d/m/Y", strtotime(api_get_local_time($currentSessionInfo['access_start_date'])));
            }
            if (!empty($currentSessionInfo['access_end_date'])) {
                $endDate = date("d/m/Y", strtotime(api_get_local_time($currentSessionInfo['access_end_date'])));
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
            if (!empty($currentSessionInfo)) {
                $dateInfo = api_get_local_time($currentSessionInfo['access_end_date']);
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
                $items[] = '<br />';
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
            $myContentHtmlBack = strip_tags(
                $infoCertificate['contents'],
                '<p><b><strong><table><tr><td><th><span><i><li><ol><ul>'.
                '<dd><dt><dl><br><hr><img><a><div><h1><h2><h3><h4><h5><h6>'
            );
            $htmlText .= $myContentHtmlBack;
            $htmlText .= '</td>';
            $htmlText .= '</tr>';
            $htmlText .= '</table>';
        }
        $htmlText .= '</div>';
    }

    if (!$exportAllInOne) {
        $htmlText .= '</body></html>';
    }
    $fileName = 'certificate_'.$userInfo['course_code'].'_'.$userInfo['complete_name'].'_'.$currentLocalTime;
    $htmlList[$fileName] = $htmlText;
}

// If for some reason we ended up with no HTML (e.g. all courses had the plugin disabled),
// show a message instead of a blank page.
if (empty($htmlList)) {
    Display::display_header($plugin->get_lang('PrintCertificate'));
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
    Display::display_footer();
    exit;
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
            'certificate'.date('Y_m_d_His'),
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
        $name = 'certificates_'.$currentLocalTime.'.zip';
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

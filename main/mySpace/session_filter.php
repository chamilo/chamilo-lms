<?php

/* For licensing terms, see /license.txt */

/**
 * Report for current courses followed by the user.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;

// CSRF token for delete action.
$token = Security::get_token();

if (!api_is_allowed_to_create_course() && !api_is_drh()) {
    api_not_allowed(true);
}

$allowCustomCertificate = 'true' === api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate');

/** @var CustomCertificatePlugin|null $plugin */
// Create plugin instance only when the plugin is enabled.
$plugin = null;
if ($allowCustomCertificate) {
    $plugin = CustomCertificatePlugin::create();
}

$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
$tblSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);

define('NO_DATE_FILTER', 0);
define('DATE_BEGIN_FILTER', 1);
define('DATE_END_FILTER', 2);
define('ALL_DATE_FILTER', 3);

$certificateList = [];
$urlParam = '';
$form = new FormValidator('search_user', 'GET', api_get_self());
$innerJoinSessionRelUser = '';
$whereCondictionDRH = '';
$whereCondictionMultiUrl = '';
if (api_is_drh()) {
    $innerJoinSessionRelUser = "INNER JOIN $tblSessionRelUser as session_rel_user
                                ON (s.id = session_rel_user.session_id)";
    $whereCondictionDRH = "WHERE session_rel_user.user_id = ".api_get_user_id();
    $whereCondictionMultiUrl = " AND session_rel_user.user_id = ".api_get_user_id();
}

// Select sessions the user can see.
$sql = "SELECT s.id, name FROM $tblSession s
        $innerJoinSessionRelUser
        $whereCondictionDRH
        ORDER BY name";

if (api_is_multiple_url_enabled()) {
    $tblSessionRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
    $accessUrlId = api_get_current_access_url_id();
    if ($accessUrlId != -1) {
        $sql = "SELECT s.id, name FROM $tblSession s
                INNER JOIN $tblSessionRelAccessUrl as session_rel_url
                    ON (s.id = session_rel_url.session_id)
                $innerJoinSessionRelUser
                WHERE access_url_id = $accessUrlId
                $whereCondictionMultiUrl
                ORDER BY name";
    }
}
$result = Database::query($sql);
$Sessions = Database::store_result($result);

// Build choices array for the advanced multi-select.
$sessionChoices = [];
foreach ($Sessions as $row) {
    $sessionChoices[$row['id']] = $row['name'];
}


$sessionMultiSelect = $form->addElement(
    'advmultiselect',
    'session_id',
    null,
    $sessionChoices
);

$sessionMultiSelect->setLabel([
    get_lang('SessionList'),
    get_lang('SearchSession'),
    get_lang('MySessions'),
]);

$form->addDatePicker('date_begin', get_lang('DateStart'), ['id' => 'date-begin']);
$form->addDatePicker('date_end', get_lang('DateEnd'), ['id' => 'date-end']);

// EXTRA FIELDS
$extraField = new ExtraField('user');
$returnParams = $extraField->addElements(
    $form,
    0,
    [],
    true,
    false,
    [],
    [],
    [],
    false,
    true
);

$form->addElement('hidden', 'formSent', 1);
$form->addButtonSearch(get_lang('Search'));
// We keep all export actions (CSV, PDF, ZIP) near the result table, so no form-level export button.

// Decide how to get values: normal search submit or CSV export icon.
$values = null;
$exportToCsv = false;

// Case 1: user clicked "Search" button (normal form submit).
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $exportToCsv = false;
} elseif (isset($_GET['export'])) {
    // Case 2: CSV export icon was clicked.
    // Use raw GET parameters as the form values so filters are preserved.
    $values = $_GET;
    $exportToCsv = true;
}

// Process filters and build result list only when we have values (search or export).
if (!empty($values)) {
    // Normalize session ids from the form to an integer array.
    $sessionIds = [];
    if (!empty($values['session_id'])) {
        if (is_array($values['session_id'])) {
            foreach ($values['session_id'] as $sessionIdValue) {
                $sessionIdValue = (int) $sessionIdValue;
                if ($sessionIdValue > 0) {
                    $sessionIds[] = $sessionIdValue;
                }
            }
        } else {
            $sessionIdValue = (int) $values['session_id'];
            if ($sessionIdValue > 0) {
                $sessionIds[] = $sessionIdValue;
            }
        }
    }

    $dateBeginRaw = isset($values['date_begin']) ? $values['date_begin'] : null;
    $dateEndRaw = isset($values['date_end']) ? $values['date_end'] : null;

    $dateBegin = !empty($dateBeginRaw) ? strtotime($dateBeginRaw) : null;
    $dateEnd = !empty($dateEndRaw) ? strtotime($dateEndRaw.' 23:59:59') : null;

    $filterDate = 0;
    if (!empty($dateBegin)) {
        $filterDate += DATE_BEGIN_FILTER;
    }
    if (!empty($dateEnd)) {
        $filterDate += DATE_END_FILTER;
    }

    // Build extra-field filter list.
    $filterCheckList = [];
    $extraField = new ExtraField('user');
    $extraFieldsAll = $extraField->get_all(['filter = ?' => 1], 'option_order');
    foreach ($extraFieldsAll as $field) {
        $fieldName = 'extra_'.$field['variable'];
        if (!empty($values[$fieldName])) {
            $filterCheckList[$field['id']] = $field;
        }
    }

    // Build certificate list for all selected sessions.
    $certificateList = [];
    if (!empty($sessionIds)) {
        foreach ($sessionIds as $sessionId) {
            $courseResult = Database::select(
                'c.id, c.code',
                "$tbl_course c INNER JOIN  $tblSessionRelCourse r ON c.id = r.c_id",
                [
                    'where' => [
                        "r.session_id = ? " => [$sessionId],
                    ],
                ]
            );

            if (empty($courseResult)) {
                continue;
            }

            $sessionInfo = api_get_session_info($sessionId);

            foreach ($courseResult as $course) {
                $courseId = $course['id'];
                $courseCode = $course['code'];

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
                    // First time load with default category id = 0.
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
                    continue;
                }

                $selectCat = (int) $cats[0]->get_id();
                if ($selectCat <= 0) {
                    continue;
                }

                $certificateListAux = GradebookUtils::get_list_users_certificates($selectCat);
                if (empty($certificateListAux)) {
                    continue;
                }

                foreach ($certificateListAux as $certificate) {
                    $createdAt = strtotime(api_get_local_time($certificate['created_at']));
                    $certificate['category_id'] = $selectCat;
                    $certificate['c_id'] = $courseId;
                    $certificate['course_code'] = $courseCode;
                    $certificate['session_id'] = $sessionId;
                    $certificate['session_name'] = isset($sessionInfo['name']) ? $sessionInfo['name'] : '';

                    $includeCertificate = false;
                    switch ($filterDate) {
                        case NO_DATE_FILTER:
                            $includeCertificate = true;
                            break;
                        case DATE_BEGIN_FILTER:
                            $includeCertificate = $createdAt >= $dateBegin;
                            break;
                        case DATE_END_FILTER:
                            $includeCertificate = $createdAt <= $dateEnd;
                            break;
                        case ALL_DATE_FILTER:
                            $includeCertificate = $createdAt >= $dateBegin && $createdAt <= $dateEnd;
                            break;
                    }

                    if ($includeCertificate) {
                        $certificateList[] = $certificate;
                    }
                }
            }
        }
    }

    // Filter by extra fields after building the global list.
    if (!empty($filterCheckList) && !empty($certificateList)) {
        foreach ($certificateList as $key => $certificate) {
            foreach ($filterCheckList as $fieldId => $field) {
                $extraFieldValue = new ExtraFieldValue('user');
                $extraFieldValueData = $extraFieldValue->get_values_by_handler_and_field_id(
                    $certificate['user_id'],
                    $fieldId
                );

                if (empty($extraFieldValueData)) {
                    unset($certificateList[$key]);
                    break;
                }

                $fieldName = 'extra_'.$field['variable'];

                switch ($field['field_type']) {
                    case ExtraField::FIELD_TYPE_TEXT:
                    case ExtraField::FIELD_TYPE_ALPHANUMERIC:
                        $filterValue = isset($values[$fieldName]) ? $values[$fieldName] : '';
                        if ($filterValue !== '') {
                            $pos = stripos($extraFieldValueData['value'], (string) $filterValue);
                            if ($pos === false) {
                                unset($certificateList[$key]);
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_RADIO:
                        $filterValue = '';
                        if (isset($values[$fieldName][$fieldName])) {
                            $filterValue = $values[$fieldName][$fieldName];
                        }
                        if ($extraFieldValueData['value'] != $filterValue) {
                            unset($certificateList[$key]);
                        }
                        break;
                    case ExtraField::FIELD_TYPE_SELECT:
                        $filterValue = isset($values[$fieldName]) ? $values[$fieldName] : null;
                        if ($filterValue !== null && $extraFieldValueData['value'] != $filterValue) {
                            unset($certificateList[$key]);
                        }
                        break;
                }
            }
        }

        // Reindex after unsetting items.
        $certificateList = array_values($certificateList);
    }

    // Build URL parameters used by the export (PDF / ZIP / CSV) buttons.
    $params = [];

    if (!empty($sessionIds)) {
        foreach ($sessionIds as $sessionId) {
            $params['session_id'][] = $sessionId;
        }
    }

    // Mark that form has been submitted, so filters can be reused.
    $params['formSent'] = 1;

    $params['date_begin'] = Security::remove_XSS((string) $dateBeginRaw);
    $params['date_end'] = Security::remove_XSS((string) $dateEndRaw);

    foreach ($filterCheckList as $field) {
        $fieldName = 'extra_'.$field['variable'];
        if (!isset($values[$fieldName])) {
            continue;
        }

        if (is_array($values[$fieldName])) {
            $cleanArray = [];
            foreach ($values[$fieldName] as $key => $val) {
                $cleanArray[$key] = is_string($val) ? Security::remove_XSS($val) : $val;
            }
            $params[$fieldName] = $cleanArray;
        } else {
            $params[$fieldName] = Security::remove_XSS((string) $values[$fieldName]);
        }
    }
    $urlParam = http_build_query($params);

    // Build CSV data when export is requested.
    if ($exportToCsv) {
        $dataToExport = [];
        $headers = [
            get_lang('Session'),
            get_lang('Course'),
            get_lang('FirstName'),
            get_lang('LastName'),
            get_lang('Score'),
            get_lang('Date'),
        ];

        $extraField = new ExtraField('user');
        foreach ($extraFieldsAll as $field) {
            $headers[] = $field['display_text'];
        }
        $dataToExport[] = $headers;

        foreach ($certificateList as $index => $value) {
            $categoryId = $value['category_id'];
            $courseCode = $value['course_code'];
            $courseInfo = api_get_course_info($courseCode);
            $extraFields = [];
            foreach ($extraFieldsAll as $field) {
                $extraFieldValue = new ExtraFieldValue('user');
                $extraFieldValueData = $extraFieldValue->get_values_by_handler_and_field_id(
                    $value['user_id'],
                    $field['id']
                );
                $fieldValue = isset($extraFieldValueData['value']) ? $extraFieldValueData['value'] : '';
                if ('true' === $fieldValue) {
                    $fieldValue = get_lang('Yes');
                }
                if ('false' === $fieldValue) {
                    $fieldValue = get_lang('No');
                }
                $extraFields[] = $fieldValue;
            }

            $list = GradebookUtils::get_list_gradebook_certificates_by_user_id($value['user_id'], $categoryId);
            foreach ($list as $valueCertificate) {
                $item = [];
                $item[] = !empty($value['session_name']) ? $value['session_name'] : '';
                $item[] = $courseInfo['title'];
                $item[] = $value['firstname'];
                $item[] = $value['lastname'];
                $item[] = $valueCertificate['score_certificate'];
                $item[] = api_get_local_time($valueCertificate['created_at']);
                $item = array_merge($item, $extraFields);
                $dataToExport[] = $item;
            }
        }
        Export::arrayToCsv($dataToExport, 'export');

        // Stop further HTML output when exporting CSV.
        exit;
    }
}

// Register JS only when the custom certificate plugin is enabled and available.
// JS only shows a confirmation dialog, it does not override URLs.
if ($allowCustomCertificate && $plugin) {
    // Escape message to avoid breaking JS string.
    $onlyCustomMsg = addslashes($plugin->get_lang('OnlyCustomCertificates'));

    $htmlHeadXtra[] = "<script>
    $(function () {
        $('#export_pdf').on('click', function(e) {
            if (!confirm('".$onlyCustomMsg."')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        $('#export_zip').on('click', function(e) {
            if (!confirm('".$onlyCustomMsg."')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });
</script>";
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('MySpace')];
Display::display_header(get_lang('CertificatesSessions'));
echo Display::page_header(get_lang('CertificatesSessions'));

// Top toolbar: only back button, always visible.
$topActions = '';
$topActions .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], 32),
    api_get_path(WEB_CODE_PATH).'mySpace'
);
echo Display::toolbarAction('actions', [$topActions]);

// Show search form.
echo $form->returnForm();

if (0 == count($certificateList)) {
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
} else {
    // Actions related to the result list (CSV, PDF, ZIP) shown just above the table.
    $resultActions = '';

    // CSV export uses the same page with current filters.
    $csvUrl = api_get_self().'?'.$urlParam.'&export=1';
    $resultActions .= Display::url(
        Display::return_icon('excel.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
        $csvUrl
    );

    if ($allowCustomCertificate) {
        $pluginUrl = api_get_path(WEB_PLUGIN_PATH).'customcertificate/src/export_pdf_all_in_one.php';
        $pluginUrlWithParams = $pluginUrl.'?'.$urlParam;

        // Open PDF export in a new tab so the filter form remains visible.
        $resultActions .= Display::url(
            Display::return_icon('pdf.png', get_lang('ExportAllCertificatesToPDF'), [], ICON_SIZE_MEDIUM),
            $pluginUrlWithParams.'&export_pdf=1',
            [
                'id' => 'export_pdf',
                'target' => '_blank',
            ]
        );

        // Open ZIP export in a new tab so the filter form remains visible.
        $resultActions .= Display::url(
            Display::return_icon('file_zip.png', get_lang('ExportAllCertificatesToZIP'), [], ICON_SIZE_MEDIUM),
            $pluginUrlWithParams.'&export_zip=1',
            [
                'id' => 'export_zip',
                'target' => '_blank',
            ]
        );
    }

    // Render result actions toolbar right above the table.
    echo Display::toolbarAction('result-actions', [$resultActions]);

    // Render table with certificates.
    echo '<table class="table table-hover table-striped data_table">';
    echo '<tbody>';
    foreach ($certificateList as $index => $value) {
        $categoryId = $value['category_id'];
        $courseCode = $value['course_code'];
        $courseInfo = api_get_course_info($courseCode);
        echo '<tr>';
        echo '<td width="50%" class="actions">';
        echo get_lang('Student').' : ';
        echo api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].')';
        echo '</td>';
        echo '<td width="50%" class="actions">'.$courseInfo['title'].'</td>';
        echo '</tr>';
        echo '<tr><td colspan="2">';
        echo '<table class="table table-hover table-striped data_table">';
        echo '<tbody>';

        $list = GradebookUtils::get_list_gradebook_certificates_by_user_id($value['user_id'], $categoryId);
        foreach ($list as $valueCertificate) {
            echo '<tr>';
            echo '<td width="50%">'.get_lang('Score').' : '.$valueCertificate['score_certificate'].'</td>';
            echo '<td width="30%">';
            echo get_lang('Date').' : '.api_convert_and_format_date($valueCertificate['created_at']);
            echo '</td>';
            echo '<td width="20%">';
            $url = api_get_path(WEB_PATH).'certificates/index.php?'.
                'id='.$valueCertificate['id'].
                '&user_id='.$value['user_id'];
            $certificateUrl = Display::url(
                get_lang('Certificate'),
                $url,
                ['target' => '_blank', 'class' => 'btn btn-default']
            );
            echo $certificateUrl.PHP_EOL;

            $urlExport = $url.'&action=export';
            $pdf = Display::url(
                Display::return_icon('pdf.png', get_lang('Download')),
                $urlExport,
                ['target' => '_blank']
            );
            echo $pdf.PHP_EOL;

            echo '<a onclick="return confirmation();" href="gradebook_display_certificate.php?'.
                'sec_token='.$token.
                '&'.api_get_cidreq().
                '&action=delete'.
                '&cat_id='.$categoryId.
                '&certificate_id='.$valueCertificate['id'].'">'.
                Display::return_icon('delete.png', get_lang('Delete')).
                '</a>'.PHP_EOL;

            echo '</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
}

Display::display_footer();

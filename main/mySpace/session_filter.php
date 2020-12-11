<?php

/* For licensing terms, see /license.txt */

/**
 * Report for current courses followed by the user.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;

if (!api_is_allowed_to_create_course() && !api_is_drh()) {
    api_not_allowed(true);
}

$allowCustomCertificate = 'true' === api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate');
$plugin = CustomCertificatePlugin::create();

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

// Select of sessions.
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
$options = [];
$options['0'] = '';
foreach ($Sessions as $enreg) {
    $options[$enreg['id']] = $enreg['name'];
}

$form->addElement('select', 'session_id', get_lang('SessionList'), $options, ['id' => 'session-id']);
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
$form->addButtonExport(get_lang('ExportAsCSV'), 'export');

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $exportToCsv = isset($values['export']);
    $sessionId = (int) $_REQUEST['session_id'];
    $dateBegin = isset($_REQUEST['date_begin']) ? strtotime($_REQUEST['date_begin']) : null;
    $dateEnd = isset($_REQUEST['date_end']) ? strtotime($_REQUEST['date_end'].' 23:59:59') : null;

    $filterDate = 0;
    if (!empty($dateBegin)) {
        $filterDate += DATE_BEGIN_FILTER;
    }
    if (!empty($dateEnd)) {
        $filterDate += DATE_END_FILTER;
    }

    $filterCheckList = [];
    $extraField = new ExtraField('user');
    $extraFieldsAll = $extraField->get_all(['filter = ?' => 1], 'option_order');
    foreach ($extraFieldsAll as $field) {
        if (!empty($_REQUEST['extra_'.$field['variable']])) {
            $filterCheckList[$field['id']] = $field;
        }
    }

    $result = Database::select(
        'c.id, c.code',
        "$tbl_course c INNER JOIN  $tblSessionRelCourse r ON c.id = r.c_id",
        [
            'where' => [
                "r.session_id = ? " => [$sessionId],
            ],
        ]
    );

    foreach ($result as $value) {
        $courseId = $value['id'];
        $courseCode = $value['code'];

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
            // first time
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

        $selectCat = (int) $cats[0]->get_id();
        $certificateListAux = [];
        if (!empty($selectCat)) {
            $certificateListAux = GradebookUtils::get_list_users_certificates($selectCat);
        }

        foreach ($certificateListAux as $value) {
            $createdAt = strtotime(api_get_local_time($value['created_at']));
            $value['category_id'] = $selectCat;
            $value['c_id'] = $courseId;
            $value['course_code'] = $courseCode;
            switch ($filterDate) {
                case NO_DATE_FILTER:
                    $certificateList[] = $value;
                    break;
                case DATE_BEGIN_FILTER:
                    if ($createdAt >= $dateBegin) {
                        $certificateList[] = $value;
                    }
                    break;
                case DATE_END_FILTER:
                    if ($createdAt <= $dateEnd) {
                        $certificateList[] = $value;
                    }
                    break;
                case ALL_DATE_FILTER:
                    if ($createdAt >= $dateBegin && $createdAt <= $dateEnd) {
                        $certificateList[] = $value;
                    }
                    break;
            }
        }

        // Filter extra field
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

                switch ($field['field_type']) {
                    case ExtraField::FIELD_TYPE_TEXT:
                    case ExtraField::FIELD_TYPE_ALPHANUMERIC:
                        $pos = stripos($extraFieldValueData['value'], $_REQUEST['extra_'.$field['variable']]);
                        if ($pos === false) {
                            unset($certificateList[$key]);
                        }
                        break;
                    case ExtraField::FIELD_TYPE_RADIO:
                        $valueRadio = $_REQUEST['extra_'.$field['variable']]['extra_'.$field['variable']];
                        if ($extraFieldValueData['value'] != $valueRadio) {
                            unset($certificateList[$key]);
                        }
                        break;
                    case ExtraField::FIELD_TYPE_SELECT:
                        if ($extraFieldValueData['value'] != $_REQUEST['extra_'.$field['variable']]) {
                            unset($certificateList[$key]);
                        }
                        break;
                 }
            }
        }
    }

    $params = [
        'session_id' => (int) $_REQUEST['session_id'],
        'date_begin' => Security::remove_XSS($_REQUEST['date_begin']),
        'date_end' => Security::remove_XSS($_REQUEST['date_end']),
    ];

    foreach ($filterCheckList as $field) {
        $params['extra_'.$field['variable']] = Security::remove_XSS($_REQUEST['extra_'.$field['variable']]);
    }
    $urlParam = http_build_query($params);

    $dataToExport = [];
    if ($exportToCsv) {
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

        $sessionInfo = api_get_session_info($sessionId);
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
                $item[] = $sessionInfo['name'];
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
    }
}

$htmlHeadXtra[] = "<script>
    $(function () {
        $('#export_pdf').click(function(e) {
            e.preventDefault();
            e.stopPropagation();

            var session_id = $('#session-id').val();
            var date_begin = $('#date-begin').val();
            var date_end = $('#date-end').val();

            if (confirm('".$plugin->get_lang('OnlyCustomCertificates')."')) {
                var url = '".api_get_path(WEB_PLUGIN_PATH)."' +
                    'customcertificate/src/export_pdf_all_in_one.php?' +
                    '".$urlParam."&' +
                    'export_pdf=1';

                $(location).attr('href',url);
            }
        });

        $('#export_zip').click(function(e) {
            e.preventDefault();
            e.stopPropagation();

            var session_id = $('#session-id').val();
            var date_begin = $('#date-begin').val();
            var date_end = $('#date-end').val();
            if (confirm('".$plugin->get_lang('OnlyCustomCertificates')."')) {
                var url = '".api_get_path(WEB_PLUGIN_PATH)."' +
                    'customcertificate/src/export_pdf_all_in_one.php?' +
                    '".$urlParam."&' +
                    'export_zip=1';

                $(location).attr('href',url);
            }
        });
    });
</script>";

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('MySpace')];
Display::display_header(get_lang('CertificatesSessions'));
echo Display::page_header(get_lang('CertificatesSessions'));
$actions = '';
$actions .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], 32),
    api_get_path(WEB_CODE_PATH).'mySpace'
);

if ($allowCustomCertificate) {
    $url = api_get_path(WEB_PLUGIN_PATH).'customcertificate/src/export_pdf_all_in_one.php';
    $actions .= Display::url(
        Display::return_icon('pdf.png', get_lang('ExportAllCertificatesToPDF'), [], ICON_SIZE_MEDIUM),
        $url,
        ['id' => 'export_pdf']
    );

    $actions .= Display::url(
        Display::return_icon('file_zip.png', get_lang('ExportAllCertificatesToZIP'), [], ICON_SIZE_MEDIUM),
        $url,
        ['id' => 'export_zip']
    );
}

echo Display::toolbarAction('actions', [$actions]);
echo $form->returnForm();

if (0 == count($certificateList)) {
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
} else {
    echo '<table class="table table-hover table-striped  data_table">';
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
        echo '<tr><td colspan="2">
            <table class="table table-hover table-striped  data_table">
                <tbody>';

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

            $url .= '&action=export';
            $pdf = Display::url(
                Display::return_icon('pdf.png', get_lang('Download')),
                $url,
                ['target' => '_blank']
            );
            echo $pdf.PHP_EOL;

            echo '<a onclick="return confirmation();" href="gradebook_display_certificate.php?'.
                'sec_token='.$token.
                '&'.api_get_cidreq().
                '&action=delete'.
                '&cat_id='.$categoryId.
                '&certificate_id='.$valueCertificate['id'].'">
                    '.Display::return_icon('delete.png', get_lang('Delete')).'
                  </a>'.PHP_EOL;
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

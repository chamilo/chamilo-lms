<?php
/* For licensing terms, see /license.txt */
/**
 * Report for current courses followed by the user.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;


if (!api_is_allowed_to_create_course()) {
    api_not_allowed(true);
}

$allowCustomCertificate = api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate') === 'true';
$plugin = CustomCertificatePlugin::create();

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
                    'session_id=' + session_id + '&'+ 
                    'date_begin=' + date_begin + '&' +
                    'date_end=' + date_end + '&' +
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
                    'session_id=' + session_id + '&'+ 
                    'date_begin=' + date_begin + '&' +
                    'date_end=' + date_end + '&' +
                    'export_zip=1';
    
                $(location).attr('href',url);
            }
        });
    });
</script>";

$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
$tblSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

define('NO_DATE_FILTER', 0);
define('DATE_BEGIN_FILTER', 1);
define('DATE_END_FILTER', 2);
define('ALL_DATE_FILTER', 3);

$certificateList = [];
$formSent = 0;

if (isset($_POST['formSent'])) {
    $formSent = $_POST['formSent'];
    $sessionId = (int) $_POST['session_id'];
    $dateBegin = isset($_POST['date_begin']) ? strtotime($_POST['date_begin']) : null;
    $dateEnd = isset($_POST['date_end']) ? strtotime($_POST['date_end'].' 23:59:59') : null;

    $filterDate = 0;
    if (!empty($dateBegin)) {
        $filterDate += DATE_BEGIN_FILTER;
    }
    if (!empty($dateEnd)) {
        $filterDate += DATE_END_FILTER;
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
        $certificateListAux = GradebookUtils::get_list_users_certificates($selectCat);

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
    }
}

//select of sessions
$sql = "SELECT id, name FROM $tblSession ORDER BY name";

if (api_is_multiple_url_enabled()) {
    $tblSessionRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
    $accessUrlId = api_get_current_access_url_id();
    if ($accessUrlId != -1) {
        $sql = "SELECT s.id, name FROM $tblSession s
                INNER JOIN $tblSessionRelAccessUrl as session_rel_url
                ON (s.id = session_rel_url.session_id)
                WHERE access_url_id = $accessUrlId
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

$form = new FormValidator('search_user', 'post', api_get_self());
$form->addElement('select', 'session_id', get_lang('SessionList'), $options, ['id' => 'session-id']);
$form->addDatePicker('date_begin', get_lang('DateStart'), ['id' => 'date-begin']);
$form->addDatePicker('date_end', get_lang('DateEnd'), ['id' => 'date-end']);
$form->addElement('hidden', 'formSent', 1);
$form->addButtonSearch(get_lang('Search'));

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

if (count($certificateList) == 0) {
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
} else {
    echo '<table class="table data_table">';
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
            <table class="table data_table">
                <tbody>';

        $list = GradebookUtils::get_list_gradebook_certificates_by_user_id(
            $value['user_id'],
            $categoryId
        );
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

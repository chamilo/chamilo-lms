<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

if (!api_is_student_boss()) {
    api_protect_course_script(true);
}

api_block_anonymous_users();

if (!api_is_allowed_to_edit() && !api_is_student_boss()) {
    api_not_allowed(true);
}

api_set_more_memory_and_time_limits();

//extra javascript functions for in html head:
$htmlHeadXtra[] = "<script>
function confirmation() {
	if (confirm(\" ".trim(get_lang('AreYouSureToDelete'))." ?\")) {
	    return true;
	} else {
	    return false;
	}
}
</script>";

$categoryId = isset($_GET['cat_id']) ? (int) $_GET['cat_id'] : 0;
$action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : null;
$filterOfficialCode = isset($_POST['filter']) ? Security::remove_XSS($_POST['filter']) : null;
$filterOfficialCodeGet = isset($_GET['filter']) ? Security::remove_XSS($_GET['filter']) : null;

$url = api_get_self().'?'.api_get_cidreq().'&cat_id='.$categoryId.'&filter='.$filterOfficialCode;
$courseInfo = api_get_course_info();

$filter = api_get_setting('certificate_filter_by_official_code');
$userList = [];
$filterForm = null;
$certificate_list = [];
if ($filter === 'true') {
    $options = UserManager::getOfficialCodeGrouped();
    $options = array_merge(['all' => get_lang('All')], $options);
    $form = new FormValidator(
        'official_code_filter',
        'POST',
        api_get_self().'?'.api_get_cidreq().'&cat_id='.$categoryId
    );
    $form->addElement('select', 'filter', get_lang('OfficialCode'), $options);
    $form->addButton('submit', get_lang('Submit'));
    $filterForm = '<br />'.$form->returnForm();

    if ($form->validate()) {
        $officialCode = $form->getSubmitValue('filter');
        if ($officialCode === 'all') {
            $certificate_list = GradebookUtils::get_list_users_certificates($categoryId);
        } else {
            $userList = UserManager::getUsersByOfficialCode($officialCode);
            if (!empty($userList)) {
                $certificate_list = GradebookUtils::get_list_users_certificates(
                    $categoryId,
                    $userList
                );
            }
        }
    } else {
        $certificate_list = GradebookUtils::get_list_users_certificates($categoryId);
    }
} else {
    $certificate_list = GradebookUtils::get_list_users_certificates($categoryId);
}

$content = '';
$courseCode = api_get_course_id();
$allowCustomCertificate = api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate') === 'true' &&
    api_get_course_setting('customcertificate_course_enable', $courseInfo) == 1;

$tags = Certificate::notificationTags();

switch ($action) {
    case 'send_notifications':
        $currentUserInfo = api_get_user_info();
        $message = isset($_POST['message']) ? $_POST['message'] : '';
        $subject = get_lang('NotificationCertificateSubject');
        if (!empty($message)) {
            foreach ($certificate_list as $index => $value) {
                $userInfo = api_get_user_info($value['user_id']);
                if (empty($userInfo)) {
                    continue;
                }
                $list = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $value['user_id'],
                    $categoryId
                );

                foreach ($list as $valueCertificate) {
                    Certificate::sendNotification(
                        $subject,
                        $message,
                        $userInfo,
                        $courseInfo,
                        $valueCertificate
                    );
                }
            }
            Display::addFlash(Display::return_message(get_lang('Sent')));
        }

        header('Location: '.$url);
        exit;
        break;
    case 'show_notification_form':
        $form = new FormValidator('notification', 'post', $url.'&action=send_notifications');
        $form->addHeader(get_lang('SendNotification'));
        $form->addHtmlEditor('message', get_lang('Message'));
        $form->addLabel(
            get_lang('Tags'),
            Display::return_message(implode('<br />', $tags), 'normal', false)
        );
        $form->addButtonSend(get_lang('Send'));
        $form->setDefaults(
            ['message' => nl2br(get_lang('NotificationCertificateTemplate'))]
        );
        $content = $form->returnForm();
        break;
    case 'export_all_certificates_zip':
        if ($allowCustomCertificate) {
            $params = 'course_code='.api_get_course_id().
                '&session_id='.api_get_session_id().
                '&'.api_get_cidreq().
                '&cat_id='.$categoryId;
            $url = api_get_path(WEB_PLUGIN_PATH).'customcertificate/src/print_certificate.php?export_all=1&'.$params;

            header('Location: '.$url);
        }
        exit;
    case 'generate_all_certificates':
        $userList = CourseManager::get_user_list_from_course_code(
            api_get_course_id(),
            api_get_session_id()
        );

        if (!empty($userList)) {
            foreach ($userList as $userInfo) {
                if ($userInfo['status'] == INVITEE) {
                    continue;
                }
                Category::generateUserCertificate($categoryId, $userInfo['user_id']);
            }
        }
        header('Location: '.$url);
        exit;
        break;
    case 'delete_all_certificates':
        Category::deleteAllCertificates($categoryId);
        Display::addFlash(Display::return_message(get_lang('Deleted')));
        header('Location: '.$url);
        exit;
        break;
    case 'download_all_certificates':
        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();
        $categoryId = (int) $_GET['catId'];
        $date = api_get_utc_datetime(null, false, true);
        $pdfName = 'certs_'.$courseCode.'_'.$sessionId.'_'.$categoryId.'_'.$date->format('Y-m-d');
        $finalFile = api_get_path(SYS_ARCHIVE_PATH)."$pdfName.pdf";

        $result = DocumentManager::file_send_for_download($finalFile, true);
        if (false === $result) {
            api_not_allowed(true);
        }
        break;
    case 'download_certificates_report':
        $exportData = array_map(function ($learner) {
            return [
                $learner['user_id'],
                $learner['username'],
                $learner['firstname'],
                $learner['lastname'],
            ];
        }, $certificate_list);

        $csvContent = [];
        $csvHeaders = [];
        $csvHeaders[] = get_lang('Id');
        $csvHeaders[] = get_lang('UserName');
        $csvHeaders[] = get_lang('FirstName');
        $csvHeaders[] = get_lang('LastName');
        $csvHeaders[] = get_lang('Score');
        $csvHeaders[] = get_lang('Date');

        $extraFields = [];
        $extraFieldsFromSettings = [];
        $extraFieldsFromSettings = api_get_configuration_value('certificate_export_report_user_extra_fields');

        if (!empty($extraFieldsFromSettings) && isset($extraFieldsFromSettings['extra_fields'])) {
            $extraFields = $extraFieldsFromSettings['extra_fields'];
            $usersProfileInfo = [];

            $userIds = array_column($certificate_list, 'user_id', 'user_id');

            foreach ($extraFields as $fieldName) {
                $extraFieldInfo = UserManager::get_extra_field_information_by_name($fieldName);

                if (!empty($extraFieldInfo)) {
                    $csvHeaders[] = $fieldName;

                    $usersProfileInfo[$extraFieldInfo['id']] = TrackingCourseLog::getAdditionalProfileInformationOfFieldByUser(
                        $extraFieldInfo['id'],
                        $userIds
                    );
                }
            }

            foreach ($exportData as $key => $row) {
                $list = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $row[0],
                    $categoryId
                );

                foreach ($list as $valueCertificate) {
                    $row[] = $valueCertificate['score_certificate'];
                    $row[] = api_convert_and_format_date($valueCertificate['created_at']);
                }

                foreach ($usersProfileInfo as $extraInfo) {
                    $row[] = $extraInfo[$row[0]][0];
                }

                $csvContent[] = $row;
            }
        }

        array_unshift($csvContent, $csvHeaders);

        $fileName = 'learner_certificate_report_'.api_get_local_time();
        Export::arrayToCsv($csvContent, $fileName);
        break;
}

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Gradebook'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('GradebookListOfStudentsCertificates')];

$this_section = SECTION_COURSES;
Display::display_header('');

if ($action === 'delete') {
    $check = Security::check_token('get');
    if ($check) {
        $certificate = new Certificate($_GET['certificate_id']);
        $result = $certificate->delete(true);
        Security::clear_token();
        if ($result == true) {
            echo Display::return_message(get_lang('CertificateRemoved'), 'confirmation');
        } else {
            echo Display::return_message(get_lang('CertificateNotRemoved'), 'error');
        }
    }
}

$token = Security::get_token();
echo Display::page_header(get_lang('GradebookListOfStudentsCertificates'));

if (!empty($content)) {
    echo $content;
}

//@todo replace all this code with something like get_total_weight()
$cats = Category::load($categoryId, null, null, null, null, null, false);

if (!empty($cats)) {
    //with this fix the teacher only can view 1 gradebook
    if (api_is_platform_admin()) {
        $stud_id = (api_is_allowed_to_edit() ? null : api_get_user_id());
    } else {
        $stud_id = api_get_user_id();
    }

    $total_weight = $cats[0]->get_weight();
    $allcat = $cats[0]->get_subcategories(
        $stud_id,
        api_get_course_id(),
        api_get_session_id()
    );
    $alleval = $cats[0]->get_evaluations($stud_id);
    $alllink = $cats[0]->get_links($stud_id);

    $datagen = new GradebookDataGenerator($allcat, $alleval, $alllink);

    $total_resource_weight = 0;
    if (!empty($datagen)) {
        $data_array = $datagen->get_data(
            0,
            0,
            null,
            true
        );

        if (!empty($data_array)) {
            $newarray = [];
            foreach ($data_array as $data) {
                $newarray[] = array_slice($data, 1);
            }

            foreach ($newarray as $item) {
                $total_resource_weight = $total_resource_weight + $item['2'];
            }
        }
    }

    if ($total_resource_weight != $total_weight) {
        echo Display::return_message(
            get_lang('SumOfActivitiesWeightMustBeEqualToTotalWeight'),
            'warning'
        );
    }
}

$actions = '';
$actions .= Display::url(
    Display::return_icon('tuning.png', get_lang('GenerateCertificates'), [], ICON_SIZE_MEDIUM),
    $url.'&action=generate_all_certificates'
);
$actions .= Display::url(
    Display::return_icon('delete.png', get_lang('DeleteAllCertificates'), [], ICON_SIZE_MEDIUM),
    $url.'&action=delete_all_certificates'
);

$hideCertificateExport = api_get_setting('hide_certificate_export_link');

if (count($certificate_list) > 0 && $hideCertificateExport !== 'true') {
    if ($allowCustomCertificate) {
        $actions .= Display::url(
            Display::return_icon('pdf.png', get_lang('ExportAllCertificatesToPDF'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH)
                .'customcertificate/src/print_certificate.php?'.api_get_cidreq().'&'
                .http_build_query(
                    [
                        'export_all_in_one' => 1,
                        'course_code' => api_get_course_id(),
                        'session_id' => api_get_session_id(),
                        'cat_id' => $categoryId,
                    ]
                )
        );
    } else {
        $actions .= Display::url(
            Display::return_icon('pdf.png', get_lang('ExportAllCertificatesToPDF'), [], ICON_SIZE_MEDIUM),
            '#',
            ['id' => 'btn-export-all']
        );
    }

    $actions .= Display::url(
        Display::return_icon('export_csv.png', get_lang('ExportCertificateReport'), [], ICON_SIZE_MEDIUM),
        $url.'&action=download_certificates_report'
    );

    if ($allowCustomCertificate) {
        $actions .= Display::url(
            Display::return_icon('file_zip.png', get_lang('ExportAllCertificatesToZIP'), [], ICON_SIZE_MEDIUM),
            $url.'&action=export_all_certificates_zip'
        );
    }

    $actions .= Display::url(
        Display::return_icon('notification_mail.png', get_lang('SendCertificateNotifications'), [], ICON_SIZE_MEDIUM),
        $url.'&action=show_notification_form'
    );
}

echo Display::toolbarAction('actions', [$actions]);
echo $filterForm;

if (count($certificate_list) == 0) {
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
} else {
    echo '<table class="table data_table">';
    echo '<tbody>';
    foreach ($certificate_list as $index => $value) {
        echo '<tr>
                <td width="100%" class="actions">'.get_lang('Student').' : '.api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].')</td>';
        echo '</tr>';
        echo '<tr><td>
            <table class="table data_table">
                <tbody>';

        $list = GradebookUtils::get_list_gradebook_certificates_by_user_id(
            $value['user_id'],
            $categoryId
        );
        foreach ($list as $valueCertificate) {
            echo '<tr>';
            echo '<td width="50%">'.get_lang('Score').' : '.$valueCertificate['score_certificate'].'</td>';
            echo '<td width="30%">'.get_lang('Date').' : '.api_convert_and_format_date($valueCertificate['created_at']).'</td>';
            echo '<td width="20%">';
            $url = api_get_path(WEB_PATH).'certificates/index.php?id='.$valueCertificate['id'].'&user_id='.$value['user_id'];
            $certificateUrl = Display::url(
                get_lang('Certificate'),
                $url,
                ['target' => '_blank', 'class' => 'btn btn-default']
            );
            echo $certificateUrl.PHP_EOL;
            if ($hideCertificateExport !== 'true') {
                $url .= '&action=export';
                $pdf = Display::url(
                    Display::return_icon('pdf.png', get_lang('Download')),
                    $url,
                    ['target' => '_blank']
                );
                echo $pdf.PHP_EOL;
            }
            echo '<a onclick="return confirmation();" href="gradebook_display_certificate.php?sec_token='.$token.'&'.api_get_cidreq().'&action=delete&cat_id='.$categoryId.'&certificate_id='.$valueCertificate['id'].'">
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

    echo GradebookUtils::returnJsExportAllCertificates(
        '#btn-export-all',
        $categoryId,
        api_get_course_id(),
        api_get_session_id(),
        $filterOfficialCodeGet
    );
}
Display::display_footer();

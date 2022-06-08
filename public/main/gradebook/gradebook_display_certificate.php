<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

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
	if (confirm(\" ".trim(get_lang('Are you sure you want to delete'))." ?\")) {
	    return true;
	} else {
	    return false;
	}
}
</script>";

$categoryId = isset($_GET['cat_id']) ? (int) $_GET['cat_id'] : 0;
$category = null;
$repo = Container::getGradeBookCategoryRepository();
if (!empty($categoryId)) {
    $category = $repo->find($categoryId);
}
$action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : null;
$filterOfficialCode = isset($_POST['filter']) ? Security::remove_XSS($_POST['filter']) : null;
$filterOfficialCodeGet = isset($_GET['filter']) ? Security::remove_XSS($_GET['filter']) : null;

$url = api_get_self().'?'.api_get_cidreq().'&cat_id='.$categoryId.'&filter='.$filterOfficialCode;
$courseInfo = api_get_course_info();

$filter = api_get_setting('certificate_filter_by_official_code');
$userList = [];
$filterForm = null;
$certificate_list = [];
if ('true' === $filter) {
    $options = UserManager::getOfficialCodeGrouped();
    $options = array_merge(['all' => get_lang('All')], $options);
    $form = new FormValidator(
        'official_code_filter',
        'POST',
        api_get_self().'?'.api_get_cidreq().'&cat_id='.$categoryId
    );
    $form->addSelect('filter', get_lang('Code'), $options);
    $form->addButton('submit', get_lang('Submit'));
    $filterForm = '<br />'.$form->returnForm();

    if ($form->validate()) {
        $officialCode = $form->getSubmitValue('filter');
        if ('all' == $officialCode) {
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
$allowCustomCertificate = 'true' === api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate') &&
    1 == api_get_course_setting('customcertificate_course_enable', $courseInfo);

$tags = Certificate::notificationTags();

switch ($action) {
    case 'send_notifications':
        $currentUserInfo = api_get_user_info();
        $message = isset($_POST['message']) ? $_POST['message'] : '';
        $subject = get_lang('Certificate notification');
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
        $form->addHeader(get_lang('Notify'));
        $form->addHtmlEditor('message', get_lang('Message'));
        $form->addLabel(
            get_lang('Tags'),
            Display::return_message(implode('<br />', $tags), 'normal', false)
        );
        $form->addButtonSend(get_lang('Send message'));
        $form->setDefaults(
            ['message' => nl2br(get_lang('((user_first_name)),'))]
        );
        $content = $form->returnForm();
        break;
    case 'export_all_certificates':
        if ($allowCustomCertificate) {
            $params = 'course_code='.api_get_course_id().
                '&session_id='.api_get_session_id().
                '&'.api_get_cidreq().
                '&cat_id='.$categoryId;
            $url = api_get_path(WEB_PLUGIN_PATH).
                'customcertificate/src/print_certificate.php?export_all_in_one=1&'.$params;
        } else {
            if (api_is_student_boss()) {
                $userGroup = new UserGroupModel();
                $userList = $userGroup->getGroupUsersByUser(api_get_user_id());
            } else {
                $userList = [];
                if (!empty($filterOfficialCodeGet)) {
                    $userList = UserManager::getUsersByOfficialCode($filterOfficialCodeGet);
                }
            }

            Category::exportAllCertificates($categoryId, $userList);
        }

        header('Location: '.$url);
        exit;
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
        $userList = CourseManager::get_user_list_from_course_code(api_get_course_id(), api_get_session_id());
        if (!empty($userList)) {
            foreach ($userList as $userInfo) {
                if (INVITEE == $userInfo['status']) {
                    continue;
                }
                Category::generateUserCertificate($category, $userInfo['user_id']);
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
}

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Assessments'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('AssessmentsListOfLearnersCertificates')];

$this_section = SECTION_COURSES;
Display::display_header('');

if ('delete' === $action) {
    $check = Security::check_token('get');
    if ($check) {
        $certificate = new Certificate($_GET['certificate_id']);
        $result = $certificate->deleteCertificate(true);
        Security::clear_token();
        if (true == $result) {
            echo Display::return_message(get_lang('Certificate removed'), 'confirmation');
        } else {
            echo Display::return_message(get_lang('Certificate can\'t be removed'), 'error');
        }
    }
}

$token = Security::get_token();
echo Display::page_header(get_lang('AssessmentsListOfLearnersCertificates'));

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
            get_lang('The sum of all activity weights must be equal to the total weight indicated in your assessment settings, otherwise the learners will not be able to reach the sufficient score to achieve their certification.'),
            'warning'
        );
    }
}

$actions = '';
$actions .= Display::url(
    Display::return_icon('tuning.png', get_lang('Generate certificates'), [], ICON_SIZE_MEDIUM),
    $url.'&action=generate_all_certificates'
);
$actions .= Display::url(
    Display::return_icon('delete.png', get_lang('Delete all certificates'), [], ICON_SIZE_MEDIUM),
    $url.'&action=delete_all_certificates'
);

$hideCertificateExport = api_get_setting('hide_certificate_export_link');

if (count($certificate_list) > 0 && 'true' !== $hideCertificateExport) {
    $actions .= Display::url(
        Display::return_icon('pdf.png', get_lang('Export all certificates to PDF'), [], ICON_SIZE_MEDIUM),
        $url.'&action=export_all_certificates'
    );

    if ($allowCustomCertificate) {
        $actions .= Display::url(
            Display::return_icon('file_zip.png', get_lang('ExportAllCertificatesToZIP'), [], ICON_SIZE_MEDIUM),
            $url.'&action=export_all_certificates_zip'
        );
    }

    $actions .= Display::url(
        Display::return_icon('notification_mail.png', get_lang('Send messageCertificateNotifications'), [], ICON_SIZE_MEDIUM),
        $url.'&action=show_notification_form'
    );
}

echo Display::toolbarAction('actions', [$actions]);
echo $filterForm;

if (0 == count($certificate_list)) {
    echo Display::return_message(get_lang('No results available'), 'warning');
} else {
    echo '<table class="table data_table">';
    echo '<tbody>';
    foreach ($certificate_list as $index => $value) {
        echo '<tr>
                <td width="100%" class="actions">'.get_lang('Learner').' : '.api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].')</td>';
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
                ['target' => '_blank', 'class' => 'btn btn--plain']
            );
            echo $certificateUrl.PHP_EOL;

            $url .= '&action=export';
            $pdf = Display::url(
                Display::return_icon('pdf.png', get_lang('Download')),
                $url,
                ['target' => '_blank']
            );
            echo $pdf.PHP_EOL;

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
}
Display::display_footer();

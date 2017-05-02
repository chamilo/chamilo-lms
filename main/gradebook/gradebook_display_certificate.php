<?php
/* For licensing terms, see /license.txt */

/**
 * Script
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

if (!api_is_student_boss()) {
    api_protect_course_script(true);
}

set_time_limit(0);
ini_set('max_execution_time', 0);

//extra javascript functions for in html head:
$htmlHeadXtra[] ="<script>
function confirmation() {
	if (confirm(\" " . trim(get_lang('AreYouSureToDelete')) . " ?\")) {
	    return true;
	} else {
	    return false;
	}
}
</script>";
api_block_anonymous_users();

if (!api_is_allowed_to_edit() && !api_is_student_boss()) {
    api_not_allowed(true);
}

$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : null;
$action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : null;
$filterOfficialCode = isset($_POST['filter']) ? Security::remove_XSS($_POST['filter']) : null;
$filterOfficialCodeGet = isset($_GET['filter']) ? Security::remove_XSS($_GET['filter']) : null;

switch ($action) {
    case 'export_all_certificates':
        if (api_is_student_boss()) {
            $userGroup = new UserGroup();
            $userList = $userGroup->getGroupUsersByUser(api_get_user_id());
        } else {
            $userList = array();
            if (!empty($filterOfficialCodeGet)) {
                $userList = UserManager::getUsersByOfficialCode($filterOfficialCodeGet);
            }
        }

        Category::exportAllCertificates($cat_id, $userList);
        break;
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
                Category::register_user_certificate($cat_id, $userInfo['user_id']);
            }
        }
        break;
    case 'delete_all_certificates':
        Category::deleteAllCertificates($cat_id);
        break;
}

$course_code = api_get_course_id();

$interbreadcrumb[] = array(
    'url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?',
    'name' => get_lang('Gradebook'),
);
$interbreadcrumb[] = array('url' => '#','name' => get_lang('GradebookListOfStudentsCertificates'));

$this_section = SECTION_COURSES;
Display::display_header('');

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
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

//@todo replace all this code with something like get_total_weight()
$cats = Category:: load($cat_id, null, null, null, null, null, false);

if (!empty($cats)) {
    //with this fix the teacher only can view 1 gradebook
    if (api_is_platform_admin()) {
        $stud_id= (api_is_allowed_to_edit() ? null : api_get_user_id());
    } else {
        $stud_id= api_get_user_id();
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
            GradebookDataGenerator :: GDG_SORT_NAME,
            0,
            null,
            true
        );

        if (!empty($data_array)) {
            $newarray = array();
            foreach ($data_array as $data) {
                $newarray[] = array_slice($data, 1);
            }

            foreach ($newarray as $item) {
                $total_resource_weight = $total_resource_weight + $item['2'];
            }
        }
    }

    if ($total_resource_weight != $total_weight) {
        echo Display::return_message(get_lang('SumOfActivitiesWeightMustBeEqualToTotalWeight'), 'warning');
    }
}

$filter = api_get_setting('certificate_filter_by_official_code');
$userList = array();
$filterForm = null;
$certificate_list = array();
if ($filter === 'true') {
    echo '<br />';
    $options = UserManager::getOfficialCodeGrouped();
    $options =array_merge(array('all' => get_lang('All')), $options);
    $form = new FormValidator(
        'official_code_filter',
        'POST',
        api_get_self().'?'.api_get_cidreq().'&cat_id='.$cat_id
    );
    $form->addElement('select', 'filter', get_lang('OfficialCode'), $options);
    $form->addButton('submit', get_lang('Submit'));
    $filterForm = '<br />'.$form->returnForm();

    if ($form->validate()) {
        $officialCode = $form->getSubmitValue('filter');
        if ($officialCode == 'all') {
            $certificate_list = GradebookUtils::get_list_users_certificates($cat_id);
        } else {
            $userList = UserManager::getUsersByOfficialCode($officialCode);
            if (!empty($userList)) {
                $certificate_list = GradebookUtils::get_list_users_certificates(
                    $cat_id,
                    $userList
                );
            }
        }
    } else {
        $certificate_list = GradebookUtils::get_list_users_certificates($cat_id);
    }
} else {
    $certificate_list = GradebookUtils::get_list_users_certificates($cat_id);
}

echo '<div class="btn-group">';
$url = api_get_self().'?action=generate_all_certificates'.'&'.api_get_cidreq().'&cat_id='.$cat_id.'&filter='.$filterOfficialCode;
echo Display::url(get_lang('GenerateCertificates'), $url, array('class' => 'btn btn-default'));

$url = api_get_self().'?action=delete_all_certificates'.'&'.api_get_cidreq().'&cat_id='.$cat_id.'&filter='.$filterOfficialCode;
echo Display::url(get_lang('DeleteAllCertificates'), $url, array('class' => 'btn btn-default'));

$hideCertificateExport = api_get_setting('hide_certificate_export_link');
if (count($certificate_list) > 0 && $hideCertificateExport !== 'true') {
    $url = api_get_self().'?action=export_all_certificates'.'&'.api_get_cidreq().'&cat_id='.$cat_id.'&filter='.$filterOfficialCode;
    echo Display::url(get_lang('ExportAllCertificatesToPDF'), $url, array('class' => 'btn btn-default'));
}
echo '</div>';

echo $filterForm;

if (count($certificate_list) == 0) {
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
} else {
    echo '<br /><br /><table class="data_table">';
    foreach ($certificate_list as $index => $value) {
        echo '<tr>
                <td width="100%" class="actions">'.get_lang('Student').' : '.api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].')</td>';
        echo '</tr>';
        echo '<tr><td>
            <table class="data_table">';

        $list_certificate = GradebookUtils::get_list_gradebook_certificates_by_user_id($value['user_id'], $cat_id);
        foreach ($list_certificate as $value_certificate) {
            echo '<tr>';
            echo '<td width="50%">'.get_lang('Score').' : '.$value_certificate['score_certificate'].'</td>';
            echo '<td width="30%">'.get_lang('Date').' : '.api_convert_and_format_date($value_certificate['created_at']).'</td>';
            echo '<td width="20%">';
            $url = api_get_path(WEB_PATH).'certificates/index.php?id='.$value_certificate['id'];
            $certificates = Display::url(get_lang('Certificate'), $url, array('target'=>'_blank', 'class' => 'btn btn-default'));
            echo $certificates;
            echo '<a onclick="return confirmation();" href="gradebook_display_certificate.php?sec_token='.$token.'&'.api_get_cidreq().'&action=delete&cat_id='.$cat_id.'&certificate_id='.$value_certificate['id'].'">
                    '.Display::return_icon('delete.png',get_lang('Delete')).'
                  </a>';
            echo '</td></tr>';
        }
        echo '</table>';
        echo '</td></tr>';
    }
    echo '</table>';
}
Display::display_footer();

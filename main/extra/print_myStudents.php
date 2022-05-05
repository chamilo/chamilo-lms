<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$from_myspace = false;
if (isset($_GET['from']) && $_GET['from'] == 'myspace') {
    $from_myspace = true;
    $this_section = SECTION_TRACKING;
} else {
    $this_section = SECTION_COURSES;
}

//$nameTools = get_lang('StudentDetails');
$cidReset = true;
$get_course_code = Security::remove_XSS($_GET['course']);
if (isset($_GET['details'])) {
    if (!empty($_GET['origin']) && $_GET['origin'] == 'user_course') {
        $course_info = CourseManager::get_course_information($get_course_code);
        if (empty($cidReq)) {
            $interbreadcrumb[] = [
                "url" => api_get_path(WEB_COURSE_PATH).$course_info['directory'],
                'name' => $course_info['title'],
            ];
        }
        $interbreadcrumb[] = [
            "url" => "../user/user.php?cidReq=".$get_course_code,
            "name" => get_lang("Users"),
        ];
    } else {
        if (!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course') {
            $course_info = CourseManager::get_course_information($get_course_code);
            if (empty($cidReq)) {
                //$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_info['directory'], 'name' => $course_info['title']);
            }
            $interbreadcrumb[] = [
                "url" => "../tracking/courseLog.php?cidReq=".$get_course_code.'&studentlist=true&id_session='.(empty($_SESSION['id_session']) ? '' : $_SESSION['id_session']),
                "name" => get_lang("Tracking"),
            ];
        } else {
            if (!empty($_GET['origin']) && $_GET['origin'] == 'resume_session') {
                $interbreadcrumb[] = [
                    'url' => '../admin/index.php',
                    "name" => get_lang('PlatformAdmin'),
                ];
                $interbreadcrumb[] = [
                    'url' => "../admin/session_list.php",
                    "name" => get_lang('SessionList'),
                ];
                $interbreadcrumb[] = [
                    'url' => "../admin/resume_session.php?id_session=".Security::remove_XSS($_GET['id_session']),
                    "name" => get_lang('SessionOverview'),
                ];
            } else {
                $interbreadcrumb[] = [
                    "url" => "index.php",
                    "name" => get_lang('MySpace'),
                ];
                if (isset($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
                    $interbreadcrumb[] = [
                        "url" => "student.php?id_coach=".Security::remove_XSS($_GET['id_coach']),
                        "name" => get_lang("CoachStudents"),
                    ];
                    $interbreadcrumb[] = [
                        "url" => "myStudents.php?student=".Security::remove_XSS(
                                $_GET['student']
                            ).'&id_coach='.Security::remove_XSS($_GET['id_coach']),
                        "name" => get_lang("StudentDetails"),
                    ];
                } else {
                    $interbreadcrumb[] = [
                        "url" => "student.php",
                        "name" => get_lang("MyStudents"),
                    ];
                    $interbreadcrumb[] = [
                        "url" => "myStudents.php?student=".Security::remove_XSS($_GET['student']),
                        "name" => get_lang("StudentDetails"),
                    ];
                }
            }
        }
    }
    $nameTools = get_lang("DetailsStudentInCourse");
} else {
    if (!empty($_GET['origin']) && $_GET['origin'] == 'resume_session') {
        $interbreadcrumb[] = [
            'url' => '../admin/index.php',
            "name" => get_lang('PlatformAdmin'),
        ];
        $interbreadcrumb[] = [
            'url' => "../admin/session_list.php",
            "name" => get_lang('SessionList'),
        ];
        $interbreadcrumb[] = [
            'url' => "../admin/resume_session.php?id_session=".Security::remove_XSS($_GET['id_session']),
            "name" => get_lang('SessionOverview'),
        ];
    } else {
        $interbreadcrumb[] = [
            "url" => "index.php",
            "name" => get_lang('MySpace'),
        ];
        if (isset($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
            if (isset($_GET['id_session']) && intval($_GET['id_session']) != 0) {
                $interbreadcrumb[] = [
                    "url" => "student.php?id_coach=".Security::remove_XSS(
                            $_GET['id_coach']
                        )."&id_session=".$_GET['id_session'],
                    "name" => get_lang("CoachStudents"),
                ];
            } else {
                $interbreadcrumb[] = [
                    "url" => "student.php?id_coach=".Security::remove_XSS($_GET['id_coach']),
                    "name" => get_lang("CoachStudents"),
                ];
            }
        } else {
            $interbreadcrumb[] = [
                "url" => "student.php",
                "name" => get_lang("MyStudents"),
            ];
        }
    }
}

api_block_anonymous_users();

if (!api_is_allowed_to_edit() && !api_is_coach() && !api_is_drh() && !api_is_course_tutor(
    ) && $_user['status'] != SESSIONADMIN && !api_is_platform_admin(true)) {
    api_not_allowed(true);
}

Display::display_header($nameTools);
$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

if (isset($_GET['user_id']) && $_GET['user_id'] != '') {
    $user_id = intval($_GET['user_id']);
} else {
    $user_id = $_user['user_id'];
}

$session_id = isset($_GET['id_session']) ? intval($_GET['id_session']) : 0;
$student_id = (int) $_GET['student'];

// Action behaviour
$check = Security::check_token('get');

if (!empty($student_id)) { // infos about user
    $info_user = api_get_user_info($student_id);
}
if (api_is_drh() && !UserManager::is_user_followed_by_drh($student_id, $_user['user_id'])) {
    api_not_allowed();
}

$info_user['name'] = api_get_person_name($info_user['firstname'], $info_user['lastname']);

?>
<table class='table table-hover table-striped data_table'>
    <tr>
        <th colspan="6">
            <?php echo get_lang('result_exam_title');
            echo $info_user['name']; ?>
        </th>
    <tr>
        <th><?php echo get_lang('module_no'); ?></th>
        <th>
            <?php echo get_lang('result_exam'); ?>
        </th>
        <th>
            <?php echo get_lang('result_rep_1'); ?>
        </th>
        <th>
            <?php echo get_lang('result_rep_2'); ?>
        </th>
        <th>
            <?php echo get_lang('comment'); ?>
        </th>
    </tr>
    <?php
    $sqlexam = "SELECT *
                 FROM $tbl_stats_exercices
                 WHERE exe_user_id = $student_id
                 AND c_id = 0 AND mod_no != '0'
                 ORDER BY mod_no ASC";
    $resultexam = Database::query($sqlexam);
    while ($a_exam = Database::fetch_array($resultexam)) {
        //$ex_id = $a_exam['ex_id'];
        $mod_no = $a_exam['mod_no'];
        $score_ex = $a_exam['score_ex'];
        $score_rep1 = $a_exam['score_rep1'];
        $score_rep2 = $a_exam['score_rep2'];
        $coment = stripslashes($a_exam['coment']);
        echo "
            <tr>
                <td> ".$a_exam['mod_no']."
                </td>
                <td>
                    ".$a_exam['score_ex']."
                </td>
                <td>
                    ".$a_exam['score_rep1']."
                </td>
                <td>
                    ".$a_exam['score_rep2']."
                </td>
                <td>
                    $coment
                </td>
            </tr>
            ";
        $exe_idd = $a_exam['exe_id'];
    }
?>
</table>
</form>
<strong><?php echo get_lang('imprime_sommaire'); ?> </strong>
<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif" border="0"></a>

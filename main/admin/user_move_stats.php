<?php

/* For licensing terms, see /license.txt */

/**
 * User move script (to move between courses and sessions).
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$em = Database::getManager();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$debug = 0;

if (isset($_REQUEST['load_ajax'])) {
    //Checking the variable $_SESSION['combination'] that has all the
    // information of the selected course (instead of using a lots of
    // hidden variables ... )
    if (isset($_SESSION['combination']) && !empty($_SESSION['combination'])) {
        $combinations = $_SESSION['combination'];
        $combination_result = isset($combinations[$_REQUEST['unique_id']]) ? $combinations[$_REQUEST['unique_id']] : [];
        if (empty($combination_result)) {
            echo get_lang('ThereWasAnError');
        } else {
            $origin_course_code = $combination_result['course_code'];
            $origin_session_id = (int) $combination_result['session_id'];
            $new_session_id = (int) $_REQUEST['session_id'];

            if ($origin_session_id == $new_session_id) {
                echo get_lang('CantMoveToTheSameSession');
                exit;
            }
            $user_id = (int) $_REQUEST['user_id'];
            $new_course_list = SessionManager::get_course_list_by_session_id($new_session_id);

            $course_founded = false;
            foreach ($new_course_list as $course_item) {
                if ($origin_course_code == $course_item['code']) {
                    $course_founded = true;
                }
            }

            $result_message = [];
            $result_message_compare = [];
            $update_database = true;
            if (isset($_REQUEST['view_stat']) && $_REQUEST['view_stat'] == 1) {
                $update_database = false;
            }

            // Check if the same course exist in the session destination
            if ($course_founded) {
                $result = SessionManager::get_users_by_session($new_session_id);
                if (empty($result) || !in_array($user_id, array_keys($result))) {
                    if ($debug) {
                        echo 'User added to the session';
                    }
                    // Registering user to the new session
                    SessionManager::subscribeUsersToSession(
                        $new_session_id,
                        [$user_id],
                        false,
                        false
                    );
                }

                $course_info = api_get_course_info($origin_course_code);
                // Check if the user is registered in the session otherwise we will add it
                Tracking::processUserDataMove(
                    $user_id,
                    $course_info,
                    $origin_session_id,
                    $new_session_id,
                    $update_database,
                    $debug
                );
            } else {
                echo get_lang('CourseDoesNotExistInThisSession');
            }
        }
    } else {
        echo get_lang('ThereWasAnError');
    }
    exit;
}
$htmlHeadXtra[] = '<script>
   function moveto (unique_id, user_id) {
        var session_id = document.getElementById(unique_id).options[document.getElementById(unique_id).selectedIndex].value;
         $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function(myObject) {
            $("div#reponse_"+unique_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
            type: "POST",
            url: "user_move_stats.php",
            data: "load_ajax=1"+"&unique_id="+unique_id+"&user_id="+user_id+"&session_id="+session_id,
            success: function(datos) {
             $("div#reponse_"+unique_id).html(datos);
            }
        });
    }
    function view_stat (unique_id, user_id) {
        var session_id = document.getElementById(unique_id).options[document.getElementById(unique_id).selectedIndex].value;

         $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function(myObject) {
            $("div#reponse_"+unique_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
            type: "POST",
            url: "user_move_stats.php",
            data: "load_ajax=1&view_stat=1"+"&unique_id="+unique_id+"&user_id="+user_id+"&session_id="+session_id,
            success: function(datos) {
             $("div#reponse_"+unique_id).html(datos);
            }
        });
    }
    </script>';

function get_courses_list_by_user_id_based_in_exercises($user_id)
{
    $TABLETRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $user_id = (int) $user_id;
    $sql = "SELECT DISTINCT exe_user_id, c_id, session_id
            FROM $TABLETRACK_EXERCICES
            WHERE exe_user_id = $user_id
            ORDER by exe_user_id, c_id ASC";

    $res = Database::query($sql);
    $course_list = [];
    while ($row = Database::fetch_array($res, 'ASSOC')) {
        $course_list[] = $row;
    }

    return $course_list;
}

Display::addFlash(
    Display::return_message(
        get_lang('CompareUserResultsBetweenCoursesAndCoursesInASession'),
        'normal',
        false
    )
);
Display::display_header(get_lang('MoveUserStats'));
echo '<div class="actions">';
echo '<a href="../admin/index.php">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// Some pagination
$page = 1;
if (isset($_GET['page']) && !empty($_GET['page'])) {
    $page = intval($_GET['page']);
}
$default = 20;
$count = UserManager::get_number_of_users(null, api_get_current_access_url_id());
$nro_pages = round($count / $default) + 1;
$begin = $default * ($page - 1);
$end = $default * $page;
$navigation = "$begin - $end  / $count<br />";

if ($page > 1) {
    $navigation .= '<a href="'.api_get_self().'?page='.($page - 1).'">'.get_lang('Previous').'</a>';
} else {
    $navigation .= get_lang('Previous');
}
$navigation .= '&nbsp;';
$page++;
if ($page < $nro_pages) {
    $navigation .= '<a href="'.api_get_self().'?page='.$page.'">'.get_lang('Next').'</a>';
} else {
    $navigation .= get_lang('Next');
}

echo $navigation;
$user_list = UserManager::get_user_list([], [], $begin, $default);
$session_list = SessionManager::get_sessions_list([], ['name']);
$options = '';
$options .= '<option value="0">--'.get_lang('SelectASession').'--</option>';
foreach ($session_list as $session_data) {
    $my_session_list[$session_data['id']] = $session_data['name'];
    $options .= '<option value="'.$session_data['id'].'">'.$session_data['name'].'</option>';
}

$combinations = [];

if (!empty($user_list)) {
    foreach ($user_list as $user) {
        $user_id = $user['user_id'];
        $name = $user['firstname'].' '.$user['lastname'];
        $course_list_registered = CourseManager::get_courses_list_by_user_id(
            $user_id,
            true,
            false
        );

        $new_course_list = [];
        foreach ($course_list_registered as $course_reg) {
            if (empty($course_reg['session_id'])) {
                $course_reg['session_id'] = 0;
            }
            // Recover the code for historical reasons. If it can be proven
            // that the code can be safely replaced by c_id in the following
            // PHP code, feel free to do so
            $courseInfo = api_get_course_info_by_id($course_reg['real_id']);
            $course_reg['code'] = $courseInfo['code'];
            $new_course_list[] = $course_reg['code'].'_'.$course_reg['session_id'];
        }

        $course_list = get_courses_list_by_user_id_based_in_exercises($user_id);

        if (is_array($course_list) && !empty($course_list)) {
            foreach ($course_list as $my_course) {
                $courseInfo = api_get_course_info_by_id($my_course['c_id']);
                $my_course['real_id'] = $my_course['c_id'];
                $key = $courseInfo['code'].'_'.$my_course['session_id'];

                if (!in_array($key, $new_course_list)) {
                    $my_course['not_registered'] = 1;
                    $course_list_registered[] = $my_course;
                }
            }
        }

        foreach ($course_list_registered as &$course) {
            $courseInfo = api_get_course_info_by_id($course['real_id']);
            $course['name'] = $courseInfo['name'];
        }

        $course_list = $course_list_registered;

        echo '<div class="table-responsive">';
        echo '<table class="table table-hover table-striped data_table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th style="text-align:left;" colspan="'.count($course_list).'">';
        echo "<h3>$name #$user_id </h3>  ";
        echo '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if (!empty($course_list)) {
            echo '<tr>';
            foreach ($course_list as $course) {
                echo '<td>';
                if (isset($course['id_session']) && !empty($course['id_session'])) {
                    echo '<b>'.get_lang('SessionName').'</b> '.$my_session_list[$course['id_session']].'<br />';
                }
                echo $course['name'];
                echo ' ('.$course['code'].') ';
                if (isset($course['not_registered']) && !empty($course['not_registered'])) {
                    echo ' <i>'.get_lang('UserNotRegistered').'</i>';
                }
                echo '</td>';
            }
            echo '</tr>';
            echo '<tr>';

            foreach ($course_list as $course) {
                $course_code = $course['code'];
                if (empty($course['id_session'])) {
                    $session_id = 0;
                } else {
                    $session_id = $course['id_session'];
                }
                echo '<td>';
                echo get_lang('MoveTo');
                echo '<br />';
                $unique_id = uniqid();
                $combinations[$unique_id] = ['course_code' => $course_code, 'session_id' => $session_id];

                echo '<select id="'.$unique_id.'" name="'.$unique_id.'" class="form-control">';
                echo $options;
                echo '</select>';
                echo '<br />';
                echo '<button type="submit" class="btn btn-success" onclick="view_stat(\''.$unique_id.'\', \''.$user_id.'\');"> '.get_lang('CompareStats').'</button>';
                echo '<button type="submit" class="btn btn-success" onclick="moveto(\''.$unique_id.'\', \''.$user_id.'\');"> '.get_lang('Move').'</button>';
                echo '<div id ="reponse_'.$unique_id.'"></div>';
                echo '</td>';
            }
            echo '</tr>';
        } else {
            echo '<td>';
            echo get_lang('NoCoursesForThisUser');
            echo '</td>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}
echo $navigation;
$_SESSION['combination'] = $combinations;

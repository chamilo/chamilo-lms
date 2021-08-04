<?php

/* For licensing terms, see /license.txt */

ob_start();
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$nameTools = get_lang('Tutors');

api_block_anonymous_users();
$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('MySpace')];

if (isset($_GET["id_student"])) {
    $interbreadcrumb[] = ["url" => "student.php", "name" => get_lang('Students')];
}

Display::display_header($nameTools);

api_display_tool_title($nameTools);

$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

/**
 * MAIN PART.
 */
if (isset($_POST['export'])) {
    $order_clause = api_is_western_name_order(PERSON_NAME_DATA_EXPORT) ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
} else {
    $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
}

if (isset($_GET["id_student"])) {
    $id_student = intval($_GET["id_student"]);
    $sql_coachs = "SELECT DISTINCT srcru.user_id as id_coach
		FROM $tbl_session_rel_course_rel_user as srcru
		WHERE srcru.user_id='$id_student' AND srcru.status=2";
} else {
    if (api_is_platform_admin()) {
        $sql_coachs = "SELECT DISTINCT
			srcru.user_id as id_coach, user_id, lastname, firstname
			FROM $tbl_user, $tbl_session_rel_course_rel_user srcru
			WHERE
			 	srcru.user_id=user_id AND
			 	srcru.status=2 ".$order_clause;
    } else {
        $sql_coachs = "SELECT DISTINCT user_id as id_coach, user.user_id, lastname, firstname
			FROM
			$tbl_user as user,
			$tbl_session_rel_course_user as srcu,
			$tbl_course_user as course_rel_user,
			$tbl_course as c
			WHERE
			 	c.id = course_rel_user.c_id AND
				c.id = srcu.c_id AND
				course_rel_user.status='1' AND
				course_rel_user.user_id='".api_get_user_id()."' AND
				srcu.user_id = user.user_id AND
				srcu.status = 2
				".$order_clause;
    }
}

$result_coachs = Database::query($sql_coachs);

if (api_is_western_name_order()) {
    echo '<table class="table table-hover table-striped data_table">
	    <tr>
            <th>'.get_lang('FirstName').'</th>
            <th>'.get_lang('LastName').'</th>
            <th>'.get_lang('ConnectionTime').'</th>
            <th>'.get_lang('AdminCourses').'</th>
            <th>'.get_lang('Students').'</th>
        </tr>';
} else {
    echo '<table class="table table-hover table-striped data_table">
	        <tr>
                <th>'.get_lang('LastName').'</th>
                <th>'.get_lang('FirstName').'</th>
                <th>'.get_lang('ConnectionTime').'</th>
                <th>'.get_lang('AdminCourses').'</th>
                <th>'.get_lang('Students').'</th>
	        </tr>';
}

if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
    $header[] = get_lang('FirstName', '');
    $header[] = get_lang('LastName', '');
} else {
    $header[] = get_lang('LastName', '');
    $header[] = get_lang('FirstName', '');
}
$header[] = get_lang('ConnectionTime', '');

if (Database::num_rows($result_coachs) > 0) {
    while ($coachs = Database::fetch_array($result_coachs)) {
        $id_coach = $coachs["id_coach"];

        if (isset($_GET["id_student"])) {
            $sql_infos_coach = "SELECT lastname, firstname
            FROM $tbl_user
            WHERE user_id='$id_coach'";
            $result_coachs_infos = Database::query($sql_infos_coach);
            $lastname = Database::result($result_coachs_infos, 0, "lastname");
            $firstname = Database::result($result_coachs_infos, 0, "firstname");
        } else {
            $lastname = $coachs["lastname"];
            $firstname = $coachs["firstname"];
        }

        $sql_connection_time = "SELECT login_date, logout_date
        FROM $tbl_track_login
        WHERE login_user_id ='$id_coach' AND logout_date <> 'null'";
        $result_connection_time = Database::query($sql_connection_time);

        $nb_seconds = 0;
        while ($connections = Database::fetch_array($result_connection_time)) {
            $login_date = $connections["login_date"];
            $logout_date = $connections["logout_date"];
            $timestamp_login_date = strtotime($login_date);
            $timestamp_logout_date = strtotime($logout_date);
            $nb_seconds += ($timestamp_logout_date - $timestamp_login_date);
        }

        if ($nb_seconds == 0) {
            $s_connection_time = '';
        } else {
            $s_connection_time = api_time_to_hms($nb_seconds);
        }

        if ($i % 2 == 0) {
            $css_class = "row_odd";
            if ($i % 20 == 0 && $i != 0) {
                if (api_is_western_name_order()) {
                    echo '<tr>
					    <th>'.get_lang('FirstName').'</th>
                        <th>'.get_lang('LastName').'</th>
                        <th>'.get_lang('ConnectionTime').'</th>
                        <th>'.get_lang('AdminCourses').'</th>
                        <th>'.get_lang('Students').'</th>
					</tr>';
                } else {
                    echo '<tr>
					    <th>'.get_lang('LastName').'</th>
                        <th>'.get_lang('FirstName').'</th>
                        <th>'.get_lang('ConnectionTime').'</th>
                        <th>'.get_lang('AdminCourses').'</th>
                        <th>'.get_lang('Students').'</th>
					</tr>';
                }
            }
        } else {
            $css_class = "row_even";
        }

        $i++;

        if (api_is_western_name_order()) {
            echo '<tr class="'.$css_class.'">
			        <td>'.$firstname.'</td><td>'.$lastname.'</td><td>'.$s_connection_time.'</td>
			        <td>
			            <a href="course.php?type=coach&user_id='.$id_coach.'">
                        '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
			            </a>
                    </td>
			        <td>
			            <a href="student.php?type=coach&user_id='.$id_coach.'">
			                '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
			            </a>
			            </td>
                    </tr>';
        } else {
            echo '<tr class="'.$css_class.'">
			        <td>'.$lastname.'</td><td>'.$firstname.'</td>
			        <td>'.$s_connection_time.'</td>
			        <td>
			            <a href="course.php?type=coach&user_id='.$id_coach.'">
			            '.Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>
                    <td>
                        <a href="student.php?type=coach&user_id='.$id_coach.'">
                        '.Display::return_icon('2rightarrow.png', get_lang('Details')).'</a>
                    </td>
                    </tr>';
        }

        if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
            $data[$id_coach]["firstname"] = $firstname;
            $data[$id_coach]["lastname"] = $lastname;
        } else {
            $data[$id_coach]["lastname"] = $lastname;
            $data[$id_coach]["firstname"] = $firstname;
        }
        $data[$id_coach]["connection_time"] = $s_connection_time;
    }
} else {
    // No results
    echo '<tr><td colspan="5">'.get_lang("NoResult").'</td></tr>';
}
echo '</table>';

if (isset($_POST['export'])) {
    export_csv($header, $data, 'coaches.csv');
}

echo "<br /><br />";
echo "
    <br /><br />
    <form method='post' action='coaches.php'>
        <button type='submit' class='save' name='export' value='".get_lang('ExportExcel')."'>
            ".get_lang('ExportExcel')."
        </button>
    <form>
";
Display::display_footer();

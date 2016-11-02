<?php
/* For licensing terms, see /license.txt */

use CpChart\Classes\pCache as pCache;
use CpChart\Classes\pData as pData;
use CpChart\Classes\pImage as pImage;

/**
 * Class MySpace
 * @package chamilo.reporting
 */
class MySpace
{
    /**
     * Get admin actions
     * @return string
     */
    public static function getAdminActions()
    {
        $actions = array(
            //array('url' => api_get_path(WEB_CODE_PATH).'mySpace/index.php', 'content' => get_lang('Home')),
            array(
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=coaches',
                'content' => get_lang('DisplayCoaches'),
            ),
            array(
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=user',
                'content' => get_lang('DisplayUserOverview'),
            ),
            array(
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=session',
                'content' => get_lang('DisplaySessionOverview'),
            ),
            array(
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=course',
                'content' => get_lang('DisplayCourseOverview'),
            ),
            array(
                'url' => api_get_path(WEB_CODE_PATH).'tracking/question_course_report.php?view=admin',
                'content' => get_lang('LPQuestionListResults'),
            ),
            array(
                'url' => api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin',
                'content' => get_lang('LPExerciseResultsBySession'),
            ),
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=accessoverview',
                'content' => get_lang('DisplayAccessOverview').' ('.get_lang('Beta').')',
            ],
        );

        return Display :: actions($actions, null);
    }

    public static function getTopMenu()
    {
        $menu_items = array();
        $menu_items[] = Display::url(
            Display::return_icon('stats.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH)."auth/my_progress.php"
        );
        $menu_items[] = Display::url(
            Display::return_icon('teacher.png', get_lang('TeacherInterface'), array(), 32),
            api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher'
        );
        $menu_items[] = Display::url(Display::return_icon('star_na.png', get_lang('AdminInterface'), array(), 32), '#');
        $menu_items[] = Display::url(
            Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), 32),
            api_get_path(WEB_CODE_PATH).'tracking/exams.php'
        );
        $menu = null;
        foreach ($menu_items as $item) {
            $menu .= $item;
        }
        $menu .= '<br />';

        return $menu;
    }

    /**
     * This function serves exporting data in CSV format.
     * @param array $header         The header labels.
     * @param array $data           The data array.
     * @param string $file_name     The name of the file which contains exported data.
     * @return string mixed             Returns a message (string) if an error occurred.
     */
    public function export_csv($header, $data, $file_name = 'export.csv')
    {
        $archive_path = api_get_path(SYS_ARCHIVE_PATH);
        $archive_url = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

        if (!$open = fopen($archive_path.$file_name, 'w+')) {
            $message = get_lang('noOpen');
        } else {
            $info = '';

            foreach ($header as $value) {
                $info .= $value.';';
            }
            $info .= "\r\n";

            foreach ($data as $row) {
                foreach ($row as $value) {
                    $info .= $value.';';
                }
                $info .= "\r\n";
            }

            fwrite($open, $info);
            fclose($open);
            @chmod($file_name, api_get_permissions_for_new_files());

            header("Location:".$archive_url.$file_name);
        }
        return $message;
    }

    /**
     * Gets the connections to a course as an array of login and logout time
     *
     * @param   int     User ud
     * @param   int   $courseId
     * @param   int     Session id (optional, default = 0)
     * @return  array   Conections
     */
    public static function get_connections_to_course($user_id, $courseId, $session_id = 0)
    {
        // Database table definitions
        $tbl_track_course = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        // protect data
        $user_id     = intval($user_id);
        $courseId = intval($courseId);
        $session_id  = intval($session_id);

        $sql = 'SELECT login_course_date, logout_course_date
                FROM ' . $tbl_track_course . '
                WHERE
                    user_id = '.$user_id.' AND
                    c_id = '.$courseId.' AND
                    session_id = '.$session_id.'
                ORDER BY login_course_date ASC';
        $rs = Database::query($sql);
        $connections = array();

        while ($row = Database::fetch_array($rs)) {
            $timestamp_login_date = api_strtotime($row['login_course_date'], 'UTC');
            $timestamp_logout_date = api_strtotime($row['logout_course_date'], 'UTC');
            $connections[] = array('login' => $timestamp_login_date, 'logout' => $timestamp_logout_date);
        }

        return $connections;
    }

    /**
     * @param $user_id
     * @param $course_list
     * @param int $session_id
     * @return array|bool
     */
    public static function get_connections_from_course_list($user_id, $course_list, $session_id = 0)
    {
        // Database table definitions
        $tbl_track_course = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        if (empty($course_list)) {
            return false;
        }

        // protect data
        $user_id = intval($user_id);
        $session_id = intval($session_id);
        $new_course_list = array();
        foreach ($course_list as $course_item) {
            $courseInfo = api_get_course_info($course_item['code']);
            $courseId = $courseInfo['real_id'];
            $new_course_list[] =  '"'.$courseId.'"';
        }
        $course_list = implode(', ', $new_course_list);

        if (empty($course_list)) {
            return false;
        }
        $sql = 'SELECT login_course_date, logout_course_date, c_id
                FROM ' . $tbl_track_course . '
                WHERE
                    user_id = '.$user_id.' AND
                    c_id IN ('.$course_list.') AND
                    session_id = '.$session_id.'
                ORDER BY login_course_date ASC';
        $rs = Database::query($sql);
        $connections = array();

        while ($row = Database::fetch_array($rs)) {
            $timestamp_login_date = api_strtotime($row['login_course_date'], 'UTC');
            $timestamp_logout_date = api_strtotime($row['logout_course_date'], 'UTC');
            $connections[] = array(
                'login' => $timestamp_login_date,
                'logout' => $timestamp_logout_date,
                'c_id' => $row['c_id']
            );
        }

        return $connections;
    }

    /**
     * Creates a small table in the last column of the table with the user overview
     *
     * @param integer $user_id the id of the user
     * @param array $url_params additonal url parameters
     * @param array $row the row information (the other columns)
     * @return string html code
     */
    public static function course_info_tracking_filter($user_id, $url_params, $row)
    {
        // the table header
        $return = '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
        /*$return .= '  <tr>';
        $return .= '        <th>'.get_lang('Course').'</th>';
        $return .= '        <th>'.get_lang('AvgTimeSpentInTheCourse').'</th>';
        $return .= '        <th>'.get_lang('AvgStudentsProgress').'</th>';
        $return .= '        <th>'.get_lang('AvgCourseScore').'</th>';
        $return .= '        <th>'.get_lang('AvgExercisesScore').'</th>';
        $return .= '        <th>'.get_lang('AvgMessages').'</th>';
        $return .= '        <th>'.get_lang('AvgAssignments').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesScoreObtained').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesScorePossible').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesAnswered').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesScorePercentage').'</th>';
        $return .= '        <th>'.get_lang('FirstLogin').'</th>';
        $return .= '        <th>'.get_lang('LatestLogin').'</th>';
        $return .= '    </tr>';*/

        // database table definition
        $tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

        // getting all the courses of the user
        $sql = "SELECT * FROM $tbl_course_user
                WHERE
                    user_id = '".intval($user_id)."' AND
                    relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $courseInfo = api_get_course_info_by_id($row['c_id']);
            if (empty($courseInfo)) {
                continue;
            }

            $courseCode = $courseInfo['code'];
            $courseId = $courseInfo['real_id'];

            $return .= '<tr>';
            // course code
            $return .= '    <td width="157px" >'.cut($courseCode, 20, true).'</td>';
            // time spent in the course
            $return .= '    <td><div>'.api_time_to_hms(Tracking :: get_time_spent_on_the_course($user_id, $courseId)).'</div></td>';
            // student progress in course
            $return .= '    <td><div>'.round(Tracking :: get_avg_student_progress($user_id, $courseCode), 2).'</div></td>';
            // student score
            $avg_score = Tracking :: get_avg_student_score($user_id, $courseCode);
            if (is_numeric($avg_score)) {
                $avg_score = round($avg_score,2);
            } else {
                $$avg_score = '-';
            }

            $return .= '    <td><div>'.$avg_score.'</div></td>';
            // student tes score
            //$return .= '  <td><div style="width:40px">'.round(Tracking :: get_avg_student_exercise_score ($user_id, $courseCode),2).'%</div></td>';
            // student messages
            $return .= '    <td><div>'.Tracking :: count_student_messages($user_id, $courseCode).'</div></td>';
            // student assignments
            $return .= '    <td><div>'.Tracking :: count_student_assignments($user_id, $courseCode).'</div></td>';
            // student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
            $exercises_results = MySpace::exercises_results($user_id, $courseCode);
            $return .= '    <td width="105px"><div>'.(is_null($exercises_results['percentage']) ? '' : $exercises_results['score_obtained'].'/'.$exercises_results['score_possible'].' ( '.$exercises_results['percentage'].'% )').'</div></td>';
            //$return .= '  <td><div>'.$exercises_results['score_possible'].'</div></td>';
            $return .= '    <td><div>'.$exercises_results['questions_answered'].'</div></td>';
            $return .= '    <td><div>'.Tracking :: get_last_connection_date_on_the_course($user_id, $courseInfo).'</div></td>';
            $return .= '<tr>';
        }
        $return .= '</table>';
        return $return;
    }

    /**
     * Display a sortable table that contains an overview off all the reporting progress of all users and all courses the user is subscribed to
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @version Dokeos 1.8.6
     * @since October 2008
     */
    public static function display_tracking_user_overview()
    {
        MySpace::display_user_overview_export_options();

        $t_head = '    <table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
        //$t_head .= '  <caption>'.get_lang('CourseInformation').'</caption>';
        $t_head .=      '<tr>';
        $t_head .= '        <th width="155px" style="border-left:0;border-bottom:0"><span>'.get_lang('Course').'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'), 6, true).'</span></th>';
        //$t_head .= '      <th><div style="width:40px">'.get_lang('AvgExercisesScore').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfMessages'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfAssignments'), 6, true).'</span></th>';
        $t_head .= '        <th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
        //$t_head .= '      <th><div>'.get_lang('TotalExercisesScorePossible').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'), 6, true).'</span></th>';
        //$t_head .= '      <th><div>'.get_lang('TotalExercisesScorePercentage').'</div></th>';
        //$t_head .= '      <th><div style="width:60px">'.get_lang('FirstLogin').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
        $t_head .= '    </tr></table>';

        $addparams = array('view' => 'admin', 'display' => 'user');

        $table = new SortableTable('tracking_user_overview', array('MySpace','get_number_of_users_tracking_overview'), array('MySpace','get_user_data_tracking_overview'), 0);
        $table->additional_parameters = $addparams;

        $table->set_header(0, get_lang('OfficialCode'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
        if (api_is_western_name_order()) {
            $table->set_header(1, get_lang('FirstName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
            $table->set_header(2, get_lang('LastName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
        } else {
            $table->set_header(1, get_lang('LastName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
            $table->set_header(2, get_lang('FirstName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
        }
        $table->set_header(3, get_lang('LoginName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
        $table->set_header(4, $t_head, false, array('style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'), array('style' => 'width:90%;padding:0;font-size:7.5pt;'));
        $table->set_column_filter(4, array('MySpace','course_info_tracking_filter'));
        $table->display();
    }

    /**
     * @param $export_csv
     */
    public static function display_tracking_coach_overview($export_csv)
    {
        if ($export_csv) {
            $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
        } else {
            $is_western_name_order = api_is_western_name_order();
        }
        $sort_by_first_name = api_sort_by_first_name();
        $tracking_column = isset($_GET['tracking_list_coaches_column']) ? $_GET['tracking_list_coaches_column'] : ($is_western_name_order xor $sort_by_first_name) ? 1 : 0;
        $tracking_direction = (isset($_GET['tracking_list_coaches_direction']) && in_array(strtoupper($_GET['tracking_list_coaches_direction']), array('ASC', 'DESC', 'ASCENDING', 'DESCENDING', '0', '1'))) ? $_GET['tracking_list_coaches_direction'] : 'DESC';
        // Prepare array for column order - when impossible, use some of user names.
        if ($is_western_name_order) {
            $order = array(
                0 => 'firstname',
                1 => 'lastname',
                2 => ($sort_by_first_name ? 'firstname' : 'lastname'),
                3 => 'login_date',
                4 => ($sort_by_first_name ? 'firstname' : 'lastname'),
                5 => ($sort_by_first_name ? 'firstname' : 'lastname'),
            );
        } else {
            $order = array(
                0 => 'lastname',
                1 => 'firstname',
                2 => ($sort_by_first_name ? 'firstname' : 'lastname'),
                3 => 'login_date',
                4 => ($sort_by_first_name ? 'firstname' : 'lastname'),
                5 => ($sort_by_first_name ? 'firstname' : 'lastname'),
            );
        }
        $table = new SortableTable(
            'tracking_list_coaches_myspace',
            array('MySpace', 'count_coaches'),
            null,
            ($is_western_name_order xor $sort_by_first_name) ? 1 : 0
        );
        $parameters['view'] = 'admin';
        $table->set_additional_parameters($parameters);
        if ($is_western_name_order) {
            $table -> set_header(0, get_lang('FirstName'), true);
            $table -> set_header(1, get_lang('LastName'), true);
        } else {
            $table -> set_header(0, get_lang('LastName'), true);
            $table -> set_header(1, get_lang('FirstName'), true);
        }
        $table -> set_header(2, get_lang('TimeSpentOnThePlatform'), false);
        $table -> set_header(3, get_lang('LastConnexion'), false);
        $table -> set_header(4, get_lang('NbStudents'), false);
        $table -> set_header(5, get_lang('CountCours'), false);
        $table -> set_header(6, get_lang('NumberOfSessions'), false);
        $table -> set_header(7, get_lang('Sessions'), false);

        if ($is_western_name_order) {
            $csv_header[] = array (
                get_lang('FirstName'),
                get_lang('LastName'),
                get_lang('TimeSpentOnThePlatform'),
                get_lang('LastConnexion'),
                get_lang('NbStudents'),
                get_lang('CountCours'),
                get_lang('NumberOfSessions')
            );
        } else {
            $csv_header[] = array (
                get_lang('LastName'),
                get_lang('FirstName'),
                get_lang('TimeSpentOnThePlatform'),
                get_lang('LastConnexion'),
                get_lang('NbStudents'),
                get_lang('CountCours'),
                get_lang('NumberOfSessions')
            );
        }

        $tbl_track_login = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_sessions = Database::get_main_table(TABLE_MAIN_SESSION);

        $sqlCoachs = "SELECT DISTINCT
                        scu.user_id as id_coach,
                        u.id as user_id,
                        lastname,
                        firstname,
                        MAX(login_date) as login_date
                        FROM $tbl_user u, $tbl_session_course_user scu, $tbl_track_login
                        WHERE
                            scu.user_id = u.id AND scu.status=2 AND login_user_id=u.id
                        GROUP BY user_id ";

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sqlCoachs = "SELECT DISTINCT
                                    scu.user_id as id_coach,
                                    u.id as user_id,
                                    lastname,
                                    firstname,
                                    MAX(login_date) as login_date
                                FROM $tbl_user u,
                                $tbl_session_course_user scu,
                                $tbl_track_login ,
                                $tbl_session_rel_access_url session_rel_url
                                WHERE
                                    scu.user_id = u.id AND
                                    scu.status = 2 AND
                                    login_user_id = u.id AND
                                    access_url_id = $access_url_id AND
                                    session_rel_url.session_id = scu.session_id
                                GROUP BY u.id";
            }
        }
        if (!empty($order[$tracking_column])) {
            $sqlCoachs .= " ORDER BY ".$order[$tracking_column]." ".$tracking_direction;
        }

        $result_coaches = Database::query($sqlCoachs);
        $total_no_coaches = Database::num_rows($result_coaches);
        $global_coaches = array();
        while ($coach = Database::fetch_array($result_coaches)) {
            $global_coaches[$coach['user_id']] = $coach;
        }

        $sql_session_coach = 'SELECT session.id_coach, u.id as user_id, lastname, firstname, MAX(login_date) as login_date
                                FROM '.$tbl_user.' u ,'.$tbl_sessions.' as session,'.$tbl_track_login.'
                                WHERE id_coach = u.id AND login_user_id = u.id
                                GROUP BY u.id
                                ORDER BY login_date '.$tracking_direction;

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql_session_coach = 'SELECT session.id_coach, u.id as user_id, lastname, firstname, MAX(login_date) as login_date
					FROM '.$tbl_user.' u ,'.$tbl_sessions.' as session, '.$tbl_track_login.' , '.$tbl_session_rel_access_url.' as session_rel_url
					WHERE
					    id_coach = u.id AND
					    login_user_id = u.id  AND
					    access_url_id = '.$access_url_id.' AND
					    session_rel_url.session_id = session.id
					GROUP BY  u.id
					ORDER BY login_date '.$tracking_direction;
            }
        }

        $result_sessions_coach = Database::query($sql_session_coach);
        $total_no_coaches += Database::num_rows($result_sessions_coach);
        while ($coach = Database::fetch_array($result_sessions_coach)) {
            $global_coaches[$coach['user_id']] = $coach;
        }

        $all_datas = array();
        foreach ($global_coaches as $id_coach => $coaches) {
            $time_on_platform   = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($coaches['user_id']));
            $last_connection    = Tracking :: get_last_connection_date($coaches['user_id']);
            $nb_students        = count(Tracking :: get_student_followed_by_coach($coaches['user_id']));
            $nb_courses         = count(Tracking :: get_courses_followed_by_coach($coaches['user_id']));
            $nb_sessions        = count(Tracking :: get_sessions_coached_by_user($coaches['user_id']));

            $table_row = array();
            if ($is_western_name_order) {
                $table_row[] = $coaches['firstname'];
                $table_row[] = $coaches['lastname'];
            } else {
                $table_row[] = $coaches['lastname'];
                $table_row[] = $coaches['firstname'];
            }
            $table_row[] = $time_on_platform;
            $table_row[] = $last_connection;
            $table_row[] = $nb_students;
            $table_row[] = $nb_courses;
            $table_row[] = $nb_sessions;
            $table_row[] = '<a href="session.php?id_coach='.$coaches['user_id'].'">
                '.Display::return_icon('2rightarrow.png').'
            </a>';
            $all_datas[] = $table_row;

            if ($is_western_name_order) {
                $csv_content[] = array(
                    api_html_entity_decode($coaches['firstname'], ENT_QUOTES),
                    api_html_entity_decode($coaches['lastname'], ENT_QUOTES),
                    $time_on_platform,
                    $last_connection,
                    $nb_students,
                    $nb_courses,
                    $nb_sessions
                );
            } else {
                $csv_content[] = array(
                    api_html_entity_decode($coaches['lastname'], ENT_QUOTES),
                    api_html_entity_decode($coaches['firstname'], ENT_QUOTES),
                    $time_on_platform,
                    $last_connection,
                    $nb_students,
                    $nb_courses,
                    $nb_sessions
                );
            }
        }

        if ($tracking_column != 3) {
            if ($tracking_direction == 'DESC') {
                usort($all_datas, array('MySpace','rsort_users'));
            } else {
                usort($all_datas, array('MySpace','sort_users'));
            }
        }

        if ($export_csv && $tracking_column != 3) {
            usort($csv_content, 'sort_users');
        }
        if ($export_csv) {
            $csv_content = array_merge($csv_header, $csv_content);
        }

        foreach ($all_datas as $row) {
            $table -> addRow($row, 'align="right"');
        }
        $table -> display();
    }

    public static function count_coaches() {
        global $total_no_coaches;
        return $total_no_coaches;
    }

    public static function sort_users($a, $b) {
        return api_strcmp(trim(api_strtolower($a[$_SESSION['tracking_column']])), trim(api_strtolower($b[$_SESSION['tracking_column']])));
    }

    public static function rsort_users($a, $b) {
        return api_strcmp(trim(api_strtolower($b[$_SESSION['tracking_column']])), trim(api_strtolower($a[$_SESSION['tracking_column']])));
    }

    /**
     * Display a sortable table that contains an overview off all the progress of the user in a session
     * @author César Perales <cesar.perales@beeznest.com>, Beeznest Team
     */
    public static function display_tracking_lp_progress_overview($sessionId = '', $courseId = '', $date_from, $date_to)
    {
        $course = api_get_course_info_by_id($courseId);
        /**
         * Column name
         * The order is important you need to check the $column variable in the model.ajax.php file
         */
        $columns = array(
            get_lang('Username'),
            get_lang('FirstName'),
            get_lang('LastName'),
        );
        //add lessons of course
        $lessons = LearnpathList::get_course_lessons($course['code'], $sessionId);

        //create columns array
        foreach ($lessons as $lesson_id => $lesson) {
            $columns[] = $lesson['name'];
        }

        $columns[] = get_lang('Total');

        /**
         * Column config
         */
        $column_model   = array(
            array(
                'name' => 'username',
                'index' => 'username',
                'align' => 'left',
                'search' => 'true',
                'wrap_cell' => "true",
            ),
            array(
                'name' => 'firstname',
                'index' => 'firstname',
                'align' => 'left',
                'search' => 'true',
            ),
            array(
                'name' => 'lastname',
                'index' => 'lastname',
                'align' => 'left',
                'search' => 'true',
            ),
        );

        // Get dinamic column names
        foreach ($lessons as $lesson_id => $lesson) {
            $column_model[] = array(
                'name' => $lesson['id'],
                'index' => $lesson['id'],
                'align' => 'left',
                'search' => 'true',
            );
        }

        $column_model[] = array(
            'name' => 'total',
            'index' => 'total',
            'align' => 'left',
            'search' => 'true',
        );

        $action_links = '';
        // jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_session_lp_progress&session_id=' . $sessionId . '&course_id=' . $courseId . '&date_to=' . $date_to . '&date_from=' . $date_from;

        //Table Id
        $tableId = 'lpProgress';

        //Autowidth
        $extra_params['autowidth'] = 'true';

        //height auto
        $extra_params['height'] = 'auto';

        $table = Display::grid_js(
            $tableId,
            $url,
            $columns,
            $column_model,
            $extra_params,
            array(),
            $action_links,
            true
        );

        $return = '<script>$(function() {'. $table .
            'jQuery("#'.$tableId.'").jqGrid("navGrid","#'.$tableId.'_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
                jQuery("#'.$tableId.'").jqGrid("navButtonAdd","#'.$tableId.'_pager",{
                       caption:"",
                       title:"' . get_lang('ExportExcel') . '",
                       onClickButton : function () {
                           jQuery("#'.$tableId.'").jqGrid("excelExport",{"url":"'.$url.'&export_format=xls"});
                       }
                });
            });</script>';
        $return .= Display::grid_html($tableId);
        return $return;
    }

    /**
     * Display a sortable table that contains an overview off all the progress of the user in a session
     * @param   int $sessionId  The session ID
     * @param   int $courseId   The course ID
     * @param   int $exerciseId The quiz ID
     * @param   int $answer Answer status (0 = incorrect, 1 = correct, 2 = both)
     * @return  string  HTML array of results formatted for gridJS
     * @author César Perales <cesar.perales@beeznest.com>, Beeznest Team
     */
    public static function display_tracking_exercise_progress_overview(
        $sessionId = 0,
        $courseId = 0,
        $exerciseId = 0,
        $date_from = null,
        $date_to = null
    ) {
        $date_from = Security::remove_XSS($date_from);
        $date_to = Security::remove_XSS($date_to);
        /**
         * Column names
         * The column order is important. Check $column variable in the main/inc/ajax/model.ajax.php file
         */
        $columns = array(
            get_lang('Session'),
            get_lang('ExerciseId'),
            get_lang('ExerciseName'),
            get_lang('Username'),
            get_lang('LastName'),
            get_lang('FirstName'),
            get_lang('Time'),
            get_lang('QuestionId'),
            get_lang('QuestionTitle'),
            get_lang('WorkDescription'),
            get_lang('Answer'),
            get_lang('Correct'),
        );

        /**
         * Column config
         */
        $column_model   = array(
            array('name'=>'session', 'index'=>'session', 'align'=>'left', 'search' => 'true', 'wrap_cell' => "true"),
            array('name'=>'exercise_id', 'index'=>'exercise_id', 'align'=>'left', 'search' => 'true'),
            array('name'=>'quiz_title', 'index'=>'quiz_title', 'align'=>'left', 'search' => 'true'),
            array('name'=>'username', 'index'=>'username', 'align'=>'left', 'search' => 'true'),
            array('name'=>'lastname', 'index'=>'lastname', 'align'=>'left', 'search' => 'true'),
            array('name'=>'firstname', 'index'=>'firstname', 'align'=>'left', 'search' => 'true'),
            array('name'=>'time', 'index'=>'time', 'align'=>'left', 'search' => 'true', 'wrap_cell' => "true"),
            array('name'=>'question_id', 'index'=>'question_id', 'align'=>'left', 'search' => 'true'),
            array('name'=>'question', 'index'=>'question', 'align'=>'left', 'search' => 'true', 'wrap_cell' => "true"),
            array('name'=>'description', 'index'=>'description', 'align'=>'left', 'width' => '550', 'search' => 'true', 'wrap_cell' => "true"),
            array('name'=>'answer', 'index'=>'answer', 'align'=>'left', 'search' => 'true', 'wrap_cell' => "true"),
            array('name'=>'correct', 'index'=>'correct', 'align'=>'left', 'search' => 'true', 'wrap_cell' => "true"),
        );
        //get dynamic column names

        // jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_exercise_progress&session_id=' . $sessionId . '&course_id=' . $courseId  . '&exercise_id=' . $exerciseId . '&date_to=' . $date_to . '&date_from=' . $date_from;

        // Autowidth
        $extra_params['autowidth'] = 'true';

        // height auto
        $extra_params['height'] = 'auto';

        $tableId = 'exerciseProgressOverview';
        $table = Display::grid_js($tableId, $url, $columns, $column_model, $extra_params, array(), '', true);

        $return = '<script>$(function() {'. $table .
            'jQuery("#'.$tableId.'").jqGrid("navGrid","#'.$tableId.'_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
                jQuery("#'.$tableId.'").jqGrid("navButtonAdd","#'.$tableId.'_pager",{
                       caption:"",
                       title:"' . get_lang('ExportExcel') . '",
                       onClickButton : function () {
                           jQuery("#'.$tableId.'").jqGrid("excelExport",{"url":"'.$url.'&export_format=xls"});
                       }
                });
            });</script>';
        $return .= Display::grid_html($tableId);
        return $return;
    }

    /**
     * Displays a form with all the additionally defined user fields of the profile
     * and give you the opportunity to include these in the CSV export
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @version 1.8.6
     * @since November 2008
     */
    public static function display_user_overview_export_options()
    {
        $message = '';
        // include the user manager and formvalidator library
        if (isset($_GET['export']) && $_GET['export'] == 'options') {
            // get all the defined extra fields
            $extrafields = UserManager::get_extra_fields(0, 50, 5, 'ASC', false, 1);

            // creating the form with all the defined extra fields
            $form = new FormValidator(
                'exportextrafields',
                'post',
                api_get_self()."?view=".Security::remove_XSS($_GET['view']).'&display='.Security::remove_XSS($_GET['display']).'&export='.Security::remove_XSS($_GET['export'])
            );

            if (is_array($extrafields) && count($extrafields) > 0) {
                foreach ($extrafields as $key => $extra) {
                    $form->addElement('checkbox', 'extra_export_field'.$extra[0], '', $extra[3]);
                }
                $form->addButtonSave(get_lang('Ok'), 'submit');

                // setting the default values for the form that contains all the extra fields
                if (is_array($_SESSION['additional_export_fields'])) {
                    foreach ($_SESSION['additional_export_fields'] as $key => $value) {
                        $defaults['extra_export_field'.$value] = 1;
                    }
                }
                $form->setDefaults($defaults);
            } else {
                $form->addElement('html', Display::display_warning_message(get_lang('ThereAreNotExtrafieldsAvailable')));
            }

            if ($form->validate()) {
                // exporting the form values
                $values = $form->exportValues();

                // re-initialising the session that contains the additional fields that need to be exported
                $_SESSION['additional_export_fields'] = array();

                // adding the fields that are checked to the session
                $message = '';
                foreach ($values as $field_ids => $value) {
                    if ($value == 1 && strstr($field_ids,'extra_export_field')) {
                        $_SESSION['additional_export_fields'][] = str_replace('extra_export_field', '', $field_ids);
                    }
                }

                // adding the fields that will be also exported to a message string
                if (is_array($_SESSION['additional_export_fields'])) {
                    foreach ($_SESSION['additional_export_fields'] as $key => $extra_field_export) {
                        $message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
                    }
                }

                // Displaying a feedback message
                if (!empty($_SESSION['additional_export_fields'])) {
                    Display::display_confirmation_message(get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>', false);
                } else  {
                    Display::display_confirmation_message(get_lang('NoAdditionalFieldsWillBeExported'), false);
                }
            } else {
                $form->display();
            }

        } else {
            if (!empty($_SESSION['additional_export_fields'])) {
                // get all the defined extra fields
                $extrafields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

                foreach ($_SESSION['additional_export_fields'] as $key => $extra_field_export) {
                    $message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
                }

                Display::display_normal_message(get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>', false);
            }
        }
    }

    /**
     * Display a sortable table that contains an overview of all the reporting progress of all courses
     */
    public static function display_tracking_course_overview()
    {
        $t_head = '    <table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
        //$t_head .= '  <caption>'.get_lang('CourseInformation').'</caption>';
        $t_head .=      '<tr>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'), 6, true).'</span></th>';
        //$t_head .= '      <th><div style="width:40px">'.get_lang('AvgExercisesScore').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfMessages'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfAssignments'), 6, true).'</span></th>';
        $t_head .= '        <th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
        //$t_head .= '      <th><div>'.get_lang('TotalExercisesScorePossible').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'), 6, true).'</span></th>';
        //$t_head .= '      <th><div>'.get_lang('TotalExercisesScorePercentage').'</div></th>';
        //$t_head .= '      <th><div style="width:60px">'.get_lang('FirstLogin').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
        $t_head .= '    </tr></table>';

        $addparams = array('view' => 'admin', 'display' => 'courseoverview');

        $table = new SortableTable('tracking_session_overview', array('MySpace', 'get_total_number_courses'), array('MySpace','get_course_data_tracking_overview'), 1);
        $table->additional_parameters = $addparams;

        $table->set_header(0, '', false, null, array('style' => 'display: none'));
        $table->set_header(1, get_lang('Course'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
        $table->set_header(2, $t_head, false, array('style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'), array('style' => 'width:90%;padding:0;font-size:7.5pt;'));
        $table->set_column_filter(2, array('MySpace','course_tracking_filter'));
        $table->display();
    }

    /**
     * Get the total number of courses
     *
     * @return integer Total number of courses
     */
    public static function get_total_number_courses()
    {
        // database table definition
        $main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);

        return Database::count_rows($main_course_table);
    }

    /**
     * Get data for the courses
     *
     * @param int Inferior limit
     * @param int Number of items to select
     * @param string Column to order on
     * @param string Order direction
     * @return array Results
     */
    public static function get_course_data_tracking_overview($from, $number_of_items, $column, $direction)
    {
        $main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        $sql = "SELECT code AS col0, title AS col1 FROM $main_course_table";
        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";
        $result = Database::query($sql);
        $return = array ();
        while ($course = Database::fetch_row($result)) {
            $return[] = $course;
        }
        return $return;
    }

    /**
     * Fills in course reporting data
     *
     * @param integer course code
     * @param array $url_params additional url parameters
     * @param array $row the row information (the other columns)
     * @return string html code
     */
    public static function course_tracking_filter($course_code, $url_params, $row)
    {
        $course_code = $row[0];
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        // the table header
        $return = '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';

        // database table definition
        $tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

        // getting all the courses of the user
        $sql = "SELECT *
                FROM $tbl_user AS u
                INNER JOIN $tbl_course_rel_user AS cu
                ON cu.user_id = u.user_id
                WHERE cu.c_id = '".$courseId."'";
        $result = Database::query($sql);
        $time_spent = 0;
        $progress = 0;
        $nb_progress_lp = 0;
        $score = 0;
        $nb_score_lp = 0;
        $nb_messages = 0;
        $nb_assignments = 0;
        $last_login_date = false;
        $total_score_obtained = 0;
        $total_score_possible = 0;
        $total_questions_answered = 0;
        while ($row = Database::fetch_object($result)) {
            // get time spent in the course and session
            $time_spent += Tracking::get_time_spent_on_the_course($row->user_id, $courseInfo['real_id']);
            $progress_tmp = Tracking::get_avg_student_progress($row->user_id, $course_code, array(), null, true);
            $progress += $progress_tmp[0];
            $nb_progress_lp += $progress_tmp[1];
            $score_tmp = Tracking :: get_avg_student_score($row->user_id, $course_code, array(), null, true);
            if(is_array($score_tmp)) {
                $score += $score_tmp[0];
                $nb_score_lp += $score_tmp[1];
            }
            $nb_messages += Tracking::count_student_messages($row->user_id, $course_code);
            $nb_assignments += Tracking::count_student_assignments($row->user_id, $course_code);
            $last_login_date_tmp = Tracking :: get_last_connection_date_on_the_course($row->user_id, $courseInfo, null, false);
            if($last_login_date_tmp != false && $last_login_date == false) { // TODO: To be cleaned
                $last_login_date = $last_login_date_tmp;
            } else if($last_login_date_tmp != false && $last_login_date != false) { // TODO: Repeated previous condition. To be cleaned.
                // Find the max and assign it to first_login_date
                if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                    $last_login_date = $last_login_date_tmp;
                }
            }

            $exercise_results_tmp = MySpace::exercises_results($row->user_id, $course_code);
            $total_score_obtained += $exercise_results_tmp['score_obtained'];
            $total_score_possible += $exercise_results_tmp['score_possible'];
            $total_questions_answered += $exercise_results_tmp['questions_answered'];
        }
        if ($nb_progress_lp > 0) {
            $avg_progress = round($progress / $nb_progress_lp, 2);
        } else {
            $avg_progress = 0;
        }
        if ($nb_score_lp > 0) {
            $avg_score = round($score / $nb_score_lp, 2);
        } else {
            $avg_score = '-';
        }
        if ($last_login_date) {
            $last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
        } else {
            $last_login_date = '-';
        }
        if ($total_score_possible > 0) {
            $total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
        } else {
            $total_score_percentage = 0;
        }
        if ($total_score_percentage > 0) {
            $total_score = $total_score_obtained.'/'.$total_score_possible.' ('.$total_score_percentage.' %)';
        } else {
            $total_score = '-';
        }
        $return .= '<tr>';
        // time spent in the course
        $return .= '    <td style="width:164px;">'.api_time_to_hms($time_spent).'</td>';
        // student progress in course
        $return .= '    <td>'.$avg_progress.'</td>';
        // student score
        $return .= '    <td>'.$avg_score.'</td>';
        // student messages
        $return .= '    <td>'.$nb_messages.'</td>';
        // student assignments
        $return .= '    <td>'.$nb_assignments.'</td>';
        // student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
        $return .= '<td width="105px;">'.$total_score.'</td>';
        $return .= '<td>'.$total_questions_answered.'</td>';
        // last connection
        $return .= '    <td>'.$last_login_date.'</td>';
        $return .= '</tr>';
        $return .= '</table>';
        return $return;
    }

    /**
     * This function exports the table that we see in display_tracking_course_overview()
     *
     */
    public static function export_tracking_course_overview()
    {
        // database table definition
        $tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

        // the values of the sortable table
        if ($_GET['tracking_course_overview_page_nr']) {
            $from = $_GET['tracking_course_overview_page_nr'];
        } else {
            $from = 0;
        }
        if ($_GET['tracking_course_overview_column']) {
            $orderby = $_GET['tracking_course_overview_column'];
        } else {
            $orderby = 0;
        }

        if ($_GET['tracking_course_overview_direction']) {
            $direction = $_GET['tracking_course_overview_direction'];
        } else {
            $direction = 'ASC';
        }

        $course_data = MySpace::get_course_data_tracking_overview($from, 1000, $orderby, $direction);

        $csv_content = array();

        // the first line of the csv file with the column headers
        $csv_row = array();
        $csv_row[] = get_lang('Course', '');
        $csv_row[] = get_lang('AvgTimeSpentInTheCourse', '');
        $csv_row[] = get_lang('AvgStudentsProgress', '');
        $csv_row[] = get_lang('AvgCourseScore', '');
        $csv_row[] = get_lang('TotalNumberOfMessages', '');
        $csv_row[] = get_lang('TotalNumberOfAssignments', '');
        $csv_row[] = get_lang('TotalExercisesScoreObtained', '');
        $csv_row[] = get_lang('TotalExercisesScorePossible', '');
        $csv_row[] = get_lang('TotalExercisesAnswered', '');
        $csv_row[] = get_lang('TotalExercisesScorePercentage', '');
        $csv_row[] = get_lang('LatestLogin', '');
        $csv_content[] = $csv_row;

        // the other lines (the data)
        foreach ($course_data as $key => $course) {
            $course_code = $course[0];
            $courseInfo = api_get_course_info($course_code);
            $course_title = $courseInfo['title'];
            $courseId = $courseInfo['real_id'];

            $csv_row = array();
            $csv_row[] = $course_title;

            // getting all the courses of the session
            $sql = "SELECT *
                    FROM $tbl_user AS u
                    INNER JOIN $tbl_course_rel_user AS cu
                    ON cu.user_id = u.user_id
                    WHERE cu.c_id = '".$courseId."'";
            $result = Database::query($sql);
            $time_spent = 0;
            $progress = 0;
            $nb_progress_lp = 0;
            $score = 0;
            $nb_score_lp = 0;
            $nb_messages = 0;
            $nb_assignments = 0;
            $last_login_date = false;
            $total_score_obtained = 0;
            $total_score_possible = 0;
            $total_questions_answered = 0;
            while ($row = Database::fetch_object($result)) {
                // get time spent in the course and session
                $time_spent += Tracking::get_time_spent_on_the_course($row->user_id, $courseId);
                $progress_tmp = Tracking::get_avg_student_progress($row->user_id, $course_code, array(), null, true);
                $progress += $progress_tmp[0];
                $nb_progress_lp += $progress_tmp[1];
                $score_tmp = Tracking :: get_avg_student_score($row->user_id, $course_code, array(), null, true);
                if(is_array($score_tmp)) {
                    $score += $score_tmp[0];
                    $nb_score_lp += $score_tmp[1];
                }
                $nb_messages += Tracking::count_student_messages($row->user_id, $course_code);
                $nb_assignments += Tracking::count_student_assignments($row->user_id, $course_code);

                $last_login_date_tmp = Tracking::get_last_connection_date_on_the_course($row->user_id, $courseInfo, null, false);
                if($last_login_date_tmp != false && $last_login_date == false) { // TODO: To be cleaned.
                    $last_login_date = $last_login_date_tmp;
                } else if($last_login_date_tmp != false && $last_login_date == false) { // TODO: Repeated previous condition. To be cleaned.
                    // Find the max and assign it to first_login_date
                    if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                        $last_login_date = $last_login_date_tmp;
                    }
                }

                $exercise_results_tmp = MySpace::exercises_results($row->user_id, $course_code);
                $total_score_obtained += $exercise_results_tmp['score_obtained'];
                $total_score_possible += $exercise_results_tmp['score_possible'];
                $total_questions_answered += $exercise_results_tmp['questions_answered'];
            }
            if($nb_progress_lp > 0) {
                $avg_progress = round($progress / $nb_progress_lp, 2);
            } else {
                $avg_progress = 0;
            }
            if($nb_score_lp > 0) {
                $avg_score = round($score / $nb_score_lp, 2);
            } else {
                $avg_score = '-';
            }
            if($last_login_date) {
                $last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
            } else {
                $last_login_date = '-';
            }
            if($total_score_possible > 0) {
                $total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
            } else {
                $total_score_percentage = 0;
            }
            // time spent in the course
            $csv_row[] = api_time_to_hms($time_spent);
            // student progress in course
            $csv_row[] = $avg_progress;
            // student score
            $csv_row[] = $avg_score;
            // student messages
            $csv_row[] = $nb_messages;
            // student assignments
            $csv_row[] = $nb_assignments;
            // student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
            $csv_row[] = $total_score_obtained;
            $csv_row[] = $total_score_possible;
            $csv_row[] = $total_questions_answered;
            $csv_row[] = $total_score_percentage;
            // last connection
            $csv_row[] = $last_login_date;
            $csv_content[] = $csv_row;
        }
        Export :: arrayToCsv($csv_content, 'reporting_course_overview');
        exit;
    }

    /**
     * Display a sortable table that contains an overview of all the reporting progress of all sessions and all courses the user is subscribed to
     * @author Guillaume Viguier <guillaume@viguierjust.com>
     */
    public static function display_tracking_session_overview()
    {
        $t_head = '    <table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
        //$t_head .= '  <caption>'.get_lang('CourseInformation').'</caption>';
        $t_head .=      '<tr>';
        $t_head .= '        <th width="155px" style="border-left:0;border-bottom:0"><span>'.get_lang('Course').'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'), 6, true).'</span></th>';
        //$t_head .= '      <th><div style="width:40px">'.get_lang('AvgExercisesScore').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfMessages'), 6, true).'</span></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfAssignments'), 6, true).'</span></th>';
        $t_head .= '        <th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
        //$t_head .= '      <th><div>'.get_lang('TotalExercisesScorePossible').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'), 6, true).'</span></th>';
        //$t_head .= '      <th><div>'.get_lang('TotalExercisesScorePercentage').'</div></th>';
        //$t_head .= '      <th><div style="width:60px">'.get_lang('FirstLogin').'</div></th>';
        $t_head .= '        <th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
        $t_head .= '    </tr></table>';

        $addparams = array('view' => 'admin', 'display' => 'sessionoverview');

        $table = new SortableTable('tracking_session_overview', array('MySpace','get_total_number_sessions'), array('MySpace','get_session_data_tracking_overview'), 1);
        $table->additional_parameters = $addparams;

        $table->set_header(0, '', false, null, array('style' => 'display: none'));
        $table->set_header(1, get_lang('Session'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
        $table->set_header(2, $t_head, false, array('style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'), array('style' => 'width:90%;padding:0;font-size:7.5pt;'));
        $table->set_column_filter(2, array('MySpace', 'session_tracking_filter'));
        $table->display();
    }

    /**
     * Get the total number of sessions
     *
     * @return integer Total number of sessions
     */
    public static function get_total_number_sessions()
    {
        // database table definition
        $main_session_table = Database :: get_main_table(TABLE_MAIN_SESSION);
        return Database::count_rows($main_session_table);
    }

    /**
     * Get data for the sessions
     *
     * @param int Inferior limit
     * @param int Number of items to select
     * @param string Column to order on
     * @param string Order direction
     * @return array Results
     */
    public static function get_session_data_tracking_overview($from, $number_of_items, $column, $direction)
    {
        $main_session_table = Database :: get_main_table(TABLE_MAIN_SESSION);

        $sql = "SELECT id AS col0, name AS col1 FROM $main_session_table";
        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";
        $result = Database::query($sql);
        $return = array ();
        while ($session = Database::fetch_row($result)) {
            $return[] = $session;
        }
        return $return;
    }

    /**
     * Fills in session reporting data
     *
     * @param integer $user_id the id of the user
     * @param array $url_params additonal url parameters
     * @param array $row the row information (the other columns)
     * @return string html code
     */
    public static function session_tracking_filter($session_id, $url_params, $row)
    {
        $session_id = $row[0];
        // the table header
        $return = '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
        /*$return .= '  <tr>';
        $return .= '        <th>'.get_lang('Course').'</th>';
        $return .= '        <th>'.get_lang('AvgTimeSpentInTheCourse').'</th>';
        $return .= '        <th>'.get_lang('AvgStudentsProgress').'</th>';
        $return .= '        <th>'.get_lang('AvgCourseScore').'</th>';
        $return .= '        <th>'.get_lang('AvgExercisesScore').'</th>';
        $return .= '        <th>'.get_lang('AvgMessages').'</th>';
        $return .= '        <th>'.get_lang('AvgAssignments').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesScoreObtained').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesScorePossible').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesAnswered').'</th>';
        $return .= '        <th>'.get_lang('TotalExercisesScorePercentage').'</th>';
        $return .= '        <th>'.get_lang('FirstLogin').'</th>';
        $return .= '        <th>'.get_lang('LatestLogin').'</th>';
        $return .= '    </tr>';*/

        // database table definition
        $tbl_session_rel_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

        // getting all the courses of the user
        $sql = "SELECT * FROM $tbl_course AS c
                INNER JOIN $tbl_session_rel_course AS sc
                ON sc.c_id = c.id
                WHERE sc.session_id = '".$session_id."';";
        $result = Database::query($sql);
        while ($row = Database::fetch_object($result)) {
            $courseId = $row->c_id;
            $courseInfo = api_get_course_info_by_id($courseId);
            $return .= '<tr>';
            // course code
            $return .= '    <td width="157px" >'.$row->title.'</td>';
            // get the users in the course
            $sql = "SELECT u.user_id
                    FROM $tbl_user AS u
                    INNER JOIN $tbl_session_rel_course_rel_user AS scu
                    ON u.user_id = scu.user_id
                    WHERE scu.session_id = '".$session_id."' AND scu.c_id = '".$courseId."'";
            $result_users = Database::query($sql);
            $time_spent = 0;
            $progress = 0;
            $nb_progress_lp = 0;
            $score = 0;
            $nb_score_lp = 0;
            $nb_messages = 0;
            $nb_assignments = 0;
            $last_login_date = false;
            $total_score_obtained = 0;
            $total_score_possible = 0;
            $total_questions_answered = 0;
            while ($row_user = Database::fetch_object($result_users)) {
                // get time spent in the course and session
                $time_spent += Tracking::get_time_spent_on_the_course($row_user->user_id, $courseId, $session_id);
                $progress_tmp = Tracking::get_avg_student_progress($row_user->user_id, $row->code, array(), $session_id, true);
                $progress += $progress_tmp[0];
                $nb_progress_lp += $progress_tmp[1];
                $score_tmp = Tracking :: get_avg_student_score($row_user->user_id, $row->code, array(), $session_id, true);
                if (is_array($score_tmp)) {
                    $score += $score_tmp[0];
                    $nb_score_lp += $score_tmp[1];
                }
                $nb_messages += Tracking::count_student_messages($row_user->user_id, $row->code, $session_id);
                $nb_assignments += Tracking::count_student_assignments($row_user->user_id, $row->code, $session_id);
                $last_login_date_tmp = Tracking::get_last_connection_date_on_the_course($row_user->user_id, $courseInfo, $session_id, false);
                if ($last_login_date_tmp != false && $last_login_date == false) {
                    // TODO: To be cleaned.
                    $last_login_date = $last_login_date_tmp;
                } else if($last_login_date_tmp != false && $last_login_date != false) {
                    // TODO: Repeated previous condition! To be cleaned.
                    // Find the max and assign it to first_login_date
                    if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                        $last_login_date = $last_login_date_tmp;
                    }
                }

                $exercise_results_tmp = MySpace::exercises_results($row_user->user_id, $row->code, $session_id);
                $total_score_obtained += $exercise_results_tmp['score_obtained'];
                $total_score_possible += $exercise_results_tmp['score_possible'];
                $total_questions_answered += $exercise_results_tmp['questions_answered'];
            }
            if($nb_progress_lp > 0) {
                $avg_progress = round($progress / $nb_progress_lp, 2);
            } else {
                $avg_progress = 0;
            }
            if($nb_score_lp > 0) {
                $avg_score = round($score / $nb_score_lp, 2);
            } else {
                $avg_score = '-';
            }
            if($last_login_date) {
                $last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
            } else {
                $last_login_date = '-';
            }
            if($total_score_possible > 0) {
                $total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
            } else {
                $total_score_percentage = 0;
            }
            if($total_score_percentage > 0) {
                $total_score = $total_score_obtained.'/'.$total_score_possible.' ('.$total_score_percentage.' %)';
            } else {
                $total_score = '-';
            }
            // time spent in the course
            $return .= '    <td><div>'.api_time_to_hms($time_spent).'</div></td>';
            // student progress in course
            $return .= '    <td><div>'.$avg_progress.'</div></td>';
            // student score
            $return .= '    <td><div>'.$avg_score.'</div></td>';
            // student messages
            $return .= '    <td><div>'.$nb_messages.'</div></td>';
            // student assignments
            $return .= '    <td><div>'.$nb_assignments.'</div></td>';
            // student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
            $return .= '<td width="105px;">'.$total_score.'</td>';
            $return .= '<td>'.$total_questions_answered.'</td>';
            // last connection
            $return .= '    <td><div>'.$last_login_date.'</div></td>';
            $return .= '<tr>';
        }
        $return .= '</table>';
        return $return;
    }

    /**
     * This function exports the table that we see in display_tracking_session_overview()
     *
     */
    public static function export_tracking_session_overview()
    {
        // database table definition
        $tbl_session_rel_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

        // the values of the sortable table
        if ($_GET['tracking_session_overview_page_nr']) {
            $from = $_GET['tracking_session_overview_page_nr'];
        } else {
            $from = 0;
        }
        if ($_GET['tracking_session_overview_column']) {
            $orderby = $_GET['tracking_session_overview_column'];
        } else {
            $orderby = 0;
        }

        if ($_GET['tracking_session_overview_direction']) {
            $direction = $_GET['tracking_session_overview_direction'];
        } else {
            $direction = 'ASC';
        }

        $session_data = MySpace::get_session_data_tracking_overview($from, 1000, $orderby, $direction);

        $csv_content = array();

        // the first line of the csv file with the column headers
        $csv_row = array();
        $csv_row[] = get_lang('Session');
        $csv_row[] = get_lang('Course', '');
        $csv_row[] = get_lang('AvgTimeSpentInTheCourse', '');
        $csv_row[] = get_lang('AvgStudentsProgress', '');
        $csv_row[] = get_lang('AvgCourseScore', '');
        $csv_row[] = get_lang('TotalNumberOfMessages', '');
        $csv_row[] = get_lang('TotalNumberOfAssignments', '');
        $csv_row[] = get_lang('TotalExercisesScoreObtained', '');
        $csv_row[] = get_lang('TotalExercisesScorePossible', '');
        $csv_row[] = get_lang('TotalExercisesAnswered', '');
        $csv_row[] = get_lang('TotalExercisesScorePercentage', '');
        $csv_row[] = get_lang('LatestLogin', '');
        $csv_content[] = $csv_row;

        // the other lines (the data)
        foreach ($session_data as $key => $session) {
            $session_id = $session[0];
            $session_title = $session[1];

            // getting all the courses of the session
            $sql = "SELECT * FROM $tbl_course AS c
                    INNER JOIN $tbl_session_rel_course AS sc
                    ON sc.c_id = c.id
                    WHERE sc.session_id = '".$session_id."';";
            $result = Database::query($sql);
            while ($row = Database::fetch_object($result)) {
                $courseId = $row->c_id;
                $courseInfo = api_get_course_info_by_id($courseId);
                $csv_row = array();
                $csv_row[] = $session_title;
                $csv_row[] = $row->title;
                // get the users in the course
                $sql = "SELECT scu.user_id
                        FROM $tbl_user AS u
                        INNER JOIN $tbl_session_rel_course_rel_user AS scu
                        ON u.user_id = scu.user_id
                        WHERE scu.session_id = '".$session_id."' AND scu.c_id = '".$courseId."'";
                $result_users = Database::query($sql);
                $time_spent = 0;
                $progress = 0;
                $nb_progress_lp = 0;
                $score = 0;
                $nb_score_lp = 0;
                $nb_messages = 0;
                $nb_assignments = 0;
                $last_login_date = false;
                $total_score_obtained = 0;
                $total_score_possible = 0;
                $total_questions_answered = 0;
                while($row_user = Database::fetch_object($result_users)) {
                    // get time spent in the course and session
                    $time_spent += Tracking::get_time_spent_on_the_course($row_user->user_id, $courseId, $session_id);
                    $progress_tmp = Tracking::get_avg_student_progress($row_user->user_id, $row->code, array(), $session_id, true);
                    $progress += $progress_tmp[0];
                    $nb_progress_lp += $progress_tmp[1];
                    $score_tmp = Tracking :: get_avg_student_score($row_user->user_id, $row->code, array(), $session_id, true);
                    if (is_array($score_tmp)) {
                        $score += $score_tmp[0];
                        $nb_score_lp += $score_tmp[1];
                    }
                    $nb_messages += Tracking::count_student_messages(
                        $row_user->user_id,
                        $row->code,
                        $session_id
                    );

                    $nb_assignments += Tracking::count_student_assignments(
                        $row_user->user_id,
                        $row->code,
                        $session_id
                    );

                    $last_login_date_tmp = Tracking:: get_last_connection_date_on_the_course(
                        $row_user->user_id,
                        $courseInfo,
                        $session_id,
                        false
                    );
                    if($last_login_date_tmp != false && $last_login_date == false) { // TODO: To be cleaned.
                        $last_login_date = $last_login_date_tmp;
                    } else if($last_login_date_tmp != false && $last_login_date == false) { // TODO: Repeated previous condition. To be cleaned.
                        // Find the max and assign it to first_login_date
                        if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                            $last_login_date = $last_login_date_tmp;
                        }
                    }

                    $exercise_results_tmp = MySpace::exercises_results($row_user->user_id, $row->code, $session_id);
                    $total_score_obtained += $exercise_results_tmp['score_obtained'];
                    $total_score_possible += $exercise_results_tmp['score_possible'];
                    $total_questions_answered += $exercise_results_tmp['questions_answered'];
                }
                if($nb_progress_lp > 0) {
                    $avg_progress = round($progress / $nb_progress_lp, 2);
                } else {
                    $avg_progress = 0;
                }
                if($nb_score_lp > 0) {
                    $avg_score = round($score / $nb_score_lp, 2);
                } else {
                    $avg_score = '-';
                }
                if($last_login_date) {
                    $last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
                } else {
                    $last_login_date = '-';
                }
                if($total_score_possible > 0) {
                    $total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
                } else {
                    $total_score_percentage = 0;
                }
                if($total_score_percentage > 0) {
                    $total_score = $total_score_obtained.'/'.$total_score_possible.' ('.$total_score_percentage.' %)';
                } else {
                    $total_score = '-';
                }
                // time spent in the course
                $csv_row[] = api_time_to_hms($time_spent);
                // student progress in course
                $csv_row[] = $avg_progress;
                // student score
                $csv_row[] = $avg_score;
                // student messages
                $csv_row[] = $nb_messages;
                // student assignments
                $csv_row[] = $nb_assignments;
                // student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
                $csv_row[] = $total_score_obtained;
                $csv_row[] = $total_score_possible;
                $csv_row[] = $total_questions_answered;
                $csv_row[] = $total_score_percentage;
                // last connection
                $csv_row[] = $last_login_date;
                $csv_content[] = $csv_row;
            }
        }
        Export :: arrayToCsv($csv_content, 'reporting_session_overview');
        exit;
    }

    /**
     * Get general information about the exercise performance of the user
     * the total obtained score (all the score on all the questions)
     * the maximum score that could be obtained
     * the number of questions answered
     * the success percentage
     * @param integer $user_id the id of the user
     * @param string $course_code the course code
     * @return array
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @version Dokeos 1.8.6
     * @since November 2008
     */
    public static function exercises_results($user_id, $course_code, $session_id = false)
    {
        $courseId = api_get_course_int_id($course_code);
        $sql = 'SELECT exe_result, exe_weighting
                FROM '.Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES)."
                WHERE 
                    c_id = ' . $courseId . ' AND 
                    exe_user_id = '".intval($user_id)."'";
        if ($session_id !== false) {
            $sql .= " AND session_id = '".$session_id."' ";
        }
        $result = Database::query($sql);
        $score_obtained = 0;
        $score_possible = 0;
        $questions_answered = 0;
        while ($row = Database::fetch_array($result)) {
            $score_obtained += $row['exe_result'];
            $score_possible += $row['exe_weighting'];
            $questions_answered ++;
        }

        if ($score_possible != 0) {
            $percentage = round(($score_obtained / $score_possible * 100), 2);
        } else {
            $percentage = null;
        }

        return array(
            'score_obtained' => $score_obtained,
            'score_possible' => $score_possible,
            'questions_answered' => $questions_answered,
            'percentage' => $percentage
        );
    }

    /**
     * This function exports the table that we see in display_tracking_user_overview()
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @version Dokeos 1.8.6
     * @since October 2008
     */
    public static function export_tracking_user_overview()
    {
        // database table definitions
        $tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

        $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);

        // the values of the sortable table
        if ($_GET['tracking_user_overview_page_nr']) {
            $from = $_GET['tracking_user_overview_page_nr'];
        } else {
            $from = 0;
        }
        if ($_GET['tracking_user_overview_column']) {
            $orderby = $_GET['tracking_user_overview_column'];
        } else {
            $orderby = 0;
        }
        if ($is_western_name_order != api_is_western_name_order() && ($orderby == 1 || $orderby == 2)) {
            // Swapping the sorting column if name order for export is different than the common name order.
            $orderby = 3 - $orderby;
        }
        if ($_GET['tracking_user_overview_direction']) {
            $direction = $_GET['tracking_user_overview_direction'];
        } else {
            $direction = 'ASC';
        }

        $user_data = MySpace::get_user_data_tracking_overview($from, 1000, $orderby, $direction);

        // the first line of the csv file with the column headers
        $csv_row = array();
        $csv_row[] = get_lang('OfficialCode');
        if ($is_western_name_order) {
            $csv_row[] = get_lang('FirstName', '');
            $csv_row[] = get_lang('LastName', '');
        } else {
            $csv_row[] = get_lang('LastName', '');
            $csv_row[] = get_lang('FirstName', '');
        }
        $csv_row[] = get_lang('LoginName');
        $csv_row[] = get_lang('CourseCode');
        // the additional user defined fields (only those that were selected to be exported)

        $fields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

        if (is_array($_SESSION['additional_export_fields'])) {
            foreach ($_SESSION['additional_export_fields'] as $key => $extra_field_export) {
                $csv_row[] = $fields[$extra_field_export][3];
                $field_names_to_be_exported[] = 'extra_'.$fields[$extra_field_export][1];
            }
        }
        $csv_row[] = get_lang('AvgTimeSpentInTheCourse', '');
        $csv_row[] = get_lang('AvgStudentsProgress', '');
        $csv_row[] = get_lang('AvgCourseScore', '');
        $csv_row[] = get_lang('AvgExercisesScore', '');
        $csv_row[] = get_lang('AvgMessages', '');
        $csv_row[] = get_lang('AvgAssignments', '');
        $csv_row[] = get_lang('TotalExercisesScoreObtained', '');
        $csv_row[] = get_lang('TotalExercisesScorePossible', '');
        $csv_row[] = get_lang('TotalExercisesAnswered', '');
        $csv_row[] = get_lang('TotalExercisesScorePercentage', '');
        $csv_row[] = get_lang('FirstLogin', '');
        $csv_row[] = get_lang('LatestLogin', '');
        $csv_content[] = $csv_row;

        // the other lines (the data)
        foreach ($user_data as $key => $user) {
            // getting all the courses of the user
            $sql = "SELECT * FROM $tbl_course_user
                    WHERE user_id = '".intval($user[4])."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
            $result = Database::query($sql);
            while ($row = Database::fetch_row($result)) {
                $courseInfo = api_get_course_info($row['course_code']);
                $courseId = $courseInfo['real_id'];

                $csv_row = array();
                // user official code
                $csv_row[] = $user[0];
                // user first|last name
                $csv_row[] = $user[1];
                // user last|first name
                $csv_row[] = $user[2];
                // user login name
                $csv_row[] = $user[3];
                // course code
                $csv_row[] = $row[0];
                // the additional defined user fields
                $extra_fields = MySpace::get_user_overview_export_extra_fields($user[4]);

                if (is_array($field_names_to_be_exported)) {
                    foreach ($field_names_to_be_exported as $key => $extra_field_export) {
                        $csv_row[] = $extra_fields[$extra_field_export];
                    }
                }
                // time spent in the course
                $csv_row[] = api_time_to_hms(Tracking::get_time_spent_on_the_course ($user[4], $courseId));
                // student progress in course
                $csv_row[] = round(Tracking::get_avg_student_progress ($user[4], $row[0]), 2);
                // student score
                $csv_row[] = round(Tracking::get_avg_student_score($user[4], $row[0]), 2);
                // student tes score
                $csv_row[] = round(Tracking::get_avg_student_exercise_score($user[4], $row[0]), 2);
                // student messages
                $csv_row[] = Tracking::count_student_messages($user[4], $row[0]);
                // student assignments
                $csv_row[] = Tracking::count_student_assignments ($user[4], $row[0]);
                // student exercises results
                $exercises_results = MySpace::exercises_results($user[4], $row[0]);
                $csv_row[] = $exercises_results['score_obtained'];
                $csv_row[] = $exercises_results['score_possible'];
                $csv_row[] = $exercises_results['questions_answered'];
                $csv_row[] = $exercises_results['percentage'];
                // first connection
                $csv_row[] = Tracking::get_first_connection_date_on_the_course ($user[4], $courseId);
                // last connection
                $csv_row[] = strip_tags(Tracking::get_last_connection_date_on_the_course($user[4], $courseInfo));

                $csv_content[] = $csv_row;
            }
        }
        Export :: arrayToCsv($csv_content, 'reporting_user_overview');
        exit;
    }

    /**
     * Get data for courses list in sortable with pagination
     * @return array
     */
    public static function get_course_data($from, $number_of_items, $column, $direction)
    {
        global $courses, $csv_content, $charset, $session_id;

        // definition database tables
        $tbl_course                 = Database :: get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user            = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $course_data = array();
        $courses_code = array_keys($courses);

        foreach ($courses_code as &$code) {
            $code = "'$code'";
        }

        // get all courses with limit
        $sql = "SELECT course.code as col1, course.title as col2
                FROM $tbl_course course
                WHERE course.code IN (".implode(',',$courses_code).")";

        if (!in_array($direction, array('ASC','DESC'))) $direction = 'ASC';

        $column = intval($column);
        $from = intval($from);
        $number_of_items = intval($number_of_items);
        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        while ($row_course = Database::fetch_row($res)) {
            $course_code = $row_course[0];
            $courseInfo = api_get_course_info($course_code);
            $courseId = $courseInfo['real_id'];
            $avg_assignments_in_course = $avg_messages_in_course = $nb_students_in_course = $avg_progress_in_course = $avg_score_in_course = $avg_time_spent_in_course = $avg_score_in_exercise = 0;

            // students directly subscribed to the course
            if (empty($session_id)) {
                $sql = "SELECT user_id
                        FROM $tbl_course_user as course_rel_user
                        WHERE
                            course_rel_user.status='5' AND
                            course_rel_user.c_id = '$courseId'";
            } else {
                $sql = "SELECT user_id FROM $tbl_session_course_user srcu
                        WHERE
                            c_id = '$courseId' AND
                            session_id = '$session_id' AND
                            status<>2";
            }
            $rs = Database::query($sql);
            $users = array();
            while ($row = Database::fetch_array($rs)) {
                $users[] = $row['user_id'];
            }

            if (count($users) > 0) {
                $nb_students_in_course = count($users);
                $avg_assignments_in_course  = Tracking::count_student_assignments($users, $course_code, $session_id);
                $avg_messages_in_course     = Tracking::count_student_messages($users, $course_code, $session_id);
                $avg_progress_in_course     = Tracking::get_avg_student_progress($users, $course_code, array(), $session_id);
                $avg_score_in_course        = Tracking::get_avg_student_score($users, $course_code, array(), $session_id);
                $avg_score_in_exercise      = Tracking::get_avg_student_exercise_score($users, $course_code, 0, $session_id);
                $avg_time_spent_in_course   = Tracking::get_time_spent_on_the_course($users, $courseInfo['real_id'], $session_id);

                $avg_progress_in_course = round($avg_progress_in_course / $nb_students_in_course, 2);
                if (is_numeric($avg_score_in_course)) {
                    $avg_score_in_course = round($avg_score_in_course / $nb_students_in_course, 2);
                }
                $avg_time_spent_in_course = api_time_to_hms($avg_time_spent_in_course / $nb_students_in_course);

            } else {
                $avg_time_spent_in_course = null;
                $avg_progress_in_course = null;
                $avg_score_in_course = null;
                $avg_score_in_exercise = null;
                $avg_messages_in_course = null;
                $avg_assignments_in_course = null;
            }
            $table_row = array();
            $table_row[] = $row_course[1];
            $table_row[] = $nb_students_in_course;
            $table_row[] = $avg_time_spent_in_course;
            $table_row[] = is_null($avg_progress_in_course) ? '' : $avg_progress_in_course.'%';
            $table_row[] = is_null($avg_score_in_course) ? '' : $avg_score_in_course.'%';
            $table_row[] = is_null($avg_score_in_exercise) ? '' : $avg_score_in_exercise.'%';
            $table_row[] = $avg_messages_in_course;
            $table_row[] = $avg_assignments_in_course;

            //set the "from" value to know if I access the Reporting by the chamilo tab or the course link
            $table_row[] = '<center><a href="../../tracking/courseLog.php?cidReq=' .$course_code.'&from=myspace&id_session='.$session_id.'">
                             '.Display::return_icon('2rightarrow.png').'
                             </a>
                            </center>';
            $csv_content[] = array(
                api_html_entity_decode($row_course[1], ENT_QUOTES, $charset),
                $nb_students_in_course,
                $avg_time_spent_in_course,
                is_null($avg_progress_in_course) ? null : $avg_progress_in_course.'%',
                is_null($avg_score_in_course) ? null : is_numeric($avg_score_in_course) ? $avg_score_in_course.'%' : $avg_score_in_course ,
                is_null($avg_score_in_exercise) ? null : $avg_score_in_exercise.'%',
                $avg_messages_in_course,
                $avg_assignments_in_course,
            );
            $course_data[] = $table_row;
        }
        return $course_data;
    }

    /**
     * get the numer of users of the platform
     *
     * @return integer
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @version Dokeos 1.8.6
     * @since October 2008
     */
    public static function get_number_of_users_tracking_overview()
    {
        // database table definition
        $main_user_table = Database :: get_main_table(TABLE_MAIN_USER);
        return Database::count_rows($main_user_table);
    }

    /**
     * get all the data for the sortable table of the reporting progress of all users and all the courses the user is subscribed to.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @version Dokeos 1.8.6
     * @since October 2008
     */
    public static function get_user_data_tracking_overview($from, $number_of_items, $column, $direction)
    {
        // database table definition
        $access_url_id = api_get_current_access_url_id();
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $main_user_table = Database::get_main_table(TABLE_MAIN_USER);
        $condition_multi_url = null;
        if (api_is_multiple_url_enabled()) {
            $condition_multi_url = ", $tbl_url_rel_user as url_user
            WHERE user.user_id=url_user.user_id AND access_url_id='$access_url_id'";
        }

        global $export_csv;
        if ($export_csv) {
            $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
        } else {
            $is_western_name_order = api_is_western_name_order();
        }
        $sql = "SELECT
                    official_code AS col0,
                    ".($is_western_name_order ? "
                    firstname       AS col1,
                    lastname        AS col2,
                    " : "
                    lastname        AS col1,
                    firstname       AS col2,
                    ").
                    "username       AS col3,
                    user.user_id        AS col4
                FROM
                $main_user_table as user $condition_multi_url
                ";
        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";
        $result = Database::query($sql);
        $return = array ();
        while ($user = Database::fetch_row($result)) {
            $return[] = $user;
        }
        return $return;
    }

    /**
     * Get all information that the user with user_id = $user_data has
     * entered in the additionally defined profile fields
     * @param integer $user_id the id of the user
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @version Dokeos 1.8.6
     * @since November 2008
     */
    public static function get_user_overview_export_extra_fields($user_id)
    {
        // include the user manager

        $extra_data = UserManager::get_extra_user_data($user_id, true);
        return $extra_data;
    }
    /**
     * Checks if a username exist in the DB otherwise it create a "double"
     * i.e. if we look into for jmontoya but the user's name already exist we create the user jmontoya2
     * the return array will be array(username=>'jmontoya', sufix='2')
     * @param string firstname
     * @param string lastname
     * @param string username
     * @return array with the username, the sufix
     * @author Julio Montoya Armas
     */
    public static function make_username($firstname, $lastname, $username, $language = null, $encoding = null)
    {
        // if username exist
        if (!UserManager::is_username_available($username) || empty($username)) {
            $i = 0;
            while (1) {
                if ($i == 0) {
                    $sufix = '';
                } else {
                    $sufix = $i;
                }
                $desired_username = UserManager::create_username(
                    $firstname,
                    $lastname,
                    $language,
                    $encoding
                );
                if (UserManager::is_username_available($desired_username.$sufix)) {
                    break;
                } else {
                    $i++;
                }
            }
            $username_array = array('username' => $desired_username , 'sufix' => $sufix);
            return $username_array;
        } else {
            $username_array = array('username' => $username, 'sufix' => '');
            return $username_array;
        }
    }

    /**
     * Checks if there are repeted users in a given array
     * @param  array $usernames list of the usernames in the uploaded file
     * @param  array $user_array['username'] and $user_array['sufix'] where sufix is the number part in a login i.e -> jmontoya2
     * @return array with the $usernames array and the $user_array array
     * @author Julio Montoya
     */
    public static function check_user_in_array($usernames, $user_array)
    {
        $user_list = array_keys($usernames);
        $username = $user_array['username'].$user_array['sufix'];

        if (in_array($username, $user_list)) {
            $user_array['sufix'] += $usernames[$username];
            $usernames[$username]++;
        } else {
            $usernames[$username] = 1;
        }
        $result_array = array($usernames, $user_array);
        return $result_array;
    }

    /**
     * Checks whether a username has been already subscribed in a session.
     * @param string $username a given username
     * @param array $course_list the array with the course list id
     * @param int $id_session the session id
     * @return int 0 if the user is not subscribed otherwise it returns the user_id of the given username
     * @author Julio Montoya
     */
    public static function user_available_in_session($username, $course_list, $id_session)
    {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $id_session = intval($id_session);
        $username = Database::escape_string($username);
        foreach ($course_list as $courseId) {
            $courseId = intval($courseId);
            $sql = " SELECT u.user_id FROM $tbl_session_rel_course_rel_user rel
                     INNER JOIN $table_user u
                     ON (rel.user_id = u.user_id)
                     WHERE
                        rel.session_id='$id_session' AND
                        u.status='5' AND
                        u.username ='$username' AND
                        rel.c_id='$courseId'";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                return Database::result($rs, 0, 0);
            } else {
                return 0;
            }
        }
        return 0;
    }

    /**
     * This function checks whether some users in the uploaded file
     * repeated and creates unique usernames if necesary.
     * A case: Within the file there is an user repeted twice (Julio Montoya / Julio Montoya)
     * and the username fields are empty.
     * Then, this function would create unique usernames based on the first and the last name.
     * Two users wiould be created - jmontoya and jmontoya2.
     * Of course, if in the database there is a user with the name jmontoya,
     * the newly created two users registered would be jmontoya2 and jmontoya3.
     * @param $users list of users
     * @author Julio Montoya Armas
     */
    function check_all_usernames($users, $course_list, $id_session)
    {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $usernames = array();
        $new_users = array();
        foreach ($users as $index => $user) {
            $desired_username = array();
            if (empty($user['UserName'])) {
                $desired_username = MySpace::make_username($user['FirstName'], $user['LastName'], '');
                $pre_username = $desired_username['username'].$desired_username['sufix'];
                $user['UserName'] = $pre_username;
                $user['create'] = '1';
            } else {
                if (UserManager::is_username_available($user['UserName'])) {
                    $desired_username = MySpace::make_username($user['FirstName'], $user['LastName'], $user['UserName']);
                    $user['UserName'] = $desired_username['username'].$desired_username['sufix'];
                    $user['create'] = '1';
                } else {
                    $is_session_avail = MySpace::user_available_in_session($user['UserName'], $course_list, $id_session);
                    if ($is_session_avail == 0) {
                        $user_name = $user['UserName'];
                        $sql_select = "SELECT user_id FROM $table_user WHERE username ='$user_name' ";
                        $rs = Database::query($sql_select);
                        $user['create'] = Database::result($rs, 0, 0); // This should be the ID because the user exists.
                    } else {
                        $user['create'] = $is_session_avail;
                    }
                }
            }
            // Usernames is the current list of users in the file.
            $result_array = MySpace::check_user_in_array($usernames, $desired_username);
            $usernames = $result_array[0];
            $desired_username = $result_array[1];
            $user['UserName'] = $desired_username['username'].$desired_username['sufix'];
            $new_users[] = $user;
        }
        return $new_users;
    }

    /**
     * This functions checks whether there are users that are already
     * registered in the DB by different creator than the current coach.
     * @param string a given username
     * @param array  the array with the course list ids
     * @param the session id
     * @author Julio Montoya Armas
     */
    public function get_user_creator($users)
    {
        $errors = array();
        foreach ($users as $index => $user) {
            // database table definition
            $table_user = Database::get_main_table(TABLE_MAIN_USER);
            $username = Database::escape_string($user['UserName']);
            $sql = "SELECT creator_id FROM $table_user WHERE username='$username' ";

            $rs = Database::query($sql);
            $creator_id = Database::result($rs, 0, 0);
            // check if we are the creators or not
            if ($creator_id != '') {
                if ($creator_id != api_get_user_id()) {
                    $user['error'] = get_lang('UserAlreadyRegisteredByOtherCreator');
                    $errors[] = $user;
                }
            }
        }

        return $errors;
    }

    /**
     * Validates imported data.
     * @param list of users
     */
    function validate_data($users, $id_session = null)
    {
        $errors = array();
        $new_users = array();
        foreach ($users as $index => $user) {
            // 1. Check whether mandatory fields are set.
            $mandatory_fields = array('LastName', 'FirstName');
            if (api_get_setting('registration', 'email') == 'true') {
                $mandatory_fields[] = 'Email';
            }

            foreach ($mandatory_fields as $key => $field) {
                if (!isset ($user[$field]) || strlen($user[$field]) == 0) {
                    $user['error'] = get_lang($field.'Mandatory');
                    $errors[] = $user;
                }
            }
            // 2. Check whether the username is too long.
            if (UserManager::is_username_too_long($user['UserName'])) {
                $user['error'] = get_lang('UserNameTooLong');
                $errors[] = $user;
            }

            $user['UserName'] = trim($user['UserName']);

            if (empty($user['UserName'])) {
                $user['UserName'] = UserManager::create_username($user['FirstName'], $user['LastName']);
            }
            $new_users[] = $user;
        }
        $results = array('errors' => $errors, 'users' => $new_users);
        return $results;
    }

    /**
     * Adds missing user-information (which isn't required, like password, etc).
     */
    function complete_missing_data($user) {
        // 1. Generate a password if it is necessary.
        if (!isset ($user['Password']) || strlen($user['Password']) == 0) {
            $user['Password'] = api_generate_password();
        }

        return $user;
    }

    /**
     * Saves imported data.
     */
    public function save_data($users, $course_list, $id_session)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $id_session = intval($id_session);
        $sendMail = $_POST['sendMail'] ? 1 : 0;

        // Adding users to the platform.
        $new_users = array();
        foreach ($users as $index => $user) {
            $user = MySpace::complete_missing_data($user);
            // coach only will registered users
            $default_status = STUDENT;
            if ($user['create'] == COURSEMANAGER) {
                $user['id'] = UserManager:: create_user(
                    $user['FirstName'],
                    $user['LastName'],
                    $default_status,
                    $user['Email'],
                    $user['UserName'],
                    $user['Password'],
                    $user['OfficialCode'],
                    api_get_setting('PlatformLanguage'),
                    $user['PhoneNumber'],
                    ''
                );
                $user['added_at_platform'] = 1;
            } else {
                $user['id'] = $user['create'];
                $user['added_at_platform'] = 0;
            }
            $new_users[] = $user;
        }
        // Update user list.
        $users = $new_users;

        // Inserting users.
        $super_list = array();
        foreach ($course_list as $enreg_course) {
            $nbr_users = 0;
            $new_users = array();
            $enreg_course = Database::escape_string($enreg_course);
            foreach ($users as $index => $user) {
                $userid = intval($user['id']);
                $sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(session_id, c_id, user_id)
                        VALUES('$id_session','$enreg_course','$userid')";
                $course_session = array('course' => $enreg_course, 'added' => 1);

                $result = Database::query($sql);
                if (Database::affected_rows($result)) {
                    $nbr_users++;
                }
                $new_users[] = $user;
            }
            $super_list[] = $new_users;

            //update the nbr_users field
            $sql_select = "SELECT COUNT(user_id) as nbUsers FROM $tbl_session_rel_course_rel_user
                           WHERE session_id='$id_session' AND c_id='$enreg_course'";
            $rs = Database::query($sql_select);
            list($nbr_users) = Database::fetch_array($rs);
            $sql_update = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users
                           WHERE session_id='$id_session' AND c_id='$enreg_course'";
            Database::query($sql_update);

            $sql_update = "UPDATE $tbl_session SET nbr_users= '$nbr_users' WHERE id='$id_session'";
            Database::query($sql_update);
        }

        $new_users = array();
        foreach ($users as $index => $user) {
            $userid = $user['id'];
            $sql_insert = "INSERT IGNORE INTO $tbl_session_rel_user(session_id, user_id, registered_at)
                           VALUES ('$id_session','$userid', '" . api_get_utc_datetime() . "')";
            Database::query($sql_insert);
            $user['added_at_session'] = 1;
            $new_users[] = $user;
        }

        $users = $new_users;
        $registered_users = get_lang('FileImported').'<br /> Import file results : <br />';
        // Sending emails.
        $addedto = '';
        if ($sendMail) {
            $i = 0;
            foreach ($users as $index => $user) {
                $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
                $emailbody = get_lang('Dear').' '.
                    api_get_person_name($user['FirstName'], $user['LastName']).",\n\n".
                    get_lang('YouAreReg')." ".api_get_setting('siteName')." ".get_lang('WithTheFollowingSettings')."\n\n".
                    get_lang('Username')." : $user[UserName]\n".
                    get_lang('Pass')." : $user[Password]\n\n".
                    get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('Is')." : ".api_get_path(WEB_PATH)." \n\n".
                    get_lang('Problem')."\n\n".
                    get_lang('SignatureFormula').",\n\n".
                    api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".
                    get_lang('Manager')." ".api_get_setting('siteName')."\nT. ".
                    api_get_setting('administratorTelephone')."\n".get_lang('Email')." : ".api_get_setting('emailAdministrator');

                api_mail_html(
                    api_get_person_name($user['FirstName'], $user['LastName'], null, PERSON_NAME_EMAIL_ADDRESS),
                    $user['Email'],
                    $emailsubject,
                    $emailbody
                );
                $userInfo = api_get_user_info($user['id']);

                if (($user['added_at_platform'] == 1  && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
                    if ($user['added_at_platform'] == 1) {
                        $addedto = get_lang('UserCreatedPlatform');
                    } else  {
                        $addedto = '          ';
                    }

                    if ($user['added_at_session'] == 1) {
                        $addedto .= get_lang('UserInSession');
                    }
                } else {
                    $addedto = get_lang('UserNotAdded');
                }

                $registered_users .= UserManager::getUserProfileLink($userInfo)." - ".$addedto.'<br />';
            }
        } else {
            $i = 0;
            foreach ($users as $index => $user) {
                $userInfo = api_get_user_info($user['id']);
                if (($user['added_at_platform'] == 1 && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
                    if ($user['added_at_platform'] == 1) {
                        $addedto = get_lang('UserCreatedPlatform');
                    } else {
                        $addedto = '          ';
                    }

                    if ($user['added_at_session'] == 1) {
                        $addedto .= ' '.get_lang('UserInSession');
                    }
                } else {
                    $addedto = get_lang('UserNotAdded');
                }
                $registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
            }
        }
        Display::addFlash(Display::return_message($registered_users));
        header('Location: course.php?id_session='.$id_session);
        exit;
    }

    /**
     * Reads CSV-file.
     * @param string $file Path to the CSV-file
     * @return array All userinformation read from the file
     */
    function parse_csv_data($file) {
        $users = Import :: csvToArray($file);
        foreach ($users as $index => $user) {
            if (isset ($user['Courses'])) {
                $user['Courses'] = explode('|', trim($user['Courses']));
            }
            $users[$index] = $user;
        }
        return $users;
    }

    /**
     * XML-parser: the handler at the beginning of element.
     */
    function element_start($parser, $data) {
        $data = api_utf8_decode($data);
        global $user;
        global $current_tag;
        switch ($data) {
            case 'Contact' :
                $user = array ();
                break;
            default :
                $current_tag = $data;
        }
    }

    /**
     * XML-parser: the handler at the end of element.
     */
    function element_end($parser, $data) {
        $data = api_utf8_decode($data);
        global $user;
        global $users;
        global $current_value;
        global $purification_option_for_usernames;
        $user[$data] = $current_value;
        switch ($data) {
            case 'Contact' :
                $user['UserName'] = UserManager::purify_username($user['UserName'], $purification_option_for_usernames);
                $users[] = $user;
                break;
            default :
                $user[$data] = $current_value;
                break;
        }
    }

    /**
     * XML-parser: the handler for character data.
     */
    function character_data($parser, $data) {
        $data = trim(api_utf8_decode($data));
        global $current_value;
        $current_value = $data;
    }

    /**
     * Reads XML-file.
     * @param string $file Path to the XML-file
     * @return array All userinformation read from the file
     */
    function parse_xml_data($file) {
        global $current_tag;
        global $current_value;
        global $user;
        global $users;
        $users = array ();
        $parser = xml_parser_create('UTF-8');
        xml_set_element_handler($parser, array('MySpace','element_start'), array('MySpace','element_end'));
        xml_set_character_data_handler($parser, "character_data");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_parse($parser, api_utf8_encode_xml(file_get_contents($file)));
        xml_parser_free($parser);
        return $users;
    }

    public static function displayTrackingAccessOverView($courseId, $sessionId, $studentId)
    {
        $courseId = intval($courseId);
        $sessionId = intval($sessionId);
        $studentId = intval($studentId);

        $em = Database::getManager();
        $sessionRepo = $em->getRepository('ChamiloCoreBundle:Session');

        $courseList = [];
        $sessionList = [];
        $studentList = [];

        if (!empty($courseId)) {
            $course = $em->find('ChamiloCoreBundle:Course', $courseId);

            $courseList[$course->getId()] = $course->getTitle();
        }

        if (!empty($sessionId)) {
            $session = $em->find('ChamiloCoreBundle:Session', $sessionId);

            $sessionList[$session->getId()] = $session->getName();
        }

        if (!empty($studentId)) {
            $student = $em->find('ChamiloUserBundle:User', $studentId);

            $studentList[$student->getId()] = $student->getCompleteName();
        }

        $form = new FormValidator('access_overview', 'GET');
        $form->addElement(
            'select_ajax',
            'course_id',
            get_lang('SearchCourse'),
            $courseList,
            [
                'url' => api_get_path(WEB_AJAX_PATH) . 'course.ajax.php?' . http_build_query([
                    'a' => 'search_course_by_session_all',
                    'session_id' => $sessionId
                ])
            ]
        );
        $form->addElement(
            'select_ajax',
            'session_id',
            get_lang('SearchSession'),
            $sessionList,
            [
                'url_function' => "
                    function () {
                        var params = $.param({
                            a: 'search_session_by_course',
                            course_id: $('#course_id').val() || 0
                        });

                        return '" . api_get_path(WEB_AJAX_PATH) . "session.ajax.php?' + params;
                    }
                "
            ]
        );
        $form->addSelect(
            'profile',
            get_lang('Profile'),
            [
                '' => get_lang('Select'),
                STUDENT => get_lang('Student'),
                COURSEMANAGER => get_lang('CourseManager'),
                DRH => get_lang('Drh')
            ],
            ['id' => 'profile']
        );
        $form->addElement(
            'select_ajax',
            'student_id',
            get_lang('SearchUsers'),
            $studentList,
            [
                'placeholder' => get_lang('All'),
                'url_function' => "
                    function () {
                        var params = $.param({
                            a: 'search_user_by_course',
                            session_id: $('#session_id').val(),
                            course_id: $('#course_id').val()
                        });

                        return '" . api_get_path(WEB_AJAX_PATH) . "course.ajax.php?' + params;
                    }
                "
            ]
        );
        $form->addDateRangePicker(
            'date',
            get_lang('DateRange'),
            true,
            [
                'id' => 'date_range',
                'format' => 'YYYY-MM-DD',
                'timePicker' => 'false',
                'validate_format' => 'Y-m-d'
            ]
        );
        $form->addHidden('display', 'accessoverview');
        $form->addRule('course_id', get_lang('Required'), 'required');
        $form->addRule('profile', get_lang('Required'), 'required');
        $form->addButton('submit', get_lang('Generate'), 'gear', 'primary');

        $table = null;

        if ($form->validate()) {
            $table = new SortableTable(
                'tracking_access_overview',
                ['MySpace', 'getNumberOfTrackAccessOverview'],
                ['MySpace', 'getUserDataAccessTrackingOverview'],
                0
            );
            $table->additional_parameters = $form->exportValues();

            $table->set_header(0, get_lang('LoginDate'), true);
            $table->set_header(1, get_lang('Username'), true);
            if (api_is_western_name_order()) {
                $table->set_header(2, get_lang('FirstName'), true);
                $table->set_header(3, get_lang('LastName'), true);
            } else {
                $table->set_header(2, get_lang('LastName'), true);
                $table->set_header(3, get_lang('FirstName'), true);
            }
            $table->set_header(4, get_lang('Clicks'), false);
            $table->set_header(5, get_lang('IP'), false);
            $table->set_header(6, get_lang('TimeLoggedIn'), false);
        }

        $template = new Template(null, false, false, false, false, false, false);
        $template->assign('form', $form->returnForm());
        $template->assign('table', $table ? $table->return_table() : null);

        echo $template->fetch(
            $template->get_template('my_space/accessoverview.tpl')
        );
    }

    public static function getNumberOfTrackAccessOverview()
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        return Database::count_rows($table);
    }

    public static function getUserDataAccessTrackingOverview($from, $numberItems, $column, $orderDirection)
    {
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $course = Database::get_main_table(TABLE_MAIN_COURSE);
        $track_e_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $track_e_course_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        global $export_csv;

        if ($export_csv) {
            $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
        } else {
            $is_western_name_order = api_is_western_name_order();
        }

        //TODO add course name
        $sql = "SELECT
                a.login_course_date as col0,
                u.username as col1,
                " . (
                    $is_western_name_order ? "
                        u.firstname AS col2,
                        u.lastname AS col3,
                    " : "
                        u.lastname AS col2,
                        u.firstname AS col3,
                " ) . "
                a.logout_course_date,
                c.title,
                c.code,
                u.user_id
            FROM $track_e_course_access a
            INNER JOIN $user u ON a.user_id = u.user_id
            INNER JOIN $course c ON a.c_id = c.id";

        if (isset($_GET['session_id']) && !empty($_GET['session_id'])) {
            $sessionId = intval($_GET['session_id']);
            $sql .= " WHERE a.session_id = " . $sessionId;
        }

        $sql .= " ORDER BY col$column $orderDirection ";
        $sql .= " LIMIT $from,$numberItems";
        $result = Database::query($sql);

        //$clicks = Tracking::get_total_clicks_by_session();
        $data = array();

        while ($user = Database::fetch_assoc($result)) {
            $data[] = $user;
        }

        $return = [];

        //TODO: Dont use numeric index
        foreach ($data as $key => $info) {
            $start_date = $info['col0'];

            $end_date = $info['logout_course_date'];

            $return[$info['user_id']] = array(
                $start_date,
                $info['col1'],
                $info['col2'],
                $info['col3'],
                $info['user_id'],
                'ip',
                //TODO is not correct/precise, it counts the time not logged between two loggins
                gmdate("H:i:s", strtotime($end_date) - strtotime($start_date))
            );
        }

        foreach ($return as $key => $info) {
            $ipResult = Database::select(
                'user_ip',
                $track_e_login,
                ['where' => [
                    '? BETWEEN login_date AND logout_date' => $info[0]
                ]],
                'first'
            );

            $return[$key][5] = $ipResult['user_ip'];
        }

        return $return;
    }

    /**
     * Gets the connections to a course as an array of login and logout time
     *
     * @param   int       $user_id
     * @param   int    $courseId
     * @author  Jorge Frisancho Jibaja
     * @author  Julio Montoya <gugli100@gmail.com> fixing the function
     * @version OCT-22- 2010
     * @return  array
     */
    public static function get_connections_to_course_by_date($user_id, $courseId, $start_date, $end_date)
    {
        // Database table definitions
        $tbl_track_course = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $course_info = api_get_course_info_by_id($courseId);
        $user_id = intval($user_id);
        $courseId = intval($courseId);
        $connections = array();

        if (!empty($course_info)) {
            $end_date = add_day_to($end_date);
            $sql = "SELECT login_course_date, logout_course_date
                FROM $tbl_track_course
                WHERE
                    user_id = $user_id AND
                    c_id = $courseId AND
                    login_course_date BETWEEN '$start_date' AND '$end_date' AND
                    logout_course_date BETWEEN '$start_date' AND '$end_date'
                ORDER BY login_course_date ASC";
            $rs = Database::query($sql);

            while ($row = Database::fetch_array($rs)) {
                $login_date = $row['login_course_date'];
                $logout_date = $row['logout_course_date'];
                $timestamp_login_date = strtotime($login_date);
                $timestamp_logout_date = strtotime($logout_date);
                $connections[] = array(
                    'login' => $timestamp_login_date,
                    'logout' => $timestamp_logout_date
                );
            }
        }
        return $connections;
    }
}

/**
 * @param $user_id
 * @param int $courseId
 * @param null $start_date
 * @param null $end_date
 * @return array
 */
function get_stats($user_id, $courseId, $start_date = null, $end_date = null)
{
    // Database table definitions
    $tbl_track_course   = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

    $course_info = api_get_course_info_by_id($courseId);
    if (!empty($course_info)) {
        $strg_sd = '';
        $strg_ed = '';
        if ($start_date != null && $end_date != null){
            $end_date = add_day_to($end_date);
            $strg_sd = "AND login_course_date BETWEEN '$start_date' AND '$end_date'";
            $strg_ed = "AND logout_course_date BETWEEN '$start_date' AND '$end_date'";
        }
        $sql = 'SELECT
                SEC_TO_TIME(avg(time_to_sec(timediff(logout_course_date,login_course_date)))) as avrg,
                SEC_TO_TIME(sum(time_to_sec(timediff(logout_course_date,login_course_date)))) as total,
                count(user_id) as times
                FROM ' . $tbl_track_course . '
                WHERE
                    user_id = ' . intval($user_id) . ' AND
                    c_id = ' . intval($courseId) . ' '.$strg_sd.' '.$strg_ed.' '.'
                ORDER BY login_course_date ASC';

        $rs = Database::query($sql);
        $result = array();

        if ($row = Database::fetch_array($rs)) {
            $foo_avg    = $row['avrg'];
            $foo_total  = $row['total'];
            $foo_times  = $row['times'];
            $result = array('avg' => $foo_avg, 'total' => $foo_total, 'times' => $foo_times);
        }
    }

    return $result;
}

function add_day_to($end_date) {
    $foo_date = strtotime( $end_date );
    $foo_date = strtotime(" +1 day", $foo_date);
    $foo_date = date("Y-m-d", $foo_date);
    return $foo_date;
}

/**
 *
 *
 * @param array
 * @author Jorge Frisancho Jibaja
 * @version OCT-22- 2010
 * @return array
 */
function convert_to_array($sql_result){
    $result_to_print = '<table>';
    foreach ($sql_result as $key => $data) {
        $result_to_print .= '<tr><td>'.date('d-m-Y (H:i:s)', $data['login']).'</td><td>'.api_time_to_hms($data['logout'] - $data['login']).'</tr></td>'."\n";
    }
    $result_to_print .= '</table>';
    $result_to_print = array("result"=>$result_to_print);
    return $result_to_print;
}


/**
 * Converte an array to a table in html
 *
 * @param array $sql_result
 * @author Jorge Frisancho Jibaja
 * @version OCT-22- 2010
 * @return string
 */
function convert_to_string($sql_result){
    $result_to_print = '<table>';
    if (!empty($sql_result)) {
        foreach ($sql_result as $key => $data) {
            $result_to_print .= '<tr><td>'.date('d-m-Y (H:i:s)', $data['login']).'</td><td>'.api_time_to_hms($data['logout'] - $data['login']).'</tr></td>'."\n";
        }
    }
    $result_to_print .= '</table>';
    return $result_to_print;
}


/**
 * This function draw the graphic to be displayed on the user view as an image
 *
 * @param array $sql_result
 * @param string $start_date
 * @param string $end_date
 * @param string $type
 * @author Jorge Frisancho Jibaja
 * @version OCT-22- 2010
 * @return string
 */
function grapher($sql_result, $start_date, $end_date, $type = "")
{
    if (empty($start_date)) { $start_date =""; }
    if (empty($end_date)) { $end_date =""; }
    if ($type == ""){ $type = 'day'; }
    $main_year  = $main_month_year = $main_day = array();
    // get last 8 days/months
    $last_days      = 5;
    $last_months    = 3;
    for ($i = $last_days; $i >= 0; $i--) {
        $main_day[date ('d-m-Y', time () - $i * 3600 * 24)] = 0;
    }
    for ($i = $last_months; $i >= 0; $i--) {
        $main_month_year[date ('m-Y', time () - $i * 30 * 3600 * 24)] = 0;
    }

    $i = 0;
    if (is_array($sql_result) && count($sql_result) > 0) {
        foreach ($sql_result as $key => $data) {
            //creating the main array
            $main_month_year[date('m-Y', $data['login'])] += float_format(($data['logout'] - $data['login']) / 60, 0);
            $main_day[date('d-m-Y', $data['login'])] += float_format(($data['logout'] - $data['login']) / 60, 0);
            if ($i > 500) {
                break;
            }
            $i++;
        }
        switch ($type) {
            case 'day':
                $main_date = $main_day;
                break;
            case 'month':
                $main_date = $main_month_year;
                break;
            case 'year':
                $main_date = $main_year;
                break;
        }

        // the nice graphics :D
        $labels = array_keys($main_date);
        if (count($main_date) == 1) {
            $labels = $labels[0];
            $main_date = $main_date[$labels];
        }

        /* Create and populate the pData object */
        $myData = new pData();
        $myData->addPoints($main_date, 'Serie1');
        if (count($main_date)!= 1) {
            $myData->addPoints($labels, 'Labels');
            $myData->setSerieDescription('Labels', 'Months');
            $myData->setAbscissa('Labels');
        }
        $myData->setSerieWeight('Serie1', 1);
        $myData->setSerieDescription('Serie1', get_lang('MyResults'));
        $myData->setAxisName(0, get_lang('Minutes'));
        $myData->loadPalette(api_get_path(SYS_CODE_PATH) . 'palettes/pchart/default.color', true);

        // Cache definition
        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        $myCache = new pCache(array('CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)));
        $chartHash = $myCache->getHash($myData);

        if ($myCache->isInCache($chartHash)) {
            //if we already created the img
            $imgPath = api_get_path(SYS_ARCHIVE_PATH) . $chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH) . $chartHash;
        } else {
            /* Define width, height and angle */
            $mainWidth = 760;
            $mainHeight = 230;
            $angle = 50;

            /* Create the pChart object */
            $myPicture = new pImage($mainWidth, $mainHeight, $myData);

            /* Turn of Antialiasing */
            $myPicture->Antialias = false;

            /* Draw the background */
            $settings = array("R" => 255, "G" => 255, "B" => 255);
            $myPicture->drawFilledRectangle(0, 0, $mainWidth, $mainHeight, $settings);

            /* Add a border to the picture */
            $myPicture->drawRectangle(
                0,
                0,
                $mainWidth - 1,
                $mainHeight - 1,
                array("R" => 0, "G" => 0, "B" => 0)
            );

            /* Set the default font */
            $myPicture->setFontProperties(
                array(
                    "FontName" => api_get_path(SYS_FONTS_PATH) . 'opensans/OpenSans-Regular.ttf',
                    "FontSize" => 10)
            );
            /* Write the chart title */
            $myPicture->drawText(
                $mainWidth / 2,
                30,
                get_lang('TimeSpentInTheCourse'),
                array(
                    "FontSize" => 12,
                    "Align" => TEXT_ALIGN_BOTTOMMIDDLE
                )
            );

            /* Set the default font */
            $myPicture->setFontProperties(
                array(
                    "FontName" => api_get_path(SYS_FONTS_PATH) . 'opensans/OpenSans-Regular.ttf',
                    "FontSize" => 8
                )
            );

            /* Define the chart area */
            $myPicture->setGraphArea(50, 40, $mainWidth - 40, $mainHeight - 80);

            /* Draw the scale */
            $scaleSettings = array(
                'XMargin' => 10,
                'YMargin' => 10,
                'Floating' => true,
                'GridR' => 200,
                'GridG' => 200,
                'GridB' => 200,
                'DrawSubTicks' => true,
                'CycleBackground' => true,
                'LabelRotation' => $angle,
                'Mode' => SCALE_MODE_ADDALL_START0,
            );
            $myPicture->drawScale($scaleSettings);

            /* Turn on Antialiasing */
            $myPicture->Antialias = true;

            /* Enable shadow computing */
            $myPicture->setShadow(
                true,
                array(
                    "X" => 1,
                    "Y" => 1,
                    "R" => 0,
                    "G" => 0,
                    "B" => 0,
                    "Alpha" => 10
                )
            );

            /* Draw the line chart */
            $myPicture->setFontProperties(
                array(
                    "FontName" => api_get_path(SYS_FONTS_PATH) . 'opensans/OpenSans-Regular.ttf',
                    "FontSize" => 10
                )
            );
            $myPicture->drawSplineChart();
            $myPicture->drawPlotChart(
                array(
                    "DisplayValues" => true,
                    "PlotBorder" => true,
                    "BorderSize" => 1,
                    "Surrounding" => -60,
                    "BorderAlpha" => 80
                )
            );

            /* Do NOT Write the chart legend */

            /* Write and save into cache */
            $myCache->writeToCache($chartHash, $myPicture);
            $imgPath = api_get_path(SYS_ARCHIVE_PATH) . $chartHash;
            $myCache->saveFromCache($chartHash, $imgPath);
            $imgPath = api_get_path(WEB_ARCHIVE_PATH) . $chartHash;
        }
        $html = '<img src="' . $imgPath . '">';

        return $html;
    } else {
        $foo_img = api_convert_encoding('<div id="messages" class="warning-message">'.get_lang('GraphicNotAvailable').'</div>','UTF-8');

        return $foo_img;
    }
}

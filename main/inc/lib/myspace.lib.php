<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;

/**
 * Class MySpace.
 */
class MySpace
{
    /**
     * Get admin actions.
     *
     * @return string
     */
    public static function getAdminActions()
    {
        $actions = [
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=coaches',
                'content' => get_lang('DisplayCoaches'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=user',
                'content' => get_lang('DisplayUserOverview'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=session',
                'content' => get_lang('DisplaySessionOverview'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=course',
                'content' => get_lang('DisplayCourseOverview'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'tracking/question_course_report.php?view=admin',
                'content' => get_lang('LPQuestionListResults'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin',
                'content' => get_lang('LPExerciseResultsBySession'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=accessoverview',
                'content' => get_lang('DisplayAccessOverview').' ('.get_lang('Beta').')',
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/exercise_category_report.php',
                'content' => get_lang('ExerciseCategoryAllSessionsReport'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/survey_report.php',
                'content' => get_lang('SurveysReport'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/tc_report.php',
                'content' => get_lang('TCReport'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/ti_report.php',
                'content' => get_lang('TIReport'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/question_stats_global.php',
                'content' => get_lang('QuestionStats'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/question_stats_global_detail.php',
                'content' => get_lang('ExerciseAttemptStatsReport'),
            ],
        ];

        $field = new ExtraField('user');
        $companyField = $field->get_handler_field_info_by_field_variable('company');
        if (!empty($companyField)) {
            $actions[] = [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=company',
                'content' => get_lang('UserByEntityReport'),
            ];
        }
        $field = new ExtraField('lp');
        $authorsField = $field->get_handler_field_info_by_field_variable('authors');
        if (!empty($authorsField)) {
            $actions[] = [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=learningPath',
                'content' => get_lang('LpByAuthor'),
            ];
        }
        $field = new ExtraField('lp_item');
        $authorsItemField = $field->get_handler_field_info_by_field_variable('authorlpitem');
        if (!empty($authorsItemField)) {
            $actions[] = [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=learningPathByItem',
                'content' => get_lang('LearningPathItemByAuthor'),
            ];
        }

        return Display::actions($actions, null);
    }

    /**
     * @return string
     */
    public static function getTopMenu()
    {
        $menuItems = [];
        $menuItems[] = Display::url(
            Display::return_icon(
                'statistics.png',
                get_lang('MyStats'),
                '',
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH)."auth/my_progress.php"
        );
        $menuItems[] = Display::url(
            Display::return_icon(
                'teacher.png',
                get_lang('TeacherInterface'),
                [],
                32
            ),
            api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher'
        );
        $menuItems[] = Display::url(
            Display::return_icon(
                'star_na.png',
                get_lang('AdminInterface'),
                [],
                32
            ),
            '#'
        );
        $menuItems[] = Display::url(
            Display::return_icon('quiz.png', get_lang('ExamTracking'), [], 32),
            api_get_path(WEB_CODE_PATH).'tracking/exams.php'
        );
        $menu = '';
        foreach ($menuItems as $item) {
            $menu .= $item;
        }
        $menu .= '<br />';

        return $menu;
    }

    /**
     * This function serves exporting data in CSV format.
     *
     * @param array  $header    the header labels
     * @param array  $data      the data array
     * @param string $file_name the name of the file which contains exported data
     *
     * @return string mixed             Returns a message (string) if an error occurred
     */
    public function export_csv($header, $data, $file_name = 'export.csv')
    {
        $archive_path = api_get_path(SYS_ARCHIVE_PATH);
        $archive_url = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=&archive=';
        $message = '';
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
            exit;
        }

        return $message;
    }

    /**
     * Gets the connections to a course as an array of login and logout time.
     *
     * @param int   $userId     User id
     * @param array $courseInfo
     * @param int   $sessionId  Session id (optional, default = 0)
     *
     * @return array Connections
     */
    public static function get_connections_to_course(
        $userId,
        $courseInfo,
        $sessionId = 0
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        // protect data
        $userId = (int) $userId;
        $courseId = (int) $courseInfo['real_id'];
        $sessionId = (int) $sessionId;
        $sessionCondition = api_get_session_condition($sessionId);

        $sql = 'SELECT login_course_date, logout_course_date
                FROM '.$table.'
                WHERE
                    user_id = '.$userId.' AND
                    c_id = '.$courseId.'
                    '.$sessionCondition.'
                ORDER BY login_course_date ASC';
        $rs = Database::query($sql);
        $connections = [];

        while ($row = Database::fetch_array($rs)) {
            $connections[] = [
                'login' => $row['login_course_date'],
                'logout' => $row['logout_course_date'],
            ];
        }

        return $connections;
    }

    /**
     * @param $user_id
     * @param $course_list
     * @param int $session_id
     *
     * @return array|bool
     */
    public static function get_connections_from_course_list(
        $user_id,
        $course_list,
        $session_id = 0
    ) {
        // Database table definitions
        $tbl_track_course = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        if (empty($course_list)) {
            return false;
        }

        // protect data
        $user_id = (int) $user_id;
        $session_id = (int) $session_id;
        $new_course_list = [];
        foreach ($course_list as $course_item) {
            $courseInfo = api_get_course_info($course_item['code']);
            if ($courseInfo) {
                $courseId = $courseInfo['real_id'];
                $new_course_list[] = '"'.$courseId.'"';
            }
        }
        $course_list = implode(', ', $new_course_list);

        if (empty($course_list)) {
            return false;
        }
        $sql = 'SELECT login_course_date, logout_course_date, c_id
                FROM '.$tbl_track_course.'
                WHERE
                    user_id = '.$user_id.' AND
                    c_id IN ('.$course_list.') AND
                    session_id = '.$session_id.'
                ORDER BY login_course_date ASC';
        $rs = Database::query($sql);
        $connections = [];

        while ($row = Database::fetch_array($rs)) {
            $timestamp_login_date = api_strtotime($row['login_course_date'], 'UTC');
            $timestamp_logout_date = api_strtotime($row['logout_course_date'], 'UTC');
            $connections[] = [
                'login' => $timestamp_login_date,
                'logout' => $timestamp_logout_date,
                'c_id' => $row['c_id'],
            ];
        }

        return $connections;
    }

    /**
     * Creates a small table in the last column of the table with the user overview.
     *
     * @param int $user_id the id of the user
     *
     * @return array List course
     */
    public static function returnCourseTracking($user_id)
    {
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return [];
        }

        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        // getting all the courses of the user
        $sql = "SELECT * FROM $tbl_course_user
                WHERE
                    user_id = $user_id AND
                    relation_type <> ".COURSE_RELATION_TYPE_RRHH;
        $result = Database::query($sql);

        $list = [];

        while ($row = Database::fetch_array($result)) {
            $courseInfo = api_get_course_info_by_id($row['c_id']);
            $courseId = $courseInfo['real_id'];
            $courseCode = $courseInfo['code'];

            if (empty($courseInfo)) {
                continue;
            }

            $avg_score = Tracking::get_avg_student_score($user_id, $courseCode);
            if (is_numeric($avg_score)) {
                $avg_score = round($avg_score, 2);
            } else {
                $avg_score = '-';
            }

            // Student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
            $exercisesResults = self::exercises_results($user_id, $courseCode);

            $resultToString = '';
            if (!is_null($exercisesResults['percentage'])) {
                $resultToString = $exercisesResults['score_obtained'].'/'.$exercisesResults['score_possible'].' ( '.$exercisesResults['percentage'].'% )';
            }

            $item = [
                'code' => $courseInfo['code'],
                'real_id' => $courseInfo['real_id'],
                'title' => $courseInfo['title'],
                'category' => $courseInfo['categoryName'],
                'image_small' => $courseInfo['course_image'],
                'image_large' => $courseInfo['course_image_large'],
                'time_spent' => api_time_to_hms(Tracking::get_time_spent_on_the_course($user_id, $courseId)),
                'student_progress' => round(Tracking::get_avg_student_progress($user_id, $courseCode)),
                'student_score' => $avg_score,
                'student_message' => Tracking::count_student_messages($user_id, $courseCode),
                'student_assignments' => Tracking::count_student_assignments($user_id, $courseCode),
                'student_exercises' => $resultToString,
                'questions_answered' => $exercisesResults['questions_answered'],
                'last_connection' => Tracking::get_last_connection_date_on_the_course($user_id, $courseInfo),
            ];
            $list[] = $item;
        }

        return $list;
    }

    /**
     * Display a sortable table that contains an overview off all the
     * reporting progress of all users and all courses the user is subscribed to.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     *          Alex Aragon <alex.aragon@beeznest.com>, BeezNest, Perú
     *
     * @version Chamilo 1.11.8
     *
     * @since April 2019
     */
    public static function returnTrackingUserOverviewFilter($user_id)
    {
        $tpl = new Template('', false, false, false, false, false, false);
        $userInfo = api_get_user_info($user_id);

        $avatar = UserManager::getUserPicture($user_id, USER_IMAGE_SIZE_SMALL);
        $user = [
            'id' => $user_id,
            'code_user' => $userInfo['official_code'],
            'complete_name' => $userInfo['complete_name'],
            'username' => $userInfo['username'],
            'course' => self::returnCourseTracking($user_id),
            'avatar' => $avatar,
        ];

        $tpl->assign('item', $user);
        $templateName = $tpl->get_template('my_space/partials/tracking_user_overview.tpl');
        $content = $tpl->fetch($templateName);

        return $content;
    }

    /**
     * Display a sortable table that contains an overview off all the
     * reporting progress of all users and all courses the user is subscribed to.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     *         Alex Aragon <alex.aragon@beeznest.com>, BeezNest, Perú
     *
     * @version Chamilo 1.11.8
     *
     * @since October 2008, Update April 2019
     */
    public static function display_tracking_user_overview()
    {
        self::display_user_overview_export_options();

        $params = ['view' => 'admin', 'display' => 'user'];
        $table = new SortableTable(
            'tracking_user_overview',
            ['MySpace', 'get_number_of_users_tracking_overview'],
            ['MySpace', 'get_user_data_tracking_overview'],
            0,
            20,
            'ASC',
            null, [
                'class' => 'table table-transparent',
            ]
        );
        $table->additional_parameters = $params;

        $table->set_column_filter(0, ['MySpace', 'returnTrackingUserOverviewFilter']);
        $tableContent = $table->return_table();
        $tpl = new Template('', false, false, false, false, false, false);
        $tpl->assign('table', $tableContent);
        $templateName = $tpl->get_template('my_space/user_summary.tpl');
        $tpl->display($templateName);
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

        if (isset($_GET['tracking_list_coaches_column'])) {
            $tracking_column = (int) $_GET['tracking_list_coaches_column'];
        } else {
            $tracking_column = ($is_western_name_order xor $sort_by_first_name) ? 1 : 0;
        }

        $tracking_direction = (isset($_GET['tracking_list_coaches_direction']) && in_array(strtoupper($_GET['tracking_list_coaches_direction']), ['ASC', 'DESC', 'ASCENDING', 'DESCENDING', '0', '1'])) ? $_GET['tracking_list_coaches_direction'] : 'DESC';
        // Prepare array for column order - when impossible, use some of user names.
        if ($is_western_name_order) {
            $order = [
                0 => 'firstname',
                1 => 'lastname',
                2 => $sort_by_first_name ? 'firstname' : 'lastname',
                3 => 'login_date',
                4 => $sort_by_first_name ? 'firstname' : 'lastname',
                5 => $sort_by_first_name ? 'firstname' : 'lastname',
            ];
        } else {
            $order = [
                0 => 'lastname',
                1 => 'firstname',
                2 => $sort_by_first_name ? 'firstname' : 'lastname',
                3 => 'login_date',
                4 => $sort_by_first_name ? 'firstname' : 'lastname',
                5 => $sort_by_first_name ? 'firstname' : 'lastname',
            ];
        }
        $table = new SortableTable(
            'tracking_list_coaches_myspace',
            ['MySpace', 'count_coaches'],
            null,
            ($is_western_name_order xor $sort_by_first_name) ? 1 : 0
        );
        $parameters['view'] = 'admin';
        $table->set_additional_parameters($parameters);
        if ($is_western_name_order) {
            $table->set_header(0, get_lang('FirstName'), true);
            $table->set_header(1, get_lang('LastName'), true);
        } else {
            $table->set_header(0, get_lang('LastName'), true);
            $table->set_header(1, get_lang('FirstName'), true);
        }
        $table->set_header(2, get_lang('TimeSpentOnThePlatform'), false);
        $table->set_header(3, get_lang('LastConnexion'), false);
        $table->set_header(4, get_lang('NbStudents'), false);
        $table->set_header(5, get_lang('CountCours'), false);
        $table->set_header(6, get_lang('NumberOfSessions'), false);
        $table->set_header(7, get_lang('Sessions'), false);

        if ($is_western_name_order) {
            $csv_header[] = [
                get_lang('FirstName'),
                get_lang('LastName'),
                get_lang('TimeSpentOnThePlatform'),
                get_lang('LastConnexion'),
                get_lang('NbStudents'),
                get_lang('CountCours'),
                get_lang('NumberOfSessions'),
            ];
        } else {
            $csv_header[] = [
                get_lang('LastName'),
                get_lang('FirstName'),
                get_lang('TimeSpentOnThePlatform'),
                get_lang('LastConnexion'),
                get_lang('NbStudents'),
                get_lang('CountCours'),
                get_lang('NumberOfSessions'),
            ];
        }

        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
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
            $sqlCoachs .= " ORDER BY `".$order[$tracking_column]."` ".$tracking_direction;
        }

        $result_coaches = Database::query($sqlCoachs);
        $global_coaches = [];
        while ($coach = Database::fetch_array($result_coaches)) {
            $global_coaches[$coach['user_id']] = $coach;
        }

        $sql_session_coach = "SELECT session.id_coach, u.id as user_id, lastname, firstname, MAX(login_date) as login_date
                                FROM $tbl_user u , $tbl_sessions as session, $tbl_track_login
                                WHERE id_coach = u.id AND login_user_id = u.id
                                GROUP BY u.id
                                ORDER BY login_date $tracking_direction";

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql_session_coach = "SELECT session.id_coach, u.id as user_id, lastname, firstname, MAX(login_date) as login_date
					FROM $tbl_user u , $tbl_sessions as session, $tbl_track_login , $tbl_session_rel_access_url as session_rel_url
					WHERE
					    id_coach = u.id AND
					    login_user_id = u.id  AND
					    access_url_id = $access_url_id AND
					    session_rel_url.session_id = session.id
					GROUP BY  u.id
					ORDER BY login_date $tracking_direction";
            }
        }

        $result_sessions_coach = Database::query($sql_session_coach);
        //$total_no_coaches += Database::num_rows($result_sessions_coach);
        while ($coach = Database::fetch_array($result_sessions_coach)) {
            $global_coaches[$coach['user_id']] = $coach;
        }

        $all_datas = [];
        foreach ($global_coaches as $id_coach => $coaches) {
            $time_on_platform = api_time_to_hms(
                Tracking::get_time_spent_on_the_platform($coaches['user_id'])
            );
            $last_connection = Tracking::get_last_connection_date(
                $coaches['user_id']
            );
            $nb_students = count(
                Tracking::get_student_followed_by_coach($coaches['user_id'])
            );
            $nb_courses = count(
                Tracking::get_courses_followed_by_coach($coaches['user_id'])
            );
            $nb_sessions = count(
                Tracking::get_sessions_coached_by_user($coaches['user_id'])
            );

            $table_row = [];
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
                '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
            </a>';
            $all_datas[] = $table_row;

            if ($is_western_name_order) {
                $csv_content[] = [
                    api_html_entity_decode($coaches['firstname'], ENT_QUOTES),
                    api_html_entity_decode($coaches['lastname'], ENT_QUOTES),
                    $time_on_platform,
                    $last_connection,
                    $nb_students,
                    $nb_courses,
                    $nb_sessions,
                ];
            } else {
                $csv_content[] = [
                    api_html_entity_decode($coaches['lastname'], ENT_QUOTES),
                    api_html_entity_decode($coaches['firstname'], ENT_QUOTES),
                    $time_on_platform,
                    $last_connection,
                    $nb_students,
                    $nb_courses,
                    $nb_sessions,
                ];
            }
        }

        if ($tracking_column != 3) {
            if ($tracking_direction == 'DESC') {
                usort($all_datas, ['MySpace', 'rsort_users']);
            } else {
                usort($all_datas, ['MySpace', 'sort_users']);
            }
        }

        if ($export_csv && $tracking_column != 3) {
            usort($csv_content, 'sort_users');
        }
        if ($export_csv) {
            $csv_content = array_merge($csv_header, $csv_content);
        }

        foreach ($all_datas as $row) {
            $table->addRow($row, 'align="right"');
        }
        $table->display();
    }

    /**
     * @return mixed
     */
    public static function count_coaches()
    {
        global $total_no_coaches;

        return $total_no_coaches;
    }

    public static function sort_users($a, $b)
    {
        $tracking = Session::read('tracking_column');

        return api_strcmp(
            trim(api_strtolower($a[$tracking])),
            trim(api_strtolower($b[$tracking]))
        );
    }

    public static function rsort_users($a, $b)
    {
        $tracking = Session::read('tracking_column');

        return api_strcmp(
            trim(api_strtolower($b[$tracking])),
            trim(api_strtolower($a[$tracking]))
        );
    }

    /**
     * Display a sortable table that contains an overview off all the progress of the user in a session.
     *
     * @deprecated ?
     *
     * @author César Perales <cesar.perales@beeznest.com>, Beeznest Team
     */
    public static function display_tracking_lp_progress_overview(
        $sessionId = '',
        $courseId = '',
        $date_from,
        $date_to
    ) {
        $course = api_get_course_info_by_id($courseId);
        /**
         * Column name
         * The order is important you need to check the $column variable in the model.ajax.php file.
         */
        $columns = [
            get_lang('Username'),
            get_lang('FirstName'),
            get_lang('LastName'),
        ];
        //add lessons of course
        $lessons = LearnpathList::get_course_lessons($course['code'], $sessionId);

        //create columns array
        foreach ($lessons as $lesson_id => $lesson) {
            $columns[] = $lesson['name'];
        }

        $columns[] = get_lang('Total');

        /**
         * Column config.
         */
        $column_model = [
            [
                'name' => 'username',
                'index' => 'username',
                'align' => 'left',
                'search' => 'true',
                'wrap_cell' => "true",
            ],
            [
                'name' => 'firstname',
                'index' => 'firstname',
                'align' => 'left',
                'search' => 'true',
            ],
            [
                'name' => 'lastname',
                'index' => 'lastname',
                'align' => 'left',
                'search' => 'true',
            ],
        ];

        // Get dinamic column names
        foreach ($lessons as $lesson_id => $lesson) {
            $column_model[] = [
                'name' => $lesson['id'],
                'index' => $lesson['id'],
                'align' => 'left',
                'search' => 'true',
            ];
        }

        $column_model[] = [
            'name' => 'total',
            'index' => 'total',
            'align' => 'left',
            'search' => 'true',
        ];

        $action_links = '';
        // jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_session_lp_progress&session_id='.$sessionId.'&course_id='.$courseId.'&date_to='.$date_to.'&date_from='.$date_from;

        // Table Id
        $tableId = 'lpProgress';

        // Autowidth
        $extra_params['autowidth'] = 'true';

        // height auto
        $extra_params['height'] = 'auto';

        $table = Display::grid_js(
            $tableId,
            $url,
            $columns,
            $column_model,
            $extra_params,
            [],
            $action_links,
            true
        );

        $return = '<script>$(function() {'.$table.
            'jQuery("#'.$tableId.'").jqGrid("navGrid","#'.$tableId.'_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
                jQuery("#'.$tableId.'").jqGrid("navButtonAdd","#'.$tableId.'_pager",{
                       caption:"",
                       title:"'.get_lang('ExportExcel').'",
                       onClickButton : function () {
                           jQuery("#'.$tableId.'").jqGrid("excelExport",{"url":"'.$url.'&export_format=xls"});
                       }
                });
            });</script>';
        $return .= Display::grid_html($tableId);

        return $return;
    }

    /**
     * Display a sortable table that contains an overview off all the progress of the user in a session.
     *
     * @param int $sessionId  The session ID
     * @param int $courseId   The course ID
     * @param int $exerciseId The quiz ID
     * @param     $date_from
     * @param     $date_to
     *
     * @return string HTML array of results formatted for gridJS
     *
     * @deprecated ?
     *
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
         * The column order is important. Check $column variable in the main/inc/ajax/model.ajax.php file.
         */
        $columns = [
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
        ];

        /**
         * Column config.
         */
        $column_model = [
            ['name' => 'session', 'index' => 'session', 'align' => 'left', 'search' => 'true', 'wrap_cell' => "true"],
            ['name' => 'exercise_id', 'index' => 'exercise_id', 'align' => 'left', 'search' => 'true'],
            ['name' => 'quiz_title', 'index' => 'quiz_title', 'align' => 'left', 'search' => 'true'],
            ['name' => 'username', 'index' => 'username', 'align' => 'left', 'search' => 'true'],
            ['name' => 'lastname', 'index' => 'lastname', 'align' => 'left', 'search' => 'true'],
            ['name' => 'firstname', 'index' => 'firstname', 'align' => 'left', 'search' => 'true'],
            ['name' => 'time', 'index' => 'time', 'align' => 'left', 'search' => 'true', 'wrap_cell' => "true"],
            ['name' => 'question_id', 'index' => 'question_id', 'align' => 'left', 'search' => 'true'],
            ['name' => 'question', 'index' => 'question', 'align' => 'left', 'search' => 'true', 'wrap_cell' => "true"],
            ['name' => 'description', 'index' => 'description', 'align' => 'left', 'width' => '550', 'search' => 'true', 'wrap_cell' => "true"],
            ['name' => 'answer', 'index' => 'answer', 'align' => 'left', 'search' => 'true', 'wrap_cell' => "true"],
            ['name' => 'correct', 'index' => 'correct', 'align' => 'left', 'search' => 'true', 'wrap_cell' => "true"],
        ];
        //get dynamic column names

        // jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_exercise_progress&session_id='.$sessionId.'&course_id='.$courseId.'&exercise_id='.$exerciseId.'&date_to='.$date_to.'&date_from='.$date_from;

        // Autowidth
        $extra_params['autowidth'] = 'true';

        // height auto
        $extra_params['height'] = 'auto';

        $tableId = 'exerciseProgressOverview';
        $table = Display::grid_js(
            $tableId,
            $url,
            $columns,
            $column_model,
            $extra_params,
            [],
            '',
            true
        );

        $return = '<script>$(function() {'.$table.
            'jQuery("#'.$tableId.'").jqGrid("navGrid","#'.$tableId.'_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
                jQuery("#'.$tableId.'").jqGrid("navButtonAdd","#'.$tableId.'_pager",{
                       caption:"",
                       title:"'.get_lang('ExportExcel').'",
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
     * and give you the opportunity to include these in the CSV export.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     *
     * @version 1.8.6
     *
     * @since November 2008
     */
    public static function display_user_overview_export_options()
    {
        $message = '';
        $defaults = [];
        // include the user manager and formvalidator library
        if (isset($_GET['export']) && 'options' == $_GET['export']) {
            // get all the defined extra fields
            $extrafields = UserManager::get_extra_fields(
                0,
                50,
                5,
                'ASC',
                false,
                1
            );

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
                $exportFields = Session::read('additional_export_fields');
                if (is_array($exportFields)) {
                    foreach ($exportFields as $key => $value) {
                        $defaults['extra_export_field'.$value] = 1;
                    }
                }
                $form->setDefaults($defaults);
            } else {
                $form->addElement('html', Display::return_message(get_lang('ThereAreNotExtrafieldsAvailable'), 'warning'));
            }

            if ($form->validate()) {
                // exporting the form values
                $values = $form->exportValues();

                // re-initialising the session that contains the additional fields that need to be exported
                Session::write('additional_export_fields', []);

                // adding the fields that are checked to the session
                $message = '';
                $additionalExportFields = [];
                foreach ($values as $field_ids => $value) {
                    if ($value == 1 && strstr($field_ids, 'extra_export_field')) {
                        $additionalExportFields[] = str_replace('extra_export_field', '', $field_ids);
                    }
                }
                Session::write('additional_export_fields', $additionalExportFields);

                // adding the fields that will be also exported to a message string
                $additionalExportFields = Session::read('additional_export_fields');
                if (is_array($additionalExportFields)) {
                    foreach ($additionalExportFields as $key => $extra_field_export) {
                        $message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
                    }
                }

                // Displaying a feedback message
                if (!empty($additionalExportFields)) {
                    echo Display::return_message(
                        get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>',
                        'confirm',
                        false
                    );
                } else {
                    echo Display::return_message(
                        get_lang('NoAdditionalFieldsWillBeExported'),
                        'confirm',
                        false
                    );
                }
            } else {
                $form->display();
            }
        } else {
            $additionalExportFields = Session::read('additional_export_fields');
            if (!empty($additionalExportFields)) {
                // get all the defined extra fields
                $extrafields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

                foreach ($additionalExportFields as $key => $extra_field_export) {
                    $message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
                }

                echo Display::return_message(
                    get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>',
                    'normal',
                    false
                );
            }
        }
    }

    /**
     * Export to cvs a list of users who were enrolled in the lessons.
     * It is necessary that in the extra field, a company is defined.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     *
     * @return array
     */
    public static function exportCompanyResumeCsv($startDate, $endDate)
    {
        $companys = self::getCompanyLearnpathSubscription($startDate, $endDate);
        $csv_content = [];
        // Printing table
        $total = 0;
        $displayText = get_lang('Company');
        // the first line of the csv file with the column headers
        $csv_row = [];
        $csv_row[] = $displayText;

        $csv_row[] = get_lang('CountOfSubscribedUsers');
        $csv_content[] = $csv_row;

        foreach ($companys as $entity => $student) {
            $csv_row = [];
            // user official code
            $csv_row[] = $entity;
            $csv_row[] = count($student);
            $total += count($student);
            $csv_content[] = $csv_row;
        }

        $csv_row = [];
        // user official code
        $csv_row[] = get_lang('GeneralTotal');
        $csv_row[] = $total;
        $csv_content[] = $csv_row;
        Export::arrayToCsv($csv_content, 'reporting_company_resume');
        exit;
    }

    /**
     * Generates a structure to show the links or names for the authors by lesson report.
     *
     * @param array $students
     * @param array $studentRegistered
     * @param       $lpCourseCode
     */
    public static function getStudentDataToReportByLp($students = [], $studentRegistered = [], $lpCourseCode)
    {
        $data = [];
        $totalStudents = 0;
        $data['csv'] = '';
        $data['html'] = '';
        $icon = Display::return_icon('statistics.png', get_lang('Stats'));
        foreach ($students as $student) {
            $lpSessionId = isset($student['session_id']) ? (int) $student['session_id'] : 0;
            $studentId = (int) $student['id'];
            if (!isset($studentRegistered[$studentId][$lpSessionId])) {
                $url = api_get_path(WEB_CODE_PATH)."mySpace/myStudents.php?details=true&student=$studentId";
                if (0 != $lpSessionId) {
                    $url .= "&id_session=$lpSessionId";
                }
                $url .= "&course=$lpCourseCode";
                $reportLink = Display::url(
                    $icon,
                    $url
                );
                $studentName = $student['complete_name']."(".$student['company'].")";
                $studentRegistered[$studentId][$lpSessionId] = $student;
                $data['csv'] .= $studentName.' / ';
                $data['html'] .= "$reportLink <strong>$studentName</strong><br>";
                $totalStudents++;
            }
        }
        $data['student_registered'] = $studentRegistered;
        $data['total_students'] = $totalStudents;

        return $data;
    }

    /**
     * * Generates a structure to show the names for the authors by lesson report by item.
     *
     * @param array  $students
     * @param array  $studentProcessed
     * @param string $typeReport
     * @param false  $csv
     */
    public static function getStudentDataToReportByLpItem($students = [], $studentProcessed = [], $typeReport = '', $csv = false)
    {
        $totalStudent = count($students);
        $sessionIcon = Display::return_icon(
            'admin_star.png',
            get_lang('StudentInSessionCourse'),
            [],
            ICON_SIZE_MEDIUM
        );
        $classIcon = Display::return_icon(
            'group_summary.png',
            get_lang('UsersInsideClass'),
            '',
            ICON_SIZE_MEDIUM
        );
        /* use 'for' to performance */
        for ($i = 0; $i < $totalStudent; $i++) {
            $student = $students[$i];
            $studentId = $student['id'];
            $lpItemIdStudent = $student['lp_item_id'];
            $sessionId = isset($student['session_id']) ? (int) $student['session_id'] : 0;
            $studentName = $student['complete_name'];
            $studentCompany = $student['company'];
            $studentName = "$studentName($studentCompany)";
            $type = isset($student['type']) ? $student['type'] : null;
            $icon = null;
            if (0 != $sessionId) {
                $icon = $sessionIcon;
            }
            if ('class' == $typeReport) {
                $icon = $classIcon;
            }
            $studentString = "$icon $studentName";
            if (0 != $sessionId) {
                $studentString = "<strong>$studentString</strong>";
            }
            if ($csv == false) {
                $studentProcessed[$lpItemIdStudent][$type][$studentId] = $studentString.'<br>';
            } else {
                $studentProcessed[$lpItemIdStudent][$type][$studentId] = "$studentName / ";
            }
        }

        return $studentProcessed;
    }

    /**
     * Displays a list as a table of users who were enrolled in the lessons.
     * It is necessary that in the extra field, a company is defined.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     */
    public static function displayResumeCompany(
        $startDate = null,
        $endDate = null
    ) {
        $companys = self::getCompanyLearnpathSubscription($startDate, $endDate);
        $tableHtml = '';
        // Printing table
        $total = 0;
        $table = "<div class='table-responsive'><table class='table table-hover table-striped table-bordered data_table'>";

        $displayText = get_lang('Company');
        $table .= "<thead><tr><th class='th-header'>$displayText</th><th class='th-header'> ".get_lang('CountOfSubscribedUsers')." </th></tr></thead><tbody>";

        foreach ($companys as $entity => $student) {
            $table .= "<tr><td>$entity</td><td>".count($student)."</td></tr>";
            $total += count($student);
        }
        $table .= "<tr><td>".get_lang('GeneralTotal')."</td><td>$total</td></tr>";
        $table .= '</tbody></table></div>';

        if (!empty($startDate) or !empty($endDate)) {
            $tableHtml = $table;
        }

        $form = new FormValidator('searchDate', 'get');
        $form->addHidden('display', 'company');
        $today = new DateTime();
        if (empty($startDate)) {
            $startDate = api_get_local_time($today->modify('first day of this month')->format('Y-m-d'));
        }
        if (empty($endDate)) {
            $endDate = api_get_local_time($today->modify('last day of this month')->format('Y-m-d'));
        }
        $form->addDatePicker(
            'startDate',
            get_lang('DateStart'),
            [
                'value' => $startDate,
            ]);
        $form->addDatePicker(
            'endDate',
            get_lang('DateEnd'),
            [
                'value' => $endDate,
            ]);
        $form->addButtonSearch(get_lang('Search'));
        if (count($companys) != 0) {
            //$form->addButtonSave(get_lang('Ok'), 'export');
            $form
                ->addButton(
                    'export_csv',
                    get_lang('ExportAsCSV'),
                    'check',
                    'primary',
                    null,
                    null,
                    [
                    ]
                );
        }

        $tableContent = $form->returnForm();
        $tableContent .= $tableHtml;
        // $tableContent .= $table->return_table();

        $tpl = new Template('', false, false, false, false, false, false);
        $tpl->assign('table', $tableContent);
        $templateName = $tpl->get_template('my_space/course_summary.tpl');
        $tpl->display($templateName);
    }

    /**
     *  Displays a list as a table of teachers who are set authors by a extra_field authors.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param bool        $csv
     */
    public static function displayResumeLP(
        $startDate = null,
        $endDate = null,
        $csv = false
    ) {
        $tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tblExtraFieldValue = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);
        $tblLp = Database::get_course_table(TABLE_LP_MAIN);
        $tblAccessUrlCourse = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $accessUrlFilter = '';
        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
            $accessUrlFilter = " INNER JOIN $tblAccessUrlCourse aurc
                      ON (c.id = aurc.c_id AND aurc.access_url_id = $urlId)";
        }
        $query = "
        SELECT DISTINCT
            lp.name,
            lpi.title,
            lp.id as lp_id,
            lpi.id AS lp_item_id,
            REPLACE (efv.value, ';', ',') AS users_id,
            c.title AS course_title,
            c.code AS course_code
        FROM $tblExtraFieldValue AS efv
        INNER JOIN $tblExtraField AS ef
        ON (
            efv.field_id = ef.id AND
            ef.variable = 'authorlpitem' AND
            efv.value != ''
            )
        INNER JOIN $tblLpItem AS lpi
        ON (efv.item_id = lpi.iid)
        INNER JOIN $tblLp AS lp
        ON (lpi.lp_id = lp.iid AND lpi.c_id = lp.c_id)
        INNER JOIN $tblCourse AS c
        ON (lp.c_id = c.id)
        $accessUrlFilter";
        $queryResult = Database::query($query);
        $dataTeachers = Database::store_result($queryResult, 'ASSOC');
        $totalData = count($dataTeachers);
        $lpItems = [];
        $teachers = [];
        $users = [];
        $learningPaths = [];
        $csvContent = [];
        $htmlData = '';
        /* use 'for' to performance */
        for ($i = 0; $i < $totalData; $i++) {
            $row = $dataTeachers[$i];
            $lpId = $row['lp_id'];
            $lpItems[] = $lpId;
            $authorData = $row['users_id'];
            $learningPaths[$lpId] = $row;
            if (strpos($authorData, ",") === false) {
                if (!isset($users[$authorData])) {
                    $users[$authorData] = api_get_user_info($authorData);
                }
                $teachers[$authorData][$lpId] = $users[$authorData];
                $learningPaths[$lpId]['teachers'][$authorData] = $users[$authorData];
            } else {
                $items = explode(',', $authorData);
                $totalItems = count($items);
                for ($j = 0; $j < $totalItems; $j++) {
                    $authorData = $items[$j];
                    if (!isset($users[$authorData])) {
                        $users[$authorData] = api_get_user_info($authorData);
                    }
                    $teachers[$authorData][$lpId] = $users[$authorData];
                    $learningPaths[$lpId]['teachers'][$authorData] = $users[$authorData];
                }
            }
        }
        $lpItems = array_unique($lpItems);
        $whereInLp = implode(',', $lpItems);
        if (count($lpItems) != 0) {
            $registeredUsers = self::getCompanyLearnpathSubscription(
                $startDate,
                $endDate,
                $whereInLp
            );
            foreach ($registeredUsers as $students) {
                $totalStudents = count($students);
                /* use 'for' to performance */
                for ($i = 0; $i < $totalStudents; $i++) {
                    $user = $students[$i];
                    $lpId = $user['lp_item'];
                    $studentId = $user['id'];
                    $learningPaths[$lpId]['courseStudent'][$studentId] = $user;
                }
            }
            $registeredUsersBySession = self::getSessionAddUserCourseFromTrackDefault(
                $startDate,
                $endDate,
                $whereInLp
            );
            foreach ($registeredUsersBySession as $lpId => $student) {
                $totalStudents = count($student);
                /* use 'for' to performance */
                for ($i = 0; $i < $totalStudents; $i++) {
                    $user = $student[$i];
                    $lpId = $user['lp'];
                    $studentId = $user['id'];
                    $learningPaths[$lpId]['sessionStudent'][$studentId] = $user;
                    $learningPaths[$lpId]['sessionStudent'][$studentId]['session_id'] = $user;
                }
            }
            $registeredUsersGroup = self::getCompanyLearnpathSubscription(
                $startDate,
                $endDate,
                $whereInLp,
                true
            );
            foreach ($registeredUsersGroup as $student) {
                $totalStudents = count($student);
                /* use 'for' to performance */
                for ($i = 0; $i < $totalStudents; $i++) {
                    $user = $student[$i];
                    $lpId = $user['lp_item'];
                    $studentId = $user['id'];
                    $learningPaths[$lpId]['courseStudentGroup'][$studentId] = $user;
                }
            }

            $index = 0;
            $iconAdd = Display::return_icon('add.png', get_lang('ShowOrHide'), '', ICON_SIZE_SMALL);
            $iconRemove = Display::return_icon('error.png', get_lang('ShowOrHide'), '', ICON_SIZE_SMALL);
            $htmlData = "<div class='table-responsive'>
            <table class='table table-hover table-striped table-bordered data_table'>
            <thead>
                <tr>
                    <th class='th-header'>".get_lang('Author')."</th>
                    <th class='th-header'>".get_lang('LearningPathList')."</th>
                    <th class='th-header'>".get_lang('CountOfSubscribedUsers')."</th>
                    <th class='th-header'>".get_lang('StudentList')."</th>
                </tr>
            </thead>
                <tbody>";
            $lastTeacher = '';
            /* csv */
            $csv_row = [];
            $csv_row[] = get_lang('Author');
            $csv_row[] = get_lang('LearningPathList');
            $csv_row[] = get_lang('CountOfSubscribedUsers');
            $csv_row[] = get_lang('StudentList');
            $csvContent[] = $csv_row;
            $studentsName = '';
            /* csv */
            foreach ($teachers as $authorLId => $teacher) {
                $totalStudents = 0;
                foreach ($teacher as $lpId => $teacherData) {
                    $lpSessionId = 0;
                    $lpData = $learningPaths[$lpId];
                    $printTeacherName = ($lastTeacher != $teacherData['complete_name']) ? $teacherData['complete_name'] : '';
                    $htmlData .= "<tr><td>$printTeacherName</td>";
                    $hiddenField = 'student_show_'.$index;
                    $hiddenFieldLink = 'student_show_'.$index.'_';
                    $lpCourseCode = $lpData['course_code'];
                    $lpName = $lpData['name'];
                    $courseStudent = isset($lpData['courseStudent']) ? $lpData['courseStudent'] : [];
                    $courseStudentGroup = isset($lpData['courseStudentGroup']) ? $lpData['courseStudentGroup'] : [];
                    $sessionStudent = isset($lpData['sessionStudent']) ? $lpData['sessionStudent'] : [];
                    $htmlData .= "<td>$lpName</td><td>".count($courseStudent)." ( ".count($sessionStudent)." )</td><td>";
                    $csv_row = [];
                    $csv_row[] = $printTeacherName;
                    $csv_row[] = $lpName;
                    $csv_row[] = count($courseStudent).' ( '.count($sessionStudent)." )";
                    if (!empty($courseStudent)
                        || !empty($courseStudentGroup)
                        || !empty($sessionStudent)
                    ) {
                        $htmlData .= "<a href='#!' id='$hiddenFieldLink' onclick='showHideStudent(\"$hiddenField\")'>
                        <div class='icon_add'>$iconAdd</div>
                        <div class='icon_remove hidden'>$iconRemove</div>
                        </a>
                        <div id='$hiddenField' class='hidden'>";
                        $studentRegistered = [];

                        $tempArray = self::getStudentDataToReportByLp($courseStudent, $studentRegistered, $lpCourseCode);
                        $studentsName .= $tempArray['csv'];
                        $htmlData .= $tempArray['html'];
                        $studentRegistered = $tempArray['student_registered'];
                        $totalStudents += $tempArray['total_students'];

                        $tempArray = self::getStudentDataToReportByLp($sessionStudent, $studentRegistered, $lpCourseCode);
                        $studentsName .= $tempArray['csv'];
                        $htmlData .= $tempArray['html'];
                        $studentRegistered = $tempArray['student_registered'];
                        $totalStudents += $tempArray['total_students'];

                        $tempArray = self::getStudentDataToReportByLp($courseStudentGroup, $studentRegistered, $lpCourseCode);
                        $studentsName .= $tempArray['csv'];
                        $htmlData .= $tempArray['html'];
                        $studentRegistered = $tempArray['student_registered'];
                        $totalStudents += $tempArray['total_students'];

                        $htmlData .= "</div>";
                    }
                    $htmlData .= "</td></tr>";
                    $index++;
                    $csv_row[] = trim($studentsName, ' / ');
                    $studentsName = '';
                    $csvContent[] = $csv_row;
                    $lastTeacher = $teacherData['complete_name'];
                }
                $htmlData .= "<tr>
                <td></td>
                <td><strong>".get_lang('LearnpathsTotal')." ".count($teacher)." </strong></td>
                <td><strong>$totalStudents</strong></td>
                <td></td>
                </tr>";
            }
            $htmlData .= "</tbody>
            </table>
            </div>";
        }
        if (false == $csv) {
            $form = new FormValidator('searchDate', 'get');
            $form->addHidden('display', 'learningPath');
            $today = new DateTime();
            if (empty($startDate)) {
                $startDate = $today->modify('first day of this month')->format('Y-m-d');
            }
            if (empty($endDate)) {
                $endDate = $today->modify('last day of this month')->format('Y-m-d');
            }
            $form->addDatePicker(
                'startDate',
                get_lang('DateStart'),
                [
                    'value' => $startDate,
                ]);
            $form->addDatePicker(
                'endDate',
                get_lang('DateEnd'),
                [
                    'value' => $endDate,
                ]);
            $form->addButtonSearch(get_lang('Search'));
            if (0 != count($csvContent)) {
                $form
                    ->addButton(
                        'export_csv',
                        get_lang('ExportAsCSV'),
                        'check',
                        'primary',
                        null,
                        null,
                        [
                        ]
                    );
            }
            $tableContent = $form->returnForm();
            if (!empty($startDate) || !empty($endDate)) {
                $tableContent .= $htmlData;
            }
            $tpl = new Template('', false, false, false, false, false, false);
            $tpl->assign('table', $tableContent);
            $templateName = $tpl->get_template('my_space/course_summary.tpl');
            $tpl->display($templateName);
        } else {
            if (count($csvContent) != 0) {
                Export::arrayToCsv($csvContent, 'reporting_lp_by_authors');
            }
        }
    }

    /**
     *  Displays a list as a table of teachers who are set authors of lp's item by a extra_field authors.
     */
    public static function displayResumeLpByItem(string $startDate = null, string $endDate = null, bool $csv = false)
    {
        $tableHtml = '';
        $table = '';
        $tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $tblExtraFieldValue = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);
        $tblLp = Database::get_course_table(TABLE_LP_MAIN);
        $tblAccessUrlCourse = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $accessUrlFilter = '';
        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
            $accessUrlFilter = " INNER JOIN $tblAccessUrlCourse aurc
                      ON (lp.c_id = aurc.c_id AND aurc.access_url_id = $urlId)";
        }
        $index = 0;
        $cLpItems = [];
        $cLpItemsAuthor = [];
        $authorArray = [];
        $studentArray = [];
        $whereInLp = [];
        $dataSet = [];
        /** Get lp items only with authors */
        $sql = " SELECT
                efv.item_id AS lp_item_id,
                efv.value AS author
            FROM $tblExtraFieldValue AS efv
            INNER JOIN $tblExtraField AS ef
            ON (
                ef.variable = 'authorlpitem' AND
                efv.field_id = ef.id AND
                efv.value != ''
            )
            ORDER BY efv.item_id ";
        $queryResult = Database::query($sql);
        $data = Database::store_result($queryResult, 'ASSOC');
        $totalData = count($data);
        /* use 'for' to performance */
        for ($i = 0; $i < $totalData; $i++) {
            $cLpItemsAuthor[$data[$i]['lp_item_id']] = $data[$i]['author'];
        }
        /** Get lp items only with price */
        $sql = " SELECT
               lp.iid AS lp_id,
               lp.name AS lp_name,
               efv.item_id AS lp_item_id,
               lpi.title AS title,
               efv.value AS price
            FROM $tblExtraFieldValue AS efv
            INNER JOIN $tblExtraField AS ef
            ON (
                ef.variable = 'price' AND
                efv.field_id = ef.id AND
                efv.value > 0
            )
            INNER JOIN $tblLpItem AS lpi
            ON (lpi.iid = efv.item_id)
            INNER JOIN $tblLp AS lp
            ON (lpi.lp_id = lp.iid AND lpi.c_id = lp.c_id)
            $accessUrlFilter";
        $queryResult = Database::query($sql);
        $data = Database::store_result($queryResult, 'ASSOC');
        $totalData = count($data);
        /* use 'for' to performance */
        for ($i = 0; $i < $totalData; $i++) {
            $item = $data[$i];
            $lpItemId = (int) $item['lp_item_id'];
            $whereInLp[] = $item['lp_id'];
            $author = isset($cLpItemsAuthor[$lpItemId]) ? $cLpItemsAuthor[$lpItemId] : null;
            $item['author'] = $author;
            if (!empty($author)) {
                $cLpItems[count($cLpItems)] = $item;
            }
        }
        $totalLpItems = count($cLpItems);
        $tableNoData = "<div class='table-responsive'>
                <table class='table table-hover table-striped table-bordered data_table'>
                <thead>
                    <tr>
                    <th class='th-header'>".get_lang('NoDataAvailable').'</th>
                </tr>
                </thead>
                </tbody>
                </tbody>
                </table>
                </div>';
        if (0 == $totalLpItems) {
            $tableHtml = $tableNoData;
        } elseif (0 == count($whereInLp)) {
            $tableHtml = $tableNoData;
        } else {
            $whereInLp = array_unique($whereInLp);
            $whereInLp = implode(',', $whereInLp);
            $registeredUsersBySession = self::getSessionAddUserCourseFromTrackDefault(
                $startDate,
                $endDate,
                $whereInLp
            );
            $registeredUsersInCourse = self::getUserSubscribedInCourseByDateAndLp($startDate, $endDate, $whereInLp);
            $registeredUsersInLp = self::getCompanyLearnpathSubscription(
                $startDate,
                $endDate,
                $whereInLp
            );
            $registeredGroupsInLp = self::getCompanyLearnpathSubscription(
                $startDate,
                $endDate,
                $whereInLp,
                true
            );
            /* use 'for' to performance */
            for ($i = 0; $i < $totalLpItems; $i++) {
                $lpItem = $cLpItems[$i];
                $lpItemId = $lpItem['lp_item_id'];
                $author = str_replace(';', ',', $lpItem['author']);
                $tempArrayAuthor = explode(',', $author);
                $byCourse = $registeredUsersInLp[$lpItemId] ?? [];
                $byCourseGroups = $registeredGroupsInLp[$lpItemId] ?? [];
                $bySession = $registeredUsersBySession[$lpItemId] ?? [];
                $byUserInCourse = $registeredUsersInCourse[$lpItemId] ?? [];
                if (is_array($tempArrayAuthor)) {
                    $totalAuthors = count($tempArrayAuthor);
                    for ($j = 0; $j < $totalAuthors; $j++) {
                        if (!isset($authorArray[$tempArrayAuthor[$j]])) {
                            $authorArray[$tempArrayAuthor[$j]] = api_get_user_info($tempArrayAuthor[$j]);
                        }
                        $dataSet[$tempArrayAuthor[$j]][$lpItemId] = [
                            'course' => $byCourse,
                            'courseGroups' => $byCourseGroups,
                            'session' => $bySession,
                            'lp_item' => $lpItem,
                            'course_user' => $byUserInCourse,
                        ];
                    }
                } else {
                    if (!isset($authorArray[$author])) {
                        $authorArray[$author] = api_get_user_info($author);
                    }
                    $dataSet[$author][$lpItemId] = [
                        'course' => $byCourse,
                        'courseGroups' => $byCourseGroups,
                        'session' => $bySession,
                        'lp_item' => $lpItem,
                        'course_user' => $byUserInCourse,
                    ];
                }
            }
        }
        if ($csv == false) {
            if (empty($tableHtml)) {
                $table .= "<div class='table-responsive'>
                    <table class='table table-hover table-striped table-bordered data_table'>
                    <thead>
                    <tr>
                    <th class='th-header'>".get_lang('Author')."</th>
                    <th class='th-header'>".get_lang('ContentList')."</th>
                    <th class='th-header'>".get_lang('Tariff')."</th>
                    <th class='th-header'>".get_lang('CountOfSubscribedUsers')."</th>
                    <th class='th-header'>".get_lang('ToInvoice')."</th>
                    <th class='th-header'>".get_lang('StudentList')."</th>
                    </tr>
                    </thead>
                    <tbody>";
                //Icon Constant
                $iconAdd = Display::return_icon('add.png', get_lang('ShowOrHide'), '', ICON_SIZE_SMALL);
                $iconRemove = Display::return_icon('error.png', get_lang('ShowOrHide'), '', ICON_SIZE_SMALL);

                $lastAuthor = '';
                $total = 0;
                foreach ($dataSet as $authorId => $lpItems) {
                    $authorTemp = $authorArray[$authorId];
                    $totalSudent = 0;
                    foreach ($lpItems as $lpItem) {
                        $totalStudents = 0;
                        $itemLp = $lpItem['lp_item'];
                        $title = $itemLp['title'];
                        $price = $itemLp['price'];
                        $byCourse = $lpItem['course'];
                        $byCourseGroups = $lpItem['courseGroups'];
                        $bySession = $lpItem['session'];
                        $byUserInCourse = $lpItem['course_user'];
                        $hide = "class='author_$authorId hidden' ";
                        $tableTemp = '';
                        if ($lastAuthor != $authorTemp) {
                            $table .= "<tr><td>".$authorTemp['complete_name']."</td>";
                        } else {
                            $table .= "<tr $hide ><td></td>";
                        }
                        $table .= "<td>$title</td><td>$price</td>";
                        $studentRegister = count($byCourse);
                        $studentGroupsRegister = count($byCourseGroups);
                        $studentRegisterBySession = count($bySession);
                        $usersInCourseCount = count($byUserInCourse);

                        $hiddenField = 'student_show_'.$index;
                        $hiddenFieldLink = 'student_show_'.$index.'_';
                        if (0 != $studentRegister ||
                            0 != $studentRegisterBySession ||
                            0 != $studentGroupsRegister ||
                            0 != $usersInCourseCount
                        ) {
                            $tableTemp .= "<td>
                                <a href='#!' id='$hiddenFieldLink' onclick='showHideStudent(\"$hiddenField\")'>
                                <div class='icon_add'>$iconAdd</div>
                                <div class='icon_remove hidden'>$iconRemove</div>
                                </a>
                                <div id='$hiddenField' class='hidden'>";
                            $studentProcessed = [];
                            /* Student by course*/
                            $studentProcessed = self::getStudentDataToReportByLpItem($byCourse, $studentProcessed);
                            /* Student by Class*/
                            $studentProcessed = self::getStudentDataToReportByLpItem($byCourseGroups, $studentProcessed, 'class');
                            /* Student by sessions*/
                            $studentProcessed = self::getStudentDataToReportByLpItem($bySession, $studentProcessed);
                            // Students in course*/
                            $studentProcessed = self::getStudentDataToReportByLpItem($byUserInCourse, $studentProcessed);
                            $index++;
                            foreach ($studentProcessed as $lpItemId => $item) {
                                foreach ($item as $type => $student) {
                                    foreach ($student as $userId => $text) {
                                        if ('LearnpathSubscription' == $type) {
                                            $tableTemp .= $text;
                                            $totalStudents++;
                                        } else {
                                            if (!isset($studentProcessed[$lpItemId]['LearnpathSubscription'])) {
                                                $tableTemp .= $text;
                                                $totalStudents++;
                                            }
                                        }
                                    }
                                }
                            }
                            $tableTemp .= "</div></td>";
                        } else {
                            $tableTemp .= "<td></td>";
                        }
                        $table .= "<td>$totalStudents</td>";
                        $invoicing = ($totalStudents * $price);
                        $table .= "<td>$invoicing</td>";
                        $total += $invoicing;
                        $totalSudent += $totalStudents;
                        $table .= $tableTemp."</tr>";
                        $lastAuthor = $authorTemp;
                    }
                    $hiddenFieldLink = 'student__show_'.$index.'_';
                    $index++;
                    $table .= "<tr>
                    <th class='th-header'></th>
                    <th class='th-header'>
                            <a href='#!' id='$hiddenFieldLink' onclick='ShowMoreAuthor(\"$authorId\")'>
                                <div class='icon_add_author_$authorId'>$iconAdd</div>
                                <div class='icon_remove_author_$authorId hidden'>$iconRemove</div>
                            </a>
                        </th>
                    <th class='th-header'></th>
                    <th class='th-header'>$totalSudent</th>
                    <th class='th-header'>$total</th>
                    <th class='th-header'></tr>";
                    $total = 0;
                }
                $table .= "</tbody></table></div>";
                $tableHtml = $table;
            }

            $form = new FormValidator('searchDate', 'get');
            $form->addHidden('display', 'learningPathByItem');
            $today = new DateTime();
            if (empty($startDate)) {
                $startDate = $today->modify('first day of this month')->format('Y-m-d');
            }
            if (empty($endDate)) {
                $endDate = $today->modify('last day of this month')->format('Y-m-d');
            }
            $form->addDatePicker(
                'startDate',
                get_lang('DateStart'),
                [
                    'value' => $startDate,
                ]
            );
            $form->addDatePicker(
                'endDate',
                get_lang('DateEnd'),
                [
                    'value' => $endDate,
                ]
            );
            $form->addButtonSearch(get_lang('Search'));

            if (count($dataSet) != 0) {
                $form->addButton(
                    'export_csv',
                    get_lang('ExportAsCSV'),
                    'check',
                    'primary',
                    null,
                    null,
                    [
                    ]
                );
            }
            $tableContent = $form->returnForm();
            $tableContent .= $tableHtml;
            $tpl = new Template('', false, false, false, false, false, false);
            $tpl->assign('table', $tableContent);
            $templateName = $tpl->get_template('my_space/course_summary.tpl');
            $tpl->display($templateName);
        } else {
            $csv_content = [];
            $csv_row = [];
            $csv_row[] = get_lang('Author');
            $csv_row[] = get_lang('ContentList');
            $csv_row[] = get_lang('Tariff');
            $csv_row[] = get_lang('CountOfSubscribedUsers');
            $csv_row[] = get_lang('ToInvoice');
            $csv_row[] = get_lang('StudentList');
            $csv_content[] = $csv_row;
            $total = 0;
            foreach ($dataSet as $authorId => $lpItems) {
                $authorTemp = $authorArray[$authorId];
                $totalSudent = 0;
                foreach ($lpItems as $lpItem) {
                    $totalStudents = 0;
                    $itemLp = $lpItem['lp_item'];
                    $itemLpId = $itemLp['lp_item_id'];
                    $title = $itemLp['title'];
                    $price = $itemLp['price'];
                    $byCourse = $lpItem['course'];
                    $bySession = $lpItem['session'];
                    $byCourseGroups = $lpItem['courseGroups'];
                    $byUserInCourse = $lpItem['course_user'];

                    $csv_row = [];
                    $csv_row[] = $authorTemp['complete_name'];
                    $csv_row[] = $title;
                    $csv_row[] = $price;

                    $studentRegister = count($byCourse);
                    $studentRegisterBySession = count($bySession);
                    $studentGroupsRegister = count($byCourseGroups);
                    $usersInCourseCount = count($byUserInCourse);

                    $studentsName = '';
                    if (0 != $studentRegister ||
                        0 != $studentRegisterBySession ||
                        0 != $studentGroupsRegister ||
                        0 != $usersInCourseCount
                    ) {
                        $studentProcessed = [];
                        /* Student by course*/
                        $studentProcessed = self::getStudentDataToReportByLpItem($byCourse, $studentProcessed, '', true);
                        /* Student by Class*/
                        $studentProcessed = self::getStudentDataToReportByLpItem($byCourseGroups, $studentProcessed, 'class', true);
                        /* Student by sessions*/
                        $studentProcessed = self::getStudentDataToReportByLpItem($bySession, $studentProcessed, '', true);
                        // Students in course*/
                        $studentProcessed = self::getStudentDataToReportByLpItem($byUserInCourse, $studentProcessed, '', true);

                        $index++;
                        foreach ($studentProcessed as $lpItemId => $item) {
                            foreach ($item as $type => $student) {
                                foreach ($student as $userId => $text) {
                                    if ('LearnpathSubscription' == $type) {
                                        $studentsName .= $text;
                                        $totalStudents++;
                                    } else {
                                        if (!isset($studentProcessed[$lpItemId]['LearnpathSubscription'])) {
                                            $studentsName .= $text;
                                            $totalStudents++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $csv_row[] = $totalStudents;
                    $csv_row[] = $price * $totalStudents;
                    $csv_row[] = trim($studentsName, " / ");
                    $csv_content[] = $csv_row;
                }
            }
            Export::arrayToCsv($csv_content, 'reporting_lp_by_authors');
        }
    }

    public static function getSessionAddUserCourseFromTrackDefault(
        $startDate = null,
        $endDate = null,
        $whereInLp = null
    ) {
        $whereInLp = Database::escape_string($whereInLp);
        $data = [];
        $tblTrackDefault = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $tblSessionRelCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tblLp = Database::get_course_table(TABLE_LP_MAIN);
        $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblAccessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlFilter = '';
        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
            $accessUrlFilter = " INNER JOIN $tblAccessUrlUser auru
                      ON (u.id = auru.user_id AND auru.access_url_id = $urlId)";
        }

        if (!empty($startDate)) {
            $startDate = new DateTime($startDate);
        } else {
            $startDate = new DateTime();
        }
        if (!empty($endDate)) {
            $endDate = new DateTime($endDate);
        } else {
            $endDate = new DateTime();
        }
        if (!empty($startDate) and !empty($endDate)) {
            if ($startDate > $endDate) {
                $dateTemp = $endDate;
                $endDate = $startDate;
                $startDate = $dateTemp;
                unset($dateTemp);
            }
        }
        $startDate = api_get_utc_datetime($startDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
        $endDate = api_get_utc_datetime($endDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
        $extra = '';
        if (!empty($whereInLp)) {
            $extra = " AND lpi.lp_id in ($whereInLp) ";
        }

        $sql = "SELECT DISTINCT
            lp.iid AS lp,
            lpi.iid AS lp_item,
            lpi.iid AS lp_item_id,
            td.default_value AS id,
            srcu.session_id AS session_id,
            u.username AS username,
            td.default_date AS default_date,
            td.default_event_type AS type,
            u.firstname as firstname,
            u.lastname as lastname
        FROM $tblTrackDefault AS td
        INNER JOIN $tblSessionRelCourseUser AS srcu
        ON (td.default_value = srcu.user_id AND td.c_id = srcu.c_id)
        INNER JOIN $tblLp AS lp
        ON (lp.c_id = srcu.c_id)
        INNER JOIN $tblLpItem AS lpi
        ON (
            lpi.c_id = srcu.c_id AND
            lp.id = lpi.lp_id AND
            lpi.c_id = lp.c_id
        )
        INNER JOIN $tblUser AS u
        ON (u.id = srcu.user_id)
        $accessUrlFilter
        WHERE
            td.default_event_type = 'session_add_user_course' AND
            td.default_date >= '$startDate' AND
            td.default_date <= '$endDate'
            $extra
        ORDER BY td.default_value ";
        $queryResult = Database::query($sql);
        $dataTrack = Database::store_result($queryResult, 'ASSOC');
        foreach ($dataTrack as $item) {
            $item['complete_name'] = api_get_person_name($item['firstname'], $item['lastname']);
            $item['company'] = self::getCompanyOfUser($item['id']);
            $data[$item['lp_item_id']][] = $item;
        }

        return $data;
    }

    public static function getUserSubscribedInCourseByDateAndLp(
        $startDate = null,
        $endDate = null,
        $whereInLp = null
    ): array {
        $whereInLp = Database::escape_string($whereInLp);
        $tblTrackDefault = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $tblCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tblLp = Database::get_course_table(TABLE_LP_MAIN);
        $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblAccessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlFilter = '';

        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
            $accessUrlFilter = " INNER JOIN $tblAccessUrlUser auru
                ON (u.id = auru.user_id AND auru.access_url_id = $urlId)";
        }

        $startDate = !empty($startDate) ? new DateTime($startDate) : new DateTime();
        $endDate = !empty($endDate) ? new DateTime($endDate) : new DateTime();

        $startDate = api_get_utc_datetime($startDate->setTime(0, 0)->format('Y-m-d H:i:s'));
        $endDate = api_get_utc_datetime($endDate->setTime(0, 0)->format('Y-m-d H:i:s'));

        $extra = '';

        if (!empty($whereInLp)) {
            $extra = " AND lpi.lp_id in ($whereInLp) ";
        }

        $sql = "SELECT DISTINCT
                lp.iid AS lp,
                lpi.iid AS lp_item,
                lpi.iid AS lp_item_id,
                u.id AS id,
                u.username AS username,
                td.default_date AS default_date,
                td.default_event_type AS type,
                u.firstname as firstname,
                u.lastname as lastname
            FROM $tblTrackDefault AS td
            INNER JOIN $tblCourseRelUser AS cru ON td.c_id = cru.c_id
            INNER JOIN $tblLp AS lp ON lp.c_id = cru.c_id
            INNER JOIN $tblLpItem AS lpi
                ON (lpi.c_id = cru.c_id AND lp.id = lpi.lp_id AND lpi.c_id = lp.c_id)
            INNER JOIN $tblUser AS u ON u.id = cru.user_id
            $accessUrlFilter
            WHERE
                td.default_event_type = '".LOG_SUBSCRIBE_USER_TO_COURSE."'
                AND td.default_date >= '$startDate'
                AND td.default_date <= '$endDate'
                AND td.default_value LIKE CONCAT('%s:2:\\\\\\\\\\\"id\\\\\\\\\";i:', cru.user_id, ';%')
                $extra
            ORDER BY u.id";

        $result = Database::query($sql);

        $data = [];

        while ($item = Database::fetch_assoc($result)) {
            $item['complete_name'] = api_get_person_name($item['firstname'], $item['lastname']);
            $item['company'] = self::getCompanyOfUser($item['id']);

            $data[$item['lp_item_id']][] = $item;
        }

        return $data;
    }

    /**
     * Display a sortable table that contains an overview of all the reporting progress of all courses.
     */
    public static function display_tracking_course_overview()
    {
        $params = ['view' => 'admin', 'display' => 'courseoverview'];
        $table = new SortableTable(
            'tracking_session_overview',
            ['MySpace', 'get_total_number_courses'],
            ['MySpace', 'get_course_data_tracking_overview'],
            1,
            20,
            'ASC',
            null, [
                'class' => 'table table-transparent',
            ]
        );
        $table->additional_parameters = $params;
        $table->set_column_filter(0, ['MySpace', 'course_tracking_filter']);
        $tableContent = $table->return_table();

        $tpl = new Template('', false, false, false, false, false, false);
        $tpl->assign('table', $tableContent);
        $templateName = $tpl->get_template('my_space/course_summary.tpl');
        $tpl->display($templateName);
    }

    /**
     * Get the total number of courses.
     *
     * @return int Total number of courses
     */
    public static function get_total_number_courses()
    {
        return CourseManager::count_courses(api_get_current_access_url_id());
    }

    /**
     * Get data for the courses.
     *
     * @param int    $from        Inferior limit
     * @param int    $numberItems Number of items to select
     * @param string $column      Column to order on
     * @param string $direction   Order direction
     *
     * @return array Results
     */
    public static function get_course_data_tracking_overview(
        $from,
        $numberItems,
        $column,
        $direction
    ) {
        switch ($column) {
            default:
            case 1:
                $column = 'title';
                break;
        }

        $courses = CourseManager::get_courses_list(
            $from,
            $numberItems,
            $column,
            $direction,
             -1,
            '',
            api_get_current_access_url_id()
        );

        $list = [];
        foreach ($courses as $course) {
            $list[] = [
                '0' => $course['code'],
                'col0' => $course['code'],
            ];
        }

        return $list;
    }

    /**
     * Fills in course reporting data.
     *
     * @param int course code
     * @param array $url_params additional url parameters
     * @param array $row        the row information (the other columns)
     *
     * @return string html code
     */
    public static function course_tracking_filter($course_code, $url_params, $row)
    {
        $course_code = $row[0];
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        $tpl = new Template('', false, false, false, false, false, false);
        $data = null;

        // database table definition
        $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

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
            $time_spent += Tracking::get_time_spent_on_the_course(
                $row->user_id,
                $courseInfo['real_id']
            );
            $progress_tmp = Tracking::get_avg_student_progress(
                $row->user_id,
                $course_code,
                [],
                null,
                true
            );

            if ($progress_tmp) {
                $progress += $progress_tmp[0];
                $nb_progress_lp += $progress_tmp[1];
            }
            $score_tmp = Tracking::get_avg_student_score(
                $row->user_id,
                $course_code,
                [],
                null,
                true
            );
            if (is_array($score_tmp)) {
                $score += $score_tmp[0];
                $nb_score_lp += $score_tmp[1];
            }
            $nb_messages += Tracking::count_student_messages(
                $row->user_id,
                $course_code
            );
            $nb_assignments += Tracking::count_student_assignments(
                $row->user_id,
                $course_code
            );
            $last_login_date_tmp = Tracking::get_last_connection_date_on_the_course(
                $row->user_id,
                $courseInfo,
                null,
                false
            );
            if ($last_login_date_tmp != false &&
                $last_login_date == false
            ) { // TODO: To be cleaned
                $last_login_date = $last_login_date_tmp;
            } elseif ($last_login_date_tmp != false && $last_login_date != false) {
                // TODO: Repeated previous condition. To be cleaned.
                // Find the max and assign it to first_login_date
                if (strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                    $last_login_date = $last_login_date_tmp;
                }
            }

            $exercise_results_tmp = self::exercises_results($row->user_id, $course_code);
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
            $last_login_date = api_convert_and_format_date(
                $last_login_date,
                DATE_FORMAT_SHORT,
                date_default_timezone_get()
            );
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

        $data = [
            'course_code' => $course_code,
            'id' => $courseId,
            'image' => $courseInfo['course_image_large'],
            'image_small' => $courseInfo['course_image'],
            'title' => $courseInfo['title'],
            'url' => $courseInfo['course_public_url'],
            'category' => $courseInfo['categoryName'],
            'time_spent' => api_time_to_hms($time_spent),
            'avg_progress' => $avg_progress,
            'avg_score' => $avg_score,
            'number_message' => $nb_messages,
            'number_assignments' => $nb_assignments,
            'total_score' => $total_score,
            'questions_answered' => $total_questions_answered,
            'last_login' => $last_login_date,
        ];

        $tpl->assign('data', $data);
        $layout = $tpl->get_template('my_space/partials/tracking_course_overview.tpl');
        $content = $tpl->fetch($layout);

        return $content;
    }

    /**
     * This function exports the table that we see in display_tracking_course_overview().
     */
    public static function export_tracking_course_overview()
    {
        // database table definition
        $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

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

        $course_data = self::get_course_data_tracking_overview(
            $from,
            1000,
            $orderby,
            $direction
        );

        $csv_content = [];

        // the first line of the csv file with the column headers
        $csv_row = [];
        $csv_row[] = get_lang('Course');
        $csv_row[] = get_lang('AvgTimeSpentInTheCourse');
        $csv_row[] = get_lang('AvgStudentsProgress');
        $csv_row[] = get_lang('AvgCourseScore');
        $csv_row[] = get_lang('TotalNumberOfMessages');
        $csv_row[] = get_lang('TotalNumberOfAssignments');
        $csv_row[] = get_lang('TotalExercisesScoreObtained');
        $csv_row[] = get_lang('TotalExercisesScorePossible');
        $csv_row[] = get_lang('TotalExercisesAnswered');
        $csv_row[] = get_lang('TotalExercisesScorePercentage');
        $csv_row[] = get_lang('LatestLogin');
        $csv_content[] = $csv_row;

        // the other lines (the data)
        foreach ($course_data as $key => $course) {
            $course_code = $course[0];
            $courseInfo = api_get_course_info($course_code);
            $course_title = $courseInfo['title'];
            $courseId = $courseInfo['real_id'];

            $csv_row = [];
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
                $time_spent += Tracking::get_time_spent_on_the_course(
                    $row->user_id,
                    $courseId
                );
                $progress_tmp = Tracking::get_avg_student_progress(
                    $row->user_id,
                    $course_code,
                    [],
                    null,
                    true
                );
                $progress += $progress_tmp[0];
                $nb_progress_lp += $progress_tmp[1];
                $score_tmp = Tracking::get_avg_student_score(
                    $row->user_id,
                    $course_code,
                    [],
                    null,
                    true
                );
                if (is_array($score_tmp)) {
                    $score += $score_tmp[0];
                    $nb_score_lp += $score_tmp[1];
                }
                $nb_messages += Tracking::count_student_messages(
                    $row->user_id,
                    $course_code
                );
                $nb_assignments += Tracking::count_student_assignments(
                    $row->user_id,
                    $course_code
                );

                $last_login_date_tmp = Tracking::get_last_connection_date_on_the_course(
                    $row->user_id,
                    $courseInfo,
                    null,
                    false
                );
                if ($last_login_date_tmp != false && $last_login_date == false) {
                    // TODO: To be cleaned.
                    $last_login_date = $last_login_date_tmp;
                } elseif ($last_login_date_tmp != false && $last_login_date == false) {
                    // TODO: Repeated previous condition. To be cleaned.
                    // Find the max and assign it to first_login_date
                    if (strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                        $last_login_date = $last_login_date_tmp;
                    }
                }

                $exercise_results_tmp = self::exercises_results($row->user_id, $course_code);
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
                $last_login_date = api_convert_and_format_date(
                    $last_login_date,
                    DATE_FORMAT_SHORT,
                    date_default_timezone_get()
                );
            } else {
                $last_login_date = '-';
            }
            if ($total_score_possible > 0) {
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
        Export::arrayToCsv($csv_content, 'reporting_course_overview');
        exit;
    }

    /**
     * Display a sortable table that contains an overview of all the reporting
     * progress of all sessions and all courses the user is subscribed to.
     *
     * @author Guillaume Viguier <guillaume@viguierjust.com>
     */
    public static function display_tracking_session_overview()
    {
        $head = '<table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
        $head .= '<tr>';
        $head .= '<th width="155px" style="border-left:0;border-bottom:0"><span>'.get_lang('Course').'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfMessages'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfAssignments'), 6, true).'</span></th>';
        $head .= '<th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
        $head .= '</tr></table>';

        $params = ['view' => 'admin', 'display' => 'sessionoverview'];
        $table = new SortableTable(
            'tracking_session_overview',
            ['MySpace', 'get_total_number_sessions'],
            ['MySpace', 'get_session_data_tracking_overview'],
            1
        );
        $table->additional_parameters = $params;

        $table->set_header(0, '', false, null, ['style' => 'display: none']);
        $table->set_header(
            1,
            get_lang('Session'),
            true,
            ['style' => 'font-size:8pt'],
            ['style' => 'font-size:8pt']
        );
        $table->set_header(
            2,
            $head,
            false,
            ['style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'],
            ['style' => 'width:90%;padding:0;font-size:7.5pt;']
        );
        $table->set_column_filter(2, ['MySpace', 'session_tracking_filter']);
        $table->display();
    }

    /**
     * Get the total number of sessions.
     *
     * @return int Total number of sessions
     */
    public static function get_total_number_sessions()
    {
        return SessionManager::count_sessions(api_get_current_access_url_id());
    }

    /**
     * Get data for the sessions.
     *
     * @param int    $from        Inferior limit
     * @param int    $numberItems Number of items to select
     * @param string $column      Column to order on
     * @param string $direction   Order direction
     *
     * @return array Results
     */
    public static function get_session_data_tracking_overview(
        $from,
        $numberItems,
        $column,
        $direction
    ) {
        $from = (int) $from;
        $numberItems = (int) $numberItems;
        $direction = Database::escape_string($direction);
        $columnName = 'name';
        if ($column === 1) {
            $columnName = 'id';
        }

        $options = [
            'order' => " $columnName $direction",
            'limit' => " $from,$numberItems",
        ];
        $sessions = SessionManager::formatSessionsAdminForGrid($options);
        $list = [];
        foreach ($sessions as $session) {
            $list[] = [
                '0' => $session['id'],
                'col0' => $session['id'],
                '1' => strip_tags($session['name']),
                'col1' => strip_tags($session['name']),
            ];
        }

        return $list;
    }

    /**
     * Fills in session reporting data.
     *
     * @param int   $session_id the id of the user
     * @param array $url_params additonal url parameters
     * @param array $row        the row information (the other columns)
     *
     * @return string html code
     */
    public static function session_tracking_filter($session_id, $url_params, $row)
    {
        $session_id = $row[0];
        // the table header
        $return = '<table class="table table-hover table-striped data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';

        // database table definition
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

        // getting all the courses of the user
        $sql = "SELECT * FROM $tbl_course AS c
                INNER JOIN $tbl_session_rel_course AS sc
                ON sc.c_id = c.id
                WHERE sc.session_id = '".$session_id."'";
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
                $progress_tmp = Tracking::get_avg_student_progress($row_user->user_id, $row->code, [], $session_id, true);
                $progress += $progress_tmp[0];
                $nb_progress_lp += $progress_tmp[1];
                $score_tmp = Tracking::get_avg_student_score($row_user->user_id, $row->code, [], $session_id, true);
                if (is_array($score_tmp)) {
                    $score += $score_tmp[0];
                    $nb_score_lp += $score_tmp[1];
                }
                $nb_messages += Tracking::count_student_messages($row_user->user_id, $row->code, $session_id);
                $nb_assignments += Tracking::count_student_assignments($row_user->user_id, $row->code, $session_id);
                $last_login_date_tmp = Tracking::get_last_connection_date_on_the_course(
                    $row_user->user_id,
                    $courseInfo,
                    $session_id,
                    false
                );
                if ($last_login_date_tmp != false && $last_login_date == false) {
                    // TODO: To be cleaned.
                    $last_login_date = $last_login_date_tmp;
                } elseif ($last_login_date_tmp != false && $last_login_date != false) {
                    // TODO: Repeated previous condition! To be cleaned.
                    // Find the max and assign it to first_login_date
                    if (strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                        $last_login_date = $last_login_date_tmp;
                    }
                }

                $exercise_results_tmp = self::exercises_results($row_user->user_id, $row->code, $session_id);
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
                $last_login_date = api_convert_and_format_date(
                    $last_login_date,
                    DATE_FORMAT_SHORT,
                    date_default_timezone_get()
                );
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
     * This function exports the table that we see in display_tracking_session_overview().
     */
    public static function export_tracking_session_overview()
    {
        // database table definition
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

        // the values of the sortable table
        $from = 0;
        if ($_GET['tracking_session_overview_page_nr']) {
            $from = $_GET['tracking_session_overview_page_nr'];
        }

        $orderby = 0;
        if ($_GET['tracking_session_overview_column']) {
            $orderby = $_GET['tracking_session_overview_column'];
        }

        $direction = 'ASC';
        if ($_GET['tracking_session_overview_direction']) {
            $direction = $_GET['tracking_session_overview_direction'];
        }

        $session_data = self::get_session_data_tracking_overview($from, 1000, $orderby, $direction);

        $csv_content = [];

        // the first line of the csv file with the column headers
        $csv_row = [];
        $csv_row[] = get_lang('Session');
        $csv_row[] = get_lang('Course');
        $csv_row[] = get_lang('AvgTimeSpentInTheCourse');
        $csv_row[] = get_lang('AvgStudentsProgress');
        $csv_row[] = get_lang('AvgCourseScore');
        $csv_row[] = get_lang('TotalNumberOfMessages');
        $csv_row[] = get_lang('TotalNumberOfAssignments');
        $csv_row[] = get_lang('TotalExercisesScoreObtained');
        $csv_row[] = get_lang('TotalExercisesScorePossible');
        $csv_row[] = get_lang('TotalExercisesAnswered');
        $csv_row[] = get_lang('TotalExercisesScorePercentage');
        $csv_row[] = get_lang('LatestLogin');
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
                $csv_row = [];
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
                while ($row_user = Database::fetch_object($result_users)) {
                    // get time spent in the course and session
                    $time_spent += Tracking::get_time_spent_on_the_course($row_user->user_id, $courseId, $session_id);
                    $progress_tmp = Tracking::get_avg_student_progress(
                        $row_user->user_id,
                        $row->code,
                        [],
                        $session_id,
                        true
                    );
                    $progress += $progress_tmp[0];
                    $nb_progress_lp += $progress_tmp[1];
                    $score_tmp = Tracking::get_avg_student_score(
                        $row_user->user_id,
                        $row->code,
                        [],
                        $session_id,
                        true
                    );
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

                    $last_login_date_tmp = Tracking::get_last_connection_date_on_the_course(
                        $row_user->user_id,
                        $courseInfo,
                        $session_id,
                        false
                    );
                    if ($last_login_date_tmp != false && $last_login_date == false) {
                        // TODO: To be cleaned.
                        $last_login_date = $last_login_date_tmp;
                    } elseif ($last_login_date_tmp != false && $last_login_date == false) {
                        // TODO: Repeated previous condition. To be cleaned.
                        // Find the max and assign it to first_login_date
                        if (strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
                            $last_login_date = $last_login_date_tmp;
                        }
                    }

                    $exercise_results_tmp = self::exercises_results($row_user->user_id, $row->code, $session_id);
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
                    $last_login_date = api_convert_and_format_date(
                        $last_login_date,
                        DATE_FORMAT_SHORT,
                        date_default_timezone_get()
                    );
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
        Export::arrayToCsv($csv_content, 'reporting_session_overview');
        exit;
    }

    /**
     * Get general information about the exercise performance of the user
     * the total obtained score (all the score on all the questions)
     * the maximum score that could be obtained
     * the number of questions answered
     * the success percentage.
     *
     * @param int    $user_id     the id of the user
     * @param string $course_code the course code
     * @param int    $session_id
     *
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     *
     * @version Dokeos 1.8.6
     *
     * @since November 2008
     */
    public static function exercises_results($user_id, $course_code, $session_id = 0)
    {
        $user_id = (int) $user_id;
        $courseId = api_get_course_int_id($course_code);
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sql = "SELECT exe_result, exe_weighting
                FROM $table
                WHERE
                    c_id = $courseId AND
                    exe_user_id = $user_id";

        $session_id = (int) $session_id;
        if (!empty($session_id)) {
            $sql .= " AND session_id = '".$session_id."' ";
        }
        $result = Database::query($sql);
        $score_obtained = 0;
        $score_possible = 0;
        $questions_answered = 0;
        while ($row = Database::fetch_array($result)) {
            $score_obtained += $row['exe_result'];
            $score_possible += $row['exe_weighting'];
            $questions_answered++;
        }

        $percentage = null;
        if ($score_possible != 0) {
            $percentage = round(($score_obtained / $score_possible * 100), 2);
        }

        return [
            'score_obtained' => $score_obtained,
            'score_possible' => $score_possible,
            'questions_answered' => $questions_answered,
            'percentage' => $percentage,
        ];
    }

    /**
     * This function exports the table that we see in display_tracking_user_overview().
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     *
     * @version Dokeos 1.8.6
     *
     * @since October 2008
     */
    public static function export_tracking_user_overview()
    {
        // database table definitions
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);

        // the values of the sortable table
        if ($_GET['tracking_user_overview_page_nr']) {
            $from = (int) $_GET['tracking_user_overview_page_nr'];
        } else {
            $from = 0;
        }
        if ($_GET['tracking_user_overview_column']) {
            $orderby = (int) $_GET['tracking_user_overview_column'];
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

        $user_data = self::get_user_data_tracking_overview(
            $from,
            1000,
            $orderby,
            $direction
        );

        // the first line of the csv file with the column headers
        $csv_row = [];
        $csv_row[] = get_lang('OfficialCode');
        if ($is_western_name_order) {
            $csv_row[] = get_lang('FirstName');
            $csv_row[] = get_lang('LastName');
        } else {
            $csv_row[] = get_lang('LastName');
            $csv_row[] = get_lang('FirstName');
        }
        $csv_row[] = get_lang('LoginName');
        $csv_row[] = get_lang('CourseCode');

        // the additional user defined fields (only those that were selected to be exported)
        $fields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

        $additionalExportFields = Session::read('additional_export_fields');

        if (is_array($additionalExportFields)) {
            foreach ($additionalExportFields as $key => $extra_field_export) {
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

                $csv_row = [];
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
                $extra_fields = self::get_user_overview_export_extra_fields($user[4]);

                if (is_array($field_names_to_be_exported)) {
                    foreach ($field_names_to_be_exported as $key => $extra_field_export) {
                        $csv_row[] = $extra_fields[$extra_field_export];
                    }
                }
                // time spent in the course
                $csv_row[] = api_time_to_hms(Tracking::get_time_spent_on_the_course($user[4], $courseId));
                // student progress in course
                $csv_row[] = round(Tracking::get_avg_student_progress($user[4], $row[0]), 2);
                // student score
                $csv_row[] = round(Tracking::get_avg_student_score($user[4], $row[0]), 2);
                // student tes score
                $csv_row[] = round(Tracking::get_avg_student_exercise_score($user[4], $row[0]), 2);
                // student messages
                $csv_row[] = Tracking::count_student_messages($user[4], $row[0]);
                // student assignments
                $csv_row[] = Tracking::count_student_assignments($user[4], $row[0]);
                // student exercises results
                $exercises_results = self::exercises_results($user[4], $row[0]);
                $csv_row[] = $exercises_results['score_obtained'];
                $csv_row[] = $exercises_results['score_possible'];
                $csv_row[] = $exercises_results['questions_answered'];
                $csv_row[] = $exercises_results['percentage'];
                // first connection
                $csv_row[] = Tracking::get_first_connection_date_on_the_course($user[4], $courseId);
                // last connection
                $csv_row[] = strip_tags(Tracking::get_last_connection_date_on_the_course($user[4], $courseInfo));

                $csv_content[] = $csv_row;
            }
        }
        Export::arrayToCsv($csv_content, 'reporting_user_overview');
        exit;
    }

    /**
     * Get data for courses list in sortable with pagination.
     *
     * @return array
     */
    public static function get_course_data($from, $number_of_items, $column, $direction)
    {
        global $courses, $csv_content, $charset, $session_id;

        // definition database tables
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $course_data = [];
        $courses_code = array_keys($courses);

        foreach ($courses_code as &$code) {
            $code = "'$code'";
        }

        // get all courses with limit
        $sql = "SELECT course.code as col1, course.title as col2
                FROM $tbl_course course
                WHERE course.code IN (".implode(',', $courses_code).")";

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

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
            $users = [];
            while ($row = Database::fetch_array($rs)) {
                $users[] = $row['user_id'];
            }

            if (count($users) > 0) {
                $nb_students_in_course = count($users);
                $avg_assignments_in_course = Tracking::count_student_assignments($users, $course_code, $session_id);
                $avg_messages_in_course = Tracking::count_student_messages($users, $course_code, $session_id);
                $avg_progress_in_course = Tracking::get_avg_student_progress($users, $course_code, [], $session_id);
                $avg_score_in_course = Tracking::get_avg_student_score($users, $course_code, [], $session_id);
                $avg_score_in_exercise = Tracking::get_avg_student_exercise_score($users, $course_code, 0, $session_id);
                $avg_time_spent_in_course = Tracking::get_time_spent_on_the_course(
                    $users,
                    $courseInfo['real_id'],
                    $session_id
                );

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
            $table_row = [];
            $table_row[] = $row_course[1];
            $table_row[] = $nb_students_in_course;
            $table_row[] = $avg_time_spent_in_course;
            $table_row[] = is_null($avg_progress_in_course) ? '' : $avg_progress_in_course.'%';
            $table_row[] = is_null($avg_score_in_course) ? '' : $avg_score_in_course.'%';
            $table_row[] = is_null($avg_score_in_exercise) ? '' : $avg_score_in_exercise.'%';
            $table_row[] = $avg_messages_in_course;
            $table_row[] = $avg_assignments_in_course;

            //set the "from" value to know if I access the Reporting by the chamilo tab or the course link
            $table_row[] = '<center><a href="../../tracking/courseLog.php?cidReq='.$course_code.'&from=myspace&id_session='.$session_id.'">
                             '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                             </a>
                            </center>';

            $scoreInCourse = null;
            if (null !== $avg_score_in_course) {
                if (is_numeric($avg_score_in_course)) {
                    $scoreInCourse = $avg_score_in_course.'%';
                } else {
                    $scoreInCourse = $avg_score_in_course;
                }
            }

            $csv_content[] = [
                api_html_entity_decode($row_course[1], ENT_QUOTES, $charset),
                $nb_students_in_course,
                $avg_time_spent_in_course,
                is_null($avg_progress_in_course) ? null : $avg_progress_in_course.'%',
                $scoreInCourse,
                is_null($avg_score_in_exercise) ? null : $avg_score_in_exercise.'%',
                $avg_messages_in_course,
                $avg_assignments_in_course,
            ];
            $course_data[] = $table_row;
        }

        return $course_data;
    }

    /**
     * Get the number of users of the platform.
     *
     * @return int
     */
    public static function get_number_of_users_tracking_overview()
    {
        return UserManager::get_number_of_users(0, api_get_current_access_url_id());
    }

    /**
     * Get all the data for the sortable table of the reporting progress of
     * all users and all the courses the user is subscribed to.
     *
     * @return array
     */
    public static function get_user_data_tracking_overview(int $from, int $numberItems, int $column, string $direction)
    {
        $isWestern = api_is_western_name_order();
        if ($direction !== 'ASC' && $direction != 'DESC') {
            $direction = 'ASC';
        }

        switch ($column) {
            case '0':
                $column = $isWestern ? 'firstname' : 'lastname';
                break;
        }

        $order = [
            " `$column` $direction",
        ];
        $userList = UserManager::get_user_list([], $order, $from, $numberItems);
        $return = [];
        foreach ($userList as $user) {
            $return[] = [
                '0' => $user['user_id'],
                'col0' => $user['user_id'],
            ];
        }

        return $return;
    }

    /**
     * Get all information that the user with user_id = $user_data has
     * entered in the additionally defined profile fields.
     *
     * @param int $user_id the id of the user
     *
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     *
     * @version Dokeos 1.8.6
     *
     * @since November 2008
     */
    public static function get_user_overview_export_extra_fields($user_id)
    {
        // include the user manager
        $data = UserManager::get_extra_user_data($user_id, true);

        return $data;
    }

    /**
     * Checks if a username exist in the DB otherwise it create a "double"
     * i.e. if we look into for jmontoya but the user's name already exist we create the user jmontoya2
     * the return array will be array(username=>'jmontoya', sufix='2').
     *
     * @param string firstname
     * @param string lastname
     * @param string username
     *
     * @return array with the username, the sufix
     *
     * @author Julio Montoya
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
                    $lastname
                );
                if (UserManager::is_username_available($desired_username.$sufix)) {
                    break;
                } else {
                    $i++;
                }
            }
            $username_array = ['username' => $desired_username, 'sufix' => $sufix];

            return $username_array;
        } else {
            $username_array = ['username' => $username, 'sufix' => ''];

            return $username_array;
        }
    }

    /**
     * Checks if there are repeted users in a given array.
     *
     * @param array $usernames  list of the usernames in the uploaded file
     * @param array $user_array $user_array['username'] and $user_array['sufix']
     *                          where suffix is the number part in a login i.e -> jmontoya2
     *
     * @return array with the $usernames array and the $user_array array
     *
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
        $result_array = [$usernames, $user_array];

        return $result_array;
    }

    /**
     * Checks whether a username has been already subscribed in a session.
     *
     * @param string $username    a given username
     * @param array  $course_list the array with the course list id
     * @param int    $id_session  the session id
     *
     * @return int 0 if the user is not subscribed otherwise it returns the user_id of the given username
     *
     * @author Julio Montoya
     */
    public static function user_available_in_session($username, $course_list, $id_session)
    {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $id_session = (int) $id_session;
        $username = Database::escape_string($username);
        foreach ($course_list as $courseId) {
            $courseId = (int) $courseId;
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
     *
     * @param $users list of users
     *
     * @return array
     *
     * @author Julio Montoya Armas
     */
    public static function check_all_usernames($users, $course_list, $id_session)
    {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $usernames = [];
        $new_users = [];
        foreach ($users as $index => $user) {
            $desired_username = [];
            if (empty($user['UserName'])) {
                $desired_username = self::make_username($user['FirstName'], $user['LastName'], '');
                $pre_username = $desired_username['username'].$desired_username['sufix'];
                $user['UserName'] = $pre_username;
                $user['create'] = '1';
            } else {
                if (UserManager::is_username_available($user['UserName'])) {
                    $desired_username = self::make_username($user['FirstName'], $user['LastName'], $user['UserName']);
                    $user['UserName'] = $desired_username['username'].$desired_username['sufix'];
                    $user['create'] = '1';
                } else {
                    $is_session_avail = self::user_available_in_session($user['UserName'], $course_list, $id_session);
                    if (0 == $is_session_avail) {
                        $user_name = $user['UserName'];
                        $sql_select = "SELECT user_id FROM $table_user WHERE username ='$user_name' ";
                        $rs = Database::query($sql_select);
                        $user['create'] = Database::result($rs, 0, 0);
                    } else {
                        $user['create'] = $is_session_avail;
                    }
                }
            }
            // Usernames is the current list of users in the file.
            $result_array = self::check_user_in_array($usernames, $desired_username);
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
     *
     * @param array $users
     *
     * @return array
     *
     * @author Julio Montoya Armas
     */
    public static function get_user_creator($users)
    {
        $errors = [];
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        foreach ($users as $index => $user) {
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
     *
     * @param array $users list of users
     */
    public static function validate_data($users, $id_session = null)
    {
        $errors = [];
        $new_users = [];
        foreach ($users as $index => $user) {
            // 1. Check whether mandatory fields are set.
            $mandatory_fields = ['LastName', 'FirstName'];
            if (api_get_setting('registration', 'email') == 'true') {
                $mandatory_fields[] = 'Email';
            }

            foreach ($mandatory_fields as $key => $field) {
                if (!isset($user[$field]) || strlen($user[$field]) == 0) {
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
        $results = ['errors' => $errors, 'users' => $new_users];

        return $results;
    }

    /**
     * Adds missing user-information (which isn't required, like password, etc).
     */
    public static function complete_missing_data($user)
    {
        // 1. Generate a password if it is necessary.
        if (!isset($user['Password']) || strlen($user['Password']) == 0) {
            $user['Password'] = api_generate_password();
        }

        return $user;
    }

    /**
     * Saves imported data.
     */
    public static function save_data($users, $course_list, $id_session)
    {
        $id_session = (int) $id_session;
        $sendMail = $_POST['sendMail'] ? 1 : 0;

        // Adding users to the platform.
        $new_users = [];
        foreach ($users as $index => $user) {
            $user = self::complete_missing_data($user);
            // coach only will registered users
            $default_status = STUDENT;
            if ($user['create'] == COURSEMANAGER) {
                $user['id'] = UserManager::create_user(
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
        SessionManager::insertUsersInCourses(
            array_column($users, 'id'),
            $course_list,
            $id_session
        );

        array_walk(
            $users,
            function (array &$user) {
                $user['added_at_session'] = 1;
            }
        );

        $registered_users = get_lang('FileImported').'<br /> Import file results : <br />';
        // Sending emails.
        $addedto = '';
        if ($sendMail) {
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

                $emailbody = nl2br($emailbody);
                MessageManager::send_message_simple($user['id'], $emailsubject, $emailbody, 0, false, false, [], false);

                $userInfo = api_get_user_info($user['id']);

                if (($user['added_at_platform'] == 1 && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
                    if ($user['added_at_platform'] == 1) {
                        $addedto = get_lang('UserCreatedPlatform');
                    } else {
                        $addedto = '          ';
                    }

                    if ($user['added_at_session'] == 1) {
                        $addedto .= get_lang('UserInSession');
                    }
                } else {
                    $addedto = get_lang('UserNotAdded');
                }

                $registered_users .= UserManager::getUserProfileLink($userInfo).' - '.$addedto.'<br />';
            }
        } else {
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
                $registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".
                    Security::remove_XSS($userInfo['complete_user_name'])."</a> - ".$addedto.'<br />';
            }
        }
        Display::addFlash(Display::return_message($registered_users, 'normal', false));
        header('Location: course.php?id_session='.$id_session);
        exit;
    }

    /**
     * Reads CSV-file.
     *
     * @param string $file Path to the CSV-file
     *
     * @return array All userinformation read from the file
     */
    public function parse_csv_data($file)
    {
        $users = Import::csvToArray($file);
        foreach ($users as $index => $user) {
            if (isset($user['Courses'])) {
                $user['Courses'] = explode('|', trim($user['Courses']));
            }
            $users[$index] = $user;
        }

        return $users;
    }

    /**
     * Reads XML-file.
     *
     * @param string $file Path to the XML-file
     *
     * @return array All userinformation read from the file
     */
    public static function parse_xml_data($file)
    {
        $crawler = Import::xml($file);
        $crawler = $crawler->filter('Contacts > Contact ');
        $array = [];
        foreach ($crawler as $domElement) {
            $row = [];
            foreach ($domElement->childNodes as $node) {
                if ($node->nodeName != '#text') {
                    $row[$node->nodeName] = $node->nodeValue;
                }
            }
            if (!empty($row)) {
                $array[] = $row;
            }
        }

        return $array;
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     * @param int $studentId
     */
    public static function displayTrackingAccessOverView(
        $courseId,
        $sessionId,
        $studentId,
        $perPage = 20,
        $dates = null
    ) {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $studentId = (int) $studentId;

        $courseList = [];
        $sessionList = [];
        $studentList = [];

        if (!empty($courseId)) {
            $course = api_get_course_entity($courseId);
            if ($course) {
                $courseList[$course->getId()] = $course->getTitle();
            }
        }

        if (!empty($sessionId)) {
            $session = api_get_session_entity($sessionId);
            if ($session) {
                $sessionList[$session->getId()] = $session->getName();
            }
        }

        if (!empty($studentId)) {
            $student = api_get_user_entity($studentId);
            if ($student) {
                $studentList[$student->getId()] = UserManager::formatUserFullName($student);
            }
        }

        $form = new FormValidator('access_overview', 'GET');
        $form->addElement(
            'select_ajax',
            'course_id',
            get_lang('SearchCourse'),
            $courseList,
            [
                'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?'.http_build_query(
                    [
                        'a' => 'search_course_by_session_all',
                        'session_id' => $sessionId,
                        'course_id' => $courseId,
                    ]
                ),
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
                            course_id: $('#access_overview_course_id').val() || 0
                        });

                        return '".api_get_path(WEB_AJAX_PATH)."session.ajax.php?' + params;
                    }
                ",
            ]
        );

        $form->addSelect(
            'profile',
            get_lang('Profile'),
            [
                '' => get_lang('Select'),
                STUDENT => get_lang('Student'),
                COURSEMANAGER => get_lang('CourseManager'),
                DRH => get_lang('Drh'),
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
                            session_id: $('#access_overview_session_id').val(),
                            course_id: $('#access_overview_course_id').val()
                        });

                        return '".api_get_path(WEB_AJAX_PATH)."course.ajax.php?' + params;
                    }
                ",
            ]
        );

        $form->addDateRangePicker(
            'date',
            get_lang('DateRange'),
            true,
            [
                'id' => 'date_range',
                'format' => 'YYYY-MM-DD HH:mm',
                'timePicker' => 'true',
                //'validate_format' => 'Y-m-d',
            ]
        );

        $form->addHidden('display', 'accessoverview');
        $form->addRule('course_id', get_lang('Required'), 'required');
        $form->addRule('profile', get_lang('Required'), 'required');
        $form->addButton('submit', get_lang('Generate'), 'gear', 'primary');

        $table = null;
        if (!empty($dates)) {
            //if ($form->validate()) {
            $table = new SortableTable(
                'tracking_access_overview',
                ['MySpace', 'getNumberOfTrackAccessOverview'],
                ['MySpace', 'getUserDataAccessTrackingOverview'],
                0,
                $perPage
            );
            $table->set_additional_parameters(
                [
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                    'student_id' => $studentId,
                    'date' => $dates,
                    'tracking_access_overview_per_page' => $perPage,
                    'display' => 'accessoverview',
                ]
            );
            $table->set_header(0, get_lang('LoginDate'), true);
            $table->set_header(1, get_lang('Username'), true);
            if (api_is_western_name_order()) {
                $table->set_header(2, get_lang('FirstName'), true);
                $table->set_header(3, get_lang('LastName'), true);
            } else {
                $table->set_header(2, get_lang('LastName'), true);
                $table->set_header(3, get_lang('FirstName'), true);
            }
            //$table->set_header(4, get_lang('Clicks'), false);
            $table->set_header(4, get_lang('IP'), false);
            $table->set_header(5, get_lang('TimeLoggedIn'), false);
        }

        $template = new Template(
            null,
            false,
            false,
            false,
            false,
            false,
            false
        );
        $template->assign('form', $form->returnForm());
        $template->assign('table', $table ? $table->return_table() : null);

        echo $template->fetch(
            $template->get_template('my_space/accessoverview.tpl')
        );
    }

    /**
     * @return int
     */
    public static function getNumberOfTrackAccessOverview()
    {
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $course = Database::get_main_table(TABLE_MAIN_COURSE);
        $trackCourseAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        $sql = "SELECT COUNT(course_access_id) count
                FROM $trackCourseAccess a
                INNER JOIN $user u
                ON a.user_id = u.id
                INNER JOIN $course c
                ON a.c_id = c.id
                ";
        $sql = self::getDataAccessTrackingFilters($sql);

        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if ($row) {
            return $row['count'];
        }

        return 0;
    }

    /**
     * @param $from
     * @param $numberItems
     * @param $column
     * @param $orderDirection
     *
     * @return array
     */
    public static function getUserDataAccessTrackingOverview(
        $from,
        $numberItems,
        $column,
        $orderDirection
    ) {
        $from = (int) $from;
        $numberItems = (int) $numberItems;
        $column = (int) $column;
        $orderDirection = Database::escape_string($orderDirection);
        $orderDirection = !in_array(strtolower(trim($orderDirection)), ['asc', 'desc']) ? 'asc' : $orderDirection;

        $user = Database::get_main_table(TABLE_MAIN_USER);
        $course = Database::get_main_table(TABLE_MAIN_COURSE);
        $track_e_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $trackCourseAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        global $export_csv;
        $is_western_name_order = api_is_western_name_order();
        if ($export_csv) {
            $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
        }

        //TODO add course name
        $sql = "SELECT
                a.login_course_date as col0,
                u.username as col1,
                ".(
                    $is_western_name_order ? "
                        u.firstname AS col2,
                        u.lastname AS col3,
                    " : "
                        u.lastname AS col2,
                        u.firstname AS col3,
                "
        )."
                a.login_course_date,
                a.logout_course_date,
                c.title,
                c.code,
                u.id as user_id,
                user_ip
            FROM $trackCourseAccess a
            INNER JOIN $user u
            ON a.user_id = u.id
            INNER JOIN $course c
            ON a.c_id = c.id
            WHERE 1=1 ";

        $sql = self::getDataAccessTrackingFilters($sql);

        $sql .= " ORDER BY col$column $orderDirection ";
        $sql .= " LIMIT $from, $numberItems";

        $result = Database::query($sql);

        $data = [];
        while ($user = Database::fetch_assoc($result)) {
            $data[] = $user;
        }

        $return = [];
        //TODO: Dont use numeric index
        foreach ($data as $key => $info) {
            $return[] = [
                api_get_local_time($info['login_course_date']),
                $info['col1'],
                $info['col2'],
                $info['col3'],
                $info['user_ip'],
                gmdate('H:i:s', strtotime($info['logout_course_date']) - strtotime($info['login_course_date'])),
            ];
        }

        return $return;
    }

    /**
     * Gets the connections to a course as an array of login and logout time.
     *
     * @param int    $user_id
     * @param array  $course_info
     * @param int    $sessionId
     * @param string $start_date
     * @param string $end_date
     * @param bool   $addUserIp
     *
     * @author  Jorge Frisancho Jibaja
     * @author  Julio Montoya <gugli100@gmail.com> fixing the function
     *
     * @version OCT-22- 2010
     *
     * @return array
     */
    public static function get_connections_to_course_by_date(
        $user_id,
        $course_info,
        $sessionId,
        $start_date,
        $end_date,
        $addUserIp = false
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $user_id = (int) $user_id;
        $connections = [];
        if (!empty($course_info)) {
            $courseId = (int) $course_info['real_id'];
            $end_date = self::add_day_to($end_date);

            $start_date = Database::escape_string($start_date);
            $end_date = Database::escape_string($end_date);
            $sessionCondition = api_get_session_condition($sessionId);
            $sql = "SELECT
                        login_course_date,
                        logout_course_date,
                        TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date) duration,
                        user_ip
                    FROM $table
                    WHERE
                        user_id = $user_id AND
                        c_id = $courseId AND
                        login_course_date BETWEEN '$start_date' AND '$end_date' AND
                        logout_course_date BETWEEN '$start_date' AND '$end_date'
                        $sessionCondition
                    ORDER BY login_course_date ASC";
            $rs = Database::query($sql);

            while ($row = Database::fetch_array($rs)) {
                $item = [
                    'login' => $row['login_course_date'],
                    'logout' => $row['logout_course_date'],
                    'duration' => $row['duration'],
                ];
                if ($addUserIp) {
                    $item['user_ip'] = $row['user_ip'];
                }
                $connections[] = $item;
            }
        }

        return $connections;
    }

    /**
     * @param int   $user_id
     * @param array $course_info
     * @param int   $sessionId
     * @param null  $start_date
     * @param null  $end_date
     *
     * @return array
     */
    public static function getStats($user_id, $course_info, $sessionId, $start_date = null, $end_date = null)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $result = [];
        if (!empty($course_info)) {
            $stringStartDate = '';
            $stringEndDate = '';
            if ($start_date != null && $end_date != null) {
                $end_date = self::add_day_to($end_date);

                $start_date = Database::escape_string($start_date);
                $end_date = Database::escape_string($end_date);

                $stringStartDate = "AND login_course_date BETWEEN '$start_date' AND '$end_date'";
                $stringEndDate = "AND logout_course_date BETWEEN '$start_date' AND '$end_date'";
            }
            $user_id = (int) $user_id;
            $courseId = (int) $course_info['real_id'];
            $sessionCondition = api_get_session_condition($sessionId);
            $sql = "SELECT
                SEC_TO_TIME(AVG(time_to_sec(timediff(logout_course_date,login_course_date)))) as avrg,
                SEC_TO_TIME(SUM(time_to_sec(timediff(logout_course_date,login_course_date)))) as total,
                count(user_id) as times
                FROM $table
                WHERE
                    user_id = $user_id AND
                    c_id = $courseId $stringStartDate $stringEndDate
                    $sessionCondition
                ORDER BY login_course_date ASC";

            $rs = Database::query($sql);
            if ($row = Database::fetch_array($rs)) {
                $foo_avg = $row['avrg'];
                $foo_total = $row['total'];
                $foo_times = $row['times'];
                $result = [
                    'avg' => $foo_avg,
                    'total' => $foo_total,
                    'times' => $foo_times,
                ];
            }
        }

        return $result;
    }

    public static function add_day_to($end_date)
    {
        $foo_date = strtotime($end_date);
        $foo_date = strtotime(' +1 day', $foo_date);
        $foo_date = date('Y-m-d', $foo_date);

        return $foo_date;
    }

    /**
     * This function draw the graphic to be displayed on the user view as an image.
     *
     * @param array  $sql_result
     * @param string $start_date
     * @param string $end_date
     * @param string $type
     *
     * @author Jorge Frisancho Jibaja
     *
     * @version OCT-22- 2010
     *
     * @return string
     */
    public static function grapher($sql_result, $start_date, $end_date, $type = '')
    {
        if (empty($start_date)) {
            $start_date = '';
        }
        if (empty($end_date)) {
            $end_date = '';
        }
        if ('' == $type) {
            $type = 'day';
        }
        $main_year = $main_month_year = $main_day = [];

        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            new DateTime($end_date)
        );

        foreach ($period as $date) {
            $main_day[$date->format('d-m-Y')] = 0;
        }

        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1M'),
            new DateTime($end_date)
        );

        foreach ($period as $date) {
            $main_month_year[$date->format('m-Y')] = 0;
        }

        $i = 0;
        if (is_array($sql_result) && count($sql_result) > 0) {
            foreach ($sql_result as $key => $data) {
                $login = api_strtotime($data['login']);
                $logout = api_strtotime($data['logout']);
                //creating the main array
                if (isset($main_month_year[date('m-Y', $login)])) {
                    $main_month_year[date('m-Y', $login)] += (float) ($logout - $login) / 60;
                }
                if (isset($main_day[date('d-m-Y', $login)])) {
                    $main_day[date('d-m-Y', $login)] += (float) ($logout - $login) / 60;
                }
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

            $labels = array_keys($main_date);
            if (1 == count($main_date)) {
                $labels = $labels[0];
                $main_date = $main_date[$labels];
            }

            /* Create and populate the pData object */
            $myData = new pData();
            $myData->addPoints($main_date, 'Serie1');
            if (count($main_date) != 1) {
                $myData->addPoints($labels, 'Labels');
                $myData->setSerieDescription('Labels', 'Months');
                $myData->setAbscissa('Labels');
            }
            $myData->setSerieWeight('Serie1', 1);
            $myData->setSerieDescription('Serie1', get_lang('MyResults'));
            $myData->setAxisName(0, get_lang('Minutes'));
            $myData->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

            // Cache definition
            $cachePath = api_get_path(SYS_ARCHIVE_PATH);
            $myCache = new pCache(['CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)]);
            $chartHash = $myCache->getHash($myData);

            if ($myCache->isInCache($chartHash)) {
                //if we already created the img
                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
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
                $settings = ["R" => 255, "G" => 255, "B" => 255];
                $myPicture->drawFilledRectangle(0, 0, $mainWidth, $mainHeight, $settings);

                /* Add a border to the picture */
                $myPicture->drawRectangle(
                    0,
                    0,
                    $mainWidth - 1,
                    $mainHeight - 1,
                    ["R" => 0, "G" => 0, "B" => 0]
                );

                /* Set the default font */
                $myPicture->setFontProperties(
                    [
                        "FontName" => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                        "FontSize" => 10, ]
                );
                /* Write the chart title */
                $myPicture->drawText(
                    $mainWidth / 2,
                    30,
                    get_lang('TimeSpentInTheCourse'),
                    [
                        "FontSize" => 12,
                        "Align" => TEXT_ALIGN_BOTTOMMIDDLE,
                    ]
                );

                /* Set the default font */
                $myPicture->setFontProperties(
                    [
                        "FontName" => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                        "FontSize" => 8,
                    ]
                );

                /* Define the chart area */
                $myPicture->setGraphArea(50, 40, $mainWidth - 40, $mainHeight - 80);

                /* Draw the scale */
                $scaleSettings = [
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
                ];
                $myPicture->drawScale($scaleSettings);

                /* Turn on Antialiasing */
                $myPicture->Antialias = true;

                /* Enable shadow computing */
                $myPicture->setShadow(
                    true,
                    [
                        "X" => 1,
                        "Y" => 1,
                        "R" => 0,
                        "G" => 0,
                        "B" => 0,
                        "Alpha" => 10,
                    ]
                );

                /* Draw the line chart */
                $myPicture->setFontProperties(
                    [
                        "FontName" => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                        "FontSize" => 10,
                    ]
                );
                $myPicture->drawSplineChart();
                $myPicture->drawPlotChart(
                    [
                        "DisplayValues" => true,
                        "PlotBorder" => true,
                        "BorderSize" => 1,
                        "Surrounding" => -60,
                        "BorderAlpha" => 80,
                    ]
                );

                /* Do NOT Write the chart legend */

                /* Write and save into cache */
                $myCache->writeToCache($chartHash, $myPicture);
                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
            }

            return '<img src="'.$imgPath.'">';
        } else {
            return api_convert_encoding(
                '<div id="messages" class="warning-message">'.get_lang('GraphicNotAvailable').'</div>',
                'UTF-8'
            );
        }
    }

    /*
     * Gets the company name of a user based on the extra field 'company'.
     *
     * @param int $userId
     *
     * @return string
     */
    public static function getCompanyOfUser($userId = 0)
    {
        $userId = (int) $userId;
        if (0 != $userId) {
            $tblExtraFieldValue = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
            $tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
            $sql = "SELECT
                    extra_field_value.item_id AS userId,
                    extra_field_value.value AS company
                FROM $tblExtraFieldValue AS extra_field_value
                INNER JOIN $tblExtraField AS extra_field
                ON (
                    extra_field_value.field_id = extra_field.id AND
                    extra_field.variable = 'company'
                )
                WHERE
                    extra_field_value.value != '' AND
                    extra_field_value.item_id = $userId ";
            $queryResult = Database::query($sql);
            $data = Database::store_result($queryResult, 'ASSOC');
            $totalData = count($data);
            /* use 'for' to performance */
            for ($i = 0; $i < $totalData; $i++) {
                $row = $data[$i];
                if (isset($row['company']) && !empty($row['company'])) {
                    return $row['company'];
                }
            }
        }

        return get_lang('NoEntity');
    }

    /**
     * Gets a list of users who were enrolled in the lessons.
     * It is necessary that in the extra field, a company is defined.
     *
     *  if lpId is different to 0, this search by lp id too
     *
     * Variable $withGroups determines the consultation of the enrollment in groups. The group in total will be taken
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int         $lpId
     * @param bool        $withGroups
     *
     * @return array
     */
    protected static function getCompanyLearnpathSubscription(
        $startDate = null,
        $endDate = null,
        $whereInLp = null,
        $withGroups = false
    ) {
        $whereInLp = Database::escape_string($whereInLp);
        $tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tblLp = Database::get_course_table(TABLE_LP_MAIN);
        $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);
        $tblGroupUser = Database::get_course_table(TABLE_GROUP_USER);
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblAccessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlFilter = '';
        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
            $accessUrlFilter = " INNER JOIN $tblAccessUrlUser auru
                      ON (u.id = auru.user_id AND auru.access_url_id = $urlId)";
        }
        $whereCondition = '';

        //Validating dates
        if (!empty($startDate)) {
            $startDate = new DateTime($startDate);
        }
        if (!empty($endDate)) {
            $endDate = new DateTime($endDate);
        }
        if (!empty($startDate) && !empty($endDate)) {
            if ($startDate > $endDate) {
                $dateTemp = $endDate;
                $endDate = $startDate;
                $startDate = $dateTemp;
                unset($dateTemp);
            }
        }

        // Settings condition and parametter GET to right date
        if (!empty($startDate)) {
            $startDate = api_get_utc_datetime($startDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
            $_GET['startDate'] = $startDate;
            $whereCondition .= " AND ip.lastedit_date >= '$startDate' ";
        }
        if (!empty($endDate)) {
            $endDate = api_get_utc_datetime($endDate->setTime(23, 59, 59)->format('Y-m-d H:i:s'));
            $_GET['endDate'] = $endDate;
            $whereCondition .= " AND ip.lastedit_date <= '$endDate' ";
        }
        if (!empty($whereInLp)) {
            $whereCondition .= " AND ip.ref in ($whereInLp) ";
        }
        $datas = [];
        if (!empty($startDate) or !empty($endDate)) {
            $query = "
            SELECT DISTINCT
                ip.ref AS lp_item,
                lpi.iid AS lp_item_id,
                ip.session_id AS session_id,
                ip.lastedit_type AS type,
                u.username AS username,
                ip.lastedit_date AS lastedit_date,
                ip.to_user_id AS id,
                u.firstname as firstname,
                u.lastname as lastname
            FROM $tblItemProperty AS ip
            INNER JOIN $tblUser AS u
            ON (u.id = ip.to_user_id)
            INNER JOIN $tblLp AS lp
            ON (lp.iid = ip.ref AND lp.c_id = ip.c_id)
            INNER JOIN $tblLpItem AS lpi
            ON (lp.id = lpi.lp_id AND lp.c_id = lpi.c_id)
            $accessUrlFilter
                    WHERE
                ip.lastedit_type = 'LearnpathSubscription' ";
            if (strlen($whereCondition) > 2) {
                $query .= $whereCondition;
            }
            if ($withGroups) {
                $query = "
                SELECT DISTINCT
                    ip.ref AS lp_item,
                    lpi.iid AS lp_item_id,
                    ip.session_id AS session_id,
                    ip.lastedit_type AS type,
                    ip.lastedit_date AS lastedit_date,
                    ip.to_group_id AS group_id,
                    ug.user_id AS id,
                    u.firstname as firstname,
                    u.lastname as lastname
            FROM
                    $tblItemProperty AS ip
                INNER JOIN $tblGroupUser AS ug
                ON (ug.group_id = ip.to_group_id AND ip.c_id = ug.c_id)
                INNER JOIN $tblUser AS u
                ON (u.id = ug.user_id)
                INNER JOIN $tblLp AS lp
                ON (lp.iid = ip.ref AND ug.c_id = lp.c_id)
                INNER JOIN $tblLpItem AS lpi
                ON (lp.id = lpi.lp_id AND lp.c_id = lpi.c_id)
                $accessUrlFilter
            WHERE
                    ip.lastedit_type = 'LearnpathSubscription' AND
                    ip.to_group_id != 0 ";
                if (strlen($whereCondition) > 2) {
                    $query .= $whereCondition;
                }
            }
            $query .= ' ORDER BY ip.ref, ip.session_id ';
            $queryResult = Database::query($query);
            $data = Database::store_result($queryResult, 'ASSOC');
            $totalData = count($data);
            /* use 'for' to performance */
            for ($i = 0; $i < $totalData; $i++) {
                $row = $data[$i];
                $row['complete_name'] = api_get_person_name($row['firstname'], $row['lastname']);
                $row['company'] = self::getCompanyOfUser($row['id']);
                $datas[$row['lp_item_id']][] = $row;
            }
        }

        return $datas;
    }

    private static function getDataAccessTrackingFilters($sql)
    {
        if (isset($_GET['course_id']) && !empty($_GET['course_id'])) {
            $courseId = (int) $_GET['course_id'];
            $sql .= " AND c.id = ".$courseId;
        }

        if (isset($_GET['session_id']) && !empty($_GET['session_id'])) {
            $sessionId = (int) $_GET['session_id'];
            $sql .= " AND a.session_id = ".$sessionId;
        }

        if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
            $userId = (int) $_GET['student_id'];
            $sql .= " AND u.user_id = ".$userId;
        }

        $sql .= " AND u.status <> ".ANONYMOUS;

        if (isset($_GET['date']) && !empty($_GET['date'])) {
            $dateRangePicker = new DateRangePicker('date', '', ['timePicker' => 'true']);
            $dates = $dateRangePicker->parseDateRange($_GET['date']);
            if (isset($dates['start']) && !empty($dates['start'])) {
                $dates['start'] = Database::escape_string(api_get_utc_datetime($dates['start']));
                $sql .= " AND login_course_date >= '".$dates['start']."'";
            }
            if (isset($dates['end']) && !empty($dates['end'])) {
                $dates['end'] = Database::escape_string(api_get_utc_datetime($dates['end']));
                $sql .= " AND logout_course_date <= '".$dates['end']."'";
            }
        }

        return $sql;
    }
}

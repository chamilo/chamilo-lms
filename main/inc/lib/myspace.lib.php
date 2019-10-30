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
                'content' => get_lang('Trainers Overview'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=user',
                'content' => get_lang('User overview'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=session',
                'content' => get_lang('Course sessions overview'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=course',
                'content' => get_lang('Courses overview'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'tracking/question_course_report.php?view=admin',
                'content' => get_lang('Learning paths exercises results list'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin',
                'content' => get_lang('Results of learning paths exercises by session'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/admin_view.php?display=accessoverview',
                'content' => get_lang('Accesses by user overview').' ('.get_lang('Beta').')',
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/exercise_category_report.php',
                'content' => get_lang('Exercise report by category for all sessions'),
            ],
            [
                'url' => api_get_path(WEB_CODE_PATH).'mySpace/survey_report.php',
                'content' => get_lang('SurveyReport'),
            ],
        ];

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
                get_lang('View my progress'),
                '',
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH)."auth/my_progress.php"
        );
        $menuItems[] = Display::url(
            Display::return_icon(
                'teacher.png',
                get_lang('Trainer View'),
                [],
                32
            ),
            api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher'
        );
        $menuItems[] = Display::url(
            Display::return_icon(
                'star_na.png',
                get_lang('Admin view'),
                [],
                32
            ),
            '#'
        );
        $menuItems[] = Display::url(
            Display::return_icon('quiz.png', get_lang('Exam tracking'), [], 32),
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
            $message = get_lang('Could not open');
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
        $tracking_column = isset($_GET['tracking_list_coaches_column']) ? $_GET['tracking_list_coaches_column'] : ($is_western_name_order xor $sort_by_first_name) ? 1 : 0;
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
            $table->set_header(0, get_lang('First name'), true);
            $table->set_header(1, get_lang('Last name'), true);
        } else {
            $table->set_header(0, get_lang('Last name'), true);
            $table->set_header(1, get_lang('First name'), true);
        }
        $table->set_header(2, get_lang('Time spent in portal'), false);
        $table->set_header(3, get_lang('Latest login'), false);
        $table->set_header(4, get_lang('Learners'), false);
        $table->set_header(5, get_lang('Courses'), false);
        $table->set_header(6, get_lang('Number of sessions'), false);
        $table->set_header(7, get_lang('Course sessions'), false);

        if ($is_western_name_order) {
            $csv_header[] = [
                get_lang('First name'),
                get_lang('Last name'),
                get_lang('Time spent in portal'),
                get_lang('Latest login'),
                get_lang('Learners'),
                get_lang('Courses'),
                get_lang('Number of sessions'),
            ];
        } else {
            $csv_header[] = [
                get_lang('Last name'),
                get_lang('First name'),
                get_lang('Time spent in portal'),
                get_lang('Latest login'),
                get_lang('Learners'),
                get_lang('Courses'),
                get_lang('Number of sessions'),
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
            $sqlCoachs .= ' ORDER BY '.$order[$tracking_column].' '.$tracking_direction;
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
        if (isset($_GET['export']) && $_GET['export'] == 'options') {
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
                $form->addButtonSave(get_lang('Validate'), 'submit');

                // setting the default values for the form that contains all the extra fields
                $exportFields = Session::read('additional_export_fields');
                if (is_array($exportFields)) {
                    foreach ($exportFields as $key => $value) {
                        $defaults['extra_export_field'.$value] = 1;
                    }
                }
                $form->setDefaults($defaults);
            } else {
                $form->addElement('html', Display::return_message(get_lang('There are not extra fields available'), 'warning'));
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
                        get_lang('The following fields will also be exported').': <br /><ul>'.$message.'</ul>',
                        'confirm',
                        false
                    );
                } else {
                    echo Display::return_message(
                        get_lang('No additional fields will be exported'),
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
                    get_lang('The following fields will also be exported').': <br /><ul>'.$message.'</ul>',
                    'normal',
                    false
                );
            }
        }
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
        $csv_row[] = get_lang('Time');
        $csv_row[] = get_lang('Progress');
        $csv_row[] = get_lang('Average score in learning paths');
        $csv_row[] = get_lang('Total number of messages');
        $csv_row[] = get_lang('Total number of assignments');
        $csv_row[] = get_lang('Total score obtained for tests');
        $csv_row[] = get_lang('Total possible score for tests');
        $csv_row[] = get_lang('Number of tests answered');
        $csv_row[] = get_lang('Total score percentage for tests');
        $csv_row[] = get_lang('Latest login');
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
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('Time'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('Progress'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('Average score in learning paths'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('Total number of messages'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('Total number of assignments'), 6, true).'</span></th>';
        $head .= '<th width="105px" style="border-bottom:0"><span>'.get_lang('Total score obtained for tests').'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('Number of tests answered'), 6, true).'</span></th>';
        $head .= '<th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('Latest login').'</span></th>';
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
        $return = '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';

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
        $csv_row[] = get_lang('Time');
        $csv_row[] = get_lang('Progress');
        $csv_row[] = get_lang('Average score in learning paths');
        $csv_row[] = get_lang('Total number of messages');
        $csv_row[] = get_lang('Total number of assignments');
        $csv_row[] = get_lang('Total score obtained for tests');
        $csv_row[] = get_lang('Total possible score for tests');
        $csv_row[] = get_lang('Number of tests answered');
        $csv_row[] = get_lang('Total score percentage for tests');
        $csv_row[] = get_lang('Latest login');
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

                    $last_login_date_tmp = Tracking:: get_last_connection_date_on_the_course(
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

        $sql = "SELECT score, max_score
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
            $score_obtained += $row['score'];
            $score_possible += $row['max_score'];
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

        $user_data = self::get_user_data_tracking_overview(
            $from,
            1000,
            $orderby,
            $direction
        );

        // the first line of the csv file with the column headers
        $csv_row = [];
        $csv_row[] = get_lang('Code');
        if ($is_western_name_order) {
            $csv_row[] = get_lang('First name');
            $csv_row[] = get_lang('Last name');
        } else {
            $csv_row[] = get_lang('Last name');
            $csv_row[] = get_lang('First name');
        }
        $csv_row[] = get_lang('Login');
        $csv_row[] = get_lang('Code');

        // the additional user defined fields (only those that were selected to be exported)
        $fields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

        $additionalExportFields = Session::read('additional_export_fields');

        if (is_array($additionalExportFields)) {
            foreach ($additionalExportFields as $key => $extra_field_export) {
                $csv_row[] = $fields[$extra_field_export][3];
                $field_names_to_be_exported[] = 'extra_'.$fields[$extra_field_export][1];
            }
        }
        $csv_row[] = get_lang('Time');
        $csv_row[] = get_lang('Progress');
        $csv_row[] = get_lang('Average score in learning paths');
        $csv_row[] = get_lang('AvgExercisesScore');
        $csv_row[] = get_lang('AvgMessages');
        $csv_row[] = get_lang('AvgAssignments');
        $csv_row[] = get_lang('Total score obtained for tests');
        $csv_row[] = get_lang('Total possible score for tests');
        $csv_row[] = get_lang('Number of tests answered');
        $csv_row[] = get_lang('Total score percentage for tests');
        $csv_row[] = get_lang('FirstLogin');
        $csv_row[] = get_lang('Latest login');
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
            $csv_content[] = [
                api_html_entity_decode($row_course[1], ENT_QUOTES, $charset),
                $nb_students_in_course,
                $avg_time_spent_in_course,
                is_null($avg_progress_in_course) ? null : $avg_progress_in_course.'%',
                is_null($avg_score_in_course) ? null : is_numeric($avg_score_in_course) ? $avg_score_in_course.'%' : $avg_score_in_course,
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
     * @param int    $from
     * @param int    $numberItems
     * @param int    $column
     * @param string $direction
     *
     * @return array
     */
    public static function get_user_data_tracking_overview($from, $numberItems, $column, $direction)
    {
        $isWestern = api_is_western_name_order();

        switch ($column) {
            case '0':
                $column = $isWestern ? 'firstname' : 'lastname';
                break;
        }

        $order = [
            "$column $direction",
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
                    if ($is_session_avail == 0) {
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
                    $user['error'] = get_lang('User already register by other coach.');
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
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $id_session = (int) $id_session;
        $sendMail = $_POST['sendMail'] ? 1 : 0;

        // Adding users to the platform.
        $new_users = [];
        foreach ($users as $index => $user) {
            $user = self::complete_missing_data($user);
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
        foreach ($course_list as $enreg_course) {
            $nbr_users = 0;
            $new_users = [];
            $enreg_course = Database::escape_string($enreg_course);
            foreach ($users as $index => $user) {
                $userid = (int) $user['id'];
                $sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(session_id, c_id, user_id)
                        VALUES('$id_session','$enreg_course','$userid')";
                $result = Database::query($sql);
                if (Database::affected_rows($result)) {
                    $nbr_users++;
                }
                $new_users[] = $user;
            }

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

        $new_users = [];
        foreach ($users as $index => $user) {
            $userid = $user['id'];
            $sql_insert = "INSERT IGNORE INTO $tbl_session_rel_user(session_id, user_id, registered_at)
                           VALUES ('$id_session','$userid', '".api_get_utc_datetime()."')";
            Database::query($sql_insert);
            $user['added_at_session'] = 1;
            $new_users[] = $user;
        }

        $users = $new_users;
        $registered_users = get_lang('File imported').'<br /> Import file results : <br />';
        // Sending emails.
        $addedto = '';
        if ($sendMail) {
            foreach ($users as $index => $user) {
                $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('Your registration on').' '.api_get_setting('siteName');
                $emailbody = get_lang('Dear').' '.
                    api_get_person_name($user['First name'], $user['Last name']).",\n\n".
                    get_lang('You are registered to')." ".api_get_setting('siteName')." ".get_lang('with the following settings:')."\n\n".
                    get_lang('Username')." : $user[UserName]\n".
                    get_lang('Pass')." : $user[Password]\n\n".
                    get_lang('The address of')." ".api_get_setting('siteName')." ".get_lang('is')." : ".api_get_path(WEB_PATH)." \n\n".
                    get_lang('In case of trouble, contact us.')."\n\n".
                    get_lang('Sincerely').",\n\n".
                    api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".
                    get_lang('Administrator')." ".api_get_setting('siteName')."\nT. ".
                    api_get_setting('administratorTelephone')."\n".get_lang('e-mail')." : ".api_get_setting('emailAdministrator');

                api_mail_html(
                    api_get_person_name($user['First name'], $user['Last name'], null, PERSON_NAME_EMAIL_ADDRESS),
                    $user['e-mail'],
                    $emailsubject,
                    $emailbody
                );
                $userInfo = api_get_user_info($user['id']);

                if (($user['added_at_platform'] == 1 && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
                    if ($user['added_at_platform'] == 1) {
                        $addedto = get_lang('User created in portal');
                    } else {
                        $addedto = '          ';
                    }

                    if ($user['added_at_session'] == 1) {
                        $addedto .= get_lang('User added into the session');
                    }
                } else {
                    $addedto = get_lang('User not added.');
                }

                $registered_users .= UserManager::getUserProfileLink($userInfo).' - '.$addedto.'<br />';
            }
        } else {
            foreach ($users as $index => $user) {
                $userInfo = api_get_user_info($user['id']);
                if (($user['added_at_platform'] == 1 && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
                    if ($user['added_at_platform'] == 1) {
                        $addedto = get_lang('User created in portal');
                    } else {
                        $addedto = '          ';
                    }

                    if ($user['added_at_session'] == 1) {
                        $addedto .= ' '.get_lang('User added into the session');
                    }
                } else {
                    $addedto = get_lang('User not added.');
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
        $crawler = new \Symfony\Component\DomCrawler\Crawler();
        $crawler->addXmlContent(file_get_contents($file));
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
    public static function displayTrackingAccessOverView($courseId, $sessionId, $studentId)
    {
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
            get_lang('Search courses'),
            $courseList,
            [
                'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?'.http_build_query([
                    'a' => 'search_course_by_session_all',
                    'session_id' => $sessionId,
                    'course_id' => $courseId,
                ]),
            ]
        );

        $form->addElement(
            'select_ajax',
            'session_id',
            get_lang('Search sessions'),
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
                STUDENT => get_lang('Learner'),
                COURSEMANAGER => get_lang('CourseAdministrator'),
                DRH => get_lang('Human Resources Manager'),
            ],
            ['id' => 'profile']
        );

        $form->addElement(
            'select_ajax',
            'student_id',
            get_lang('Search users'),
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
            get_lang('Date range'),
            true,
            [
                'id' => 'date_range',
                'format' => 'YYYY-MM-DD',
                'timePicker' => 'false',
                'validate_format' => 'Y-m-d',
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
            $table->set_header(0, get_lang('Login date'), true);
            $table->set_header(1, get_lang('Username'), true);
            if (api_is_western_name_order()) {
                $table->set_header(2, get_lang('First name'), true);
                $table->set_header(3, get_lang('Last name'), true);
            } else {
                $table->set_header(2, get_lang('Last name'), true);
                $table->set_header(3, get_lang('First name'), true);
            }
            $table->set_header(4, get_lang('clicks'), false);
            $table->set_header(5, get_lang('IP'), false);
            $table->set_header(6, get_lang('Time connected (hh:mm)'), false);
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
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT COUNT(course_access_id) count FROM $table";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        return $row['count'];
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

        $user = Database::get_main_table(TABLE_MAIN_USER);
        $course = Database::get_main_table(TABLE_MAIN_COURSE);
        $track_e_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $track_e_course_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

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
                a.logout_course_date,
                c.title,
                c.code,
                u.user_id
            FROM $track_e_course_access a
            INNER JOIN $user u ON a.user_id = u.user_id
            INNER JOIN $course c ON a.c_id = c.id
            WHERE 1=1 ";

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

        if (isset($_GET['date']) && !empty($_GET['date'])) {
            $dates = DateRangePicker::parseDateRange($_GET['date']);
            if (isset($dates['start']) && !empty($dates['start'])) {
                $dates['start'] = Database::escape_string($dates['start']);
                $sql .= " AND login_course_date >= '".$dates['start']."'";
            }
            if (isset($dates['end']) && !empty($dates['end'])) {
                $dates['end'] = Database::escape_string($dates['end']);
                $sql .= " AND logout_course_date <= '".$dates['end']."'";
            }
        }

        $sql .= " ORDER BY col$column $orderDirection ";
        $sql .= " LIMIT $from,$numberItems";

        $result = Database::query($sql);

        $data = [];
        while ($user = Database::fetch_assoc($result)) {
            $data[] = $user;
        }

        $return = [];
        //TODO: Dont use numeric index
        foreach ($data as $key => $info) {
            $start_date = $info['col0'];
            $end_date = $info['logout_course_date'];

            $return[$info['user_id']] = [
                $start_date,
                $info['col1'],
                $info['col2'],
                $info['col3'],
                $info['user_id'],
                'ip',
                //TODO is not correct/precise, it counts the time not logged between two loggins
                gmdate("H:i:s", strtotime($end_date) - strtotime($start_date)),
            ];
        }

        foreach ($return as $key => $info) {
            $ipResult = Database::select(
                'user_ip',
                $track_e_login,
                ['where' => [
                    '? BETWEEN login_date AND logout_date' => $info[0],
                ]],
                'first'
            );

            $return[$key][5] = $ipResult['user_ip'];
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
        $end_date
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $user_id = (int) $user_id;
        $connections = [];
        if (!empty($course_info)) {
            $courseId = (int) $course_info['real_id'];
            $end_date = add_day_to($end_date);

            $start_date = Database::escape_string($start_date);
            $end_date = Database::escape_string($end_date);
            $sessionCondition = api_get_session_condition($sessionId);
            $sql = "SELECT 
                        login_course_date, 
                        logout_course_date, 
                        TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date) duration
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
                $connections[] = [
                    'login' => $row['login_course_date'],
                    'logout' => $row['logout_course_date'],
                    'duration' => $row['duration'],
                ];
            }
        }

        return $connections;
    }
}

/**
 * @param $user_id
 * @param array $course_info
 * @param int   $sessionId
 * @param null  $start_date
 * @param null  $end_date
 *
 * @return array
 */
function get_stats($user_id, $course_info, $sessionId, $start_date = null, $end_date = null)
{
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
    $result = [];
    if (!empty($course_info)) {
        $stringStartDate = '';
        $stringEndDate = '';
        if ($start_date != null && $end_date != null) {
            $end_date = add_day_to($end_date);

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

function add_day_to($end_date)
{
    $foo_date = strtotime($end_date);
    $foo_date = strtotime(' +1 day', $foo_date);
    $foo_date = date('Y-m-d', $foo_date);

    return $foo_date;
}

/**
 * Converte an array to a table in html.
 *
 * @param array $result
 *
 * @author Jorge Frisancho Jibaja
 *
 * @version OCT-22- 2010
 *
 * @return string
 */
function convert_to_string($result)
{
    $html = '<table class="table">';
    if (!empty($result)) {
        foreach ($result as $key => $data) {
            $html .= '<tr><td>';
            $html .= api_get_local_time($data['login']);
            $html .= '</td>';
            $html .= '<td>';

            $html .= api_time_to_hms(api_strtotime($data['logout']) - api_strtotime($data['login']));
            $html .= '</tr></td>';
        }
    }
    $html .= '</table>';

    return $html;
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
function grapher($sql_result, $start_date, $end_date, $type = '')
{
    if (empty($start_date)) {
        $start_date = '';
    }
    if (empty($end_date)) {
        $end_date = '';
    }
    if ($type == '') {
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
                $main_month_year[date('m-Y', $login)] += float_format(($logout - $login) / 60, 0);
            }
            if (isset($main_day[date('d-m-Y', $login)])) {
                $main_day[date('d-m-Y', $login)] += float_format(($logout - $login) / 60, 0);
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
        if (count($main_date) == 1) {
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
        $myData->setSerieDescription('Serie1', get_lang('My results'));
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
                get_lang('Time spent in the course'),
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
        $html = '<img src="'.$imgPath.'">';

        return $html;
    } else {
        $foo_img = api_convert_encoding(
            '<div id="messages" class="warning-message">'.get_lang('Graphic not available').'</div>',
            'UTF-8'
        );

        return $foo_img;
    }
}

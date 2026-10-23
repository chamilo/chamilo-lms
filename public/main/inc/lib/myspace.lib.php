<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
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
     * Generate the list of admin actions to be shown
     * @return array
     */
    public static function generateAdminActionLinks(): array
    {
        $actions = [
            [
                'url' => '/reporting/admin/trainers',
                'content' => get_lang('Trainers Overview'),
            ],
            [
                'url' => '/reporting/admin/users',
                'content' => get_lang('User overview'),
            ],
            [
                'url' => '/reporting/admin/sessions',
                'content' => get_lang('Sessions overview'),
            ],
            [
                'url' => '/reporting/admin/courses',
                'content' => get_lang('Courses overview'),
            ],
            [
                'url' => '/reporting/admin/learning-results',
                'content' => get_lang('Learning paths exercises results list'),
            ],
            [
                'url' => '/reporting/admin/session-results',
                'content' => get_lang('Results of learning paths exercises by session'),
            ],
            [
                'url' => '/reporting/admin/accesses',
                'content' => get_lang('Accesses by user overview').' ('.get_lang('Beta').')',
            ],
            [
                'url' => '/reporting/admin/exercise-categories',
                'content' => get_lang('Exercise report by category for all sessions'),
            ],
            [
                'url' => '/reporting/admin/surveys',
                'content' => get_lang('Surveys report'),
            ],
            [
                'url' => '/reporting/admin/student-bosses',
                'content' => get_lang("Student's superior follow up"),
            ],
            [
                'url' => '/reporting/admin/tutor-planning',
                'content' => get_lang('General tutor planning'),
            ],
            [
                'url' => '/reporting/admin/question-stats',
                'content' => get_lang('Question stats'),
            ],
            [
                'url' => '/reporting/admin/question-stats-detail',
                'content' => get_lang('Detailed questions stats'),
            ],
        ];

        $field = new ExtraField('user');
        $companyField = $field->get_handler_field_info_by_field_variable('company');
        if (!empty($companyField)) {
            $actions[] =
                [
                    'url' => '/reporting/admin/organization',
                    'content' => get_lang('User by organization'),
                ];
        }
        $field = new ExtraField('lp');
        $authorsField = $field->get_handler_field_info_by_field_variable('authors');
        if (!empty($authorsField)) {
            $actions[] =
                [
                    'url' => '/reporting/admin/learning-path-authors',
                    'content' => get_lang('Learning path by author'),
                ];
        }
        $field = new ExtraField('lp_item');
        $authorsItemField = $field->get_handler_field_info_by_field_variable('authorlpitem');
        if (!empty($authorsItemField)) {
            $actions[] =
                [
                    'url' => '/reporting/admin/learning-path-item-authors',
                    'content' => get_lang('LP item by author'),
                ];
        }
        return $actions;
    }

    /**
     * @return string
     */
    public static function getTopMenu()
    {
        $menuItems = [];

        // Always available: student's own progress
        $menuItems[] = Display::url(
            Display::getMdiIcon('chart-box', 'ch-tool-icon', null, 32, get_lang('View my progress')),
            '/reporting/my-progress'
        );

        // Trainer view: only for course teachers / coaches
        if (api_is_allowed_to_edit(null, true)) {
            $menuItems[] = Display::url(
                Display::getMdiIcon('human-male-board', 'ch-tool-icon', null, 32, get_lang('Trainer View')),
                '/reporting'
            );
        }

        // Admin view: only for platform admins and DRH
        if (api_is_platform_admin() || api_is_hrm()) {
            $menuItems[] = Display::url(
                Display::getMdiIcon('star', 'ch-tool-icon', null, 32, get_lang('Admin view')),
                '/reporting/admin'
            );
        }

        // Exam tracking
        if (api_is_platform_admin() || api_is_hrm() || api_is_allowed_to_edit(null, true)) {
            $menuItems[] = Display::url(
                Display::getMdiIcon('order-bool-ascending-variant', 'ch-tool-icon', null, 32, get_lang('Exam tracking')),
                '/reporting/exams'
            );
        }

        return Display::toolbarAction('myspace', $menuItems);
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

            fwrite($open, \Chamilo\CoreBundle\Helpers\CsvFormulaEscaper::escapeDelimitedContent($info));
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
                            status = ".SessionEntity::STUDENT;
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

            $courseReportingUrl = api_get_path(WEB_PATH).'resources/course-reporting/?'.http_build_query([
                'cid' => (int) $courseInfo['real_id'],
                'sid' => (int) $session_id,
                'gid' => 0,
            ]);
            $table_row[] = '<center><a href="'.$courseReportingUrl.'">
                             '.Display::getMdiIcon('fast-forward-outline', 'ch-tool-icon', null, 22, get_lang('Details')).'
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
        if (!Container::getUserRepository()->isUsernameAvailable($username) || empty($username)) {
            $i = 0;
            while (1) {
                if (0 == $i) {
                    $sufix = '';
                } else {
                    $sufix = $i;
                }
                $desired_username = UserManager::create_username(
                    $firstname,
                    $lastname
                );
                if (Container::getUserRepository()->isUsernameAvailable($desired_username.$sufix)) {
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
            $sql = " SELECT u.id as user_id FROM $tbl_session_rel_course_rel_user rel
                     INNER JOIN $table_user u
                     ON (rel.user_id = u.id)
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
                if (Container::getUserRepository()->isUsernameAvailable($user['UserName'])) {
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
            if ('' != $creator_id) {
                if ($creator_id != api_get_user_id()) {
                    $user['error'] = get_lang('User is already registered by another tutor.');
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
            if ('true' == api_get_setting('registration', 'email')) {
                $mandatory_fields[] = 'Email';
            }

            foreach ($mandatory_fields as $key => $field) {
                if (!isset($user[$field]) || 0 == strlen($user[$field])) {
                    $user['error'] = get_lang($field.'Mandatory');
                    $errors[] = $user;
                }
            }
            // 2. Check whether the username is too long.
            if (UserManager::is_username_too_long($user['UserName'])) {
                $user['error'] = get_lang('This login is too long');
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
        if (!isset($user['Password']) || 0 == strlen($user['Password'])) {
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
            if (COURSEMANAGER == $user['create']) {
                $user['id'] = UserManager:: create_user(
                    $user['FirstName'],
                    $user['LastName'],
                    $default_status,
                    $user['Email'],
                    $user['UserName'],
                    $user['Password'],
                    $user['OfficialCode'],
                    api_get_setting('PlatformLanguage'),
                    $user['PhoneNumber']
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

        $registered_users = get_lang('File imported').'<br /> Import file results : <br />';
        // Sending emails.
        $addedto = '';
        if ($sendMail) {
            foreach ($users as $index => $user) {
                $emailsubject = '['.api_get_setting('site_name').'] '.sprintf(get_lang('Your registration on %s'), api_get_setting('site_name'));
                $emailbody = get_lang('Dear').' '.
                    api_get_person_name($user['First name'], $user['Last name']).",\n\n".
                    get_lang('You are registered to')." ".api_get_setting('site_name')." ".get_lang('with the following settings:')."\n\n".
                    get_lang('Username')." : $user[UserName]\n".
                    get_lang('Pass')." : $user[Password]\n\n".
                    get_lang('The address of')." ".api_get_setting('site_name')." ".get_lang('is')." : ".api_get_path(WEB_PATH)." \n\n".
                    get_lang('In case of trouble, contact us.')."\n\n".
                    get_lang('Sincerely').",\n\n".
                    api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".
                    get_lang('Administrator')." ".api_get_setting('site_name')."\nT. ".
                    api_get_setting('administratorTelephone')."\n".get_lang('E-mail')." : ".api_get_setting('emailAdministrator');

                api_mail_html(
                    api_get_person_name($user['First name'], $user['Last name'], null, PERSON_NAME_EMAIL_ADDRESS),
                    $user['e-mail'],
                    $emailsubject,
                    $emailbody
                );
                $userInfo = api_get_user_info($user['id']);

                if ((1 == $user['added_at_platform'] && 1 == $user['added_at_session']) || 1 == $user['added_at_session']) {
                    if (1 == $user['added_at_platform']) {
                        $addedto = get_lang('User created in portal');
                    } else {
                        $addedto = '          ';
                    }

                    if (1 == $user['added_at_session']) {
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
                if ((1 == $user['added_at_platform'] && 1 == $user['added_at_session']) || 1 == $user['added_at_session']) {
                    if (1 == $user['added_at_platform']) {
                        $addedto = get_lang('User created in portal');
                    } else {
                        $addedto = '          ';
                    }

                    if (1 == $user['added_at_session']) {
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
    public static function parse_csv_data($file)
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
        $crawler = Import::xml($file)->filter('Contacts > Contact ');
        $array = [];
        foreach ($crawler as $domElement) {
            $row = [];
            foreach ($domElement->childNodes as $node) {
                if ('#text' != $node->nodeName) {
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
            if (null != $start_date && null != $end_date) {
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
        if (empty($start_date) || empty($end_date) || empty($sql_result) || !is_array($sql_result)) {
            return api_convert_encoding(
                '<div class="warning-message">'.get_lang('Graphic not available').'</div>',
                'UTF-8'
            );
        }

        if (!in_array($type, ['day', 'month', 'year'], true)) {
            $type = 'day';
        }

        try {
            $startDate = new DateTime($start_date);
            $endDate = new DateTime($end_date);
        } catch (Exception $e) {
            return api_convert_encoding(
                '<div class="warning-message">'.get_lang('Graphic not available').'</div>',
                'UTF-8'
            );
        }

        if ($startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        $mainDay = [];
        $mainMonthYear = [];
        $mainYear = [];

        $cursorDay = clone $startDate;
        while ($cursorDay <= $endDate) {
            $mainDay[$cursorDay->format('d-m-Y')] = 0.0;
            $cursorDay->modify('+1 day');
        }

        $cursorMonth = (clone $startDate)->modify('first day of this month');
        $monthEnd = (clone $endDate)->modify('first day of next month');
        while ($cursorMonth < $monthEnd) {
            $mainMonthYear[$cursorMonth->format('m-Y')] = 0.0;
            $cursorMonth->modify('+1 month');
        }

        $cursorYear = (clone $startDate)->modify('first day of january');
        $yearEnd = (clone $endDate)->modify('first day of january next year');
        while ($cursorYear < $yearEnd) {
            $mainYear[$cursorYear->format('Y')] = 0.0;
            $cursorYear->modify('+1 year');
        }

        $i = 0;
        foreach ($sql_result as $data) {
            if (empty($data['login']) || empty($data['logout'])) {
                continue;
            }

            $login = api_strtotime($data['login']);
            $logout = api_strtotime($data['logout']);

            if ($logout < $login) {
                continue;
            }

            $minutes = (float) ($logout - $login) / 60;

            $dayKey = date('d-m-Y', $login);
            $monthKey = date('m-Y', $login);
            $yearKey = date('Y', $login);

            if (isset($mainDay[$dayKey])) {
                $mainDay[$dayKey] += $minutes;
            }

            if (isset($mainMonthYear[$monthKey])) {
                $mainMonthYear[$monthKey] += $minutes;
            }

            if (isset($mainYear[$yearKey])) {
                $mainYear[$yearKey] += $minutes;
            }

            if ($i > 5000) {
                break;
            }

            $i++;
        }

        switch ($type) {
            case 'month':
                $mainDate = $mainMonthYear;
                break;
            case 'year':
                $mainDate = $mainYear;
                break;
            case 'day':
            default:
                $mainDate = $mainDay;
                break;
        }

        if (empty($mainDate)) {
            return api_convert_encoding(
                '<div class="warning-message">'.get_lang('Graphic not available').'</div>',
                'UTF-8'
            );
        }

        $labels = array_keys($mainDate);
        $values = array_values($mainDate);

        $displayLabels = $labels;
        $labelCount = count($displayLabels);
        $labelRotation = 50;

        // Reduce label density for large ranges.
        if ($labelCount > 24) {
            $step = max(1, (int) ceil($labelCount / 12));
            foreach ($displayLabels as $index => $label) {
                if (0 !== $index % $step) {
                    $displayLabels[$index] = '';
                }
            }
            $labelRotation = 0;
        } elseif ($labelCount > 12) {
            $labelRotation = 30;
        }

        $displayPointValues = $labelCount <= 12;

        $myData = new pData();
        $myData->addPoints($values, 'Serie1');
        $myData->addPoints($displayLabels, 'Labels');
        $myData->setAbscissa('Labels');
        $myData->setSerieWeight('Serie1', 1);
        $myData->setSerieDescription('Serie1', get_lang('My results'));
        $myData->setAxisName(0, get_lang('Minutes'));
        $myData->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

        $cachePath = rtrim(api_get_path(SYS_ARCHIVE_PATH), '/');
        $myCache = new pCache([
            'CacheFolder' => $cachePath,
        ]);

        $chartHash = $myCache->getHash($myData);
        $imagePath = $cachePath.'/'.$chartHash.'.png';

        if ($myCache->isInCache($chartHash)) {
            $myCache->saveFromCache($chartHash, $imagePath);
        } else {
            $mainWidth = 980;
            $mainHeight = 360;

            $myPicture = new pImage($mainWidth, $mainHeight, $myData);
            $myPicture->Antialias = false;

            $myPicture->drawFilledRectangle(
                0,
                0,
                $mainWidth,
                $mainHeight,
                ['R' => 255, 'G' => 255, 'B' => 255]
            );

            $myPicture->drawRectangle(
                0,
                0,
                $mainWidth - 1,
                $mainHeight - 1,
                ['R' => 0, 'G' => 0, 'B' => 0]
            );

            $fontPath = api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf';

            $myPicture->setFontProperties([
                'FontName' => $fontPath,
                'FontSize' => 10,
            ]);

            $myPicture->drawText(
                (int) ($mainWidth / 2),
                30,
                get_lang('Time spent in the course'),
                [
                    'FontSize' => 12,
                    'Align' => TEXT_ALIGN_BOTTOMMIDDLE,
                ]
            );

            $myPicture->setFontProperties([
                'FontName' => $fontPath,
                'FontSize' => 8,
            ]);

            $myPicture->setGraphArea(60, 50, $mainWidth - 30, $mainHeight - 90);

            $myPicture->drawScale([
                'XMargin' => 10,
                'YMargin' => 10,
                'Floating' => true,
                'GridR' => 220,
                'GridG' => 220,
                'GridB' => 220,
                'DrawSubTicks' => true,
                'CycleBackground' => true,
                'LabelRotation' => $labelRotation,
                'Mode' => SCALE_MODE_ADDALL_START0,
            ]);

            $myPicture->Antialias = true;

            $myPicture->setShadow(true, [
                'X' => 1,
                'Y' => 1,
                'R' => 0,
                'G' => 0,
                'B' => 0,
                'Alpha' => 10,
            ]);

            $myPicture->setFontProperties([
                'FontName' => $fontPath,
                'FontSize' => 9,
            ]);

            $myPicture->drawLineChart();
            $myPicture->drawPlotChart([
                'DisplayValues' => $displayPointValues,
                'PlotBorder' => true,
                'BorderSize' => 1,
                'Surrounding' => -60,
                'BorderAlpha' => 80,
                'PlotSize' => $labelCount > 40 ? 1 : 3,
            ]);

            $myCache->writeToCache($chartHash, $myPicture);
            $myCache->saveFromCache($chartHash, $imagePath);
        }

        if (!is_file($imagePath) || !is_readable($imagePath)) {
            return api_convert_encoding(
                '<div class="warning-message">'.get_lang('Graphic not available').'</div>',
                'UTF-8'
            );
        }

        $imageContents = file_get_contents($imagePath);
        if (false === $imageContents) {
            return api_convert_encoding(
                '<div class="warning-message">'.get_lang('Graphic not available').'</div>',
                'UTF-8'
            );
        }

        $base64Image = base64_encode($imageContents);
        $alt = Security::remove_XSS(get_lang('Time spent in the course'));

        return '<img src="data:image/png;base64,'.$base64Image.'" alt="'.$alt.'" class="max-w-full h-auto mx-auto" />';
    }

    /**
     * Render the admin reports navigation cards (common layout for tracking pages).
     *
     * @param string|null $activeDisplay            Report key used in ?display=... (admin_view)
     * @param string|null $currentScript            Current PHP script name (e.g. "tc_report.php")
     * @param bool        $includeCurrentReportNote Append the "Current report" helper text
     *
     * @return string
     */
    public static function renderAdminReportCardsSection(
        ?string $activeDisplay = null,
        ?string $currentScriptName = null,
        bool $includeNote = false
    ): string {
        $actions = self::generateAdminActionLinks();
        $currentReportLabel = null;

        // If there is no display parameter we fall back to script-based matching.
        $useScriptMatching = null === $activeDisplay;

        if ($useScriptMatching && empty($currentScriptName)) {
            $currentScriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
        } else {
            // Normalize in case a full path is passed.
            $currentScriptName = basename((string) $currentScriptName);
        }

        $html = '';

        $html .= '<section class="bg-white rounded-xl shadow-sm border border-gray-50 reporting-admin-list w-full">';
        $html .= '  <div class="p-4 md:p-5 space-y-4">';
        $html .= '    <h2 class="text-base md:text-lg font-semibold text-gray-800">'.get_lang('Available reports').'</h2>';
        $html .= '    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">';

        foreach ($actions as $action) {
            $url       = $action['url'];
            $labelHtml = $action['content'];
            $labelText = strip_tags($labelHtml);

            $parsedUrl   = parse_url($url);
            $queryParams = [];
            if (!empty($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            $reportKey = $queryParams['display'] ?? null;

            // -------- Active state detection --------
            $isActive = false;

            // 1) Classic admin_view.php (?display=...)
            if (null !== $activeDisplay && null !== $reportKey && $reportKey === $activeDisplay) {
                $isActive = true;
                // 2) Fallback: match by script name (tc_report.php, bosses report, etc.)
            } elseif (
                $useScriptMatching &&
                !empty($parsedUrl['path']) &&
                !empty($currentScriptName) &&
                str_contains($parsedUrl['path'], $currentScriptName)
            ) {
                $isActive = true;
            }

            if ($isActive) {
                $currentReportLabel = $labelText;
            }

            // -------- Icon selection --------
            $iconName = 'file-chart';
            switch ($reportKey) {
                case 'coaches':
                    $iconName = 'account-tie';
                    break;
                case 'user':
                    $iconName = 'account-multiple';
                    break;
                case 'session':
                    $iconName = 'calendar-multiple';
                    break;
                case 'course':
                    $iconName = 'book-open-page-variant';
                    break;
                case 'company':
                    $iconName = 'office-building';
                    break;
                case 'learningPath':
                    $iconName = 'timeline-text';
                    break;
                case 'learningPathByItem':
                    $iconName = 'format-list-bulleted';
                    break;
                case 'accessoverview':
                    $iconName = 'eye';
                    break;
                default:
                    // Special icon for the general coach report without ?display=...
                    if (!empty($parsedUrl['path']) && basename($parsedUrl['path']) === 'tc_report.php') {
                        $iconName = 'account-group';
                    } else {
                        $iconName = 'file-chart';
                    }
            }

            $cardClass = 'admin-report-card block rounded-xl border border-gray-50 bg-gray-20 '.
                'hover:bg-white hover:border-sky-400 shadow-sm hover:shadow-md transition';
            if ($isActive) {
                $cardClass .= ' admin-report-card-active';
            }

            $html .= '      <a href="'.$url.'" class="'.$cardClass.'">';
            $html .= '        <div class="flex items-center gap-3 p-3 md:p-4">';
            $html .= '          <div class="flex-shrink-0">';
            $html .=                Display::getMdiIcon(
                $iconName,
                'ch-tool-icon',
                null,
                32,
                $labelText
            );
            $html .= '          </div>';
            $html .= '          <div class="text-sm md:text-base text-gray-800">';
            $html .=                $labelHtml;
            $html .= '          </div>';
            $html .= '        </div>';
            $html .= '      </a>';
        }

        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '</section>';

        if ($includeNote && !empty($currentReportLabel)) {
            $html .= '<div class="mt-2">';
            $html .= '  <p class="text-sm text-gray-600">'.
                get_lang('Current report').': <span class="font-semibold text-gray-900">'.
                $currentReportLabel.
                '</span></p>';
            $html .= '</div>';
        }

        return $html;
    }
}

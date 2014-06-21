<?php
/* For licensing terms, see /license.txt */

/**
 * Class SessionManager
 *
 * This is the session library for Chamilo.
 * All main sessions functions should be placed here.
 * This class provides methods for sessions management.
 * Include/require it in your code to use its features.
 *
 * @package chamilo.library
 *
 */
class SessionManager
{
    public static $_debug = false;

    public function __construct()
    {

    }

    /**
     * Fetches a session from the database
     * @param   int     Session ID
     * @return  array   Session details
     */
    public static function fetch($id)
    {
        $t = Database::get_main_table(TABLE_MAIN_SESSION);
        if ($id != strval(intval($id))) {
            return array();
        }
        $s = "SELECT * FROM $t WHERE id = $id";
        $r = Database::query($s);
        if (Database::num_rows($r) != 1) {
            return array();
        }
        return Database::fetch_array($r, 'ASSOC');
    }

    /**
     * Create a session
     * @author Carlos Vargas <carlos.vargas@beeznest.com>, from existing code
     * @param	string 		name
     * @param 	integer		Start year (yyyy)
     * @param 	integer		Start month (mm)
     * @param 	integer		Start day (dd)
     * @param 	integer		End year (yyyy)
     * @param 	integer		End month (mm)
     * @param 	integer		End day (dd)
     * @param 	integer		Number of days that the coach can access the session before the start date
     * @param 	integer		Number of days that the coach can access the session after the end date
     * @param 	integer		If 1, means there are no date limits
     * @param 	mixed		If integer, this is the session coach id, if string, the coach ID will be looked for from the user table
     * @param 	integer		ID of the session category in which this session is registered
     * @param  integer     Visibility after end date (0 = read-only, 1 = invisible, 2 = accessible)
     * @param  string      Start limit = true if the start date has to be considered
     * @param  string      End limit = true if the end date has to be considered
     * @param  string $fix_name
     * @todo use an array to replace all this parameters or use the model.lib.php ...
     * @return mixed       Session ID on success, error message otherwise
     * */
    public static function create_session(
        $sname,
        $syear_start,
        $smonth_start,
        $sday_start,
        $syear_end,
        $smonth_end,
        $sday_end,
        $snb_days_acess_before,
        $snb_days_acess_after,
        $nolimit,
        $coach_username,
        $id_session_category,
        $id_visibility,
        $start_limit = true,
        $end_limit = true,
        $fix_name = false
    ) {
        global $_configuration;

        //Check portal limits
        $access_url_id = 1;
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
        }
        if (is_array($_configuration[$access_url_id]) && isset($_configuration[$access_url_id]['hosting_limit_sessions']) && $_configuration[$access_url_id]['hosting_limit_sessions'] > 0) {
            $num = self::count_sessions();
            if ($num >= $_configuration[$access_url_id]['hosting_limit_sessions']) {
                return get_lang('PortalSessionsLimitReached');
            }
        }

        $name = Database::escape_string(trim($sname));
        $year_start = intval($syear_start);
        $month_start = intval($smonth_start);
        $day_start = intval($sday_start);
        $year_end = intval($syear_end);
        $month_end = intval($smonth_end);
        $day_end = intval($sday_end);
        $nb_days_acess_before = intval($snb_days_acess_before);
        $nb_days_acess_after = intval($snb_days_acess_after);
        $id_session_category = intval($id_session_category);
        $id_visibility = intval($id_visibility);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        if (is_int($coach_username)) {
            $id_coach = $coach_username;
        } else {
            $sql = 'SELECT user_id FROM ' . $tbl_user . ' WHERE username="' . Database::escape_string($coach_username) . '"';
            $rs = Database::query($sql);
            $id_coach = Database::result($rs, 0, 'user_id');
        }

        if (empty($nolimit)) {
            $date_start = "$year_start-" . (($month_start < 10) ? "0$month_start" : $month_start) . "-" . (($day_start < 10) ? "0$day_start" : $day_start);
            $date_end = "$year_end-" . (($month_end < 10) ? "0$month_end" : $month_end) . "-" . (($day_end < 10) ? "0$day_end" : $day_end);
        } else {
            $id_visibility = 1; // by default session visibility is read only
            $date_start = "0000-00-00";
            $date_end = "0000-00-00";
        }

        if (empty($end_limit)) {
            $date_end = "0000-00-00";
            $id_visibility = 1; // by default session visibility is read only
        }

        if (empty($start_limit)) {
            $date_start = "0000-00-00";
        }

        if (empty($name)) {
            $msg = get_lang('SessionNameIsRequired');
            return $msg;
        } elseif (empty($coach_username)) {
            $msg = get_lang('CoachIsRequired');
            return $msg;
        } elseif (!empty($start_limit) && empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start, $day_start, $year_start))) {
            $msg = get_lang('InvalidStartDate');
            return $msg;
        } elseif (!empty($end_limit) && empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end, $day_end, $year_end))) {
            $msg = get_lang('InvalidEndDate');
            return $msg;
        } elseif (!empty($start_limit) && !empty($end_limit) && empty($nolimit) && $date_start >= $date_end) {
            $msg = get_lang('StartDateShouldBeBeforeEndDate');
            return $msg;
        } else {
            $ready_to_create = false;
            if ($fix_name) {
                $name = self::generate_nice_next_session_name($name);
                if ($name) {
                    $ready_to_create = true;
                } else {
                    $msg = get_lang('SessionNameAlreadyExists');
                    return $msg;
                }
            } else {
                $rs = Database::query("SELECT 1 FROM $tbl_session WHERE name='" . $name . "'");
                if (Database::num_rows($rs)) {
                    $msg = get_lang('SessionNameAlreadyExists');
                    return $msg;
                }
                $ready_to_create = true;
            }

            if ($ready_to_create) {
                $sql_insert = "INSERT INTO $tbl_session(name,date_start,date_end,id_coach,session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end, session_category_id,visibility)
							   VALUES('" . $name . "','$date_start','$date_end','$id_coach'," . api_get_user_id() . "," . $nb_days_acess_before . ", " . $nb_days_acess_after . ", " . $id_session_category . ", " . $id_visibility . ")";
                Database::query($sql_insert);
                $session_id = Database::insert_id();

                if (!empty($session_id)) {
                    /*
                      Sends a message to the user_id = 1

                      $user_info = api_get_user_info(1);
                      $complete_name = $user_info['firstname'].' '.$user_info['lastname'];
                      $subject = api_get_setting('siteName').' - '.get_lang('ANewSessionWasCreated');
                      $message = get_lang('ANewSessionWasCreated')." <br /> ".get_lang('NameOfTheSession').' : '.$name;
                      api_mail_html($complete_name, $user_info['email'], $subject, $message);
                     *
                     */
                    //Adding to the correct URL
                    $access_url_id = api_get_current_access_url_id();
                    UrlManager::add_session_to_url($session_id, $access_url_id);

                    // add event to system log
                    $user_id = api_get_user_id();
                    event_system(LOG_SESSION_CREATE, LOG_SESSION_ID, $session_id, api_get_utc_datetime(), $user_id);
                }
                return $session_id;
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function session_name_exists($name)
    {
        $name = Database::escape_string($name);
        $sql = "SELECT COUNT(*) as count FROM " . Database::get_main_table(TABLE_MAIN_SESSION) . "
                WHERE name = '$name'";
        $result = Database::fetch_array(Database::query($sql));
        return $result['count'] > 0;
    }

    /**
     * @param string $where_condition
     * @return mixed
     */
    public static function get_count_admin($where_condition = null)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_access_url_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $where = 'WHERE 1=1 ';
        $user_id = api_get_user_id();

        $extraJoin = null;

        if (api_is_session_admin() &&
            api_get_setting('allow_session_admins_to_manage_all_sessions') == 'false'
        ) {
            $where .= " AND (
                            s.session_admin_id = $user_id  OR
                            sru.id_user = '$user_id' AND
                            sru.relation_type = '" . SESSION_RELATION_TYPE_RRHH . "'
                            )
                      ";

            $extraJoin = " INNER JOIN $tbl_session_rel_user sru
                            ON sru.id_session = s.id ";
        }

        $today = api_get_utc_datetime();
        $today = api_strtotime($today, 'UTC');
        $today = date('Y-m-d', $today);

        if (!empty($where_condition)) {
            $where_condition = str_replace("(  session_active = ':'  )", '1=1', $where_condition);

            $where_condition = str_replace('category_name', 'sc.name', $where_condition);
            $where_condition = str_replace(
                array("AND session_active = '1'  )", " AND (  session_active = '1'  )"),
                array(') GROUP BY s.name HAVING session_active = 1 ', " GROUP BY s.name HAVING session_active = 1 " )
                , $where_condition
            );
            $where_condition = str_replace(
                array("AND session_active = '0'  )", " AND (  session_active = '0'  )"),
                array(') GROUP BY s.name HAVING session_active = 0 ', " GROUP BY s.name HAVING session_active = '0' "),
                $where_condition
            );
        } else {
            $where_condition = " AND 1 = 1";
        }

        $courseCondition = null;
        if (strpos($where_condition, 'c.id')) {
            $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
            $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
            $courseCondition = " INNER JOIN $table course_rel_session ON (s.id = course_rel_session.id_session)
                                 INNER JOIN $tableCourse c ON (course_rel_session.course_code = c.code)
                                ";
        }

        $sql = "SELECT COUNT(id) as total_rows FROM (
                SELECT DISTINCT
                 IF (
					(s.date_start <= '$today' AND '$today' <= s.date_end) OR
                    (s.nb_days_access_before_beginning > 0 AND DATEDIFF(s.date_start, '$today') <= s.nb_days_access_before_beginning) OR
                    (s.nb_days_access_after_end > 0 AND DATEDIFF('$today',s.date_end) <= s.nb_days_access_after_end) OR
                    (s.date_start  = '0000-00-00' AND s.date_end  = '0000-00-00' ) OR
					(s.date_start <= '$today' AND '0000-00-00' = s.date_end) OR
					('$today' <= s.date_end AND '0000-00-00' = s.date_start)
				, 1, 0) as session_active,
                s.id
                FROM $tbl_session s
                    LEFT JOIN  $tbl_session_category sc ON s.session_category_id = sc.id
                    INNER JOIN $tbl_user u ON s.id_coach = u.user_id
                    $courseCondition
                    $extraJoin
                $where $where_condition ) as session_table";

        if (api_is_multiple_url_enabled()) {

            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $where.= " AND ar.access_url_id = $access_url_id ";

                $sql = "SELECT count(id) as total_rows FROM (
                SELECT DISTINCT
                  IF (
					(s.date_start <= '$today' AND '$today' <= s.date_end) OR
                    (s.nb_days_access_before_beginning > 0 AND DATEDIFF(s.date_start, '$today') <= s.nb_days_access_before_beginning) OR
                    (s.nb_days_access_after_end > 0 AND DATEDIFF('$today',s.date_end) <= s.nb_days_access_after_end) OR
                    (s.date_start  = '0000-00-00' AND s.date_end  = '0000-00-00' ) OR
					(s.date_start <= '$today' AND '0000-00-00' = s.date_end) OR
					('$today' <= s.date_end AND '0000-00-00' = s.date_start)
				, 1, 0)
				as session_active,
				s.id
                FROM $tbl_session s
                    LEFT JOIN  $tbl_session_category sc ON s.session_category_id = sc.id
                    INNER JOIN $tbl_user u ON s.id_coach = u.user_id
                    INNER JOIN $table_access_url_rel_session ar ON ar.session_id = s.id
                    $courseCondition
                    $extraJoin
                $where $where_condition) as session_table";
            }
        }

        $result_rows = Database::query($sql);
        $row = Database::fetch_array($result_rows);
        $num = $row['total_rows'];

        return $num;
    }

    /**
     * Gets the admin session list callback of the admin/session_list.php page
     * @param array $options order and limit keys
     * @return array
     */
    public static function get_sessions_admin($options)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $table_access_url_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        $where = ' WHERE 1=1 ';
        $user_id = api_get_user_id();

        $extraJoin = null;

        if (api_is_session_admin() && api_get_setting('allow_session_admins_to_manage_all_sessions') == 'false') {
            $where .= " AND (
                            s.session_admin_id = $user_id  OR
                            sru.id_user = '$user_id' AND
                            sru.relation_type = '" . SESSION_RELATION_TYPE_RRHH . "'
                            )
                      ";

            $extraJoin = " INNER JOIN $tbl_session_rel_user sru
                            ON sru.id_session = s.id ";
        }

        if (api_is_allowed_to_edit() && !api_is_platform_admin()) {
            $where.=" AND s.id_coach = $user_id ";
        }

        $coach_name = " CONCAT(u.lastname , ' ', u.firstname) as coach_name ";

        if (api_is_western_name_order()) {
            $coach_name = " CONCAT(u.firstname, ' ', u.lastname) as coach_name ";
        }

        $options['where'] = str_replace(
            array("AND session_active = '1'  )", " AND (  session_active = '1'  )"), array(') GROUP BY s.name HAVING session_active = 1 ', " GROUP BY s.name HAVING session_active = 1 ")
            , $options['where']
        );

        $options['where'] = str_replace("(  session_active = ':'  )", '1=1', $options['where']);

        $options['where'] = str_replace(
            array("AND session_active = '0'  )", " AND (  session_active = '0'  )"),
            array(') GROUP BY s.name HAVING session_active = 0 ', " GROUP BY s.name HAVING session_active = '0' "),
            $options['where']
        );

        $today = api_get_utc_datetime();
        $today = api_strtotime($today, 'UTC');
        $today = date('Y-m-d', $today);

        $courseCondition = null;
        if (strpos($options['where'], 'c.id')) {
            $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
            $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
            $courseCondition = " INNER JOIN $table course_rel_session ON (s.id = course_rel_session.id_session)
                                 INNER JOIN $tableCourse c ON (course_rel_session.course_code = c.code)
                                ";
        }

        $select = "SELECT * FROM (SELECT
                IF (
					(s.date_start <= '$today' AND '$today' <= s.date_end) OR
                    (s.nb_days_access_before_beginning > 0 AND DATEDIFF(s.date_start,'" . $today . "' " . ") <= s.nb_days_access_before_beginning) OR
                    (s.nb_days_access_after_end > 0 AND DATEDIFF('" . $today . "',s.date_end) <= s.nb_days_access_after_end) OR
                    (s.date_start  = '0000-00-00' AND s.date_end  = '0000-00-00' ) OR
					(s.date_start <= '$today' AND '0000-00-00' = s.date_end) OR
					('$today' <= s.date_end AND '0000-00-00' = s.date_start)
				, 1, 0)
				as session_active,
				s.name,
                nbr_courses,
                s.nbr_users,
                s.date_start,
                s.date_end,
                $coach_name,
                sc.name as category_name,
                s.visibility,
                u.user_id,
                s.id";

        $limit = null;
        if (!empty($options['limit'])) {
            $limit = " LIMIT " . $options['limit'];
        }

        if (!empty($options['where'])) {
            $where .= ' '.$options['where'];
        }

        $order = null;
        if (!empty($options['order'])) {
            $order = " ORDER BY " . $options['order'];
        }

        $query = "$select FROM $tbl_session s
				LEFT JOIN $tbl_session_category sc ON s.session_category_id = sc.id
				LEFT JOIN $tbl_user u ON s.id_coach = u.user_id
				$courseCondition
				$extraJoin
                $where $order $limit";

        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $query = " $select
                           FROM $tbl_session s
                               LEFT JOIN  $tbl_session_category sc ON s.session_category_id = sc.id
                               LEFT JOIN $tbl_user u ON s.id_coach = u.user_id
                               INNER JOIN $table_access_url_rel_session ar ON ar.session_id = s.id AND ar.access_url_id = $access_url_id
                               $courseCondition
                               $extraJoin
				 $where $order $limit";
            }
        }

        $query .= ") AS session_table";

        $result = Database::query($query);
        $formatted_sessions = array();
        if (Database::num_rows($result)) {
            $sessions = Database::store_result($result);
            foreach ($sessions as $session) {
                $session['name'] = Display::url($session['name'], "resume_session.php?id_session=" . $session['id']);
                $session['coach_name'] = Display::url($session['coach_name'], "user_information.php?user_id=" . $session['user_id']);

                if ($session['date_start'] == '0000-00-00' && $session['date_end'] == '0000-00-00') {
                    //    $session['session_active'] = 1;
                }

                if ($session['session_active'] == 1) {
                    $session['session_active'] = Display::return_icon('accept.png', get_lang('Active'), array(), ICON_SIZE_SMALL);
                } else {
                    $session['session_active'] = Display::return_icon('error.png', get_lang('Inactive'), array(), ICON_SIZE_SMALL);
                }

                if ($session['date_start'] == '0000-00-00') {
                    $session['date_start'] = '';
                }
                if ($session['date_end'] == '0000-00-00') {
                    $session['date_end'] = '';
                }

                switch ($session['visibility']) {
                    case SESSION_VISIBLE_READ_ONLY: //1
                        $session['visibility'] = get_lang('ReadOnly');
                        break;
                    case SESSION_VISIBLE:           //2
                        $session['visibility'] = get_lang('Visible');
                        break;
                    case SESSION_INVISIBLE:         //3
                        $session['visibility'] = api_ucfirst(get_lang('Invisible'));
                        break;
                }
                $formatted_sessions[] = $session;
            }
        }
        return $formatted_sessions;
    }

    /**
     *  Get total of records for progress of learning paths in the given session
     *  @param int session id
     *  @return int
     */
    public static function get_count_session_lp_progress($sessionId = 0)
    {
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        $tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

        $sql = "select  count(*) as total_rows
                FROM $tbl_lp_view v
                INNER JOIN $tbl_lp l ON l.id = v.lp_id
                INNER JOIN $tbl_user u ON u.user_id = v.user_id
                INNER JOIN $tbl_course c";
        $sql .= ' WHERE v.session_id = ' . $sessionId;
        $result_rows = Database::query($sql);
        $row = Database::fetch_array($result_rows);
        $num = $row['total_rows'];
        return $num;
    }

    /**
     * Gets the progress of learning paths in the given session
     * @param int   session id
     * @param array options order and limit keys
     * @return array table with user name, lp name, progress
     */
    public static function get_session_lp_progress($sessionId = 0, $courseId = 0, $date_from, $date_to, $options)
    {
        //escaping vars
        $sessionId = $sessionId == 'T' ? 'T' : intval($sessionId);
        $courseId = intval($courseId);
        $date_from = Database :: escape_string($date_from);
        $date_to = Database :: escape_string($date_to);

        //tables
        $session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_course_lp_view = Database::get_course_table(TABLE_LP_VIEW);

        $course = api_get_course_info_by_id($courseId);


        //getting all the students of the course
        //we are not using this because it only returns user ids
        /* if (empty($sessionId)
          {
          // Registered students in a course outside session.
          $users = CourseManager :: get_student_list_from_course_code($course_code);
          } else {
          // Registered students in session.
          $users = CourseManager :: get_student_list_from_course_code($course_code, true, $sessionId);
          } */

        $sessionCond = 'and id_session = %s';
        if ($sessionId == 'T') {
            $sessionCond = "";
        }

        $where = " WHERE course_code = '%s'
        AND s.status <> 2 $sessionCond";

        $limit = null;
        if (!empty($options['limit'])) {
            $limit = " LIMIT " . $options['limit'];
        }

        if (!empty($options['where'])) {
            $where .= ' '.$options['where'];
        }

        $order = null;
        if (!empty($options['order'])) {
            $order = " ORDER BY " . $options['order'];
        }

        $sql = "SELECT u.user_id, u.lastname, u.firstname, u.username, u.email, s.course_code
        FROM $session_course_user s
        INNER JOIN $user u ON u.user_id = s.id_user
        $where $order $limit";

        $sql_query = sprintf($sql, Database::escape_string($course['code']), $sessionId);

        $rs = Database::query($sql_query);
        while ($user = Database::fetch_array($rs)) {
            $users[$user['user_id']] = $user;
        }

        //Get lessons
        require_once api_get_path(SYS_CODE_PATH) . 'newscorm/learnpathList.class.php';
        $lessons = LearnpathList::get_course_lessons($course['code'], $sessionId);

        $table = array();
        foreach ($users as $user) {
            $data = array(
                'lastname' => $user[1],
                'firstname' => $user[2],
                'username' => $user[3],
            );

            $sessionCond = 'AND v.session_id = %d';
            if ($sessionId == 'T') {
                $sessionCond = "";
            }

            //Get lessons progress by user
            $sql = "SELECT v.lp_id as id, v.progress
                    FROM  $tbl_course_lp_view v
                    WHERE v.c_id = %d
                    AND v.user_id = %d
            $sessionCond";

            $sql_query = sprintf($sql,
                intval($courseId),
                Database::escape_string($user['user_id']),
                $sessionId
            );

            $result = Database::query($sql_query);

            $user_lessons = array();
            while ($row = Database::fetch_array($result)) {
                $user_lessons[$row['id']] = $row;
            }

            //Match course lessons with user progress
            $progress = 0;
            $count = 0;
            foreach ($lessons as $lesson) {
                $data[$lesson['id']] = (!empty($user_lessons[$lesson['id']]['progress'])) ? $user_lessons[$lesson['id']]['progress'] : 0;
                $progress += $data[$lesson['id']];
                $data[$lesson['id']] = $data[$lesson['id']] . '%';
                $count++;
            }
            if ($count == 0) {
                $data['total'] = 0;
            } else {
                $data['total'] = round($progress / $count, 2) . '%';
            }
            $table[] = $data;
        }
        return $table;
    }

    /**
     * Gets the survey answers
     * @param int   session id
     * @param int   course id
     * @param int   survey id
     * @param array options order and limit keys
     * @todo fix the query
     * @return array table with user name, lp name, progress
     */
    public static function get_survey_overview($sessionId = 0, $courseId = 0, $surveyId = 0, $date_from, $date_to, $options)
    {
        //escaping vars
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);
        $surveyId = intval($surveyId);
        $date_from = Database::escape_string($date_from);
        $date_to = Database::escape_string($date_to);

        //tables
        $session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_course_lp_view = Database::get_course_table(TABLE_LP_VIEW);
        $c_survey = Database::get_course_table(TABLE_SURVEY);
        $c_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $c_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $c_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        $course = api_get_course_info_by_id($courseId);

        $where = " WHERE course_code = '%s' AND s.status <> 2 AND id_session = %s";

        $limit = null;
        if (!empty($options['limit'])) {
            $limit = " LIMIT " . $options['limit'];
        }

        if (!empty($options['where'])) {
            $where .= ' '.$options['where'];
        }

        $order = null;
        if (!empty($options['order'])) {
            $order = " ORDER BY " . $options['order'];
        }

        /* $where_survey = '';
          if (!empty($date_to) && !empty($date_from)) {
          $where_survey = sprintf(" AND s.avail_from >= '%s'
          AND s.avail_till <= '%s'", $date_from, $date_to);
          } */

        $sql = "SELECT u.user_id, u.lastname, u.firstname, u.username, u.email, s.course_code
                FROM $session_course_user s
                INNER JOIN $user u ON u.user_id = s.id_user
                $where $order $limit";

        $sql_query = sprintf($sql, Database::escape_string($course['code']), $sessionId);
        $rs = Database::query($sql_query);
        while ($user = Database::fetch_array($rs)) {
            $users[$user['user_id']] = $user;
        }

        //Get survey questions
        $questions = survey_manager::get_questions($surveyId, $courseId);

        //Survey is anonymous?
        $result = Database::query(sprintf("SELECT anonymous FROM $c_survey WHERE survey_id = %d", $surveyId));
        $row = Database::fetch_array($result);
        $anonymous = ($row['anonymous'] == 1) ? true : false;

        $table = array();
        foreach ($users as $user) {
            $data = array(
                'lastname' => ($anonymous ? '***' : $user[1]),
                'firstname' => ($anonymous ? '***' : $user[2]),
                'username' => ($anonymous ? '***' : $user[3]),
            );

            //Get questions by user
            $sql = "SELECT sa.question_id, sa.option_id, sqo.option_text, sq.type
                    FROM $c_survey_answer sa
                    INNER JOIN $c_survey_question sq
                    ON sq.question_id = sa.question_id
                    LEFT JOIN $c_survey_question_option sqo
                    ON
                      sqo.c_id = sa.c_id AND
                      sqo.question_id = sq.question_id AND
                      sqo.question_option_id = sa.option_id AND
                      sqo.survey_id = sq.survey_id
                    WHERE
                      sa.survey_id = %d AND
                      sa.c_id = %d AND
                      sa.user = %d
            "; //. $where_survey;
            $sql_query = sprintf($sql, $surveyId, $courseId, $user['user_id']);

            $result = Database::query($sql_query);

            $user_questions = array();
            while ($row = Database::fetch_array($result)) {
                $user_questions[$row['question_id']] = $row;
            }

            //Match course lessons with user progress
            foreach ($questions as $question_id => $question) {
                $option_text = 'option_text';
                if ($user_questions[$question_id]['type'] == 'open') {
                    $option_text = 'option_id';
                }
                $data[$question_id] = $user_questions[$question_id][$option_text];
            }

            $table[] = $data;
        }
        return $table;
    }

    /**
     * Gets the progress of the given session
     * @param int   session id
     * @param array options order and limit keys
     * @return array table with user name, lp name, progress
     */
    public static function get_session_progress($sessionId, $courseId, $date_from, $date_to, $options)
    {
        $sessionId = intval($sessionId);

        $getAllSessions = false;
        if (empty($sessionId)) {
            $sessionId = 0;
            $getAllSessions = true;
        }

        //tables
        $session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $workTableAssignment = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
        $forum = Database::get_course_table(TABLE_FORUM);
        $forum_post = Database::get_course_table(TABLE_FORUM_POST);
        $tbl_course_lp = Database::get_course_table(TABLE_LP_MAIN);
        $wiki = Database::get_course_table(TABLE_WIKI);
        $table_stats_default = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $table_stats_access = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);

        $course = api_get_course_info_by_id($courseId);

        //getting all the students of the course
        //we are not using this because it only returns user ids
        /* if (empty($sessionId)
          {
          // Registered students in a course outside session.
          $users = CourseManager :: get_student_list_from_course_code($course_code);
          } else {
          // Registered students in session.
          $users = CourseManager :: get_student_list_from_course_code($course_code, true, $sessionId);
          } */
        $where = " WHERE course_code = '%s' AND s.status <> 2 ";

        $limit = null;
        if (!empty($options['limit'])) {
            $limit = " LIMIT " . $options['limit'];
        }

        if (!empty($options['where'])) {
            $where .= ' '.$options['where'];
        }

        $order = null;
        if (!empty($options['order'])) {
            $order = " ORDER BY " . $options['order'];
        }

        //TODO, fix create report without session
        $queryVariables = array($course['code']);
        if (!empty($sessionId)) {
            $where .= ' AND id_session = %s';
            $queryVariables[] = $sessionId;
            $sql = "SELECT
                      u.user_id, u.lastname, u.firstname, u.username,
                      u.email, s.course_code, s.id_session
                    FROM $session_course_user s
                    INNER JOIN $user u
                    ON u.user_id = s.id_user
                    $where $order $limit";
        } else {
            $sql = "SELECT
                        u.user_id, u.lastname, u.firstname, u.username,
                        u.email, s.course_code, s.id_session
                    FROM $session_course_user s
                    INNER JOIN $user u ON u.user_id = s.id_user
                    $where $order $limit";
        }

        $sql_query = vsprintf($sql, $queryVariables);
        $rs = Database::query($sql_query);
        while ($user = Database::fetch_array($rs)) {
            $users[$user['user_id']] = $user;
        }

        /**
         *  Lessons
         */
        $sql = "SELECT * FROM $tbl_course_lp WHERE c_id = %s ";  //AND session_id = %s
        $sql_query = sprintf($sql, $course['real_id']);
        $result = Database::query($sql_query);
        $arrLesson = array(array());
        while ($row = Database::fetch_array($result)) {
//            if (api_get_item_visibility(api_get_course_info($course['code']), 'learnpath', $row['id'], $row['session_id']))
//            {
            if (empty($arrLesson[$row['session_id']]['lessons_total'])) {
                $arrLesson[$row['session_id']]['lessons_total'] = 1;
            } else {
                $arrLesson[$row['session_id']]['lessons_total'] ++;
            }
//            }
        }

        /**
         *  Exercises
         */
        require_once api_get_path(SYS_CODE_PATH) . 'exercice/exercise.lib.php';
        $exercises = get_all_exercises($course, $sessionId, false, '', $getAllSessions);
        $exercises_total = count($exercises);

        /**
         *  Assignments
         */
        //total
        if ($getAllSessions) {
            $sql = "SELECT count(w.id) as count
            FROM $workTable w
            LEFT JOIN  $workTableAssignment a ON (a.publication_id = w.id AND a.c_id = w.c_id)
            WHERE w.c_id = %s
            AND parent_id = 0
            AND active IN (1, 0)";
        } else {
            $sql = "SELECT count(w.id) as count
            FROM $workTable w
            LEFT JOIN  $workTableAssignment a ON (a.publication_id = w.id AND a.c_id = w.c_id)
            WHERE w.c_id = %s
            AND parent_id = 0
            AND active IN (1, 0)
            AND  session_id = %s";
        }

        $sql_query = sprintf($sql, $course['real_id'], $sessionId);
        $result = Database::query($sql_query);
        $row = Database::fetch_array($result);
        $assignments_total = $row['count'];

        /**
         * Wiki
         */
        if ($getAllSessions) {
            $sql = "SELECT count(distinct page_id)  as count FROM $wiki
                WHERE c_id = %s";
        } else {
            $sql = "SELECT count(distinct page_id)  as count FROM $wiki
                WHERE c_id = %s and session_id = %s";
        }
        $sql_query = sprintf($sql, $course['real_id'], $sessionId);
        $result = Database::query($sql_query);
        $row = Database::fetch_array($result);
        $wiki_total = $row['count'];

        /**
         * Surveys
         */
        $survey_user_list = array();
        $survey_list = survey_manager::get_surveys($course['code'], $sessionId);

        $surveys_total = count($survey_list);
        $survey_data = array();
        foreach ($survey_list as $survey) {
            $user_list = survey_manager::get_people_who_filled_survey($survey['survey_id'], false, $course['real_id']);
            foreach ($user_list as $user_id) {
                isset($survey_user_list[$user_id]) ? $survey_user_list[$user_id] ++ : $survey_user_list[$user_id] = 1;
            }
        }
        /**
         * Forums
         */
        //total
        if ($getAllSessions) {
            $sql = "SELECT count(*) as count
            FROM $forum f
            where f.c_id = %s";
        } else {
            $sql = "SELECT count(*) as count
            FROM $forum f
            where f.c_id = %s and f.session_id = %s";
        }
        $sql_query = sprintf($sql, $course['real_id'], $sessionId);
        $result = Database::query($sql_query);
        $row = Database::fetch_array($result);
        $forums_total = $row['count'];

        //process table info
        foreach ($users as $user) {
            //Course description
            $sql = "SELECT count(*) as count
            FROM $table_stats_access
            WHERE access_tool = 'course_description'
            AND access_cours_code = '%s'
            AND access_session_id = %s
            AND access_user_id = %s ";
            $sql_query = sprintf($sql, $course['code'], $user['id_session'], $user['user_id']);

            $result = Database::query($sql_query);
            $row = Database::fetch_array($result);
            $course_description_progress = ($row['count'] > 0) ? 100 : 0;

            if (!empty($arrLesson[$user['id_session']]['lessons_total'])) {
                $lessons_total = $arrLesson[$user['id_session']]['lessons_total'];
            } else {
                $lessons_total = !empty($arrLesson[0]['lessons_total']) ? $arrLesson[0]['lessons_total'] : 0;
            }

            //Lessons
            //TODO: Lessons done and left is calculated by progress per item in lesson, maybe we should calculate it only per completed lesson?
            $lessons_progress = Tracking::get_avg_student_progress($user['user_id'], $course['code'], array(), $user['id_session']);
            $lessons_done = ($lessons_progress * $lessons_total) / 100;
            $lessons_left = $lessons_total - $lessons_done;

            //Exercises
            $exercises_progress = str_replace('%', '', Tracking::get_exercise_student_progress($exercises, $user['user_id'], $course['code'], $user['id_session']));
            $exercises_done = round(($exercises_progress * $exercises_total) / 100);
            $exercises_left = $exercises_total - $exercises_done;

            //Assignments
            $assignments_done = Tracking::count_student_assignments($user['user_id'], $course['code'], $user['id_session']);
            $assignments_left = $assignments_total - $assignments_done;
            if (!empty($assignments_total)) {
                $assignments_progress = round((( $assignments_done * 100 ) / $assignments_total), 2);
            } else {
                $assignments_progress = 0;
            }


            //Wiki
            //total revisions per user
            $sql = "SELECT count(*) as count
            FROM $wiki
            where c_id = %s and session_id = %s and user_id = %s";
            $sql_query = sprintf($sql, $course['real_id'], $user['id_session'], $user['user_id']);
            $result = Database::query($sql_query);
            $row = Database::fetch_array($result);
            $wiki_revisions = $row['count'];
            //count visited wiki pages
            $sql = "SELECT count(distinct default_value) as count
            FROM $table_stats_default
            WHERE default_user_id = %s
            AND default_cours_code = '%s'
            AND default_event_type = 'wiki_page_view'
            AND default_value_type = 'wiki_page_id'
            AND c_id = %s";
            $sql_query = sprintf($sql, $user['user_id'], $course['code'], $course['real_id']);
            $result = Database::query($sql_query);
            $row = Database::fetch_array($result);

            $wiki_read = $row['count'];
            $wiki_unread = $wiki_total - $wiki_read;
            if (!empty($wiki_total)) {
                $wiki_progress = round((( $wiki_read * 100 ) / $wiki_total), 2);
            } else {
                $wiki_progress = 0;
            }


            //Surveys
            $surveys_done = (isset($survey_user_list[$user['user_id']]) ? $survey_user_list[$user['user_id']] : 0);
            $surveys_left = $surveys_total - $surveys_done;
            if (!empty($surveys_total)) {
                $surveys_progress = round((( $surveys_done * 100 ) / $surveys_total), 2);
            } else {
                $surveys_progress = 0;
            }

            //Forums
            #$forums_done = Tracking::count_student_messages($user['user_id'], $course_code, $session_id);
            $sql = "SELECT count(distinct f.forum_id) as count FROM $forum_post p
            INNER JOIN $forum f ON f.forum_id = p.forum_id
            WHERE p.poster_id = %s and f.session_id = %s and p.c_id = %s";
            $sql_query = sprintf($sql, $user['user_id'], $user['id_session'], $course['real_id']);

            $result = Database::query($sql_query);
            $row = Database::fetch_array($result);

            $forums_done = $row['count'];
            $forums_left = $forums_total - $forums_done;
            if (!empty($forums_total)) {
                $forums_progress = round((( $forums_done * 100 ) / $forums_total), 2);
            } else {
                $forums_progress = 0;
            }

            //Overall Total
            $overall_total = ($course_description_progress + $exercises_progress + $forums_progress + $assignments_progress + $wiki_progress + $surveys_progress) / 6;

            $link = '<a href="' . api_get_path(WEB_CODE_PATH) . 'mySpace/myStudents.php?student=' . $user[0] . '&details=true&course=' . $course['code'] . '&id_session=' . $user['id_session'] . '"> %s </a>';
            $linkForum = '<a href="' . api_get_path(WEB_CODE_PATH) . 'forum/index.php?cidReq=' . $course['code'] . '&id_session=' . $user['id_session'] . '"> %s </a>';
            $linkWork = '<a href="' . api_get_path(WEB_CODE_PATH) . 'work/work.php?cidReq=' . $course['code'] . '&id_session=' . $user['id_session'] . '"> %s </a>';
            $linkWiki = '<a href="' . api_get_path(WEB_CODE_PATH) . 'wiki/index.php?cidReq=' . $course['code'] . '&session_id=' . $user['id_session'] . '&action=statistics"> %s </a>';
            $linkSurvey = '<a href="' . api_get_path(WEB_CODE_PATH) . 'survey/survey_list.php?cidReq=' . $course['code'] . '&id_session=' . $user['id_session'] . '"> %s </a>';

            $table[] = array(
                'lastname' => $user[1],
                'firstname' => $user[2],
                'username' => $user[3],
                #'profile'   => '',
                'total' => round($overall_total, 2) . '%',
                'courses' => sprintf($link, $course_description_progress . '%'),
                'lessons' => sprintf($link, $lessons_progress . '%'),
                'exercises' => sprintf($link, $exercises_progress . '%'),
                'forums' => sprintf($link, $forums_progress . '%'),
                'homeworks' => sprintf($link, $assignments_progress . '%'),
                'wikis' => sprintf($link, $wiki_progress . '%'),
                'surveys' => sprintf($link, $surveys_progress . '%'),
                //course description
                'course_description_progress' => $course_description_progress . '%',
                //lessons
                'lessons_total' => sprintf($link, $lessons_total),
                'lessons_done' => sprintf($link, $lessons_done),
                'lessons_left' => sprintf($link, $lessons_left),
                'lessons_progress' => sprintf($link, $lessons_progress . '%'),
                //exercises
                'exercises_total' => sprintf($link, $exercises_total),
                'exercises_done' => sprintf($link, $exercises_done),
                'exercises_left' => sprintf($link, $exercises_left),
                'exercises_progress' => sprintf($link, $exercises_progress . '%'),
                //forums
                'forums_total' => sprintf($linkForum, $forums_total),
                'forums_done' => sprintf($linkForum, $forums_done),
                'forums_left' => sprintf($linkForum, $forums_left),
                'forums_progress' => sprintf($linkForum, $forums_progress . '%'),
                //assignments
                'assignments_total' => sprintf($linkWork, $assignments_total),
                'assignments_done' => sprintf($linkWork, $assignments_done),
                'assignments_left' => sprintf($linkWork, $assignments_left),
                'assignments_progress' => sprintf($linkWork, $assignments_progress . '%'),
                //wiki
                'wiki_total' => sprintf($linkWiki, $wiki_total),
                'wiki_revisions' => sprintf($linkWiki, $wiki_revisions),
                'wiki_read' => sprintf($linkWiki, $wiki_read),
                'wiki_unread' => sprintf($linkWiki, $wiki_unread),
                'wiki_progress' => sprintf($linkWiki, $wiki_progress . '%'),
                //survey
                'surveys_total' => sprintf($linkSurvey, $surveys_total),
                'surveys_done' => sprintf($linkSurvey, $surveys_done),
                'surveys_left' => sprintf($linkSurvey, $surveys_left),
                'surveys_progress' => sprintf($linkSurvey, $surveys_progress . '%'),
            );
        }
        return $table;
    }

    function get_number_of_tracking_access_overview()
    {
        // database table definition
        $track_e_course_access = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        return Database::count_rows($track_e_course_access);
    }

    /**
     * Get the ip, total of clicks, login date and time logged in for all user, in one session
     * @todo track_e_course_access table should have ip so we dont have to look for it in track_e_login
     *
     * @author César Perales <cesar.perales@beeznest.com>, Beeznest Team
     * @version Chamilo 1.9.6
     */
    static function get_user_data_access_tracking_overview(
        $sessionId,
        $courseId,
        $studentId = 0,
        $profile = '',
        $date_from = '',
        $date_to = '',
        $options
    ) {
        global $_configuration;

        //escaping variables
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);
        $studentId = intval($studentId);
        $profile = intval($profile);
        $date_from = Database::escape_string($date_from);
        $date_to = Database::escape_string($date_to);

        // database table definition
        $user = Database :: get_main_table(TABLE_MAIN_USER);
        $course = Database :: get_main_table(TABLE_MAIN_COURSE);
        $track_e_login = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $track_e_course_access = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sessionTable = Database :: get_main_table(TABLE_MAIN_SESSION);

        global $export_csv;
        if ($export_csv) {
            $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
        } else {
            $is_western_name_order = api_is_western_name_order();
        }

        if (isset($sessionId) && !empty($sessionId)) {
            $where = sprintf(" WHERE a.session_id = %d", $sessionId);
        }
        if (isset($courseId) && !empty($courseId)) {
            $where .= sprintf(" AND c.id = %d", $courseId);
        }
        if (isset($studentId) && !empty($studentId)) {
            $where .= sprintf(" AND u.user_id = %d", $studentId);
        }
        if (isset($profile) && !empty($profile)) {
            $where .= sprintf(" AND u.status = %d", $profile);
        }
        if (!empty($date_to) && !empty($date_from)) {
            $where .= sprintf(
                " AND a.login_course_date >= '%s 00:00:00'
                 AND a.login_course_date <= '%s 23:59:59'",
                $date_from,
                $date_to
            );
        }

        $limit = null;
        if (!empty($options['limit'])) {
            $limit = " LIMIT " . $options['limit'];
        }

        if (!empty($options['where'])) {
            $where .= ' '.$options['where'];
        }

        $order = null;
        if (!empty($options['order'])) {
            $order = " ORDER BY " . $options['order'];
        }

        //TODO add course name
        $sql = "SELECT
                a.login_course_date ,
                u.username ,
                " . ($is_western_name_order ? "
                    u.firstname,
                    u.lastname,
                    " : "
                    u.lastname,
                    u.firstname,
                ") . "

                a.logout_course_date,
                a.counter,
                c.title,
                c.code,
                u.user_id,
                a.session_id
            FROM $track_e_course_access a
            INNER JOIN $user u ON a.user_id = u.user_id
            INNER JOIN $course c ON a.course_code = c.code
            $where $order $limit";
        $result = Database::query(sprintf($sql, $sessionId, $courseId));

        $data = array();
        while ($user = Database::fetch_assoc($result)) {
            $data[] = $user;
        }

        //foreach
        foreach ($data as $key => $info) {

            $sql = "SELECT
                    name
                    FROM $sessionTable
                    WHERE
                    id = {$info['session_id']}";
            $result = Database::query($sql);
            $session = Database::fetch_assoc($result);

            //We are not using this becaouse the range its to small and no other date match the condition of this function
            //$clicks = Tracking::get_total_clicks($info['user_id'], $courseId, $sessionId, $info['login_course_date'], $info['logout_course_date']);
            #building array to display
            $return[] = array(
                'user_id' => $info['user_id'],
                'logindate' => $info['login_course_date'],
                'username' => $info['username'],
                'firstname' => $info['firstname'],
                'lastname' => $info['lastname'],
                'clicks' => $info['counter'], //+ $clicks[$info['user_id']],
                'ip' => '',
                'timeLoggedIn' => gmdate("H:i:s", strtotime($info['logout_course_date']) - strtotime($info['login_course_date'])),
                'session' => $session['name']);
        }

        foreach ($return as $key => $info) {
            //Search for ip, we do less querys if we iterate the final array
            $sql = sprintf("SELECT login_ip FROM $track_e_login WHERE login_user_id = %d AND login_date < '%s' ORDER BY login_date DESC LIMIT 1", $info['user_id'], $info['logindate']); //TODO add select by user too
            $result = Database::query($sql);
            $ip = Database::fetch_assoc($result);
            //if no ip founded, we search the closest higher ip
            if (empty($ip['login_ip'])) {
                $sql = sprintf("SELECT login_ip FROM $track_e_login WHERE login_user_id = %d AND login_date > '%s'  ORDER BY login_date ASC LIMIT 1", $info['user_id'], $info['logindate']); //TODO add select by user too
                $result = Database::query($sql);
                $ip = Database::fetch_assoc($result);
            }
            #add ip to final array
            $return[$key]['ip'] = $ip['login_ip'];
        }
        return $return;
    }

    /**
     * Creates a new course code based in given code
     *
     * @param string	wanted code
     * <code>
     * $wanted_code = 'curse' if there are in the DB codes like curse1 curse2 the function will return: course3
     * if the course code doest not exist in the DB the same course code will be returned
     * </code>
     * @return string	wanted unused code
     */
    public static function generate_nice_next_session_name($session_name)
    {
        $session_name_ok = !self::session_name_exists($session_name);
        if (!$session_name_ok) {
            $table = Database::get_main_table(TABLE_MAIN_SESSION);
            $session_name = Database::escape_string($session_name);
            $sql = "SELECT count(*) as count FROM $table WHERE name LIKE '$session_name%'";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result);
                $count = $row['count'] + 1;
                $session_name = $session_name . '_' . $count;
                $result = self::session_name_exists($session_name);
                if (!$result) {
                    return $session_name;
                }
            }
            return false;
        }
        return $session_name;
    }

    /**
     * Edit a session
     * @author Carlos Vargas from existing code
     * @param	integer		id
     * @param	string 		name
     * @param 	integer		year_start
     * @param 	integer		month_start
     * @param 	integer		day_start
     * @param 	integer		year_end
     * @param 	integer		month_end
     * @param 	integer		day_end
     * @param 	integer		nb_days_acess_before
     * @param 	integer		nb_days_acess_after
     * @param 	integer		nolimit
     * @param 	integer		id_coach
     * @param 	integer		id_session_category
     * @param   int $id_visibility
     * @param bool
     * @param bool
     * @param string $description
     * @param int  $showDescription
     * @return $id;
     * The parameter id is a primary key
     * */
    public static function edit_session(
        $id, $name, $year_start, $month_start, $day_start, $year_end, $month_end, $day_end, $nb_days_acess_before, $nb_days_acess_after, $nolimit, $id_coach, $id_session_category, $id_visibility, $start_limit = true, $end_limit = true, $description = null, $showDescription = null
    )
    {
        $name = trim(stripslashes($name));
        $year_start = intval($year_start);
        $month_start = intval($month_start);
        $day_start = intval($day_start);
        $year_end = intval($year_end);
        $month_end = intval($month_end);
        $day_end = intval($day_end);
        $id_coach = intval($id_coach);
        $nb_days_acess_before = intval($nb_days_acess_before);
        $nb_days_acess_after = intval($nb_days_acess_after);
        $id_session_category = intval($id_session_category);
        $id_visibility = intval($id_visibility);

        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        if (empty($nolimit)) {
            $date_start = "$year_start-" . (($month_start < 10) ? "0$month_start" : $month_start) . "-" . (($day_start < 10) ? "0$day_start" : $day_start);
            $date_end = "$year_end-" . (($month_end < 10) ? "0$month_end" : $month_end) . "-" . (($day_end < 10) ? "0$day_end" : $day_end);
        } else {
            $date_start = "0000-00-00";
            $date_end = "0000-00-00";
            $id_visibility = 1; //force read only
        }

        if (!empty($no_end_limit)) {
            $date_end = "0000-00-00";
        }

        if (empty($end_limit)) {
            $date_end = "0000-00-00";
            $id_visibility = 1; //force read only
        }

        if (empty($start_limit)) {
            $date_start = "0000-00-00";
        }

        if (empty($name)) {
            $msg = get_lang('SessionNameIsRequired');
            return $msg;
        } elseif (empty($id_coach)) {
            $msg = get_lang('CoachIsRequired');
            return $msg;
        } elseif (!empty($start_limit) && empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start, $day_start, $year_start))) {
            $msg = get_lang('InvalidStartDate');
            return $msg;
        } elseif (!empty($end_limit) && empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end, $day_end, $year_end))) {
            $msg = get_lang('InvalidEndDate');
            return $msg;
        } elseif (!empty($start_limit) && !empty($end_limit) && empty($nolimit) && $date_start >= $date_end) {
            $msg = get_lang('StartDateShouldBeBeforeEndDate');
            return $msg;
        } else {

            $rs = Database::query("SELECT id FROM $tbl_session WHERE name='" . Database::escape_string($name) . "'");
            $exists = false;
            while ($row = Database::fetch_array($rs)) {
                if ($row['id'] != $id) {
                    $exists = true;
                }
            }

            if ($exists) {
                $msg = get_lang('SessionNameAlreadyExists');
                return $msg;
            } else {

                $sessionInfo = SessionManager::fetch($id);

                $descriptionCondition = null;
                if (array_key_exists('description', $sessionInfo)) {
                    $descriptionCondition = ' description = "' . Database::escape_string($description) . '" ,';
                }

                $showDescriptionCondition = null;
                if (array_key_exists('show_description', $sessionInfo)) {
                    $showDescriptionCondition = ' show_description = "' . Database::escape_string($showDescription) . '" ,';
                }

                $sql = "UPDATE $tbl_session " .
                    "SET name='" . Database::escape_string($name) . "',
						date_start='" . $date_start . "',
						date_end='" . $date_end . "',
						id_coach='" . $id_coach . "',
						nb_days_access_before_beginning = " . $nb_days_acess_before . ",
						nb_days_access_after_end = " . $nb_days_acess_after . ",
						session_category_id = " . $id_session_category . " ,
                        $descriptionCondition
                        $showDescriptionCondition
						visibility= " . $id_visibility . "
					  WHERE id='$id'";
                Database::query($sql);
                return $id;
            }
        }
    }

    /**
     * Delete session
     * @author Carlos Vargas  from existing code
     * @param	array	id_checked an array to delete sessions
     * @param   boolean  optional, true if the function is called by a webservice, false otherwise.
     * @return	void	Nothing, or false on error
     * */
    public static function delete_session($id_checked, $from_ws = false)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_url_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

        $userId = api_get_user_id();

        if (is_array($id_checked)) {
            $id_checked = Database::escape_string(implode(',', $id_checked));
        } else {
            $id_checked = intval($id_checked);
        }

        if (!api_is_platform_admin() && !$from_ws) {
            $sql = 'SELECT session_admin_id FROM ' . Database :: get_main_table(TABLE_MAIN_SESSION) . ' WHERE id=' . $id_checked;
            $rs = Database::query($sql);
            if (Database::result($rs, 0, 0) != $userId) {
                api_not_allowed(true);
            }
        }

        Database::query("DELETE FROM $tbl_session WHERE id IN($id_checked)");
        Database::query("DELETE FROM $tbl_session_rel_course WHERE id_session IN($id_checked)");
        Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session IN($id_checked)");
        Database::query("DELETE FROM $tbl_session_rel_user WHERE id_session IN($id_checked)");
        Database::query("DELETE FROM $tbl_url_session WHERE session_id IN($id_checked)");

        $sql_delete_sfv = "DELETE FROM $t_sfv WHERE session_id = '$id_checked'";
        Database::query($sql_delete_sfv);

        // Add event to system log
        event_system(LOG_SESSION_DELETE, LOG_SESSION_ID, $id_checked, api_get_utc_datetime(), $userId);
    }

    /**
     * @param int $id_promotion
     * @return bool
     */
    public static function clear_session_ref_promotion($id_promotion)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $id_promotion = intval($id_promotion);
        $update_sql = "UPDATE $tbl_session SET promotion_id=0 WHERE promotion_id='$id_promotion'";
        if (Database::query($update_sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Subscribes students to the given session and optionally (default) unsubscribes previous users
     *
     * @author Carlos Vargas from existing code
     * @author Julio Montoya. Cleaning code.
     * @param int $id_session
     * @param array $user_list
     * @param int $session_visibility
     * @param bool $empty_users
     * @param bool $send_email
     * @return bool
     */
    public static function suscribe_users_to_session(
        $id_session, $user_list, $session_visibility = SESSION_VISIBLE_READ_ONLY, $empty_users = true, $send_email = false
    )
    {

        if ($id_session != strval(intval($id_session))) {
            return false;
        }

        foreach ($user_list as $intUser) {
            if ($intUser != strval(intval($intUser))) {
                return false;
            }
        }

        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        $session_info = api_get_session_info($id_session);
        $session_name = $session_info['name'];

        // from function parameter
        if (empty($session_visibility)) {
            $session_visibility = $session_info['visibility']; //loaded from DB
            //default status loaded if empty
            if (empty($session_visibility))
                $session_visibility = SESSION_VISIBLE_READ_ONLY; // by default readonly 1
        } else {
            if (!in_array($session_visibility, array(SESSION_VISIBLE_READ_ONLY, SESSION_VISIBLE, SESSION_INVISIBLE))) {
                $session_visibility = SESSION_VISIBLE_READ_ONLY;
            }
        }

        $sql = "SELECT id_user FROM $tbl_session_rel_course_rel_user WHERE id_session = '$id_session' AND status = 0";
        $result = Database::query($sql);
        $existingUsers = array();
        while ($row = Database::fetch_array($result)) {
            $existingUsers[] = $row['id_user'];
        }

        $sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session = '$id_session'";
        $result = Database::query($sql);
        $course_list = array();
        while ($row = Database::fetch_array($result)) {
            $course_list[] = $row['course_code'];
        }

        if ($send_email) {
            // Sending emails only
            if (is_array($user_list) && count($user_list) > 0) {
                foreach ($user_list as $user_id) {
                    if (!in_array($user_id, $existingUsers)) {
                        $subject = '[' . get_setting('siteName') . '] ' . get_lang('YourReg') . ' ' . get_setting('siteName');
                        $user_info = api_get_user_info($user_id);
                        $content = get_lang('Dear') . " " . stripslashes($user_info['complete_name']) . ",\n\n" . sprintf(get_lang('YouAreRegisterToSessionX'), $session_name) . " \n\n" . get_lang('Address') . " " . get_setting('siteName') . " " . get_lang('Is') . " : " . api_get_path(WEB_PATH) . "\n\n" . get_lang('Problem') . "\n\n" . get_lang('Formula') . ",\n\n" . get_setting('administratorName') . " " . get_setting('administratorSurname') . "\n" . get_lang('Manager') . " " . get_setting('siteName') . "\nT. " . get_setting('administratorTelephone') . "\n" . get_lang('Email') . " : " . get_setting('emailAdministrator');
                        MessageManager::send_message($user_id, $subject, $content, array(), array(), null, null, null, null, null);
                    }
                }
            }
        }

        foreach ($course_list as $enreg_course) {
            // for each course in the session
            $nbr_users = 0;
            $enreg_course = Database::escape_string($enreg_course);
            // delete existing users
            if ($empty_users) {
                foreach ($existingUsers as $existing_user) {
                    if (!in_array($existing_user, $user_list)) {
                        $sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user' AND status = 0";
                        Database::query($sql);
                        if (Database::affected_rows()) {
                            $nbr_users--;
                        }
                    }
                }
            }

            // Replace with this new function
            // insert new users into session_rel_course_rel_user and ignore if they already exist
            foreach ($user_list as $enreg_user) {
                if (!in_array($enreg_user, $existingUsers)) {
                    $enreg_user = Database::escape_string($enreg_user);
                    $insert_sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session, course_code, id_user, visibility, status) VALUES('$id_session','$enreg_course','$enreg_user','$session_visibility', '0')";
                    Database::query($insert_sql);
                    if (Database::affected_rows()) {
                        $nbr_users++;
                    }
                }
            }
            // Count users in this session-course relation
            $sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND status<>2";
            $rs = Database::query($sql);
            list($nbr_users) = Database::fetch_array($rs);
            // update the session-course relation to add the users total
            $update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
            Database::query($update_sql);
        }

        // Delete users from the session
        if ($empty_users === true) {
            Database::query("DELETE FROM $tbl_session_rel_user WHERE id_session = $id_session AND relation_type<>" . SESSION_RELATION_TYPE_RRHH . "");
        }

        // Insert missing users into session
        $nbr_users = 0;
        foreach ($user_list as $enreg_user) {
            $enreg_user = Database::escape_string($enreg_user);
            $nbr_users++;
            $insert_sql = "INSERT IGNORE INTO $tbl_session_rel_user (id_session, id_user) VALUES ('$id_session', '$enreg_user')";
            Database::query($insert_sql);
        }

        // update number of users in the session
        $nbr_users = count($user_list);
        if ($empty_users) {
            // update number of users in the session
            $update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
            Database::query($update_sql);
        } else {
            $update_sql = "UPDATE $tbl_session SET nbr_users= nbr_users + $nbr_users WHERE id='$id_session'";
            Database::query($update_sql);
        }
    }

    /**
     * Subscribe a user to an specific course inside a session.
     *
     * @param array $user_list
     * @param int $session_id
     * @param string $course_code
     * @param int $session_visibility
     * @return bool
     */
    public static function subscribe_users_to_session_course(
        $user_list, $session_id, $course_code, $session_visibility = SESSION_VISIBLE_READ_ONLY
    )
    {
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        if (empty($user_list) || empty($session_id) || empty($course_code)) {
            return false;
        }

        $session_id = intval($session_id);
        $course_code = Database::escape_string($course_code);
        $session_visibility = intval($session_visibility);

        $nbr_users = 0;
        /* AND
          visibility = $session_visibility */
        foreach ($user_list as $enreg_user) {
            $enreg_user = intval($enreg_user);
            $sql = "SELECT count(id_user) as count
                    FROM $tbl_session_rel_course_rel_user
                    WHERE id_session = $session_id AND
                          course_code = '$course_code' and
                          id_user = $enreg_user ";
            $result = Database::query($sql);
            $count = 0;

            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result, 'ASSOC');
                $count = $row['count'];
            }

            if ($count == 0) {
                $insert_sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user,visibility)
                               VALUES ('$session_id','$course_code','$enreg_user','$session_visibility')";
                Database::query($insert_sql);
                if (Database::affected_rows()) {
                    $nbr_users++;
                }
            }
        }

        // count users in this session-course relation
        $sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user
                WHERE id_session='$session_id' AND course_code='$course_code' AND status<>2";
        $rs = Database::query($sql);
        list($nbr_users) = Database::fetch_array($rs);
        // update the session-course relation to add the users total
        $update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users
                       WHERE id_session='$session_id' AND course_code='$course_code'";
        Database::query($update_sql);
    }

    /**
     * Unsubscribe user from session
     *
     * @param int Session id
     * @param int User id
     * @return bool True in case of success, false in case of error
     */
    public static function unsubscribe_user_from_session($session_id, $user_id)
    {
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;

        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        $delete_sql = "DELETE FROM $tbl_session_rel_user
		               WHERE    id_session = '$session_id' AND
		                        id_user ='$user_id' AND
		                        relation_type <> " . SESSION_RELATION_TYPE_RRHH . "";
        Database::query($delete_sql);
        $return = Database::affected_rows();

        // Update number of users
        $sql = "UPDATE $tbl_session SET nbr_users = nbr_users - $return WHERE id='$session_id' ";
        Database::query($sql);

        // Get the list of courses related to this session
        $course_list = SessionManager::get_course_list_by_session_id($session_id);
        if (!empty($course_list)) {
            foreach ($course_list as $course) {
                $course_code = $course['code'];
                // Delete user from course
                $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                        WHERE id_session='$session_id' AND course_code='$course_code' AND id_user='$user_id'";
                Database::query($sql);
                if (Database::affected_rows()) {
                    // Update number of users in this relation
                    $sql = "UPDATE $tbl_session_rel_course SET nbr_users=nbr_users - 1
                            WHERE id_session='$session_id' AND course_code='$course_code'";
                    Database::query($sql);
                }
            }
        }
        return true;
    }

    /** Subscribes courses to the given session and optionally (default) unsubscribes previous users
     * @author Carlos Vargas from existing code
     * @param	int		Session ID
     * @param	array	List of courses IDs
     * @param	bool	Whether to unsubscribe existing users (true, default) or not (false)
     * @return	void	Nothing, or false on error
     * */
    public static function add_courses_to_session($id_session, $course_list, $empty_courses = true)
    {
        // Security checks
        if ($id_session != strval(intval($id_session))) {
            return false;
        }

        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        // get general coach ID
        $id_coach = Database::query("SELECT id_coach FROM $tbl_session WHERE id=$id_session");
        $id_coach = Database::fetch_array($id_coach);
        $id_coach = $id_coach[0];
        // get list of courses subscribed to this session
        $rs = Database::query("SELECT course_code FROM $tbl_session_rel_course WHERE id_session=$id_session");
        $existingCourses = Database::store_result($rs);
        $nbr_courses = count($existingCourses);
        // get list of users subscribed to this session
        $sql = "SELECT id_user
			FROM $tbl_session_rel_user
			WHERE id_session = $id_session AND relation_type<>" . SESSION_RELATION_TYPE_RRHH . "";
        $result = Database::query($sql);
        $user_list = Database::store_result($result);

        // remove existing courses from the session
        if ($empty_courses === true) {
            foreach ($existingCourses as $existingCourse) {
                if (!in_array($existingCourse['course_code'], $course_list)) {
                    Database::query("DELETE FROM $tbl_session_rel_course WHERE course_code='" . $existingCourse['course_code'] . "' AND id_session=$id_session");
                    Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE course_code='" . $existingCourse['course_code'] . "' AND id_session=$id_session");
                }
            }
            $nbr_courses = 0;
        }

        // Pass through the courses list we want to add to the session
        foreach ($course_list as $enreg_course) {
            $enreg_course = Database::escape_string($enreg_course);
            $exists = false;
            // check if the course we want to add is already subscribed
            foreach ($existingCourses as $existingCourse) {
                if ($enreg_course == $existingCourse['course_code']) {
                    $exists = true;
                }
            }
            if (!$exists) {
                //if the course isn't subscribed yet
                $sql_insert_rel_course = "INSERT INTO $tbl_session_rel_course (id_session,course_code) VALUES ('$id_session','$enreg_course')";
                Database::query($sql_insert_rel_course);
                //We add the current course in the existing courses array, to avoid adding another time the current course
                $existingCourses[] = array('course_code' => $enreg_course);
                $nbr_courses++;

                // subscribe all the users from the session to this course inside the session
                $nbr_users = 0;
                foreach ($user_list as $enreg_user) {
                    $enreg_user_id = Database::escape_string($enreg_user['id_user']);
                    $sql_insert = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user (id_session,course_code,id_user) VALUES ('$id_session','$enreg_course','$enreg_user_id')";
                    Database::query($sql_insert);
                    if (Database::affected_rows()) {
                        $nbr_users++;
                    }
                }
                Database::query("UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'");
            }
        }
        Database::query("UPDATE $tbl_session SET nbr_courses=$nbr_courses WHERE id='$id_session'");
    }

    /**
     * Unsubscribe course from a session
     *
     * @param int Session id
     * @param int Course id
     * @return bool True in case of success, false otherwise
     */
    public static function unsubscribe_course_from_session($session_id, $course_id)
    {
        $session_id = (int) $session_id;
        $course_id = (int) $course_id;

        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        // Get course code
        $course_code = CourseManager::get_course_code_from_course_id($course_id);
        if ($course_code == 0) {
            return false;
        }

        // Unsubscribe course
        Database::query("DELETE FROM $tbl_session_rel_course WHERE course_code='$course_code' AND id_session='$session_id'");
        $nb_affected = Database::affected_rows();

        Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE course_code='$course_code' AND id_session='$session_id'");
        if ($nb_affected > 0) {
            // Update number of courses in the session
            Database::query("UPDATE $tbl_session SET nbr_courses= nbr_courses + $nb_affected WHERE id='$session_id' ");
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates a new extra field for a given session
     * @param	string	Field's internal variable name
     * @param	int		Field's type
     * @param	string	Field's language var name
     * @return int     new extra field id
     */
    public static function create_session_extra_field($fieldvarname, $fieldtype, $fieldtitle)
    {
        // database table definition
        $t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
        $fieldvarname = Database::escape_string($fieldvarname);
        $fieldtitle = Database::escape_string($fieldtitle);
        $fieldtype = (int) $fieldtype;
        $time = time();
        $sql_field = "SELECT id FROM $t_sf WHERE field_variable = '$fieldvarname'";
        $res_field = Database::query($sql_field);

        $r_field = Database::fetch_row($res_field);

        if (Database::num_rows($res_field) > 0) {
            $field_id = $r_field[0];
        } else {
            // save new fieldlabel into course_field table
            $sql = "SELECT MAX(field_order) FROM $t_sf";
            $res = Database::query($sql);

            $order = 0;
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_row($res);
                $order = $row[0] + 1;
            }

            $sql = "INSERT INTO $t_sf SET
                    field_type = '$fieldtype',
                    field_variable = '$fieldvarname',
                    field_display_text = '$fieldtitle',
                    field_order = '$order',
                    tms = FROM_UNIXTIME($time)";
            Database::query($sql);

            $field_id = Database::insert_id();
        }
        return $field_id;
    }

    /**
     * Update an extra field value for a given session
     * @param	integer	Course ID
     * @param	string	Field variable name
     * @param	string	Field value
     * @return	boolean	true if field updated, false otherwise
     */
    public static function update_session_extra_field_value($session_id, $fname, $fvalue = '')
    {

        $t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
        $t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
        $fname = Database::escape_string($fname);
        $session_id = (int) $session_id;
        $fvalues = '';
        if (is_array($fvalue)) {
            foreach ($fvalue as $val) {
                $fvalues .= Database::escape_string($val) . ';';
            }
            if (!empty($fvalues)) {
                $fvalues = substr($fvalues, 0, -1);
            }
        } else {
            $fvalues = Database::escape_string($fvalue);
        }

        $sqlsf = "SELECT * FROM $t_sf WHERE field_variable='$fname'";
        $ressf = Database::query($sqlsf);
        if (Database::num_rows($ressf) == 1) {
            //ok, the field exists
            //	Check if enumerated field, if the option is available
            $rowsf = Database::fetch_array($ressf);

            $tms = time();
            $sqlsfv = "SELECT * FROM $t_sfv WHERE session_id = '$session_id' AND field_id = '" . $rowsf['id'] . "' ORDER BY id";
            $ressfv = Database::query($sqlsfv);
            $n = Database::num_rows($ressfv);
            if ($n > 1) {
                //problem, we already have to values for this field and user combination - keep last one
                while ($rowsfv = Database::fetch_array($ressfv)) {
                    if ($n > 1) {
                        $sqld = "DELETE FROM $t_sfv WHERE id = " . $rowsfv['id'];
                        $resd = Database::query($sqld);
                        $n--;
                    }
                    $rowsfv = Database::fetch_array($ressfv);
                    if ($rowsfv['field_value'] != $fvalues) {
                        $sqlu = "UPDATE $t_sfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = " . $rowsfv['id'];
                        $resu = Database::query($sqlu);
                        return($resu ? true : false);
                    }
                    return true;
                }
            } else if ($n == 1) {
                //we need to update the current record
                $rowsfv = Database::fetch_array($ressfv);
                if ($rowsfv['field_value'] != $fvalues) {
                    $sqlu = "UPDATE $t_sfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = " . $rowsfv['id'];
                    //error_log('UM::update_extra_field_value: '.$sqlu);
                    $resu = Database::query($sqlu);
                    return($resu ? true : false);
                }
                return true;
            } else {
                $sqli = "INSERT INTO $t_sfv (session_id,field_id,field_value,tms) " .
                    "VALUES ('$session_id'," . $rowsf['id'] . ",'$fvalues',FROM_UNIXTIME($tms))";
                //error_log('UM::update_extra_field_value: '.$sqli);
                $resi = Database::query($sqli);
                return($resi ? true : false);
            }
        } else {
            return false; //field not found
        }
    }

    /**
     * Checks the relationship between a session and a course.
     * @param int $session_id
     * @param int $course_id
     * @return bool Returns TRUE if the session and the course are related, FALSE otherwise.
     * */
    public static function relation_session_course_exist($session_id, $course_id)
    {
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $return_value = false;
        $sql = "SELECT course_code FROM $tbl_session_course WHERE id_session = " . Database::escape_string($session_id) . " AND course_code = '" . Database::escape_string($course_id) . "'";
        $result = Database::query($sql);
        $num = Database::num_rows($result);
        if ($num > 0) {
            $return_value = true;
        }
        return $return_value;
    }

    /**
     * Get the session information by name
     * @param string session name
     * @return mixed false if the session does not exist, array if the session exist
     * */
    public static function get_session_by_name($session_name)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $session_name = trim($session_name);
        if (empty($session_name)) {
            return false;
        }

        $sql = 'SELECT *
		        FROM ' . $tbl_session . '
		        WHERE name = "' . Database::escape_string($session_name) . '"';
        $result = Database::query($sql);
        $num = Database::num_rows($result);
        if ($num > 0) {
            return Database::fetch_array($result);
        } else {
            return false;
        }
    }

    /**
     * Create a session category
     * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>, from existing code
     * @param	string 		name
     * @param 	integer		year_start
     * @param 	integer		month_start
     * @param 	integer		day_start
     * @param 	integer		year_end
     * @param 	integer		month_end
     * @param 	integer		day_end
     * @return $id_session;
     * */
    public static function create_category_session($sname, $syear_start, $smonth_start, $sday_start, $syear_end, $smonth_end, $sday_end)
    {
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $name = trim($sname);
        $year_start = intval($syear_start);
        $month_start = intval($smonth_start);
        $day_start = intval($sday_start);
        $year_end = intval($syear_end);
        $month_end = intval($smonth_end);
        $day_end = intval($sday_end);

        $date_start = "$year_start-" . (($month_start < 10) ? "0$month_start" : $month_start) . "-" . (($day_start < 10) ? "0$day_start" : $day_start);
        $date_end = "$year_end-" . (($month_end < 10) ? "0$month_end" : $month_end) . "-" . (($day_end < 10) ? "0$day_end" : $day_end);

        if (empty($name)) {
            $msg = get_lang('SessionCategoryNameIsRequired');
            return $msg;
        } elseif (!$month_start || !$day_start || !$year_start || !checkdate($month_start, $day_start, $year_start)) {
            $msg = get_lang('InvalidStartDate');
            return $msg;
        } elseif (!$month_end && !$day_end && !$year_end) {
            $date_end = "null";
        } elseif (!$month_end || !$day_end || !$year_end || !checkdate($month_end, $day_end, $year_end)) {
            $msg = get_lang('InvalidEndDate');
            return $msg;
        } elseif ($date_start >= $date_end) {
            $msg = get_lang('StartDateShouldBeBeforeEndDate');
            return $msg;
        }
        $access_url_id = api_get_current_access_url_id();
        $sql = "INSERT INTO $tbl_session_category (name, date_start, date_end, access_url_id)
        		VALUES('" . Database::escape_string($name) . "','$date_start','$date_end', '$access_url_id')";
        Database::query($sql);
        $id_session = Database::insert_id();
        // Add event to system log
        $user_id = api_get_user_id();
        event_system(LOG_SESSION_CATEGORY_CREATE, LOG_SESSION_CATEGORY_ID, $id_session, api_get_utc_datetime(), $user_id);
        return $id_session;
    }

    /**
     * Edit a sessions categories
     * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>,from existing code
     * @param	integer		id
     * @param	string 		name
     * @param 	integer		year_start
     * @param 	integer		month_start
     * @param 	integer		day_start
     * @param 	integer		year_end
     * @param 	integer		month_end
     * @param 	integer		day_end
     * @return $id;
     * The parameter id is a primary key
     * */
    public static function edit_category_session($id, $sname, $syear_start, $smonth_start, $sday_start, $syear_end, $smonth_end, $sday_end)
    {
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $name = trim($sname);
        $year_start = intval($syear_start);
        $month_start = intval($smonth_start);
        $day_start = intval($sday_start);
        $year_end = intval($syear_end);
        $month_end = intval($smonth_end);
        $day_end = intval($sday_end);
        $id = intval($id);
        $date_start = "$year_start-" . (($month_start < 10) ? "0$month_start" : $month_start) . "-" . (($day_start < 10) ? "0$day_start" : $day_start);
        $date_end = "$year_end-" . (($month_end < 10) ? "0$month_end" : $month_end) . "-" . (($day_end < 10) ? "0$day_end" : $day_end);

        if (empty($name)) {
            $msg = get_lang('SessionCategoryNameIsRequired');
            return $msg;
        } elseif (!$month_start || !$day_start || !$year_start || !checkdate($month_start, $day_start, $year_start)) {
            $msg = get_lang('InvalidStartDate');
            return $msg;
        } elseif (!$month_end && !$day_end && !$year_end) {
            $date_end = null;
        } elseif (!$month_end || !$day_end || !$year_end || !checkdate($month_end, $day_end, $year_end)) {
            $msg = get_lang('InvalidEndDate');
            return $msg;
        } elseif ($date_start >= $date_end) {
            $msg = get_lang('StartDateShouldBeBeforeEndDate');
            return $msg;
        }
        if ($date_end <> null) {
            $sql = "UPDATE $tbl_session_category SET name = '" . Database::escape_string($name) . "', date_start = '$date_start' " .
                ", date_end = '$date_end' WHERE id= '" . $id . "' ";
        } else {
            $sql = "UPDATE $tbl_session_category SET name = '" . Database::escape_string($name) . "', date_start = '$date_start' " .
                ", date_end = NULL WHERE id= '" . $id . "' ";
        }
        $result = Database::query($sql);
        return ($result ? true : false);
    }

    /**
     * Delete sessions categories
     * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>, from existing code
     * @param	array	id_checked
     * @param	bool	include delete session
     * @param	bool	optional, true if the function is called by a webservice, false otherwise.
     * @return	void	Nothing, or false on error
     * The parameters is a array to delete sessions
     * */
    public static function delete_session_category($id_checked, $delete_session = false, $from_ws = false)
    {
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        if (is_array($id_checked)) {
            $id_checked = Database::escape_string(implode(',', $id_checked));
        } else {
            $id_checked = intval($id_checked);
        }

        //Setting session_category_id to 0
        $sql = "UPDATE $tbl_session SET session_category_id = 0 WHERE session_category_id IN (" . $id_checked . ")";
        Database::query($sql);

        $sql = "SELECT id FROM $tbl_session WHERE session_category_id IN (" . $id_checked . ")";
        $result = Database::query($sql);
        while ($rows = Database::fetch_array($result)) {
            $session_id = $rows['id'];
            if ($delete_session) {
                if ($from_ws) {
                    SessionManager::delete_session($session_id, true);
                } else {
                    SessionManager::delete_session($session_id);
                }
            }
        }
        $sql = "DELETE FROM $tbl_session_category WHERE id IN (" . $id_checked . ")";
        Database::query($sql);

        // Add event to system log
        $user_id = api_get_user_id();
        event_system(LOG_SESSION_CATEGORY_DELETE, LOG_SESSION_CATEGORY_ID, $id_checked, api_get_utc_datetime(), $user_id);

        // delete extra session fields where field variable is "PERIODO"
        $t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
        $t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

        $sql = "SELECT t_sfv.field_id FROM $t_sfv t_sfv, $t_sf t_sf  WHERE t_sfv.session_id = '$id_checked' AND t_sf.field_variable = 'PERIODO' ";
        $rs_field = Database::query($sql);

        $field_id = 0;
        if (Database::num_rows($rs_field) > 0) {
            $row_field = Database::fetch_row($rs_field);
            $field_id = $row_field[0];
            $sql_delete_sfv = "DELETE FROM $t_sfv WHERE session_id = '$id_checked' AND field_id = '$field_id'";
            Database::query($sql_delete_sfv);
        }

        $sql = "SELECT * FROM $t_sfv WHERE field_id = '$field_id' ";
        $rs_field_id = Database::query($sql);

        if (Database::num_rows($rs_field_id) == 0) {
            $sql_delete_sf = "DELETE FROM $t_sf WHERE id = '$field_id'";
            Database::query($sql_delete_sf);
        }

        return true;
    }

    /**
     * Get a list of sessions of which the given conditions match with an = 'cond'
     * @param  array $conditions a list of condition (example : array('status =' =>STUDENT) or array('s.name LIKE' => "%$needle%")
     * @param  array $order_by a list of fields on which sort
     * @return array An array with all sessions of the platform.
     * @todo   optional course code parameter, optional sorting parameters...
     */
    public static function get_sessions_list($conditions = array(), $order_by = array())
    {
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        $session_category_table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $table_access_url_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $session_course_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);

        $access_url_id = api_get_current_access_url_id();

        $return_array = array();

        $sql_query = " SELECT s.id, s.name, s.nbr_courses, s.date_start, s.date_end, u.firstname, u.lastname, sc.name as category_name, s.promotion_id
				FROM $session_table s
				INNER JOIN $user_table u ON s.id_coach = u.user_id
				INNER JOIN $table_access_url_rel_session ar ON ar.session_id = s.id
				LEFT JOIN  $session_category_table sc ON s.session_category_id = sc.id
				LEFT JOIN $session_course_table sco ON (sco.id_session = s.id)
				INNER JOIN $course_table c ON sco.course_code = c.code
				WHERE ar.access_url_id = $access_url_id ";

        if (count($conditions) > 0) {
            foreach ($conditions as $field => $value) {
                $sql_query .= ' AND ';
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql_query .= $field . " '" . $value . "'";
            }
        }
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY ' . Database::escape_string(implode(',', $order_by));
        }
        $sql_result = Database::query($sql_query);
        if (Database::num_rows($sql_result) > 0) {
            while ($result = Database::fetch_array($sql_result)) {
                $return_array[$result['id']] = $result;
            }
        }
        return $return_array;
    }

    /**
     * Get the session category information by id
     * @param string session category ID
     * @return mixed false if the session category does not exist, array if the session category exists
     */
    public static function get_session_category($id)
    {
        $id = intval($id);
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $sql = "SELECT id, name, date_start, date_end FROM $tbl_session_category WHERE id= $id";
        $result = Database::query($sql);
        $num = Database::num_rows($result);
        if ($num > 0) {
            return Database::fetch_array($result);
        } else {
            return false;
        }
    }

    /**
     * Get all session categories (filter by access_url_id)
     * @return mixed false if the session category does not exist, array if the session category exists
     */
    public static function get_all_session_category()
    {
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $id = api_get_current_access_url_id();
        $sql = 'SELECT * FROM ' . $tbl_session_category . ' WHERE access_url_id ="' . $id . '" ORDER BY name ASC';
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $data = Database::store_result($result, 'ASSOC');
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Assign a coach to course in session with status = 2
     * @param int  		- user id
     * @param int  		- session id
     * @param string  	- course code
     * @param bool  	- optional, if is true the user don't be a coach now, otherwise it'll assign a coach
     * @return bool true if there are affected rows, otherwise false
     */
    public static function set_coach_to_course_session($user_id, $session_id = 0, $course_code = '', $nocoach = false)
    {
        // Definition of variables
        $user_id = intval($user_id);

        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }

        if (!empty($course_code)) {
            $course_code = Database::escape_string($course_code);
        } else {
            $course_code = api_get_course_id();
        }

        // Table definition
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

        // check if user is a teacher
        $sql = "SELECT * FROM $tbl_user WHERE status='1' AND user_id = '$user_id'";

        $rs_check_user = Database::query($sql);

        if (Database::num_rows($rs_check_user) > 0) {
            if ($nocoach) {
                // check if user_id exits int session_rel_user
                $sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session = '$session_id' AND id_user = '$user_id'";
                $res = Database::query($sql);

                if (Database::num_rows($res) > 0) {
                    // The user don't be a coach now
                    $sql = "UPDATE $tbl_session_rel_course_rel_user SET status = 0 WHERE id_session = '$session_id' AND course_code = '$course_code' AND id_user = '$user_id' ";
                    Database::query($sql);
                    if (Database::affected_rows() > 0)
                        return true;
                    else
                        return false;
                } else {
                    // The user don't be a coach now
                    $sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session = '$session_id' AND course_code = '$course_code' AND id_user = '$user_id' ";
                    Database::query($sql);
                    if (Database::affected_rows() > 0)
                        return true;
                    else
                        return false;
                }
            } else {
                // Assign user like a coach to course
                // First check if the user is registered in the course
                $sql = "SELECT id_user FROM $tbl_session_rel_course_rel_user WHERE id_session = '$session_id' AND course_code = '$course_code' AND id_user = '$user_id'";
                $rs_check = Database::query($sql);

                // Then update or insert.
                if (Database::num_rows($rs_check) > 0) {
                    $sql = "UPDATE $tbl_session_rel_course_rel_user SET status = 2
					        WHERE id_session = '$session_id' AND course_code = '$course_code' AND id_user = '$user_id' ";
                    Database::query($sql);
                    if (Database::affected_rows() > 0) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    $sql = "INSERT INTO $tbl_session_rel_course_rel_user(id_session, course_code, id_user, status) VALUES('$session_id', '$course_code', '$user_id', 2)";
                    Database::query($sql);
                    if (Database::affected_rows() > 0) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Subscribes sessions to human resource manager (Dashboard feature)
     * @param	int 		Human Resource Manager id
     * @param	array 		Sessions id
     * @return int
     * */
    public static function suscribe_sessions_to_hr_manager($userInfo, $sessions_list, $sendEmail = false, $removeOldConnections = true)
    {
        // Database Table Definitions
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        if (empty($userInfo)) {
            return 0;
        }

        $userId = $userInfo['user_id'];
        // Only subscribe DRH users.
        if ($userInfo['status'] != DRH) {
            return 0;
        }

        $affected_rows = 0;

        // Deleting assigned sessions to hrm_id.
        if ($removeOldConnections) {
            if (api_is_multiple_url_enabled()) {
                $sql = "SELECT id_session
                        FROM $tbl_session_rel_user s
                        INNER JOIN $tbl_session_rel_access_url a ON (a.session_id = s.id_session)
                        WHERE
                            id_user = $userId AND
                            relation_type=" . SESSION_RELATION_TYPE_RRHH . " AND
                            access_url_id = " . api_get_current_access_url_id() . "";
            } else {
                $sql = "SELECT id_session FROM $tbl_session_rel_user s
                        WHERE id_user = $userId AND relation_type=" . SESSION_RELATION_TYPE_RRHH . "";
            }
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $sql = "DELETE FROM $tbl_session_rel_user
                            WHERE
                                id_session = {$row['id_session']} AND
                                id_user = $userId AND
                                relation_type=" . SESSION_RELATION_TYPE_RRHH . " ";
                    Database::query($sql);
                }
            }
        }

        // Inserting new sessions list.
        if (!empty($sessions_list) && is_array($sessions_list)) {
            foreach ($sessions_list as $session_id) {
                $session_id = intval($session_id);
                $sql = "INSERT IGNORE INTO $tbl_session_rel_user (id_session, id_user, relation_type)
				        VALUES ($session_id, $userId, '" . SESSION_RELATION_TYPE_RRHH . "')";
                Database::query($sql);
                $affected_rows++;
            }
        }
        return $affected_rows;
    }

    /**
     * @param int $userId
     * @param int $sessionId
     * @return array
     */
    public static function getSessionFollowedByDrh($userId, $sessionId)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        $userId = intval($userId);
        $sessionId = intval($sessionId);

        $select = " SELECT * ";
        if (api_is_multiple_url_enabled()) {
            $sql = " $select FROM $tbl_session s
                    INNER JOIN $tbl_session_rel_user sru ON (sru.id_session = s.id)
                    LEFT JOIN $tbl_session_rel_access_url a ON (s.id = a.session_id)
                    WHERE
                        sru.id_user = '$userId' AND
                        sru.id_session = '$sessionId' AND
                        sru.relation_type = '" . SESSION_RELATION_TYPE_RRHH . "' AND
                        access_url_id = " . api_get_current_access_url_id() . "
                        ";
        } else {
            $sql = "$select FROM $tbl_session s
                     INNER JOIN $tbl_session_rel_user sru
                     ON
                        sru.id_session = s.id AND
                        sru.id_user = '$userId' AND
                        sru.id_session = '$sessionId' AND
                        sru.relation_type = '" . SESSION_RELATION_TYPE_RRHH . "'
                        ";
        }
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            $row['course_list'] = self::get_course_list_by_session_id($sessionId);
            return $row;
        }
        return array();
    }

    /**
     * Get sessions followed by human resources manager
     * @param int $userId
     * @param int $start
     * @param int $limit
     * @param bool $getCount
     * @param bool $getOnlySessionId
     * @param bool $getSql
     * @param string $orderCondition
     * @return array sessions
     */
    public static function get_sessions_followed_by_drh(
        $userId,
        $start = null,
        $limit = null,
        $getCount = false,
        $getOnlySessionId = false,
        $getSql = false,
        $orderCondition = null,
        $keyword = null
    ) {
        return self::getSessionsFollowedByUser(
            $userId,
            DRH,
            $start,
            $limit,
            $getCount,
            $getOnlySessionId,
            $getSql,
            $orderCondition,
            $keyword
        );
    }

    /**
     * Get sessions followed by human resources manager
     * @param int $userId
     * @param int $start
     * @param int $limit
     * @param bool $getCount
     * @param bool $getOnlySessionId
     * @param bool $getSql
     * @param string $orderCondition
     * @param string $keyword
     * @return array sessions
     */
    public static function getSessionsFollowedByUser(
        $userId,
        $status = null,
        $start = null,
        $limit = null,
        $getCount = false,
        $getOnlySessionId = false,
        $getSql = false,
        $orderCondition = null,
        $keyword = null
    ) {
        // Database Table Definitions
        $tbl_session 			= Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_user 	= Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        $userId = intval($userId);

        $select = " SELECT DISTINCT * ";

        if ($getCount) {
            $select = " SELECT count(DISTINCT(s.id)) as count ";
        }

        if ($getOnlySessionId) {
            $select = " SELECT DISTINCT(s.id) ";
        }

        $limitCondition = null;
        if (!empty($start) && !empty($limit)) {
            $limitCondition = " LIMIT " . intval($start) . ", " . intval($limit);
        }

        if (empty($orderCondition)) {
            $orderCondition = " ORDER BY s.name ";
        }


        $whereConditions = null;
        $sessionCourseConditions = null;
        $sessionConditions = null;
        $sessionQuery = null;
        $courseSessionQuery = null;

        switch($status) {
            case DRH:
                $sessionQuery = "SELECT sru.id_session
                                 FROM
                                 $tbl_session_rel_user sru
                                 WHERE
                                    sru.relation_type = '".SESSION_RELATION_TYPE_RRHH."' AND
                                    sru.id_user = $userId";
                break;
            case COURSEMANAGER:
                $courseSessionQuery = "
                    SELECT scu.id_session as id
                    FROM $tbl_session_rel_course_rel_user scu
                    WHERE (scu.status = 2 AND scu.id_user = $userId)";

                $whereConditions = " OR (s.id_coach = $userId) ";
                break;
        }

        $keywordCondition = null;
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (s.name LIKE '%$keyword%' ) ";
        }
        $whereConditions .= $keywordCondition;

        $subQuery = $sessionQuery.$courseSessionQuery;

        $sql = " $select FROM $tbl_session s
                INNER JOIN $tbl_session_rel_access_url a ON (s.id = a.session_id)
                WHERE
                    access_url_id = ".api_get_current_access_url_id()." AND
                    s.id IN (
                        $subQuery
                    )
                    $whereConditions
                    $orderCondition
                    $limitCondition";


        if ($getSql) {
            return $sql;
        }

        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result);
            return $row['count'];
        }

        $sessions = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $sessions[$row['id']] = $row;
            }
        }
        return $sessions;
    }

    /**
     * Gets the list of courses by session filtered by access_url
     * @param int $session_id
     * @param string $course_name
     * @return array list of courses
     */
    public static function get_course_list_by_session_id($session_id, $course_name = '')
    {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $session_id = intval($session_id);

        // select the courses
        $sql = "SELECT * FROM $tbl_course c
                INNER JOIN $tbl_session_rel_course src ON c.code = src.course_code
		        WHERE src.id_session = '$session_id'";
        if (!empty($course_name)) {
            $course_name = Database::escape_string($course_name);
            $sql .= " AND c.title LIKE '%$course_name%' ";
        }

        $sql .= "ORDER BY title;";
        $result 	= Database::query($sql);
        $num_rows 	= Database::num_rows($result);
        $courses = array();
        if ($num_rows > 0) {
            while ($row = Database::fetch_array($result,'ASSOC'))	{
                $courses[$row['id']] = $row;
            }
        }
        return $courses;
    }

    /**
     * Gets the list of courses by session filtered by access_url
     *
     * @param $userId
     * @param $sessionId
     * @param null $from
     * @param null $limit
     * @param null $column
     * @param null $direction
     * @param bool $getCount
     * @return array
     */
    public static function getAllCoursesFollowedByUser(
        $userId,
        $sessionId,
        $from = null,
        $limit = null,
        $column = null,
        $direction = null,
        $getCount = false,
        $keyword = null
    ) {
        if (empty($sessionId)) {
            $sessionsSQL = SessionManager::get_sessions_followed_by_drh(
                $userId,
                null,
                null,
                null,
                true,
                true
            );
        } else {
            $sessionsSQL = intval($sessionId);
        }

        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        if ($getCount) {
            $select = "SELECT COUNT(DISTINCT(c.code)) as count ";
        } else {
            $select = "SELECT DISTINCT c.* ";
        }

        $keywordCondition = null;
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (c.code LIKE '%$keyword%' OR c.title LIKE '%$keyword%' ) ";
        }

        // Select the courses
        $sql = "$select
                FROM $tbl_course c
                INNER JOIN $tbl_session_rel_course src
                ON c.code = src.course_code
		        WHERE
		            src.id_session IN ($sessionsSQL)
		            $keywordCondition
		        ";
        if ($getCount) {
            $result = Database::query($sql);
            $row = Database::fetch_array($result,'ASSOC');
            return $row['count'];
        }

        if (isset($from) && isset($limit)) {
            $from = intval($from);
            $limit = intval($limit);
            $sql .= " LIMIT $from, $limit";
        }

        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);
        $courses = array();

        if ($num_rows > 0) {
            while ($row = Database::fetch_array($result,'ASSOC'))	{
                $courses[$row['id']] = $row;
            }
        }
        return $courses;
    }

    /**
     * Gets the list of courses by session filtered by access_url
     * @param int session id
     * @param string course_name
     * @return array list of courses
     */
    public static function get_course_list_by_session_id_like($session_id, $course_name = '')
    {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $session_id = intval($session_id);
        $course_name = Database::escape_string($course_name);

        // select the courses
        $sql = "SELECT * FROM $tbl_course c INNER JOIN $tbl_session_rel_course src
                ON c.code = src.course_code
		        WHERE src.id_session LIKE '$session_id'";
        if (!empty($course_name)) {
            $sql .= " AND UPPER(c.title) LIKE UPPER('%$course_name%') ";
        }

        $sql .= "ORDER BY title;";
        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);
        $courses = array();
        if ($num_rows > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $courses[$row['id']] = $row;
            }
        }
        return $courses;
    }


    /**
     * Gets the count of courses by session filtered by access_url
     * @param int session id
     * @return array list of courses
     */
    public static function getCourseCountBySessionId($session_id, $keyword = null)
    {
        $tbl_course				= Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        // select the courses
        $sql = "SELECT COUNT(c.code) count
                FROM $tbl_course c
                INNER JOIN $tbl_session_rel_course src
                ON c.code = src.course_code
		        WHERE src.id_session = '$session_id' ";

        $keywordCondition = null;
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (c.code LIKE '%$keyword%' OR c.title LIKE '%$keyword%' ) ";
        }
        $sql .= $keywordCondition;

        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);
        if ($num_rows > 0) {
            $row = Database::fetch_array($result,'ASSOC');
            return $row['count'];
        }
        return null;
    }

    /**
     * Get the session id based on the original id and field name in the extra fields. Returns 0 if session was not found
     *
     * @param string Original session id
     * @param string Original field name
     * @return int Session id
     */
    public static function get_session_id_from_original_id($original_session_id_value, $original_session_id_name)
    {
        $t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
        $table_field = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
        $sql = "SELECT session_id
                FROM $table_field sf INNER JOIN $t_sfv sfv ON sfv.field_id=sf.id
                WHERE field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
        $res_session = Database::query($sql);
        $row = Database::fetch_object($res_session);
        if ($row) {
            return $row->session_id;
        } else {
            return 0;
        }
    }

    /**
     * Get users by session
     * @param  int session id
     * @param	int	filter by status coach = 2
     * @return  array a list with an user list
     */
    public static function get_users_by_session($id, $status = null)
    {
        if (empty($id)) {
            return array();
        }
        $id = intval($id);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $sql = "SELECT $tbl_user.user_id, lastname, firstname, username
                FROM $tbl_user INNER JOIN $tbl_session_rel_user
                    ON $tbl_user.user_id = $tbl_session_rel_user.id_user
                    AND $tbl_session_rel_user.id_session = $id";

        if (isset($status) && $status != '') {
            $status = intval($status);
            $sql .= " WHERE relation_type = $status ";
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $return_array[] = $row;
        }
        return $return_array;
    }

    /**
     * The general coach (field: session.id_coach)
     * @param int user id
     * @return array
     */
    public static function get_sessions_by_general_coach($user_id)
    {
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        $user_id = intval($user_id);

        // session where we are general coach
        $sql = "SELECT DISTINCT *
                FROM $session_table
                WHERE id_coach = $user_id";

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT DISTINCT session.*
                    FROM ' . $session_table . ' session INNER JOIN ' . $tbl_session_rel_access_url . ' session_rel_url
                    ON (session.id = session_rel_url.session_id)
                    WHERE id_coach = ' . $user_id . ' AND access_url_id = ' . $access_url_id;
            }
        }
        $sql .= ' ORDER by name';
        $result = Database::query($sql);
        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $user_id
     * @return array
     */
    public static function get_sessions_by_coach($user_id)
    {
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        return Database::select('*', $session_table, array('where' => array('id_coach = ?' => $user_id)));
    }

    /**
     * @param int $user_id
     * @param string $course_code
     * @param int $session_id
     * @return array|bool
     */
    public static function get_user_status_in_course_session($user_id, $course_code, $session_id)
    {
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT session_rcru.status
                FROM $tbl_session_rel_course_rel_user session_rcru, $tbl_user user
                WHERE session_rcru.id_user = user.user_id AND
                session_rcru.id_session = '" . intval($session_id) . "' AND
                session_rcru.course_code ='" . Database::escape_string($course_code) . "' AND
                user.user_id = " . intval($user_id);

        $result = Database::query($sql);
        $status = false;
        if (Database::num_rows($result)) {
            $status = Database::fetch_row($result);
            $status = $status['0'];
        }
        return $status;
    }

    /**
     * Gets user status within a session
     * @param $user_id
     * @param $course_code
     * @param $session_id
     * @return int
     * @assert (null,null,null) === false
     */
    public static function get_user_status_in_session($user_id, $course_code, $session_id)
    {
        if (empty($user_id) or empty($course_code) or empty($session_id)) {
            return false;
        }
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT session_rcru.status
                FROM $tbl_session_rel_course_rel_user session_rcru, $tbl_user user
                WHERE session_rcru.id_user = user.user_id AND
                session_rcru.id_session = '" . intval($session_id) . "' AND
                session_rcru.course_code ='" . Database::escape_string($course_code) . "' AND
                user.user_id = " . intval($user_id);
        $result = Database::query($sql);
        $status = false;
        if (Database::num_rows($result)) {
            $status = Database::fetch_row($result);
            $status = $status['0'];
        }
        return $status;
    }

    /**
     * @param int $id
     * @return array
     */
    public static function get_all_sessions_by_promotion($id)
    {
        $t = Database::get_main_table(TABLE_MAIN_SESSION);
        return Database::select('*', $t, array('where' => array('promotion_id = ?' => $id)));
    }

    /**
     * @param int $promotion_id
     * @param array $list
     */
    public static function suscribe_sessions_to_promotion($promotion_id, $list)
    {
        $t = Database::get_main_table(TABLE_MAIN_SESSION);
        $params = array();
        $params['promotion_id'] = 0;
        Database::update($t, $params, array('promotion_id = ?' => $promotion_id));

        $params['promotion_id'] = $promotion_id;
        if (!empty($list)) {
            foreach ($list as $session_id) {
                $session_id = intval($session_id);
                Database::update($t, $params, array('id = ?' => $session_id));
            }
        }
    }

    /**
     * Updates a session status
     * @param	int 	session id
     * @param	int 	status
     */
    public static function set_session_status($session_id, $status)
    {
        $t = Database::get_main_table(TABLE_MAIN_SESSION);
        $params['visibility'] = $status;
        Database::update($t, $params, array('id = ?' => $session_id));
    }

    /**
     * Copies a session with the same data to a new session.
     * The new copy is not assigned to the same promotion. @see suscribe_sessions_to_promotions() for that
     * @param   int     Session ID
     * @param   bool    Whether to copy the relationship with courses
     * @param   bool    Whether to copy the relationship with users
     * @param	bool	New courses will be created
     * @return  int     The new session ID on success, 0 otherwise
     * @todo make sure the extra session fields are copied too
     */
    public static function copy_session($id, $copy_courses = true, $copy_users = true, $create_new_courses = false, $set_exercises_lp_invisible = false)
    {
        $id = intval($id);
        $s = self::fetch($id);
        $s['year_start'] = substr($s['date_start'], 0, 4);
        $s['month_start'] = substr($s['date_start'], 5, 2);
        $s['day_start'] = substr($s['date_start'], 8, 2);
        $s['year_end'] = substr($s['date_end'], 0, 4);
        $s['month_end'] = substr($s['date_end'], 5, 2);
        $s['day_end'] = substr($s['date_end'], 8, 2);
        $consider_start = true;
        if ($s['year_start'] . '-' . $s['month_start'] . '-' . $s['day_start'] == '0000-00-00') {
            $consider_start = false;
        }
        $consider_end = true;
        if ($s['year_end'] . '-' . $s['month_end'] . '-' . $s['day_end'] == '0000-00-00') {
            $consider_end = false;
        }

        $sid = self::create_session($s['name'] . ' ' . get_lang('CopyLabelSuffix'), $s['year_start'], $s['month_start'], $s['day_start'], $s['year_end'], $s['month_end'], $s['day_end'], $s['nb_days_acess_before_beginning'], $s['nb_days_acess_after_end'], false, (int) $s['id_coach'], $s['session_category_id'], (int) $s['visibility'], $consider_start, $consider_end, true);

        if (!is_numeric($sid) || empty($sid)) {
            return false;
        }

        if ($copy_courses) {
            // Register courses from the original session to the new session
            $courses = self::get_course_list_by_session_id($id);

            $short_courses = $new_short_courses = array();
            if (is_array($courses) && count($courses) > 0) {
                foreach ($courses as $course) {
                    $short_courses[] = $course;
                }
            }

            $courses = null;

            //We will copy the current courses of the session to new courses
            if (!empty($short_courses)) {
                if ($create_new_courses) {
                    //Just in case
                    if (function_exists('ini_set')) {
                        api_set_memory_limit('256M');
                        ini_set('max_execution_time', 0);
                    }
                    $params = array();
                    $params['skip_lp_dates'] = true;

                    foreach ($short_courses as $course_data) {
                        $course_info = CourseManager::copy_course_simple($course_data['title'] . ' ' . get_lang('CopyLabelSuffix'), $course_data['course_code'], $id, $sid, $params);
                        if ($course_info) {
                            //By default new elements are invisible
                            if ($set_exercises_lp_invisible) {
                                require_once api_get_path(SYS_CODE_PATH) . 'newscorm/learnpathList.class.php';
                                $list = new LearnpathList('', $course_info['code'], $sid);
                                $flat_list = $list->get_flat_list();
                                if (!empty($flat_list)) {
                                    foreach ($flat_list as $lp_id => $data) {
                                        api_item_property_update($course_info, TOOL_LEARNPATH, $lp_id, 'invisible', api_get_user_id(), 0, 0, 0, 0, $sid);
                                        api_item_property_update($course_info, TOOL_LEARNPATH, $lp_id, 'invisible', api_get_user_id(), 0, 0, 0, 0);
                                    }
                                }
                                $quiz_table = Database::get_course_table(TABLE_QUIZ_TEST);
                                $course_id = $course_info['real_id'];
                                //@todo check this query
                                $sql = "UPDATE $quiz_table SET active = 0 WHERE c_id = $course_id ";
                                $result = Database::query($sql);
                            }
                            $new_short_courses[] = $course_info['code'];
                        }
                    }
                } else {
                    foreach ($short_courses as $course_data) {
                        $new_short_courses[] = $course_data['code'];
                    }
                }

                $short_courses = $new_short_courses;
                self::add_courses_to_session($sid, $short_courses, true);
                $short_courses = null;
            }
        }
        if ($copy_users) {
            // Register users from the original session to the new session
            $users = self::get_users_by_session($id);
            $short_users = array();
            if (is_array($users) && count($users) > 0) {
                foreach ($users as $user) {
                    $short_users[] = $user['user_id'];
                }
            }
            $users = null;
            //Subscribing in read only mode
            self::suscribe_users_to_session($sid, $short_users, SESSION_VISIBLE_READ_ONLY, true, false);
            $short_users = null;
        }
        return $sid;
    }

    /**
     * @param int $user_id
     * @param int $session_id
     * @return bool
     */
    static function user_is_general_coach($user_id, $session_id)
    {
        $session_id = intval($session_id);
        $user_id = intval($user_id);
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        $sql = "SELECT DISTINCT id
	         	FROM $session_table
	         	WHERE session.id_coach =  '" . $user_id . "' AND id = '$session_id'";
        $result = Database::query($sql);
        if ($result && Database::num_rows($result)) {
            return true;
        }
        return false;
    }

    /**
     * Get the number of sessions
     * @param  int ID of the URL we want to filter on (optional)
     * @return int Number of sessions
     */
    public static function count_sessions($access_url_id = null)
    {
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        $access_url_rel_session_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $sql = "SELECT count(id) FROM $session_table s";
        if (!empty($access_url_id) && $access_url_id == intval($access_url_id)) {
            $sql .= ", $access_url_rel_session_table u " .
                " WHERE s.id = u.session_id AND u.access_url_id = $access_url_id";
        }
        $res = Database::query($sql);
        $row = Database::fetch_row($res);
        return $row[0];
    }

    /**
     * @param int $id
     */
    static function protect_session_edit($id)
    {
        api_protect_admin_script(true);
        $session_info = self::fetch($id);
        if (!api_is_platform_admin() && api_get_setting('allow_session_admins_to_manage_all_sessions') != 'true') {
            if ($session_info['session_admin_id'] != api_get_user_id()) {
                api_not_allowed(true);
            }
        }
    }

    static function protect_teacher_session_edit($id) {
        if (!api_is_coach($id) && !api_is_platform_admin()) {
            api_not_allowed(true);
        } else {
            return true;
        }
    }

    /**
     * @param string $course_code
     * @return array
     */
    public static function get_session_by_course($course_code)
    {
        $table_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $table_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $course_code = Database::escape_string($course_code);
        $sql = "SELECT name, s.id
                FROM $table_session_course sc INNER JOIN $table_session s ON (sc.id_session = s.id)
                WHERE sc.course_code = '$course_code' ";
        $result = Database::query($sql);
        return Database::store_result($result);
    }

    /**
     * @param int $user_id
     * @param bool $ignore_visibility_for_admins
     * @return array
     */
    public static function get_sessions_by_user($user_id, $ignore_visibility_for_admins = false)
    {
        $session_categories = UserManager::get_sessions_by_category($user_id, false, $ignore_visibility_for_admins);
        $session_array = array();
        if (!empty($session_categories)) {
            foreach ($session_categories as $category) {
                if (isset($category['sessions'])) {
                    foreach ($category['sessions'] as $session) {
                        $session_array[] = $session;
                    }
                }
            }
        }
        return $session_array;
    }

    /**
     * @param string $file
     * @param bool $updateSession options:
     *  true: if the session exists it will be updated.
     *  false: if session exists a new session will be created adding a counter session1, session2, etc
     * @param int $defaultUserId
     * @param mixed $logger
     * @param array $extraFields convert a file row to an extra field. Example in CSV file there's a SessionID then it will
     * converted to extra_external_session_id if you set this: array('SessionId' => 'extra_external_session_id')
     * @param string $extraFieldId
     * @param int $daysCoachAccessBeforeBeginning
     * @param int $daysCoachAccessAfterBeginning
     * @param int $sessionVisibility
     * @param array $fieldsToAvoidUpdate
     * @param bool $deleteUsersNotInList
     * @param bool $updateCourseCoaches
     * @param bool $sessionWithCoursesModifier
     * @return array
     */
    static function importCSV(
        $file,
        $updateSession,
        $defaultUserId = null,
        $logger = null,
        $extraFields = array(),
        $extraFieldId = null,
        $daysCoachAccessBeforeBeginning = null,
        $daysCoachAccessAfterBeginning = null,
        $sessionVisibility = 1,
        $fieldsToAvoidUpdate = array(),
        $deleteUsersNotInList = false,
        $updateCourseCoaches = false,
        $sessionWithCoursesModifier = false
    ) {
        $content = file($file);

        $error_message = null;
        $session_counter = 0;

        if (empty($defaultUserId)) {
            $defaultUserId = api_get_user_id();
        }

        $eol = PHP_EOL;
        if (PHP_SAPI != 'cli') {
            $eol = '<br />';
        }

        $debug = false;
        if (isset($logger)) {
            $debug = true;
        }

        $extraParameters = null;

        if (!empty($daysCoachAccessBeforeBeginning) && !empty($daysCoachAccessAfterBeginning)) {
            $extraParameters .= ' , nb_days_access_before_beginning = '.intval($daysCoachAccessBeforeBeginning);
            $extraParameters .= ' , nb_days_access_after_end = '.intval($daysCoachAccessAfterBeginning);
        }

        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_course  = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $sessions = array();

        if (!api_strstr($content[0], ';')) {
            $error_message = get_lang('NotCSV');
        } else {
            $tag_names = array();

            foreach ($content as $key => $enreg) {
                $enreg = explode(';', trim($enreg));
                if ($key) {
                    foreach ($tag_names as $tag_key => $tag_name) {
                        $sessions[$key - 1][$tag_name] = $enreg[$tag_key];
                    }
                } else {
                    foreach ($enreg as $tag_name) {
                        $tag_names[] = api_preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag_name);
                    }
                    if (!in_array('SessionName', $tag_names) || !in_array('DateStart', $tag_names) || !in_array('DateEnd', $tag_names)) {
                        $error_message = get_lang('NoNeededData');
                        break;
                    }
                }
            }

            $sessionList = array();
            // Looping the sessions.
            foreach ($sessions as $enreg) {
                $user_counter = 0;
                $course_counter = 0;

                if (isset($extraFields) && !empty($extraFields)) {
                    foreach ($extraFields as $original => $to) {
                        $enreg[$to] = isset($enreg[$original]) ? $enreg[$original] : null;
                    }
                }

                $session_name = Database::escape_string($enreg['SessionName']);

                if (empty($session_name)) {
                    continue;
                }

                $date_start             = $enreg['DateStart'];
                $date_end               = $enreg['DateEnd'];
                $visibility             = isset($enreg['Visibility']) ? $enreg['Visibility'] : $sessionVisibility;
                $session_category_id    = isset($enreg['SessionCategory']) ? $enreg['SessionCategory'] : null;
                $sessionDescription     = isset($enreg['SessionDescription']) ? $enreg['SessionDescription'] : null;

                $extraSessionParameters = null;
                if (!empty($sessionDescription)) {
                    $extraSessionParameters = " , description = '".Database::escape_string($sessionDescription)."'";
                }

                // Searching a general coach.
                if (!empty($enreg['Coach'])) {
                    $coach_id = UserManager::get_user_id_from_username($enreg['Coach']);
                    if ($coach_id === false) {
                        // If the coach-user does not exist - I'm the coach.
                        $coach_id = $defaultUserId;
                    }
                } else {
                    $coach_id = $defaultUserId;
                }

                if (!$updateSession) {
                    // Always create a session.
                    $unique_name = false;
                    $i = 0;
                    // Change session name, verify that session doesn't exist.
                    $suffix = null;
                    while (!$unique_name) {
                        if ($i > 1) {
                            $suffix = ' - ' . $i;
                        }
                        $sql = 'SELECT 1 FROM ' . $tbl_session . ' WHERE name="' . $session_name . $suffix . '"';
                        $rs = Database::query($sql);

                        if (Database::result($rs, 0, 0)) {
                            $i++;
                        } else {
                            $unique_name = true;
                            $session_name .= $suffix;
                        }
                    }

                    // Creating the session.
                    $sql = "INSERT IGNORE INTO $tbl_session SET
                            name = '" . $session_name . "',
                            id_coach = '$coach_id',
                            date_start = '$date_start',
                            date_end = '$date_end',
                            visibility = '$visibility',
                            session_category_id = '$session_category_id',
                            session_admin_id = " . intval($defaultUserId) . $extraParameters . $extraSessionParameters;
                    Database::query($sql);

                    $session_id = Database::insert_id();
                    if ($debug) {
                        if ($session_id) {
                            foreach ($enreg as $key => $value) {
                                if (substr($key, 0, 6) == 'extra_') { //an extra field
                                    self::update_session_extra_field_value($session_id, substr($key, 6), $value);
                                }
                            }

                            $logger->addInfo("Sessions - Session created: #$session_id - $session_name");
                        } else {
                            $logger->addError("Sessions - Session NOT created: $session_name");
                        }
                    }
                    $session_counter++;
                } else {
                    $sessionId = null;
                    if (isset($extraFields) && !empty($extraFields) && !empty($enreg['extra_'.$extraFieldId])) {
                        $sessionId = self::get_session_id_from_original_id($enreg['extra_'.$extraFieldId], $extraFieldId);
                        if (empty($sessionId)) {
                            $my_session_result = false;
                        } else {
                            $my_session_result = true;
                        }
                    } else {
                        $my_session_result = self::get_session_by_name($enreg['SessionName']);
                    }

                    if ($my_session_result === false) {

                        // Creating a session.
                        $sql_session = "INSERT IGNORE INTO $tbl_session SET
                                name = '$session_name',
                                id_coach = '$coach_id',
                                date_start = '$date_start',
                                date_end = '$date_end',
                                visibility = '$visibility',
                                session_category_id = '$session_category_id' " . $extraParameters . $extraSessionParameters;

                        Database::query($sql_session);

                        // We get the last insert id.
                        $my_session_result = SessionManager::get_session_by_name($enreg['SessionName']);
                        $session_id = $my_session_result['id'];

                        if ($session_id) {

                            if ($session_id) {
                                foreach ($enreg as $key => $value) {
                                    if (substr($key, 0, 6) == 'extra_') { //an extra field
                                        self::update_session_extra_field_value($session_id, substr($key, 6), $value);
                                    }
                                }
                                if ($debug) {
                                    $logger->addInfo("Sessions - #$session_id created: $session_name");
                                }
                            } else {
                                if ($debug) {
                                    $logger->addError("Sessions - Session NOT created: $session_name");
                                }
                            }

                            // Delete session-user relation only for students
                            $sql = "DELETE FROM $tbl_session_user
                                    WHERE id_session = '$session_id' AND relation_type <> " . SESSION_RELATION_TYPE_RRHH;
                            Database::query($sql);

                            $sql = "DELETE FROM $tbl_session_course WHERE id_session = '$session_id'";
                            Database::query($sql);

                            // Delete session-course-user relationships students and coaches.
                            if ($updateCourseCoaches) {
                                $sql = "DELETE FROM $tbl_session_course_user WHERE id_session = '$session_id' AND status in ('0', '2')";
                                Database::query($sql);
                            } else {
                                // Delete session-course-user relation ships *only* for students.
                                $sql = "DELETE FROM $tbl_session_course_user WHERE id_session = '$session_id' AND status <> 2";
                                Database::query($sql);
                            }
                        }
                    } else {

                        // Updating the session.
                        $params = array(
                            'id_coach' => $coach_id,
                            'date_start' => $date_start,
                            'date_end' => $date_end,
                            'visibility' => $visibility,
                            'session_category_id' => $session_category_id
                        );

                        if (!empty($sessionDescription)) {
                            $params['description'] = $sessionDescription;
                        }

                        if (!empty($fieldsToAvoidUpdate)) {
                            foreach ($fieldsToAvoidUpdate as $field) {
                                unset($params[$field]);
                            }
                        }

                        if (isset($sessionId) && !empty($sessionId)) {
                            Database::update($tbl_session, $params, array('id = ?' => $sessionId));
                            $session_id = $sessionId;
                        } else {
                            Database::update($tbl_session, $params, array("name = '?' " => $enreg['SessionName']));

                            $row = Database::query("SELECT id FROM $tbl_session WHERE name = '$session_name'");
                            list($session_id) = Database::fetch_array($row);
                        }

                        if ($session_id) {

                            foreach ($enreg as $key => $value) {
                                if (substr($key, 0, 6) == 'extra_') { //an extra field
                                    self::update_session_extra_field_value($session_id, substr($key, 6), $value);
                                }
                            }

                            // Delete session-user relation only for students
                            $sql = "DELETE FROM $tbl_session_user
                                    WHERE id_session = '$session_id' AND relation_type <> " . SESSION_RELATION_TYPE_RRHH;
                            Database::query($sql);

                            $sql = "DELETE FROM $tbl_session_course WHERE id_session = '$session_id'";
                            Database::query($sql);

                            // Delete session-course-user relationships students and coaches.
                            if ($updateCourseCoaches) {
                                $sql = "DELETE FROM $tbl_session_course_user WHERE id_session = '$session_id' AND status in ('0', '2')";
                                Database::query($sql);
                            } else {
                                // Delete session-course-user relation ships *only* for students.
                                $sql = "DELETE FROM $tbl_session_course_user WHERE id_session = '$session_id' AND status <> 2";
                                Database::query($sql);
                            }
                        }
                    }
                    $session_counter++;
                }

                $sessionList[] = $session_id;

                $users = explode('|', $enreg['Users']);

                // Adding the relationship "Session - User" for students
                $userList = array();
                if (is_array($users)) {
                    foreach ($users as $user) {
                        $user_id = UserManager::get_user_id_from_username($user);
                        if ($user_id !== false) {
                            $userList[] = $user_id;
                            // Insert new users.
                            $sql = "INSERT IGNORE INTO $tbl_session_user SET
                                    id_user = '$user_id',
                                    id_session = '$session_id'";
                            Database::query($sql);
                            if ($debug) {
                                $logger->addInfo("Sessions - Adding User #$user_id ($user) to session #$session_id");
                            }
                            $user_counter++;
                        }
                    }
                }

                if ($deleteUsersNotInList) {
                    // Getting user in DB in order to compare to the new list.
                    $usersListInDatabase = self::get_users_by_session($session_id, 0);

                    if (!empty($usersListInDatabase)) {
                        if (empty($userList)) {
                            foreach ($usersListInDatabase as $userInfo) {
                                self::unsubscribe_user_from_session($session_id, $userInfo['user_id']);
                            }
                        } else {
                            foreach ($usersListInDatabase as $userInfo) {
                                if (!in_array($userInfo['user_id'], $userList)) {
                                    self::unsubscribe_user_from_session($session_id, $userInfo['user_id']);
                                }
                            }
                        }
                    }
                }

                $courses = explode('|', $enreg['Courses']);

                // See BT#6449
                $onlyAddFirstCoachOrTeacher = false;
                $removeAllTeachersFromCourse = false;

                if ($sessionWithCoursesModifier) {

                    if (count($courses) >= 2) {
                        // Only first teacher in course session;
                        $onlyAddFirstCoachOrTeacher = true;

                        // Remove all teachers from course.
                        $removeAllTeachersFromCourse = true;
                    }
                }

                foreach ($courses as $course) {
                    $courseArray = bracketsToArray($course);
                    $course_code = $courseArray[0];

                    if (CourseManager::course_exists($course_code)) {

                        $courseInfo = api_get_course_info($course_code);

                        // Adding the course to a session.
                        $sql_course = "INSERT IGNORE INTO $tbl_session_course
                                       SET course_code = '$course_code', id_session='$session_id'";
                        Database::query($sql_course);
                        $course_info = api_get_course_info($course_code);
                        SessionManager::installCourse($session_id, $course_info['real_id']);

                        if ($debug) {
                            $logger->addInfo("Sessions - Adding course '$course_code' to session #$session_id");
                        }

                        $course_counter++;

                        $course_coaches = isset($courseArray[1]) ? $courseArray[1] : null;
                        $course_users   = isset($courseArray[2]) ? $courseArray[2] : null;

                        $course_users   = explode(',', $course_users);
                        $course_coaches = explode(',', $course_coaches);

                        // Checking if the flag is set TeachersWillBeAddedAsCoachInAllCourseSessions (course_edit.php)
                        $addTeachersToSession = true;

                        if (array_key_exists('add_teachers_to_sessions_courses', $courseInfo)) {
                            $addTeachersToSession = $courseInfo['add_teachers_to_sessions_courses'];
                        }

                        // If any user provided for a course, use the users array.
                        if (empty($course_users)) {
                            if (!empty($userList)) {
                                SessionManager::subscribe_users_to_session_course($userList, $session_id, $course_code);
                                if ($debug) {
                                    $msg = "Sessions - Adding student list ".implode(', #', $userList)." to course: '$course_code' and session #$session_id";
                                    $logger->addInfo($msg);
                                }
                            }
                        }

                        // Adding coaches to session course user.
                        if (!empty($course_coaches)) {
                            $savedCoaches = array();
                            // only edit if add_teachers_to_sessions_courses is set.
                            if ($addTeachersToSession) {
                                // Adding course teachers as course session teachers.
                                $alreadyAddedTeachers = CourseManager::get_teacher_list_from_course_code($course_code);

                                if (!empty($alreadyAddedTeachers)) {
                                    $teachersToAdd = array();
                                    foreach ($alreadyAddedTeachers as $user) {
                                        $teachersToAdd[] = $user['username'];
                                    }
                                    $course_coaches = array_merge($course_coaches, $teachersToAdd);
                                }
                                foreach ($course_coaches as $course_coach) {
                                    $coach_id = UserManager::get_user_id_from_username($course_coach);
                                    if ($coach_id !== false) {
                                        // Just insert new coaches
                                        SessionManager::updateCoaches($session_id, $course_code, array($coach_id), false);

                                        if ($debug) {
                                            $logger->addInfo("Sessions - Adding course coach: user #$coach_id ($course_coach) to course: '$course_code' and session #$session_id");
                                        }
                                        $savedCoaches[] = $coach_id;
                                    } else {
                                        $error_message .= get_lang('UserDoesNotExist').' : '.$course_coach.$eol;
                                    }
                                }
                            }

                            // Custom courses/session coaches
                            $teacherToAdd = null;
                            if ($onlyAddFirstCoachOrTeacher == true) {
                                foreach ($course_coaches as $course_coach) {
                                    $coach_id = UserManager::get_user_id_from_username($course_coach);
                                    if ($coach_id !== false) {
                                        $teacherToAdd = $coach_id;
                                        break;
                                    }
                                }
                                if (!empty($teacherToAdd)) {
                                    SessionManager::updateCoaches($session_id, $course_code, array($teacherToAdd), true);
                                }
                            }

                            if ($removeAllTeachersFromCourse && !empty($teacherToAdd)) {
                                // Deleting all course teachers and adding the only coach as teacher.
                                $teacherList = CourseManager::get_teacher_list_from_course_code($course_code);
                                if (!empty($teacherList)) {
                                    foreach ($teacherList as $teacher) {
                                        CourseManager::unsubscribe_user($teacher['user_id'], $course_code);
                                    }
                                }
                                CourseManager::subscribe_user($teacherToAdd, $course_code, COURSEMANAGER);
                            }

                            // Continue default behaviour.
                            if ($onlyAddFirstCoachOrTeacher == false) {
                                // Checking one more time see BT#6449#note-149
                                $coaches = SessionManager::getCoachesByCourseSession($session_id, $course_code);

                                if (empty($coaches)) {
                                    foreach ($course_coaches as $course_coach) {
                                        $course_coach = trim($course_coach);
                                        $coach_id = UserManager::get_user_id_from_username($course_coach);
                                        if ($coach_id !== false) {
                                            // Just insert new coaches
                                            SessionManager::updateCoaches($session_id, $course_code, array($coach_id), false);

                                            if ($debug) {
                                                $logger->addInfo("Sessions - Adding course coach: user #$coach_id ($course_coach) to course: '$course_code' and session #$session_id");
                                            }
                                            $savedCoaches[] = $coach_id;
                                        } else {
                                            $error_message .= get_lang('UserDoesNotExist').' : '.$course_coach.$eol;
                                        }
                                    }
                                }
                            }
                        }

                        // Adding Students, updating relationship "Session - Course - User".
                        if (!empty($course_users)) {
                            foreach ($course_users as $user) {
                                $user_id = UserManager::get_user_id_from_username($user);

                                if ($user_id !== false) {
                                    SessionManager::subscribe_users_to_session_course(array($user_id), $session_id, $course_code);
                                    if ($debug) {
                                        $logger->addInfo("Sessions - Adding student: user #$user_id ($user) to course: '$course_code' and session #$session_id");
                                    }
                                } else {
                                    $error_message .= get_lang('UserDoesNotExist').': '.$user.$eol;
                                }
                            }
                        }

                        $course_info = CourseManager::get_course_information($course_code);
                        $inserted_in_course[$course_code] = $course_info['title'];
                    }
                }
                $access_url_id = api_get_current_access_url_id();
                UrlManager::add_session_to_url($session_id, $access_url_id);
                $sql_update_users = "UPDATE $tbl_session SET nbr_users = '$user_counter', nbr_courses = '$course_counter' WHERE id = '$session_id'";
                Database::query($sql_update_users);
            }
        }
        return array(
            'error_message' => $error_message,
            'session_counter' => $session_counter,
            'session_list' => $sessionList
        );
    }

    /**
     * @param int $sessionId
     * @param string $courseCode
     * @return array
     */
    public static function getCoachesByCourseSession($sessionId, $courseCode)
    {
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sessionId = intval($sessionId);
        $courseCode = Database::escape_string($courseCode);

        $sql = "SELECT id_user FROM $tbl_session_rel_course_rel_user WHERE id_session = '$sessionId' AND course_code = '$courseCode' AND status = 2";
        $result = Database::query($sql);

        $coaches = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_row($result)) {
                $coaches[] = $row[0];
            }
        }
        return $coaches;
    }

    /**
     * Get all coaches added in the session - course relationship
     * @param int $sessionId
     * @return array
     */
    public static function getCoachesBySession($sessionId)
    {
        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sessionId = intval($sessionId);

        $sql = "SELECT DISTINCT id_user FROM $table
                WHERE id_session = '$sessionId' AND status = 2";
        $result = Database::query($sql);

        $coaches = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $coaches[] = $row['id_user'];
            }
        }
        return $coaches;
    }

    /**
     * @param int $userId
     * @return array
     */
    public static function getAllCoursesFromAllSessionFromDrh($userId)
    {
        $sessions = SessionManager::get_sessions_followed_by_drh($userId);
        $coursesFromSession = array();
        if (!empty($sessions)) {
            foreach ($sessions as $session) {
                $courseList = SessionManager::get_course_list_by_session_id($session['id']);
                foreach ($courseList as $course) {
                    $coursesFromSession[] = $course['code'];
                }
            }
        }
        return $coursesFromSession;
    }

    /**
     * @param string $status
     * @param int $userId
     * @param bool $getCount
     * @param int  $from
     * @param int  $numberItems
     * @param int $column
     * @param string $direction
     * @param string $keyword
     * @param string $active
     * @param string $lastConnectionDate
     * @param array $sessionIdList
     * @param array $studentIdList
     * @param int $filterByStatus
     * @return array|int
     */
    public static function getAllUsersFromCoursesFromAllSessionFromStatus(
        $status,
        $userId,
        $getCount = false,
        $from = null,
        $numberItems = null,
        $column = 1,
        $direction = 'asc',
        $keyword = null,
        $active = null,
        $lastConnectionDate = null,
        $sessionIdList = array(),
        $studentIdList = array(),
        $filterByStatus = null
    ) {
        $filterByStatus = intval($filterByStatus);

        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        $direction = in_array(strtolower($direction), array('asc', 'desc')) ? $direction : 'asc';
        $column = Database::escape_string($column);
        $userId = intval($userId);

        $limitCondition = null;

        if (isset($from) && isset($numberItems)) {
            $from = intval($from);
            $numberItems = intval($numberItems);
            $limitCondition = "LIMIT $from, $numberItems";
        }

        $urlId = api_get_current_access_url_id();

        $sessionConditions = null;
        $courseConditions = null;
        $userConditions = null;

        if (isset($active)) {
            $active = intval($active);
            $userConditions .= " AND active = $active";
        }

        switch ($status) {
            case 'drh':
                // Classic DRH
                if (empty($studentIdList)) {
                    $studentListSql = UserManager::get_users_followed_by_drh(
                        $userId,
                        $filterByStatus,
                        true,
                        false
                    );
                    $studentIdList = array_keys($studentListSql);
                    $studentListSql = "'".implode("','", $studentIdList)."'";
                } else {
                    $studentIdList = array_map('intval', $studentIdList);
                    $studentListSql = "'".implode("','", $studentIdList)."'";
                }
                if (!empty($studentListSql)) {
                    $userConditions = " AND u.user_id IN (".$studentListSql.") ";
                }
                break;
            case 'drh_all':
                // Show all by DRH
                if (empty($sessionIdList)) {
                    $sessionsListSql = SessionManager::get_sessions_followed_by_drh(
                        $userId,
                        null,
                        null,
                        false,
                        true,
                        true
                    );
                } else {
                    $sessionIdList = array_map('intval', $sessionIdList);
                    $sessionsListSql = "'".implode("','", $sessionIdList)."'";
                }
                if (!empty($sessionsListSql)) {
                    $sessionConditions = " AND s.id IN (".$sessionsListSql.") ";
                }
                break;
            case 'session_admin';
                $sessionConditions = " AND s.id_coach = $userId ";
                break;
            case 'admin':
                break;
            case 'teacher':
                $sessionConditions = " AND s.id_coach = $userId ";

                // $statusConditions = " AND s.id_coach = $userId";
                break;
        }

        $select = "SELECT DISTINCT u.* ";
        $masterSelect = "SELECT DISTINCT * FROM ";

        if ($getCount) {
            $select = "SELECT DISTINCT u.user_id ";
            $masterSelect = "SELECT COUNT(DISTINCT(user_id)) as count FROM ";
        }

        if (!empty($filterByStatus)) {
            $userConditions .= " AND u.status = ".$filterByStatus;
        }

        if (!empty($lastConnectionDate)) {
            $lastConnectionDate = Database::escape_string($lastConnectionDate);
            $userConditions .=  " AND u.last_login <= '$lastConnectionDate' ";
        }

        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $userConditions .= " AND (
                u.username LIKE '%$keyword%' OR
                u.firstname LIKE '%$keyword%' OR
                u.lastname LIKE '%$keyword%' OR
                u.official_code LIKE '%$keyword%' OR
                u.email LIKE '%$keyword%'
            )";
        }

        $where = " WHERE
                   access_url_id = $urlId
                   $userConditions
        ";

        $sql = "$masterSelect (
                ($select
                FROM $tbl_session s
                    INNER JOIN $tbl_session_rel_course_rel_user su ON (s.id = su.id_session)
                    INNER JOIN $tbl_user u ON (u.user_id = su.id_user AND s.id = id_session)
                    INNER JOIN $tbl_session_rel_access_url url ON (url.session_id = s.id)
                    $where
                    $sessionConditions
                )
                UNION (
                    $select
                    FROM $tbl_course c
                    INNER JOIN $tbl_course_user cu ON (cu.course_code = c.code)
                    INNER JOIN $tbl_user u ON (u.user_id = cu.user_id)
                    INNER JOIN $tbl_course_rel_access_url url ON (url.course_code = c.code)
                    $where
                    $courseConditions
                )
                ) as t1
                ";

        if ($getCount) {
            ///var_dump($sql);
            $result = Database::query($sql);
            $count = 0;
            if (Database::num_rows($result)) {
                $rows = Database::fetch_array($result);
                $count = $rows['count'];
            }
            return $count;
        }

        if (!empty($column) && !empty($direction)) {
            $column = str_replace('u.', '', $column);
            $sql .= " ORDER BY $column $direction ";
        }

        $sql .= $limitCondition;
        $result = Database::query($sql);
        $result = Database::store_result($result);

        return $result ;
    }

    /**
     * @param int $sessionId
     * @param string $courseCode
     * @param array $coachList
     * @param bool $deleteCoachesNotInList
     */
    public static function updateCoaches($sessionId, $courseCode, $coachList, $deleteCoachesNotInList = false)
    {
        $currentCoaches = self::getCoachesByCourseSession($sessionId, $courseCode);

        if (!empty($coachList)) {
            foreach ($coachList as $userId) {
                self::set_coach_to_course_session($userId, $sessionId, $courseCode);
            }
        }

        if ($deleteCoachesNotInList) {
            if (!empty($coachList)) {
                $coachesToDelete = array_diff($currentCoaches, $coachList);
            } else {
                $coachesToDelete = $currentCoaches;
            }

            if (!empty($coachesToDelete)) {
                foreach ($coachesToDelete as $userId) {
                    self::set_coach_to_course_session($userId, $sessionId, $courseCode, true);
                }
            }
        }
    }

    /**
     * @param array $sessions
     * @param array $sessionsDestination
     * @return string
     */
    public static function copyStudentsFromSession($sessions, $sessionsDestination)
    {
        $messages = array();
        if (!empty($sessions)) {
            foreach ($sessions as $sessionId) {
                $sessionInfo = self::fetch($sessionId);
                $userList = self::get_users_by_session($sessionId, 0);
                if (!empty($userList)) {
                    $newUserList = array();
                    $userToString = null;
                    foreach ($userList as $userInfo) {
                        $newUserList[] = $userInfo['user_id'];
                        $userToString .= $userInfo['firstname'] . ' ' . $userInfo['lastname'] . '<br />';
                    }

                    if (!empty($sessionsDestination)) {
                        foreach ($sessionsDestination as $sessionDestinationId) {
                            $sessionDestinationInfo = self::fetch($sessionDestinationId);
                            $messages[] = Display::return_message(
                                sprintf(get_lang('AddingStudentsFromSessionXToSessionY'), $sessionInfo['name'], $sessionDestinationInfo['name']), 'info', false
                            );
                            if ($sessionId == $sessionDestinationId) {
                                $messages[] = Display::return_message(sprintf(get_lang('SessionXSkipped'), $sessionDestinationId), 'warning', false);
                                continue;
                            }
                            $messages[] = Display::return_message(get_lang('StudentList') . '<br />' . $userToString, 'info', false);
                            SessionManager::suscribe_users_to_session($sessionDestinationId, $newUserList, SESSION_VISIBLE_READ_ONLY, false);
                        }
                    } else {
                        $messages[] = Display::return_message(get_lang('NoDestinationSessionProvided'), 'warning');
                    }
                } else {
                    $messages[] = Display::return_message(get_lang('NoStudentsFoundForSession') . ' #' . $sessionInfo['name'], 'warning');
                }
            }
        } else {
            $messages[] = Display::return_message(get_lang('NoData'), 'warning');
        }
        return $messages;
    }

    /**
     * Assign coaches of a session(s) as teachers to a given course (or courses)
     * @param array A list of session IDs
     * @param array A list of course IDs
     * @return string
     */
    public static function copyCoachesFromSessionToCourse($sessions, $courses)
    {
        $coachesPerSession = array();
        foreach ($sessions as $sessionId) {
            $coaches = self::getCoachesBySession($sessionId);
            $coachesPerSession[$sessionId] = $coaches;
        }

        $result = array();

        if (!empty($courses)) {
            foreach ($courses as $courseId) {
                $courseInfo = api_get_course_info_by_id($courseId);
                foreach ($coachesPerSession as $sessionId => $coachList) {
                    CourseManager::updateTeachers(
                        $courseInfo['code'], $coachList, false, false, false
                    );
                    $result[$courseInfo['code']][$sessionId] = $coachList;
                }
            }
        }
        $sessionUrl = api_get_path(WEB_CODE_PATH) . 'admin/resume_session.php?id_session=';

        $htmlResult = null;

        if (!empty($result)) {
            foreach ($result as $courseCode => $data) {
                $url = api_get_course_url($courseCode);
                $htmlResult .= sprintf(get_lang('CoachesSubscribedAsATeacherInCourseX'), Display::url($courseCode, $url, array('target' => '_blank')));
                foreach ($data as $sessionId => $coachList) {
                    $sessionInfo = self::fetch($sessionId);
                    $htmlResult .= '<br />';
                    $htmlResult .= Display::url(
                        get_lang('Session') . ': ' . $sessionInfo['name'] . ' <br />', $sessionUrl . $sessionId, array('target' => '_blank')
                    );
                    $teacherList = array();
                    foreach ($coachList as $coachId) {
                        $userInfo = api_get_user_info($coachId);
                        $teacherList[] = $userInfo['complete_name'];
                    }
                    if (!empty($teacherList)) {
                        $htmlResult .= implode(', ', $teacherList);
                    } else {
                        $htmlResult .= get_lang('NothingToAdd');
                    }
                }
                $htmlResult .= '<br />';
            }
            $htmlResult = Display::return_message($htmlResult, 'normal', false);
        }
        return $htmlResult;
    }

    /**
     * @param string $keyword
     * @param string $active
     * @param string $lastConnectionDate
     * @param array $sessionIdList
     * @param array $studentIdList
     * @param int $userStatus STUDENT|COURSEMANAGER constants
     *
     * @return array|int
     */
    public static function getCountUserTracking(
        $keyword = null,
        $active = null,
        $lastConnectionDate = null,
        $sessionIdList = array(),
        $studentIdList = array(),
        $filterUserStatus = null
    ) {
        $userId = api_get_user_id();
        $drhLoaded = false;

        if (api_is_drh()) {
            if (api_drh_can_access_all_session_content()) {
                $count = self::getAllUsersFromCoursesFromAllSessionFromStatus(
                    'drh_all',
                    $userId,
                    true,
                    null,
                    null,
                    null,
                    null,
                    $keyword,
                    $active,
                    $lastConnectionDate,
                    $sessionIdList,
                    $studentIdList,
                    $filterUserStatus
                );
                $drhLoaded = true;
            }
        }

        if ($drhLoaded == false) {
            $count = UserManager::getUsersFollowedByUser(
                $userId,
                $filterUserStatus,
                false,
                false,
                true,
                null,
                null,
                null,
                null,
                $active,
                $lastConnectionDate,
                COURSEMANAGER,
                $keyword
            );
        }

        return $count;
    }

    /**
     * Get teachers followed by a user
     * @param int $userId
     * @param int $active
     * @param string $lastConnectionDate
     * @param bool $getCount
     * @param array $sessionIdList
     * @return array|int
     */
    public static function getTeacherTracking(
        $userId,
        $active = 1,
        $lastConnectionDate = null,
        $getCount = false,
        $sessionIdList = array()
    ) {
        $teacherResult = array();

        if (api_is_drh() || api_is_platform_admin()) {
            // Followed teachers by drh
            if (api_drh_can_access_all_session_content()) {
                if (empty($sessionIdList)) {
                    $sessions = SessionManager::get_sessions_followed_by_drh($userId);
                    $sessionIdList = array();
                    foreach ($sessions as $session) {
                        $sessionIdList[] = $session['id'];
                    }
                }

                $sessionIdList = array_map('intval', $sessionIdList);
                $sessionToString = implode("', '",  $sessionIdList);

                $course = Database::get_main_table(TABLE_MAIN_COURSE);
                $sessionCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
                $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);

                // Select the teachers.
                $sql = "SELECT DISTINCT(cu.user_id) FROM $course c
                        INNER JOIN $sessionCourse src ON c.code = src.course_code
                        INNER JOIN $courseUser cu ON (cu.course_code = c.code)
		                WHERE src.id_session IN ('$sessionToString') AND cu.status = 1";
                $result = Database::query($sql);
                $teacherListId = array();
                while($row = Database::fetch_array($result, 'ASSOC')) {
                    $teacherListId[$row['user_id']] = $row['user_id'];
                }
            } else {
                $teacherResult = UserManager::get_users_followed_by_drh($userId, COURSEMANAGER);
                $teacherListId = array();
                foreach ($teacherResult as $userInfo) {
                    $teacherListId[] = $userInfo['user_id'];
                }
            }
        }

        if (!empty($teacherListId)) {
            $tableUser = Database::get_main_table(TABLE_MAIN_USER);

            $select = "SELECT DISTINCT u.* ";
            if ($getCount) {
                $select = "SELECT count(DISTINCT(u.user_id)) as count";
            }

            $sql = "$select FROM $tableUser u";

            if (!empty($lastConnectionDate)) {
                $tableLogin = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
                //$sql .= " INNER JOIN $tableLogin l ON (l.login_user_id = u.user_id) ";
            }
            $active = intval($active);
            $teacherListId = implode("','", $teacherListId);
            $where = " WHERE u.active = $active AND u.user_id IN ('$teacherListId') ";

            if (!empty($lastConnectionDate)) {
                $lastConnectionDate = Database::escape_string($lastConnectionDate);
                //$where .= " AND l.login_date <= '$lastConnectionDate' ";
            }

            $sql .= $where;
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                if ($getCount) {
                    $row = Database::fetch_array($result);
                    return $row['count'];
                } else {

                    return Database::store_result($result, 'ASSOC');
                }
            }
        }

        return 0;
    }

    /**
     * Get the list of course tools that have to be dealt with in case of
     * registering any course to a session
     * @return array The list of tools to be dealt with (literal names)
     */
    public static function getCourseToolToBeManaged()
    {
        return array(
            'courseDescription',
            'courseIntroduction'
        );
    }

    /**
     * Calls the methods bound to each tool when a course is registered into a session
     * @param int $sessionId
     * @param int $courseId
     * @return void
     */
    public static function installCourse($sessionId, $courseId)
    {
        return true;
        $toolList = self::getCourseToolToBeManaged();

        foreach ($toolList as $tool) {
            $method = 'add' . $tool;
            if (method_exists(get_class(), $method)) {
                self::$method($sessionId, $courseId);
            }
        }
    }

    /**
     * Calls the methods bound to each tool when a course is unregistered from
     * a session
     * @param int $sessionId
     * @param int $courseId
     */
    public static function unInstallCourse($sessionId, $courseId)
    {
        return true;
        $toolList = self::getCourseToolToBeManaged();

        foreach ($toolList as $tool) {
            $method = 'remove' . $tool;
            if (method_exists(get_class(), $method)) {
                self::$method($sessionId, $courseId);
            }
        }
    }

    /**
     * @param int $sessionId
     * @param int $courseId
     */
    public static function addCourseIntroduction($sessionId, $courseId)
    {
        // @todo create a tool intro lib
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);

        $TBL_INTRODUCTION = Database::get_course_table(TABLE_TOOL_INTRO);
        $sql = "SELECT * FROM $TBL_INTRODUCTION WHERE c_id = $courseId";
        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');

        if (!empty($result)) {
            foreach ($result as $result) {
                // @todo check if relation exits.
                $result['session_id'] = $sessionId;
                Database::insert($TBL_INTRODUCTION, $result);
            }
        }
    }

    /**
     * @param int $sessionId
     * @param int $courseId
     */
    public static function removeCourseIntroduction($sessionId, $courseId)
    {
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);
        $TBL_INTRODUCTION = Database::get_course_table(TABLE_TOOL_INTRO);
        $sql = "DELETE FROM $TBL_INTRODUCTION WHERE c_id = $courseId AND session_id = $sessionId";
        Database::query($sql);
    }

    /**
     * @param int $sessionId
     * @param int $courseId
     */
    public static function addCourseDescription($sessionId, $courseId)
    {
        /* $description = new CourseDescription();
          $descriptions = $description->get_descriptions($courseId);
          foreach ($descriptions as $description) {
          } */
    }

    /**
     * @param int $sessionId
     * @param int $courseId
     */
    public static function removeCourseDescription($sessionId, $courseId)
    {

    }

    /**
     * @param array $list  format see self::importSessionDrhCSV()
     * @param bool $sendEmail
     * @param bool $removeOldRelationShips
     * @return string
     */
    public static function subscribeDrhToSessionList($userSessionList, $sendEmail, $removeOldRelationShips)
    {
        if (!empty($userSessionList)) {
            foreach ($userSessionList as $userId => $data) {
                $sessionList = array();
                foreach ($data['session_list'] as $sessionInfo) {
                    $sessionList[] = $sessionInfo['session_id'];
                }
                $userInfo = $data['user_info'];
                self::suscribe_sessions_to_hr_manager($userInfo, $sessionList, $sendEmail, $removeOldRelationShips);
            }
        }
    }

    /**
     * @param array $userSessionList format see self::importSessionDrhCSV()
     */
    public static function checkSubscribeDrhToSessionList($userSessionList)
    {
        $message = null;
        if (!empty($userSessionList)) {
            if (!empty($userSessionList)) {
                foreach ($userSessionList as $userId => $data) {
                    $userInfo = $data['user_info'];

                    $sessionListSubscribed = self::get_sessions_followed_by_drh($userId);
                    if (!empty($sessionListSubscribed)) {
                        $sessionListSubscribed = array_keys($sessionListSubscribed);
                    }

                    $sessionList = array();
                    if (!empty($data['session_list'])) {
                        foreach ($data['session_list'] as $sessionInfo) {
                            if (in_array($sessionInfo['session_id'], $sessionListSubscribed)) {
                                $sessionList[] = $sessionInfo['session_info']['name'];
                            }
                        }
                    }

                    $message .= '<strong>' . get_lang('User') . '</strong> ' . $userInfo['complete_name'] . ' <br />';

                    if (!in_array($userInfo['status'], array(DRH)) && !api_is_platform_admin_by_id($userInfo['user_id'])) {
                        $message .= get_lang('UserMustHaveTheDrhRole') . '<br />';
                        continue;
                    }

                    if (!empty($sessionList)) {
                        $message .= '<strong>' . get_lang('Sessions') . ':</strong> <br />';
                        $message .= implode(', ', $sessionList) . '<br /><br />';
                    } else {
                        $message .= get_lang('NoSessionProvided') . ' <br /><br />';
                    }
                }
            }
        }
        return $message;
    }

    /**
     * @param string $file
     * @param bool $sendEmail
     * @param bool $removeOldRelationShips
     */
    public static function importSessionDrhCSV($file, $sendEmail, $removeOldRelationShips)
    {
        $list = Import::csv_reader($file);

        if (!empty($list)) {
            $userSessionList = array();
            foreach ($list as $data) {
                $userInfo = api_get_user_info_from_username($data['Username']);
                $sessionInfo = self::get_session_by_name($data['SessionName']);

                if (!empty($userInfo) && !empty($sessionInfo)) {
                    $userSessionList[$userInfo['user_id']]['session_list'][] = array(
                        'session_id' => $sessionInfo['id'],
                        'session_info' => $sessionInfo
                    );
                    $userSessionList[$userInfo['user_id']]['user_info'] = $userInfo;
                }
            }

            self::subscribeDrhToSessionList($userSessionList, $sendEmail, $removeOldRelationShips);
            return self::checkSubscribeDrhToSessionList($userSessionList);
        }
    }
}

<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use \ExtraField as ExtraFieldModel;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Session;

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

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Fetches a session from the database
     * @param  int $id Session Id
     *
     * @return  array   Session details
     */
    public static function fetch($id)
    {
        $em = Database::getManager();
        /** @var Session $session */
        $session = $em->find('ChamiloCoreBundle:Session', $id);

        if (!$session) {
            return [];
        }

        return [
            'id' => $session->getId(),
            'id_coach' => $session->getGeneralCoach() ? $session->getGeneralCoach()->getId() : null,
            'session_category_id' => $session->getCategory() ? $session->getCategory()->getId() : null,
            'name' => $session->getName(),
            'description' => $session->getDescription(),
            'show_description' => $session->getShowDescription(),
            'duration' => $session->getDuration(),
            'nbr_courses' => $session->getNbrCourses(),
            'nbr_users' => $session->getNbrUsers(),
            'nbr_classes' => $session->getNbrClasses(),
            'session_admin_id' => $session->getSessionAdminId(),
            'visibility' => $session->getVisibility(),
            'promotion_id' => $session->getPromotionId(),
            'display_start_date' => $session->getDisplayStartDate()
                ? $session->getDisplayStartDate()->format('Y-m-d H:i:s')
                : null,
            'display_end_date' => $session->getDisplayEndDate()
                ? $session->getDisplayEndDate()->format('Y-m-d H:i:s')
                : null,
            'access_start_date' => $session->getAccessStartDate()
                ? $session->getAccessStartDate()->format('Y-m-d H:i:s')
                : null,
            'access_end_date' => $session->getAccessEndDate()
                ? $session->getAccessEndDate()->format('Y-m-d H:i:s')
                : null,
            'coach_access_start_date' => $session->getCoachAccessStartDate()
                ? $session->getCoachAccessStartDate()->format('Y-m-d H:i:s')
                : null,
            'coach_access_end_date' => $session->getCoachAccessEndDate()
                ? $session->getCoachAccessEndDate()->format('Y-m-d H:i:s')
                : null,
            'send_subscription_notification' => $session->getSendSubscriptionNotification()
        ];
    }

    /**
     * Create a session
     * @author Carlos Vargas <carlos.vargas@beeznest.com>, from existing code
     * @param   string  $name
     * @param   string  $startDate (YYYY-MM-DD hh:mm:ss)
     * @param   string  $endDate (YYYY-MM-DD hh:mm:ss)
     * @param   string  $displayStartDate (YYYY-MM-DD hh:mm:ss)
     * @param   string  $displayEndDate (YYYY-MM-DD hh:mm:ss)
     * @param   string  $coachStartDate (YYYY-MM-DD hh:mm:ss)
     * @param   string  $coachEndDate (YYYY-MM-DD hh:mm:ss)
     * @param   mixed   $coachId If integer, this is the session coach id, if string, the coach ID will be looked for from the user table
     * @param   integer $sessionCategoryId ID of the session category in which this session is registered
     * @param   integer $visibility Visibility after end date (0 = read-only, 1 = invisible, 2 = accessible)
     * @param   bool    $fixSessionNameIfExists
     * @param   string  $duration
     * @param   string  $description Optional. The session description
     * @param   int     $showDescription Optional. Whether show the session description
     * @param   array   $extraFields
     * @param   int     $sessionAdminId Optional. If this sessions was created by a session admin, assign it to him
     * @param boolean $sendSubscriptionNotification Optional.
     *          Whether send a mail notification to users being subscribed
     * @todo use an array to replace all this parameters or use the model.lib.php ...
     * @return mixed       Session ID on success, error message otherwise
     * */
    public static function create_session(
        $name,
        $startDate,
        $endDate,
        $displayStartDate,
        $displayEndDate,
        $coachStartDate,
        $coachEndDate,
        $coachId,
        $sessionCategoryId,
        $visibility = 1,
        $fixSessionNameIfExists = false,
        $duration = null,
        $description = null,
        $showDescription = 0,
        $extraFields = array(),
        $sessionAdminId = 0,
        $sendSubscriptionNotification = false
    ) {
        global $_configuration;

        //Check portal limits
        $access_url_id = 1;

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
        }

        if (is_array($_configuration[$access_url_id]) &&
            isset($_configuration[$access_url_id]['hosting_limit_sessions']) &&
            $_configuration[$access_url_id]['hosting_limit_sessions'] > 0
        ) {
            $num = self::count_sessions();
            if ($num >= $_configuration[$access_url_id]['hosting_limit_sessions']) {
                api_warn_hosting_contact('hosting_limit_sessions');
                return get_lang('PortalSessionsLimitReached');
            }
        }

        $name = Database::escape_string(trim($name));
        $sessionCategoryId = intval($sessionCategoryId);
        $visibility = intval($visibility);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        $startDate = Database::escape_string($startDate);
        $endDate = Database::escape_string($endDate);

        if (empty($name)) {
            $msg = get_lang('SessionNameIsRequired');
            return $msg;
        } elseif (empty($coachId)) {
            $msg = get_lang('CoachIsRequired');
            return $msg;
        } elseif (!empty($startDate) && !api_is_valid_date($startDate, 'Y-m-d H:i') && !api_is_valid_date($startDate, 'Y-m-d H:i:s')) {
            $msg = get_lang('InvalidStartDate');
            return $msg;
        } elseif (!empty($endDate) && !api_is_valid_date($endDate, 'Y-m-d H:i') && !api_is_valid_date($endDate, 'Y-m-d H:i:s')) {
            $msg = get_lang('InvalidEndDate');
            return $msg;
        } elseif (!empty($startDate) && !empty($endDate) && $startDate >= $endDate) {
            $msg = get_lang('StartDateShouldBeBeforeEndDate');
            return $msg;
        } else {
            $ready_to_create = false;
            if ($fixSessionNameIfExists) {
                $name = self::generateNextSessionName($name);
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
                $sessionAdminId = !empty($sessionAdminId) ? $sessionAdminId : api_get_user_id();
                $values = array(
                    'name' => $name,
                    'id_coach' => $coachId,
                    'session_admin_id' => $sessionAdminId,
                    'visibility' => $visibility,
                    'description' => $description,
                    'show_description' => intval($showDescription),
                    'send_subscription_notification' => (int) $sendSubscriptionNotification
                );

                if (!empty($startDate)) {
                    $values['access_start_date'] = api_get_utc_datetime($startDate);
                }

                if (!empty($endDate)) {
                    $values['access_end_date'] = api_get_utc_datetime($endDate);
                }

                if (!empty($displayStartDate)) {
                    $values['display_start_date'] = api_get_utc_datetime($displayStartDate);
                }

                if (!empty($displayEndDate)) {
                    $values['display_end_date'] = api_get_utc_datetime($displayEndDate);
                }

                if (!empty($coachStartDate)) {
                    $values['coach_access_start_date'] = api_get_utc_datetime($coachStartDate);
                }
                if (!empty($coachEndDate)) {
                    $values['coach_access_end_date'] = api_get_utc_datetime($coachEndDate);
                }

                if (!empty($sessionCategoryId)) {
                    $values['session_category_id'] = $sessionCategoryId;
                }

                $session_id = Database::insert($tbl_session, $values);

                $duration = intval($duration);

                if (!empty($duration)) {
                    $sql = "UPDATE $tbl_session SET
                        access_start_date = NULL,
                        access_end_date = NULL,
                        display_start_date = NULL,
                        display_end_date = NULL,
                        coach_access_start_date = NULL,
                        coach_access_end_date = NULL,
                        duration = $duration
                    WHERE id = $session_id";
                    Database::query($sql);
                } else {
                    $sql = "UPDATE $tbl_session
                        SET duration = 0
                        WHERE id = $session_id";
                    Database::query($sql);
                }

                if (!empty($session_id)) {
                    $extraFields['item_id'] = $session_id;

                    $sessionFieldValue = new ExtraFieldValue('session');
                    $sessionFieldValue->saveFieldValues($extraFields);

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
                    Event::addEvent(
                        LOG_SESSION_CREATE,
                        LOG_SESSION_ID,
                        $session_id,
                        api_get_utc_datetime(),
                        $user_id
                    );
                }

                return $session_id;
            }
        }
    }

    /**
     * @param string $name
     *
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
     *
     * @return mixed
     */
    public static function get_count_admin($where_condition = '')
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_access_url_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $where = 'WHERE 1=1 ';
        $user_id = api_get_user_id();

        $extraJoin = '';

        if (api_is_session_admin() &&
            api_get_setting('allow_session_admins_to_manage_all_sessions') == 'false'
        ) {
            $where .= " AND (
                            s.session_admin_id = $user_id  OR
                            sru.user_id = '$user_id' AND
                            sru.relation_type = '" . SESSION_RELATION_TYPE_RRHH . "'
                            )
                      ";

            $extraJoin = " INNER JOIN $tbl_session_rel_user sru
                           ON sru.session_id = s.id ";
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
            $courseCondition = " INNER JOIN $table course_rel_session
                                 ON (s.id = course_rel_session.session_id)
                                 INNER JOIN $tableCourse c
                                 ON (course_rel_session.c_id = c.id)
                                ";
        }

        $sql = "SELECT COUNT(id) as total_rows FROM (
                SELECT DISTINCT
                 IF (
					(s.access_start_date <= '$today' AND '$today' <= s.access_end_date) OR
                    (s.access_start_date IS NULL AND s.access_end_date  = IS NULL ) OR
					(s.access_start_date <= '$today' AND s.access_end_date IS NULL) OR
					('$today' <= s.access_end_date AND s.access_start_date IS NULL)
				, 1, 0) as session_active,
                s.id
                FROM $tbl_session s
                LEFT JOIN $tbl_session_category sc
                ON s.session_category_id = sc.id
                INNER JOIN $tbl_user u
                ON s.id_coach = u.user_id
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
					(s.access_start_date <= '$today' AND '$today' <= s.access_end_date) OR
                    (s.access_start_date IS NULL AND s.access_end_date IS NULL) OR
					(s.access_start_date <= '$today' AND s.access_end_date IS NULL) OR
					('$today' <= s.access_end_date AND s.access_start_date IS NULL)
				, 1, 0)
				as session_active,
				s.id
                FROM $tbl_session s
                    LEFT JOIN  $tbl_session_category sc
                    ON s.session_category_id = sc.id
                    INNER JOIN $tbl_user u ON s.id_coach = u.user_id
                    INNER JOIN $table_access_url_rel_session ar
                    ON ar.session_id = s.id
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
     * Gets the admin session list callback of the session/session_list.php page
     * @param array $options order and limit keys
     * @param boolean $get_count Whether to get all the results or only the count
     * @return mixed Integer for number of rows, or array of results
     * @assert (array(),true) !== false
     */
    public static function get_sessions_admin($options = array(), $get_count = false)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $sessionCategoryTable = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

        $where = 'WHERE 1 = 1 ';
        $user_id = api_get_user_id();

        if (!api_is_platform_admin()) {
            if (api_is_session_admin() &&
                api_get_setting('allow_session_admins_to_manage_all_sessions') == 'false'
            ) {
                $where .=" AND s.session_admin_id = $user_id ";
            }
        }

        if (!api_is_platform_admin() && api_is_teacher() &&
            api_get_setting('allow_teachers_to_create_sessions') == 'true'
        ) {
            $where .=" AND s.id_coach = $user_id ";
        }

        $extra_field = new ExtraFieldModel('session');
        $conditions = $extra_field->parseConditions($options);
        $inject_joins = $conditions['inject_joins'];
        $where .= $conditions['where'];
        $inject_where = $conditions['inject_where'];
        $inject_extra_fields = $conditions['inject_extra_fields'];

        $order = $conditions['order'];
        $limit = $conditions['limit'];

        $isMakingOrder = false;

        if ($get_count == true) {
            $select = " SELECT count(*) as total_rows";
        } else {
            $select =
                "SELECT DISTINCT ".
                " s.name, ".
                " s.display_start_date, ".
                " s.display_end_date, ".
                " access_start_date, ".
                " access_end_date, ".
                " s.visibility, ".
                " s.session_category_id, ".
                " $inject_extra_fields ".
                " s.id ";

            $isMakingOrder = strpos($options['order'], 'category_name') === 0;
        }

        $isFilteringSessionCategory = strpos($where, 'category_name') !== false;

        if ($isMakingOrder || $isFilteringSessionCategory) {
            $inject_joins .= " LEFT JOIN $sessionCategoryTable sc ON s.session_category_id = sc.id ";

            if ($isFilteringSessionCategory) {
                $where = str_replace('category_name', 'sc.name', $where);
            }

            if ($isMakingOrder) {
                $order = str_replace('category_name', 'sc.name', $order);
            }
        }

        $query = "$select FROM $tbl_session s $inject_joins $where $inject_where";

        if (api_is_multiple_url_enabled()) {
            $table_access_url_rel_session= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $where.= " AND ar.access_url_id = $access_url_id ";
                $query = "$select
                        FROM $tbl_session s $inject_joins
                        INNER JOIN $table_access_url_rel_session ar
                        ON (ar.session_id = s.id) $where";
            }
        }

        $query .= $order;
        $query .= $limit;
        $result = Database::query($query);

        $categories = self::get_all_session_category();
        $orderedCategories = array();
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $orderedCategories[$category['id']] = $category['name'];
            }
        }

        $formatted_sessions = array();

        if (Database::num_rows($result)) {
            $sessions = Database::store_result($result, 'ASSOC');
            if ($get_count) {
                return $sessions[0]['total_rows'];
            }

            foreach ($sessions as $session) {
                $session_id = $session['id'];
                $session['name'] = Display::url($session['name'], "resume_session.php?id_session=".$session['id']);

                if (isset($session['session_active']) && $session['session_active'] == 1) {
                    $session['session_active'] = Display::return_icon('accept.png', get_lang('Active'), array(), ICON_SIZE_SMALL);
                } else {
                    $session['session_active'] = Display::return_icon('error.png', get_lang('Inactive'), array(), ICON_SIZE_SMALL);
                }

                $session = self::convert_dates_to_local($session, true);

                switch ($session['visibility']) {
                    case SESSION_VISIBLE_READ_ONLY: //1
                        $session['visibility'] = get_lang('ReadOnly');
                        break;
                    case SESSION_VISIBLE:           //2
                    case SESSION_AVAILABLE:         //4
                        $session['visibility'] = get_lang('Visible');
                        break;
                    case SESSION_INVISIBLE:         //3
                        $session['visibility'] = api_ucfirst(get_lang('Invisible'));
                        break;
                }

                // Cleaning double selects.
                foreach ($session as $key => &$value) {
                    if (isset($options_by_double[$key]) || isset($options_by_double[$key.'_second'])) {
                        $options = explode('::', $value);
                    }
                    $original_key = $key;

                    if (strpos($key, '_second') === false) {
                    } else {
                        $key = str_replace('_second', '', $key);
                    }

                    if (isset($options_by_double[$key])) {
                        if (isset($options[0])) {
                            if (isset($options_by_double[$key][$options[0]])) {
                                if (strpos($original_key, '_second') === false) {
                                    $value = $options_by_double[$key][$options[0]]['option_display_text'];
                                } else {
                                    $value = $options_by_double[$key][$options[1]]['option_display_text'];
                                }
                            }
                        }
                    }
                }
                $formatted_sessions[$session_id] = $session;
                $categoryName = isset($orderedCategories[$session['session_category_id']]) ? $orderedCategories[$session['session_category_id']] : '';
                $formatted_sessions[$session_id]['category_name'] = $categoryName;
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

        $sessionId = intval($sessionId);

        $sql = "SELECT  count(*) as total_rows
                FROM $tbl_lp_view v
                INNER JOIN $tbl_lp l ON l.id = v.lp_id
                INNER JOIN $tbl_user u ON u.user_id = v.user_id
                INNER JOIN $tbl_course c
                WHERE v.session_id = " . $sessionId;
        $result_rows = Database::query($sql);
        $row = Database::fetch_array($result_rows);
        $num = $row['total_rows'];

        return $num;
    }

    /**
     * Gets the progress of learning paths in the given session
     * @param int   $sessionId
     * @param int $courseId
     * @param string $date_from
     * @param string $date_to
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

        $sessionCond = 'and session_id = %s';
        if ($sessionId == 'T') {
            $sessionCond = "";
        }

        $where = " WHERE c_id = '%s' AND s.status <> 2 $sessionCond";

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

        $sql = "SELECT u.user_id, u.lastname, u.firstname, u.username, u.email, s.c_id
                FROM $session_course_user s
                INNER JOIN $user u ON u.user_id = s.user_id
                $where
                $order
                $limit";

        $sql_query = sprintf($sql, Database::escape_string($course['real_id']), $sessionId);

        $rs = Database::query($sql_query);
        while ($user = Database::fetch_array($rs)) {
            $users[$user['user_id']] = $user;
        }

        //Get lessons
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
                intval($user['user_id']),
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
     * @param int   $sessionId
     * @param int   $courseId
     * @param int   $surveyId
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

        $where = " WHERE c_id = '%s' AND s.status <> 2 AND session_id = %s";

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

        $sql = "SELECT u.user_id, u.lastname, u.firstname, u.username, u.email, s.c_id
                FROM $session_course_user s
                INNER JOIN $user u ON u.user_id = s.user_id
                $where $order $limit";

        $sql_query = sprintf($sql, intval($course['real_id']), $sessionId);
        $rs = Database::query($sql_query);
        while ($user = Database::fetch_array($rs)) {
            $users[$user['user_id']] = $user;
        }

        //Get survey questions
        $questions = SurveyManager::get_questions($surveyId, $courseId);

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
     * @param int   $sessionId
     * @param int   $courseId
     * @param array options order and limit keys
     *
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
        $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $workTableAssignment = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
        $tbl_course_lp = Database::get_course_table(TABLE_LP_MAIN);
        $wiki = Database::get_course_table(TABLE_WIKI);
        $table_stats_default = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $table_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);

        $course = api_get_course_info_by_id($courseId);
        $where = " WHERE c_id = '%s' AND s.status <> 2 ";

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
        $queryVariables = array($course['real_id']);
        if (!empty($sessionId)) {
            $where .= ' AND session_id = %s';
            $queryVariables[] = $sessionId;
            $sql = "SELECT
                        u.user_id, u.lastname, u.firstname, u.username,
                        u.email, s.c_id, s.session_id
                    FROM $session_course_user s
                    INNER JOIN $user u
                    ON u.user_id = s.user_id
                    $where $order $limit";
        } else {
            $sql = "SELECT
                        u.user_id, u.lastname, u.firstname, u.username,
                        u.email, s.c_id, s.session_id
                    FROM $session_course_user s
                    INNER JOIN $user u ON u.user_id = s.user_id
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
            if (empty($arrLesson[$row['session_id']]['lessons_total'])) {
                $arrLesson[$row['session_id']]['lessons_total'] = 1;
            } else {
                $arrLesson[$row['session_id']]['lessons_total'] ++;
            }
        }

        /**
         *  Exercises
         */
        $exercises = ExerciseLib::get_all_exercises($course, $sessionId, false, '', $getAllSessions);
        $exercises_total = count($exercises);

        /**
         *  Assignments
         */
        //total
        $params = [$course['real_id']];
        if ($getAllSessions) {
            $sql = "SELECT count(w.id) as count
                    FROM $workTable w
                    LEFT JOIN $workTableAssignment a
                    ON (a.publication_id = w.id AND a.c_id = w.c_id)
                    WHERE w.c_id = %s
                    AND parent_id = 0
                    AND active IN (1, 0)";
        } else {
            $sql = "SELECT count(w.id) as count
                    FROM $workTable w
                    LEFT JOIN $workTableAssignment a
                    ON (a.publication_id = w.id AND a.c_id = w.c_id)
                    WHERE w.c_id = %s
                    AND parent_id = 0
                    AND active IN (1, 0)";

            if (empty($sessionId)) {
                $sql .= ' AND w.session_id = NULL ';
            } else {
                $sql .= ' AND w.session_id = %s ';
                $params[] = $sessionId;
            }
        }

        $sql_query = vsprintf($sql, $params);
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
        $survey_list = SurveyManager::get_surveys($course['code'], $sessionId);

        $surveys_total = count($survey_list);
        foreach ($survey_list as $survey) {
            $user_list = SurveyManager::get_people_who_filled_survey(
                $survey['survey_id'],
                false,
                $course['real_id']
            );
            foreach ($user_list as $user_id) {
                isset($survey_user_list[$user_id]) ? $survey_user_list[$user_id] ++ : $survey_user_list[$user_id] = 1;
            }
        }

        /**
         * Forums
         */
        $forums_total = CourseManager::getCountForum(
            $course['real_id'],
            $sessionId,
            $getAllSessions
        );

        //process table info
        foreach ($users as $user) {
            //Course description
            $sql = "SELECT count(*) as count
                    FROM $table_stats_access
                    WHERE access_tool = 'course_description'
                    AND c_id = '%s'
                    AND access_session_id = %s
                    AND access_user_id = %s ";
            $sql_query = sprintf($sql, $course['real_id'], $user['id_session'], $user['user_id']);

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
            $lessons_progress = Tracking::get_avg_student_progress(
                $user['user_id'],
                $course['code'],
                array(),
                $user['id_session']
            );
            $lessons_done = ($lessons_progress * $lessons_total) / 100;
            $lessons_left = $lessons_total - $lessons_done;

            //Exercises
            $exercises_progress = str_replace('%', '', Tracking::get_exercise_student_progress($exercises, $user['user_id'], $course['real_id'], $user['id_session']));
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
                    WHERE c_id = %s and session_id = %s and user_id = %s";
            $sql_query = sprintf($sql, $course['real_id'], $user['id_session'], $user['user_id']);
            $result = Database::query($sql_query);
            $row = Database::fetch_array($result);
            $wiki_revisions = $row['count'];
            //count visited wiki pages
            $sql = "SELECT count(distinct default_value) as count
                    FROM $table_stats_default
                    WHERE
                        default_user_id = %s AND
                        default_event_type = 'wiki_page_view' AND
                        default_value_type = 'wiki_page_id' AND
                        c_id = %s
                    ";
            $sql_query = sprintf($sql, $user['user_id'], $course['real_id']);
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
            $forums_done = CourseManager::getCountForumPerUser(
                $user['user_id'],
                $course['real_id'],
                $user['id_session']
            );
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

    /**
     * @return int
     */
    public static function get_number_of_tracking_access_overview()
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
     * @version 1.9.6
     */
    public static function get_user_data_access_tracking_overview(
        $sessionId,
        $courseId,
        $studentId = 0,
        $profile = '',
        $date_from = '',
        $date_to = '',
        $options
    ) {
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

        $where = null;
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
            INNER JOIN $course c ON a.c_id = c.id
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

            // building array to display
            $return[] = array(
                'user_id' => $info['user_id'],
                'logindate' => $info['login_course_date'],
                'username' => $info['username'],
                'firstname' => $info['firstname'],
                'lastname' => $info['lastname'],
                'clicks' => $info['counter'], //+ $clicks[$info['user_id']],
                'ip' => '',
                'timeLoggedIn' => gmdate("H:i:s", strtotime($info['logout_course_date']) - strtotime($info['login_course_date'])),
                'session' => $session['name']
            );
        }

        foreach ($return as $key => $info) {
            //Search for ip, we do less querys if we iterate the final array
            $sql = sprintf("SELECT user_ip FROM $track_e_login WHERE login_user_id = %d AND login_date < '%s' ORDER BY login_date DESC LIMIT 1", $info['user_id'], $info['logindate']); //TODO add select by user too
            $result = Database::query($sql);
            $ip = Database::fetch_assoc($result);
            //if no ip founded, we search the closest higher ip
            if (empty($ip['user_ip'])) {
                $sql = sprintf("SELECT user_ip FROM $track_e_login WHERE login_user_id = %d AND login_date > '%s'  ORDER BY login_date ASC LIMIT 1", $info['user_id'], $info['logindate']); //TODO add select by user too
                $result = Database::query($sql);
                $ip = Database::fetch_assoc($result);
            }
            #add ip to final array
            $return[$key]['ip'] = $ip['user_ip'];
        }

        return $return;
    }

    /**
     * Creates a new course code based in given code
     *
     * @param string	$session_name
     * <code>
     * $wanted_code = 'curse' if there are in the DB codes like curse1 curse2 the function will return: course3
     * if the course code doest not exist in the DB the same course code will be returned
     * </code>
     * @return string	wanted unused code
     */
    public static function generateNextSessionName($session_name)
    {
        $session_name_ok = !self::session_name_exists($session_name);
        if (!$session_name_ok) {
            $table = Database::get_main_table(TABLE_MAIN_SESSION);
            $session_name = Database::escape_string($session_name);
            $sql = "SELECT count(*) as count FROM $table
                    WHERE name LIKE '$session_name%'";
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
     * @param integer   $id Session primary key
     * @param string    $name
     * @param string    $startDate
     * @param string    $endDate
     * @param string    $displayStartDate
     * @param string    $displayEndDate
     * @param string    $coachStartDate
     * @param string    $coachEndDate
     * @param integer   $coachId
     * @param integer   $sessionCategoryId
     * @param int       $visibility
     * @param string    $description
     * @param int       $showDescription
     * @param int       $duration
     * @param array     $extraFields
     * @param int       $sessionAdminId
     * @param boolean $sendSubscriptionNotification Optional.
     *          Whether send a mail notification to users being subscribed
     * @return mixed
     */
    public static function edit_session(
        $id,
        $name,
        $startDate,
        $endDate,
        $displayStartDate,
        $displayEndDate,
        $coachStartDate,
        $coachEndDate,
        $coachId,
        $sessionCategoryId,
        $visibility,
        $description = null,
        $showDescription = 0,
        $duration = null,
        $extraFields = array(),
        $sessionAdminId = 0,
        $sendSubscriptionNotification = false
    ) {
        $coachId = intval($coachId);
        $sessionCategoryId = intval($sessionCategoryId);
        $visibility = intval($visibility);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        if (empty($name)) {
            Display::addFlash(
                Display::return_message(get_lang('SessionNameIsRequired'), 'warning')
            );

            return false;
        } elseif (empty($coachId)) {
            Display::addFlash(
                Display::return_message(get_lang('CoachIsRequired'), 'warning')
            );

            return false;
        } elseif (!empty($startDate) && !api_is_valid_date($startDate, 'Y-m-d H:i') && !api_is_valid_date($startDate, 'Y-m-d H:i:s')) {
            Display::addFlash(
                Display::return_message(get_lang('InvalidStartDate'), 'warning')
            );

            return false;
        } elseif (!empty($endDate) && !api_is_valid_date($endDate, 'Y-m-d H:i') && !api_is_valid_date($endDate, 'Y-m-d H:i:s')) {
            Display::addFlash(
                Display::return_message(get_lang('InvalidEndDate'), 'warning')
            );

            return false;
        } elseif (!empty($startDate) && !empty($endDate) && $startDate >= $endDate) {
            Display::addFlash(
                Display::return_message(get_lang('StartDateShouldBeBeforeEndDate'), 'warning')
            );

            return false;
        } else {
            $sessionInfo = self::get_session_by_name($name);
            $exists = false;

            if (!empty($sessionInfo)) {
                if ($sessionInfo['id'] != $id) {
                    $exists = true;
                }
            }

            if ($exists) {
                Display::addFlash(
                    Display::return_message(get_lang('SessionNameAlreadyExists'), 'warning')
                );

                return false;
            } else {
                $values = [
                    'name' => $name,
                    'duration' => $duration,
                    'id_coach' => $coachId,
                    'description'=> $description,
                    'show_description' => intval($showDescription),
                    'visibility' => $visibility,
                    'send_subscription_notification' => $sendSubscriptionNotification,
                    'access_start_date' => null,
                    'access_end_date' => null,
                    'display_start_date' => null,
                    'display_end_date' => null,
                    'coach_access_start_date' => null,
                    'coach_access_end_date' => null
                ];

                if (!empty($sessionAdminId)) {
                    $values['session_admin_id'] = $sessionAdminId;
                }

                if (!empty($startDate)) {
                    $values['access_start_date'] = api_get_utc_datetime($startDate);
                }

                if (!empty($endDate)) {
                    $values['access_end_date'] = api_get_utc_datetime($endDate);
                }

                if (!empty($displayStartDate)) {
                    $values['display_start_date'] = api_get_utc_datetime($displayStartDate);
                }

                if (!empty($displayEndDate)) {
                    $values['display_end_date'] = api_get_utc_datetime($displayEndDate);
                }

                if (!empty($coachStartDate)) {
                    $values['coach_access_start_date'] = api_get_utc_datetime($coachStartDate);
                }
                if (!empty($coachEndDate)) {
                    $values['coach_access_end_date'] = api_get_utc_datetime($coachEndDate);
                }

                if (!empty($sessionCategoryId)) {
                    $values['session_category_id'] = $sessionCategoryId;
                } else {
                    $values['session_category_id'] = null;
                }

                Database::update(
                    $tbl_session,
                    $values,
                    array('id = ?' => $id)
                );

                if (!empty($extraFields)) {
                    $extraFields['item_id'] = $id;
                    $sessionFieldValue = new ExtraFieldValue('session');
                    $sessionFieldValue->saveFieldValues($extraFields);
                }

                return $id;
            }
        }
    }

    /**
     * Delete session
     * @author Carlos Vargas  from existing code
     * @param	array	$id_checked an array to delete sessions
     * @param   boolean  $from_ws optional, true if the function is called
     * by a webservice, false otherwise.
     * @return	void	Nothing, or false on error
     * */
    public static function delete($id_checked, $from_ws = false)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_url_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $tbl_item_properties = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $tbl_student_publication_assignment = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
        $ticket = Database::get_main_table(TABLE_TICKET_TICKET);
        $em = Database::getManager();

        $userId = api_get_user_id();

        if (is_array($id_checked)) {
            foreach ($id_checked as $sessionId) {
                self::delete($sessionId);
            }
        } else {
            $id_checked = intval($id_checked);
        }

        if (SessionManager::allowed($id_checked) && !$from_ws) {
            $qb = $em
                ->createQuery('
                    SELECT s.sessionAdminId FROM ChamiloCoreBundle:Session s
                    WHERE s.id = ?1
                ')
                ->setParameter(1, $id_checked);

            $res = $qb->getSingleScalarResult();

            if ($res != $userId && !api_is_platform_admin()) {
                api_not_allowed(true);
            }
        }

        // Delete documents inside a session
        $courses = SessionManager::getCoursesInSession($id_checked);
        foreach ($courses as $courseId) {
            $courseInfo = api_get_course_info_by_id($courseId);
            DocumentManager::deleteDocumentsFromSession($courseInfo, $id_checked);

            $works = Database::select(
                '*',
                $tbl_student_publication,
                [
                    'where' => ['session_id = ? AND c_id = ?' => [$id_checked, $courseId]]
                ]
            );

            $currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/';

            foreach ($works as $index => $work) {
                if ($work['filetype'] = 'folder') {
                    Database::query("DELETE FROM $tbl_student_publication_assignment WHERE publication_id = $index");
                }
                my_delete($currentCourseRepositorySys.'/'.$work['url']);
            }
        }

        Database::query("DELETE FROM $tbl_student_publication WHERE session_id IN($id_checked)");
        Database::query("DELETE FROM $tbl_session_rel_course WHERE session_id IN($id_checked)");
        Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE session_id IN($id_checked)");
        Database::query("DELETE FROM $tbl_session_rel_user WHERE session_id IN($id_checked)");
        Database::query("DELETE FROM $tbl_item_properties WHERE session_id IN ($id_checked)");
        Database::query("DELETE FROM $tbl_url_session WHERE session_id IN($id_checked)");

        $sql = "UPDATE $ticket SET session_id = NULL WHERE session_id IN ($id_checked)";
        Database::query($sql);

        $sql = "DELETE FROM $tbl_session WHERE id IN ($id_checked)";
        Database::query($sql);

        $extraFieldValue = new ExtraFieldValue('session');
        $extraFieldValue->deleteValuesByItem($id_checked);

        /** @var \Chamilo\CoreBundle\Entity\Repository\SequenceRepository $repo */
        $repo = Database::getManager()->getRepository('ChamiloCoreBundle:SequenceResource');
        $repo->deleteResource(
            $id_checked,
            \Chamilo\CoreBundle\Entity\SequenceResource::SESSION_TYPE
        );

        // Add event to system log
        Event::addEvent(
            LOG_SESSION_DELETE,
            LOG_SESSION_ID,
            $id_checked,
            api_get_utc_datetime(),
            $userId
        );
    }

    /**
     * @param int $id promotion id
     *
     * @return bool
     */
    public static function clear_session_ref_promotion($id)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $id = intval($id);
        $sql = "UPDATE $tbl_session 
                SET promotion_id = 0
                WHERE promotion_id = $id";
        if (Database::query($sql)) {
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
     * @return bool
     */
    public static function subscribe_users_to_session(
        $id_session,
        $user_list,
        $session_visibility = SESSION_VISIBLE_READ_ONLY,
        $empty_users = true
    ) {
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

        $entityManager = Database::getManager();
        $session = $entityManager->find('ChamiloCoreBundle:Session', $id_session);

        // from function parameter
        if (empty($session_visibility)) {
            $session_visibility = $session->getVisibility();
            //default status loaded if empty
            // by default readonly 1
            if (empty($session_visibility)) {
                $session_visibility = SESSION_VISIBLE_READ_ONLY;
            }
        } else {
            if (!in_array($session_visibility, array(SESSION_VISIBLE_READ_ONLY, SESSION_VISIBLE, SESSION_INVISIBLE))) {
                $session_visibility = SESSION_VISIBLE_READ_ONLY;
            }
        }

        $sql = "SELECT user_id FROM $tbl_session_rel_course_rel_user
                WHERE session_id = $id_session AND status = 0";
        $result = Database::query($sql);
        $existingUsers = array();
        while ($row = Database::fetch_array($result)) {
            $existingUsers[] = $row['user_id'];
        }

        $sql = "SELECT c_id FROM $tbl_session_rel_course
                WHERE session_id = $id_session";
        $result = Database::query($sql);
        $course_list = array();
        while ($row = Database::fetch_array($result)) {
            $course_list[] = $row['c_id'];
        }

        if ($session->getSendSubscriptionNotification() &&
            is_array($user_list)
        ) {
            // Sending emails only
            foreach ($user_list as $user_id) {
                if (in_array($user_id, $existingUsers)) {
                    continue;
                }

                $tplSubject = new Template(null, false, false, false, false, false);
                $layoutSubject = $tplSubject->get_template(
                    'mail/subject_subscription_to_session_confirmation.tpl'
                );
                $subject = $tplSubject->fetch($layoutSubject);

                $user_info = api_get_user_info($user_id);

                $tplContent = new Template(null, false, false, false, false, false);
                // Variables for default template
                $tplContent->assign(
                    'complete_name',
                    stripslashes($user_info['complete_name'])
                );
                $tplContent->assign('session_name', $session->getName());
                $tplContent->assign(
                    'session_coach',
                    $session->getGeneralCoach()->getCompleteName()
                );
                $layoutContent = $tplContent->get_template(
                    'mail/content_subscription_to_session_confirmation.tpl'
                );
                $content = $tplContent->fetch($layoutContent);

                api_mail_html(
                    $user_info['complete_name'],
                    $user_info['mail'],
                    $subject,
                    $content,
                    api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname')
                    ),
                    api_get_setting('emailAdministrator')
                );
            }
        }

        foreach ($course_list as $courseId) {
            // for each course in the session
            $nbr_users = 0;
            $courseId = intval($courseId);

            $sql = "SELECT DISTINCT user_id
                    FROM $tbl_session_rel_course_rel_user
                    WHERE
                        session_id = $id_session AND
                        c_id = $courseId AND
                        status = 0
                    ";
            $result = Database::query($sql);
            $existingUsers = array();
            while ($row = Database::fetch_array($result)) {
                $existingUsers[] = $row['user_id'];
            }

            // Delete existing users
            if ($empty_users) {
                foreach ($existingUsers as $existing_user) {
                    if (!in_array($existing_user, $user_list)) {
                        $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                                WHERE
                                    session_id = $id_session AND
                                    c_id = $courseId AND
                                    user_id = $existing_user AND
                                    status = 0 ";
                        $result = Database::query($sql);

                        Event::addEvent(
                            LOG_SESSION_DELETE_USER_COURSE,
                            LOG_USER_ID,
                            $existing_user,
                            api_get_utc_datetime(),
                            api_get_user_id(),
                            $courseId,
                            $id_session
                        );

                        if (Database::affected_rows($result)) {
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
                    $sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user (session_id, c_id, user_id, visibility, status)
                            VALUES($id_session, $courseId, $enreg_user, $session_visibility, 0)";
                    $result = Database::query($sql);

                    Event::addEvent(
                        LOG_SESSION_ADD_USER_COURSE,
                        LOG_USER_ID,
                        $enreg_user,
                        api_get_utc_datetime(),
                        api_get_user_id(),
                        $courseId,
                        $id_session
                    );

                    if (Database::affected_rows($result)) {

                        $nbr_users++;
                    }
                }
            }

            // Count users in this session-course relation
            $sql = "SELECT COUNT(user_id) as nbUsers
                    FROM $tbl_session_rel_course_rel_user
                    WHERE session_id = $id_session AND c_id = $courseId AND status<>2";
            $rs = Database::query($sql);
            list($nbr_users) = Database::fetch_array($rs);
            // update the session-course relation to add the users total
            $sql = "UPDATE $tbl_session_rel_course SET nbr_users = $nbr_users
                    WHERE session_id = $id_session AND c_id = $courseId";
            Database::query($sql);
        }

        // Delete users from the session
        if ($empty_users === true) {
            $sql = "DELETE FROM $tbl_session_rel_user
                    WHERE session_id = $id_session AND relation_type<>" . SESSION_RELATION_TYPE_RRHH . "";
            Database::query($sql);
        }

        // Insert missing users into session
        $nbr_users = 0;

        foreach ($user_list as $enreg_user) {
            $enreg_user = Database::escape_string($enreg_user);
            $nbr_users++;
            $sql = "INSERT IGNORE INTO $tbl_session_rel_user (relation_type, session_id, user_id, registered_at)
                    VALUES (0, $id_session, $enreg_user, '" . api_get_utc_datetime() . "')";
            Database::query($sql);
        }

        // update number of users in the session
        $nbr_users = count($user_list);
        if ($empty_users) {
            // update number of users in the session
            $sql = "UPDATE $tbl_session SET nbr_users= $nbr_users
                    WHERE id = $id_session ";
            Database::query($sql);
        } else {
            $sql = "UPDATE $tbl_session SET nbr_users = nbr_users + $nbr_users
                    WHERE id = $id_session";
            Database::query($sql);
        }
    }

    /**
     * Returns user list of the current users subscribed in the course-session
     * @param int $sessionId
     * @param array $courseInfo
     * @param int $status
     *
     * @return array
     */
    public static function getUsersByCourseSession(
        $sessionId,
        $courseInfo,
        $status = null
    ) {
        $sessionId = intval($sessionId);
        $courseCode = $courseInfo['code'];
        $courseId = $courseInfo['real_id'];

        if (empty($sessionId) || empty($courseCode)) {
            return array();
        }

        $statusCondition = null;
        if (isset($status) && !is_null($status)) {
            $status = intval($status);
            $statusCondition = " AND status = $status";
        }

        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $sql = "SELECT DISTINCT user_id
                FROM $table
                WHERE
                    session_id = $sessionId AND
                    c_id = $courseId
                    $statusCondition
                ";
        $result = Database::query($sql);
        $existingUsers = array();
        while ($row = Database::fetch_array($result)) {
            $existingUsers[] = $row['user_id'];
        }

        return $existingUsers;
    }

    /**
     * Remove a list of users from a course-session
     * @param array $userList
     * @param int $sessionId
     * @param array $courseInfo
     * @param int $status
     * @param bool $updateTotal
     * @return bool
     */
    public static function removeUsersFromCourseSession(
        $userList,
        $sessionId,
        $courseInfo,
        $status = null,
        $updateTotal = true
    ) {
        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tableSessionCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $sessionId = intval($sessionId);

        if (empty($sessionId) || empty($userList) || empty($courseInfo)) {
            return false;
        }

        is_array($courseInfo) ? $courseId = $courseInfo['real_id'] : $courseId = $courseInfo;

        $statusCondition = null;
        if (isset($status) && !is_null($status))  {
            $status = intval($status);
            $statusCondition  = " AND status = $status";
        }

        foreach ($userList as $userId) {
            $userId = intval($userId);
            $sql = "DELETE FROM $table
                    WHERE
                        session_id = $sessionId AND
                        c_id = $courseId AND
                        user_id = $userId
                        $statusCondition
                    ";
            Database::query($sql);
        }

        if ($updateTotal) {
            // Count users in this session-course relation
            $sql = "SELECT COUNT(user_id) as nbUsers
                    FROM $table
                    WHERE
                        session_id = $sessionId AND
                        c_id = $courseId AND
                        status <> 2";
            $result = Database::query($sql);
            list($userCount) = Database::fetch_array($result);

            // update the session-course relation to add the users total
            $sql = "UPDATE $tableSessionCourse
                    SET nbr_users = $userCount
                    WHERE
                        session_id = $sessionId AND
                        c_id = $courseId";
            Database::query($sql);
        }
    }

    /**
     * Subscribe a user to an specific course inside a session.
     *
     * @param array $user_list
     * @param int $session_id
     * @param string $course_code
     * @param int $session_visibility
     * @param bool $removeUsersNotInList
     * @return bool
     */
    public static function subscribe_users_to_session_course(
        $user_list,
        $session_id,
        $course_code,
        $session_visibility = SESSION_VISIBLE_READ_ONLY,
        $removeUsersNotInList = false
    ) {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        if (empty($session_id) || empty($course_code)) {
            return false;
        }

        $session_id = intval($session_id);
        $course_code = Database::escape_string($course_code);
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        $session_visibility = intval($session_visibility);

        if ($removeUsersNotInList) {

            $currentUsers = self::getUsersByCourseSession($session_id, $courseInfo, 0);

            if (!empty($user_list)) {
                $userToDelete = array_diff($currentUsers, $user_list);
            } else {
                $userToDelete = $currentUsers;
            }

            if (!empty($userToDelete)) {
                self::removeUsersFromCourseSession(
                    $userToDelete,
                    $session_id,
                    $courseInfo,
                    0,
                    true
                );
            }
        }

        $nbr_users = 0;
        foreach ($user_list as $enreg_user) {
            $enreg_user = intval($enreg_user);
            // Checking if user exists in session - course - user table.
            $sql = "SELECT count(user_id) as count
                    FROM $tbl_session_rel_course_rel_user
                    WHERE
                        session_id = $session_id AND
                        c_id = $courseId and
                        user_id = $enreg_user ";
            $result = Database::query($sql);
            $count = 0;

            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result, 'ASSOC');
                $count = $row['count'];
            }

            if ($count == 0) {
                $sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user (session_id, c_id, user_id, visibility)
                        VALUES ($session_id, $courseId, $enreg_user, $session_visibility)";
                $result = Database::query($sql);
                if (Database::affected_rows($result)) {
                    $nbr_users++;
                }
            }

            // Checking if user exists in session - user table.
            $sql = "SELECT count(user_id) as count
                    FROM $tbl_session_rel_user
                    WHERE session_id = $session_id AND user_id = $enreg_user ";
            $result = Database::query($sql);
            $count = 0;

            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result, 'ASSOC');
                $count = $row['count'];
            }

            if (empty($count)) {
                // If user is not registered to a session then add it.
                $sql = "INSERT IGNORE INTO $tbl_session_rel_user (session_id, user_id, registered_at)
                        VALUES ($session_id, $enreg_user, '" . api_get_utc_datetime() . "')";
                Database::query($sql);

                $sql = "UPDATE $tbl_session SET nbr_users = nbr_users + 1
                        WHERE id = $session_id ";
                Database::query($sql);
            }
        }

        // count users in this session-course relation
        $sql = "SELECT COUNT(user_id) as nbUsers
                FROM $tbl_session_rel_course_rel_user
                WHERE session_id = $session_id AND c_id = $courseId AND status <> 2";
        $rs = Database::query($sql);
        list($nbr_users) = Database::fetch_array($rs);
        // update the session-course relation to add the users total
        $sql = "UPDATE $tbl_session_rel_course
                SET nbr_users = $nbr_users
                WHERE session_id = $session_id AND c_id = $courseId";
        Database::query($sql);
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

        $sql = "DELETE FROM $tbl_session_rel_user
                WHERE
                    session_id = $session_id AND
                    user_id = $user_id AND
                    relation_type <> " . SESSION_RELATION_TYPE_RRHH . "";
        $result = Database::query($sql);
        $return = Database::affected_rows($result);

        // Update number of users
        $sql = "UPDATE $tbl_session
                SET nbr_users = nbr_users - $return
                WHERE id = $session_id ";
        Database::query($sql);

        // Get the list of courses related to this session
        $course_list = SessionManager::get_course_list_by_session_id($session_id);

        if (!empty($course_list)) {
            foreach ($course_list as $course) {
                $courseId = $course['id'];
                // Delete user from course
                $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                        WHERE session_id = $session_id AND c_id = $courseId AND user_id = $user_id";
                $result = Database::query($sql);

                Event::addEvent(
                    LOG_SESSION_DELETE_USER_COURSE,
                    LOG_USER_ID,
                    $user_id,
                    api_get_utc_datetime(),
                    api_get_user_id(),
                    $courseId,
                    $session_id
                );

                if (Database::affected_rows($result)) {
                    // Update number of users in this relation
                    $sql = "UPDATE $tbl_session_rel_course SET 
                            nbr_users = nbr_users - 1
                            WHERE session_id = $session_id AND c_id = $courseId";
                    Database::query($sql);
                }
            }
        }

        return true;
    }

    /**
     * Subscribes courses to the given session and optionally (default)
     * unsubscribes previous users
     * @author Carlos Vargas from existing code
     * @param	int		$sessionId
     * @param	array	$courseList List of courses int ids
     * @param	bool	$removeExistingCoursesWithUsers Whether to unsubscribe
     * existing courses and users (true, default) or not (false)
     * @param $copyEvaluation from base course to session course
     * @return	void	Nothing, or false on error
     * */
    public static function add_courses_to_session(
        $sessionId,
        $courseList,
        $removeExistingCoursesWithUsers = true,
        $copyEvaluation = false
    ) {
        $sessionId = intval($sessionId);

        if (empty($sessionId) || empty($courseList)) {
            return false;
        }

        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        // Get list of courses subscribed to this session
        $sql = "SELECT c_id
                FROM $tbl_session_rel_course
                WHERE session_id = $sessionId";
        $rs = Database::query($sql);
        $existingCourses = Database::store_result($rs);
        $nbr_courses = count($existingCourses);

        // Get list of users subscribed to this session
        $sql = "SELECT user_id
                FROM $tbl_session_rel_user
                WHERE
                    session_id = $sessionId AND
                    relation_type<>" . SESSION_RELATION_TYPE_RRHH;
        $result = Database::query($sql);
        $user_list = Database::store_result($result);

        // Remove existing courses from the session.
        if ($removeExistingCoursesWithUsers === true && !empty($existingCourses)) {
            foreach ($existingCourses as $existingCourse) {
                if (!in_array($existingCourse['c_id'], $courseList)) {

                    $sql = "DELETE FROM $tbl_session_rel_course
                            WHERE
                                c_id = " . $existingCourse['c_id'] . " AND
                                session_id = $sessionId";
                    Database::query($sql);

                    $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                            WHERE
                                c_id = ".$existingCourse['c_id']." AND
                                session_id = $sessionId";
                    Database::query($sql);

                    Event::addEvent(
                        LOG_SESSION_DELETE_COURSE,
                        LOG_COURSE_ID,
                        $existingCourse['c_id'],
                        api_get_utc_datetime(),
                        api_get_user_id(),
                        $existingCourse['c_id'],
                        $sessionId
                    );

                    CourseManager::remove_course_ranking(
                        $existingCourse['c_id'],
                        $sessionId
                    );

                    $nbr_courses--;
                }
            }
        }

        // Pass through the courses list we want to add to the session
        foreach ($courseList as $courseId) {
            $courseInfo = api_get_course_info_by_id($courseId);

            // If course doesn't exists continue!
            if (empty($courseInfo)) {
                continue;
            }

            $exists = false;
            // check if the course we want to add is already subscribed
            foreach ($existingCourses as $existingCourse) {
                if ($courseId == $existingCourse['c_id']) {
                    $exists = true;
                }
            }

            if (!$exists) {
                // Copy gradebook categories and links (from base course)
                // to the new course session
                if ($copyEvaluation) {
                    $cats = Category::load(null, null, $courseInfo['code']);
                    if (!empty($cats)) {
                        $categoryIdList = [];
                        /** @var Category $cat */
                        foreach ($cats as $cat) {
                            $categoryIdList[$cat->get_id()] = $cat->get_id();
                        }
                        $newCategoryIdList = [];
                        foreach ($cats as $cat) {
                            $links = $cat->get_links(null, false, $courseInfo['code'], 0);

                            $cat->set_session_id($sessionId);
                            $oldCategoryId= $cat->get_id();
                            $newId = $cat->add();
                            $newCategoryIdList[$oldCategoryId] = $newId;
                            $parentId = $cat->get_parent_id();

                            if (!empty($parentId)) {
                                $newParentId = $newCategoryIdList[$parentId];
                                $cat->set_parent_id($newParentId);
                                $cat->save();
                            }

                            /** @var AbstractLink $link */
                            foreach ($links as $link) {
                                $newCategoryId = $newCategoryIdList[$link->get_category_id()];
                                $link->set_category_id($newCategoryId);
                                $link->add();
                            }
                        }

                        // Create
                        DocumentManager::generateDefaultCertificate(
                            $courseInfo,
                            true,
                            $sessionId
                        );
                    }
                }

                // If the course isn't subscribed yet
                $sql = "INSERT INTO $tbl_session_rel_course (session_id, c_id, nbr_users, position)
                        VALUES ($sessionId, $courseId, 0, 0)";
                Database::query($sql);

                Event::addEvent(
                    LOG_SESSION_ADD_COURSE,
                    LOG_COURSE_ID,
                    $courseId,
                    api_get_utc_datetime(),
                    api_get_user_id(),
                    $courseId,
                    $sessionId
                );

                // We add the current course in the existing courses array,
                // to avoid adding another time the current course
                $existingCourses[] = array('c_id' => $courseId);
                $nbr_courses++;

                // subscribe all the users from the session to this course inside the session
                $nbr_users = 0;
                foreach ($user_list as $enreg_user) {
                    $enreg_user_id = intval($enreg_user['user_id']);
                    $sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user (session_id, c_id, user_id)
                            VALUES ($sessionId, $courseId, $enreg_user_id)";
                    $result = Database::query($sql);

                    Event::addEvent(
                        LOG_SESSION_ADD_USER_COURSE,
                        LOG_USER_ID,
                        $enreg_user_id,
                        api_get_utc_datetime(),
                        api_get_user_id(),
                        $courseId,
                        $sessionId
                    );

                    if (Database::affected_rows($result)) {
                        $nbr_users++;
                    }
                }
                $sql = "UPDATE $tbl_session_rel_course
                        SET nbr_users = $nbr_users
                        WHERE session_id = $sessionId AND c_id = $courseId";
                Database::query($sql);
            }
        }

        $sql = "UPDATE $tbl_session
                SET nbr_courses = $nbr_courses
                WHERE id = $sessionId";
        Database::query($sql);
    }

    /**
     * Unsubscribe course from a session
     *
     * @param int $session_id
     * @param int $course_id
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
        $course_id = intval($course_id);

        if (empty($course_code)) {
            return false;
        }

        // Unsubscribe course
        $sql = "DELETE FROM $tbl_session_rel_course
                WHERE c_id = $course_id AND session_id = $session_id";
        $result = Database::query($sql);
        $nb_affected = Database::affected_rows($result);

        $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                WHERE c_id = $course_id AND session_id = $session_id";
        Database::query($sql);

        Event::addEvent(
            LOG_SESSION_DELETE_COURSE,
            LOG_COURSE_ID,
            $course_id,
            api_get_utc_datetime(),
            api_get_user_id(),
            $course_id,
            $session_id
        );

        if ($nb_affected > 0) {
            // Update number of courses in the session
            $sql = "UPDATE $tbl_session SET nbr_courses= nbr_courses - $nb_affected
                    WHERE id = $session_id";
            Database::query($sql);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates a new extra field for a given session
     * @param	string	$variable Field's internal variable name
     * @param	int		$fieldType Field's type
     * @param	string	$displayText Field's language var name
     * @return int     new extra field id
     */
    public static function create_session_extra_field($variable, $fieldType, $displayText)
    {
        $extraField = new ExtraFieldModel('session');
        $params = [
            'variable' => $variable,
            'field_type' => $fieldType,
            'display_text' => $displayText,
        ];

        return $extraField->save($params);
    }

    /**
     * Update an extra field value for a given session
     * @param	integer	Course ID
     * @param	string	Field variable name
     * @param	string	Field value
     * @return	boolean	true if field updated, false otherwise
     */
    public static function update_session_extra_field_value($sessionId, $variable, $value = '')
    {
        $extraFieldValue = new ExtraFieldValue('session');
        $params = [
            'item_id' => $sessionId,
            'variable' => $variable,
            'value' => $value,
        ];
        return $extraFieldValue->save($params);
    }

    /**
     * Checks the relationship between a session and a course.
     * @param int $session_id
     * @param int $courseId
     * @return bool Returns TRUE if the session and the course are related, FALSE otherwise.
     * */
    public static function relation_session_course_exist($session_id, $courseId)
    {
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $return_value = false;
        $sql = "SELECT c_id FROM $tbl_session_course
                WHERE
                  session_id = " . intval($session_id) . " AND
                  c_id = " . intval($courseId);
        $result = Database::query($sql);
        $num = Database::num_rows($result);
        if ($num > 0) {
            $return_value = true;
        }
        return $return_value;
    }

    /**
     * Get the session information by name
     * @param string $session_name
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
    public static function create_category_session(
        $sname,
        $syear_start,
        $smonth_start,
        $sday_start,
        $syear_end,
        $smonth_end,
        $sday_end
    ) {
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
        $params = [
            'name' => $name,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'access_url_id' => $access_url_id
        ];
        $id_session = Database::insert($tbl_session_category, $params);

        // Add event to system log
        $user_id = api_get_user_id();
        Event::addEvent(
            LOG_SESSION_CATEGORY_CREATE,
            LOG_SESSION_CATEGORY_ID,
            $id_session,
            api_get_utc_datetime(),
            $user_id
        );
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
    public static function edit_category_session(
        $id,
        $sname,
        $syear_start,
        $smonth_start,
        $sday_start,
        $syear_end,
        $smonth_end,
        $sday_end
    ) {
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
            $sql = "UPDATE $tbl_session_category
                    SET
                        name = '" . Database::escape_string($name) . "',
                        date_start = '$date_start' ,
                        date_end = '$date_end'
                    WHERE id= $id";
        } else {
            $sql = "UPDATE $tbl_session_category SET
                        name = '" . Database::escape_string($name) . "',
                        date_start = '$date_start',
                        date_end = NULL
                    WHERE id= $id";
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
        $sql = "UPDATE $tbl_session SET session_category_id = 0
                WHERE session_category_id IN (" . $id_checked . ")";
        Database::query($sql);

        $sql = "SELECT id FROM $tbl_session WHERE session_category_id IN (" . $id_checked . ")";
        $result = Database::query($sql);
        while ($rows = Database::fetch_array($result)) {
            $session_id = $rows['id'];
            if ($delete_session) {
                if ($from_ws) {
                    SessionManager::delete($session_id, true);
                } else {
                    SessionManager::delete($session_id);
                }
            }
        }
        $sql = "DELETE FROM $tbl_session_category WHERE id IN (" . $id_checked . ")";
        Database::query($sql);

        // Add event to system log
        $user_id = api_get_user_id();
        Event::addEvent(
            LOG_SESSION_CATEGORY_DELETE,
            LOG_SESSION_CATEGORY_ID,
            $id_checked,
            api_get_utc_datetime(),
            $user_id
        );

        return true;
    }

    /**
     * Get a list of sessions of which the given conditions match with an = 'cond'
     * @param  array $conditions a list of condition example :
     * array('status' => STUDENT) or
     * array('s.name' => array('operator' => 'LIKE', value = '%$needle%'))
     * @param  array $order_by a list of fields on which sort
     * @return array An array with all sessions of the platform.
     * @todo   optional course code parameter, optional sorting parameters...
     */
    public static function get_sessions_list($conditions = array(), $order_by = array(), $from = null, $to = null)
    {
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        $session_category_table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $table_access_url_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $session_course_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $access_url_id = api_get_current_access_url_id();
        $return_array = array();

        $sql_query = " SELECT
                    DISTINCT(s.id),
                    s.name,
                    s.nbr_courses,
                    s.access_start_date,
                    s.access_end_date,
                    u.firstname,
                    u.lastname,
                    sc.name as category_name,
                    s.promotion_id
				FROM $session_table s
				INNER JOIN $user_table u ON s.id_coach = u.user_id
				INNER JOIN $table_access_url_rel_session ar ON ar.session_id = s.id
				LEFT JOIN  $session_category_table sc ON s.session_category_id = sc.id
				LEFT JOIN $session_course_table sco ON (sco.session_id = s.id)
				INNER JOIN $course_table c ON sco.c_id = c.id
				WHERE ar.access_url_id = $access_url_id ";

        $availableFields = array(
            's.id',
            's.name',
            'c.id'
        );

        $availableOperator = array(
            'like',
            '>=',
            '<=',
            '=',
        );

        if (count($conditions) > 0) {
            foreach ($conditions as $field => $options) {
                $operator = strtolower($options['operator']);
                $value = Database::escape_string($options['value']);
                $sql_query .= ' AND ';
                if (in_array($field, $availableFields) && in_array($operator, $availableOperator)) {
                    $sql_query .= $field . " $operator '" . $value . "'";
                }
            }
        }

        $orderAvailableList = array('name');

        if (count($order_by) > 0) {
            $order = null;
            $direction = null;
            if (isset($order_by[0]) && in_array($order_by[0], $orderAvailableList)) {
                $order = $order_by[0];
            }
            if (isset($order_by[1]) && in_array(strtolower($order_by[1]), array('desc', 'asc'))) {
                $direction = $order_by[1];
            }

            if (!empty($order)) {
                $sql_query .= " ORDER BY $order $direction ";
            }
        }

        if (!is_null($from) && !is_null($to)) {
            $to = intval($to);
            $from = intval($from);
            $sql_query .= "LIMIT $from, $to";
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
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $id = intval($id);
        $sql = "SELECT id, name, date_start, date_end
                FROM $tbl_session_category
                WHERE id= $id";
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
        $sql = 'SELECT * FROM ' . $tbl_session_category . '
                WHERE access_url_id = ' . $id . '
                ORDER BY name ASC';
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
     * @param int  $user_id
     * @param int  $session_id
     * @param int  $courseId
     * @param bool $nocoach optional, if is true the user don't be a coach now,
     * otherwise it'll assign a coach
     * @return bool true if there are affected rows, otherwise false
     */
    public static function set_coach_to_course_session(
        $user_id,
        $session_id = 0,
        $courseId = 0,
        $nocoach = false
    ) {
        // Definition of variables
        $user_id = intval($user_id);

        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }

        if (!empty($courseId)) {
            $courseId = intval($courseId);
        } else {
            $courseId = api_get_course_id();
        }

        if (empty($session_id) || empty($courseId) || empty($user_id)) {
            return false;
        }

        // Table definition
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

        // check if user is a teacher
        $sql = "SELECT * FROM $tbl_user
                WHERE status = 1 AND user_id = $user_id";

        $rs_check_user = Database::query($sql);

        if (Database::num_rows($rs_check_user) > 0) {
            if ($nocoach) {
                // check if user_id exists in session_rel_user (if the user is
                // subscribed to the session in any manner)
                $sql = "SELECT user_id FROM $tbl_session_rel_user
                        WHERE
                            session_id = $session_id AND
                            user_id = $user_id";
                $res = Database::query($sql);

                if (Database::num_rows($res) > 0) {
                    // The user is already subscribed to the session. Change the
                    // record so the user is NOT a coach for this course anymore
                    // and then exit
                    $sql = "UPDATE $tbl_session_rel_course_rel_user
                            SET status = 0
                            WHERE
                                session_id = $session_id AND
                                c_id = $courseId AND
                                user_id = $user_id ";
                    $result = Database::query($sql);
                    if (Database::affected_rows($result) > 0)
                        return true;
                    else
                        return false;
                } else {
                    // The user is not subscribed to the session, so make sure
                    // he isn't subscribed to a course in this session either
                    // and then exit
                    $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                            WHERE
                                session_id = $session_id AND
                                c_id = $courseId AND
                                user_id = $user_id ";
                    $result = Database::query($sql);
                    if (Database::affected_rows($result) > 0) {
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                // Assign user as a coach to course
                // First check if the user is registered to the course
                $sql = "SELECT user_id FROM $tbl_session_rel_course_rel_user
                        WHERE
                            session_id = $session_id AND
                            c_id = $courseId AND
                            user_id = $user_id";
                $rs_check = Database::query($sql);

                // Then update or insert.
                if (Database::num_rows($rs_check) > 0) {
                    $sql = "UPDATE $tbl_session_rel_course_rel_user SET status = 2
					        WHERE
					            session_id = $session_id AND
					            c_id = $courseId AND
					            user_id = $user_id ";
                    $result = Database::query($sql);
                    if (Database::affected_rows($result) > 0) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    $sql = "INSERT INTO $tbl_session_rel_course_rel_user(session_id, c_id, user_id, status)
                            VALUES($session_id, $courseId, $user_id, 2)";
                    $result = Database::query($sql);
                    if (Database::affected_rows($result) > 0) {
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
     * @param int $sessionId
     * @return bool
     */
    public static function removeAllDrhFromSession($sessionId)
    {
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $sessionId = (int) $sessionId;

        if (empty($sessionId)) {
            return false;
        }

        $sql = "DELETE FROM $tbl_session_rel_user
                WHERE
                    session_id = $sessionId AND                            
                    relation_type =" . SESSION_RELATION_TYPE_RRHH;
        Database::query($sql);

        return true;
    }

    /**
     * Subscribes sessions to human resource manager (Dashboard feature)
     * @param array $userInfo Human Resource Manager info
     * @param array $sessions_list Sessions id
     * @param bool $sendEmail
     * @param bool $removeSessionsFromUser
     * @return int
     * */
    public static function subscribeSessionsToDrh(
        $userInfo,
        $sessions_list,
        $sendEmail = false,
        $removeSessionsFromUser = true
    ) {
        // Database Table Definitions
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        if (empty($userInfo)) {

            return 0;
        }

        $userId = $userInfo['user_id'];

        // Only subscribe DRH users.
        $rolesAllowed = array(
            DRH,
            SESSIONADMIN,
            PLATFORM_ADMIN,
            COURSE_TUTOR
        );
        $isAdmin = api_is_platform_admin_by_id($userInfo['user_id']);
        if (!$isAdmin && !in_array($userInfo['status'], $rolesAllowed)) {

            return 0;
        }

        $affected_rows = 0;
        // Deleting assigned sessions to hrm_id.
        if ($removeSessionsFromUser) {
            if (api_is_multiple_url_enabled()) {
                $sql = "SELECT s.session_id
                        FROM $tbl_session_rel_user s
                        INNER JOIN $tbl_session_rel_access_url a 
                        ON (a.session_id = s.session_id)
                        WHERE
                            s.user_id = $userId AND
                            relation_type = " . SESSION_RELATION_TYPE_RRHH . " AND
                            access_url_id = " . api_get_current_access_url_id();
            } else {
                $sql = "SELECT s.session_id 
                        FROM $tbl_session_rel_user s
                        WHERE user_id = $userId AND relation_type=" . SESSION_RELATION_TYPE_RRHH;
            }
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $sql = "DELETE FROM $tbl_session_rel_user
                            WHERE
                                session_id = {$row['session_id']} AND
                                user_id = $userId AND
                                relation_type =" . SESSION_RELATION_TYPE_RRHH;
                    Database::query($sql);
                }
            }
        }

        // Inserting new sessions list.
        if (!empty($sessions_list) && is_array($sessions_list)) {
            foreach ($sessions_list as $session_id) {
                $session_id = intval($session_id);
                $sql = "INSERT IGNORE INTO $tbl_session_rel_user (session_id, user_id, relation_type, registered_at)
                        VALUES (
                            $session_id,
                            $userId,
                            '" . SESSION_RELATION_TYPE_RRHH . "',
                            '" . api_get_utc_datetime() . "'
                        )";
                Database::query($sql);
                $affected_rows++;
            }
        }

        return $affected_rows;
    }

    /**
     * @param int $sessionId
     * @return array
     */
    public static function getDrhUsersInSession($sessionId)
    {
        return self::get_users_by_session($sessionId, SESSION_RELATION_TYPE_RRHH);
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
                    INNER JOIN $tbl_session_rel_user sru ON (sru.session_id = s.id)
                    LEFT JOIN $tbl_session_rel_access_url a ON (s.id = a.session_id)
                    WHERE
                        sru.user_id = '$userId' AND
                        sru.session_id = '$sessionId' AND
                        sru.relation_type = '" . SESSION_RELATION_TYPE_RRHH . "' AND
                        access_url_id = " . api_get_current_access_url_id() . "
                        ";
        } else {
            $sql = "$select FROM $tbl_session s
                     INNER JOIN $tbl_session_rel_user sru
                     ON
                        sru.session_id = s.id AND
                        sru.user_id = '$userId' AND
                        sru.session_id = '$sessionId' AND
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
     * @param string $description
     *
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
        $keyword = '',
        $description = ''
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
            $keyword,
            $description
        );
    }

    /**
     * Get sessions followed by human resources manager
     * @param int $userId
     * @param int $status Optional
     * @param int $start
     * @param int $limit
     * @param bool $getCount
     * @param bool $getOnlySessionId
     * @param bool $getSql
     * @param string $orderCondition
     * @param string $keyword
     * @param string $description
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
        $keyword = '',
        $description = ''
    ) {
        // Database Table Definitions
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
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

        switch ($status) {
            case DRH:
                $sessionQuery = "SELECT sru.session_id
                                 FROM
                                 $tbl_session_rel_user sru
                                 WHERE
                                    sru.relation_type = '".SESSION_RELATION_TYPE_RRHH."' AND
                                    sru.user_id = $userId";
                break;
            case COURSEMANAGER:
                $courseSessionQuery = "
                    SELECT scu.session_id as id
                    FROM $tbl_session_rel_course_rel_user scu
                    WHERE (scu.status = 2 AND scu.user_id = $userId)";

                $whereConditions = " OR (s.id_coach = $userId) ";
                break;
            default:
                $sessionQuery = "SELECT sru.session_id
                                 FROM
                                 $tbl_session_rel_user sru
                                 WHERE
                                    sru.user_id = $userId";
                break;
        }

        $keywordCondition = '';
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (s.name LIKE '%$keyword%' ) ";

            if (!empty($description)) {
                $description = Database::escape_string($description);
                $keywordCondition = " AND (s.name LIKE '%$keyword%' OR s.description LIKE '%$description%' ) ";
            }
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
            $sysUploadPath = api_get_path(SYS_UPLOAD_PATH). 'sessions/';
            $webUploadPath = api_get_path(WEB_UPLOAD_PATH). 'sessions/';
            $imgPath = Display::return_icon('session_default_small.png', null, null, null, null, true);

            $tableExtraFields = Database::get_main_table(TABLE_EXTRA_FIELD);
            $sql = "SELECT id FROM " . $tableExtraFields . "
                    WHERE extra_field_type = 3 AND variable='image'";
            $resultField = Database::query($sql);
            $imageFieldId = Database::fetch_assoc($resultField);

            while ($row = Database::fetch_array($result)) {

                $row['image'] =  null;
                $sessionImage = $sysUploadPath . $imageFieldId['id'] . '_' . $row['id'] . '.png';

                if (is_file($sessionImage)) {
                    $sessionImage = $webUploadPath . $imageFieldId['id'] . '_' . $row['id'] . '.png';
                    $row['image'] = $sessionImage;
                } else {
                    $row['image'] =  $imgPath;
                }

                if ($row['display_start_date'] == '0000-00-00 00:00:00' || $row['display_start_date'] == '0000-00-00') {
                    $row['display_start_date'] = null;
                }

                if ($row['display_end_date'] == '0000-00-00 00:00:00' || $row['display_end_date'] == '0000-00-00') {
                    $row['display_end_date'] = null;
                }

                if ($row['access_start_date'] == '0000-00-00 00:00:00' || $row['access_start_date'] == '0000-00-00') {
                    $row['access_start_date'] = null;
                }

                if ($row['access_end_date'] == '0000-00-00 00:00:00' || $row['access_end_date'] == '0000-00-00') {
                    $row['access_end_date'] = null;
                }

                if (
                    $row['coach_access_start_date'] == '0000-00-00 00:00:00' ||
                    $row['coach_access_start_date'] == '0000-00-00'
                ) {
                    $row['coach_access_start_date'] = null;
                }

                if (
                    $row['coach_access_end_date'] == '0000-00-00 00:00:00' ||
                    $row['coach_access_end_date'] == '0000-00-00'
                ) {
                    $row['coach_access_end_date'] = null;
                }

                $sessions[$row['id']] = $row;

            }
        }

        return $sessions;
    }

    /**
     * Gets the list (or the count) of courses by session filtered by access_url
     * @param int $session_id The session id
     * @param string $course_name The course code
     * @param string $orderBy Field to order the data
     * @param boolean $getCount Optional. Count the session courses
     * @return array|int List of courses. Whether $getCount is true, return the count
     */
    public static function get_course_list_by_session_id(
        $session_id,
        $course_name = '',
        $orderBy = null,
        $getCount = false
    ) {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $session_id = intval($session_id);

        $sqlSelect = "*, c.id, c.id as real_id";

        if ($getCount) {
            $sqlSelect = "COUNT(1) as count";
        }

        // select the courses
        $sql = "SELECT $sqlSelect
                FROM $tbl_course c
                INNER JOIN $tbl_session_rel_course src
                ON c.id = src.c_id
		        WHERE src.session_id = '$session_id' ";

        if (!empty($course_name)) {
            $course_name = Database::escape_string($course_name);
            $sql .= " AND c.title LIKE '%$course_name%' ";
        }

        if (!empty($orderBy)) {
            $orderBy = Database::escape_string($orderBy);
            $orderBy = " ORDER BY $orderBy";
        } else {
            if (SessionManager::orderCourseIsEnabled()) {
                $orderBy .= " ORDER BY position ";
            } else {
                $orderBy .= " ORDER BY title ";
            }
        }

        $sql .= Database::escape_string($orderBy);
        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);
        $courses = array();
        if ($num_rows > 0) {
            if ($getCount) {
                $count = Database::fetch_assoc($result);

                return intval($count['count']);
            }

            while ($row = Database::fetch_array($result,'ASSOC'))	{
                $courses[$row['real_id']] = $row;
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
                ON c.id = src.c_id
		        WHERE
		            src.session_id IN ($sessionsSQL)
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
     * @param int $session_id
     * @param string $course_name
     * @return array list of courses
     */
    public static function get_course_list_by_session_id_like($session_id, $course_name = '')
    {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $session_id = intval($session_id);
        $course_name = Database::escape_string($course_name);

        // select the courses
        $sql = "SELECT c.id, c.title FROM $tbl_course c
                INNER JOIN $tbl_session_rel_course src
                ON c.id = src.c_id
		        WHERE ";

        if (!empty($session_id)) {
            $sql .= "src.session_id LIKE '$session_id' AND ";
        }

        if (!empty($course_name)) {
            $sql .= "UPPER(c.title) LIKE UPPER('%$course_name%') ";
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
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $session_id = intval($session_id);

        // select the courses
        $sql = "SELECT COUNT(c.code) count
                FROM $tbl_course c
                INNER JOIN $tbl_session_rel_course src
                ON c.id = src.c_id
		        WHERE src.session_id = '$session_id' ";

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
     * Get the session id based on the original id and field name in the extra fields.
     * Returns 0 if session was not found
     *
     * @param string $value Original session id
     * @param string $variable Original field name
     * @return int Session id
     */
    public static function getSessionIdFromOriginalId($value, $variable)
    {
        $extraFieldValue = new ExtraFieldValue('session');
        $result = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            $variable,
            $value
        );

        if (!empty($result)) {
            return $result['item_id'];
        }

        return 0;
    }

    /**
     * Get users by session
     * @param  int $id session id
     * @param    int $status filter by status coach = 2
     * @param bool $getCount Optional. Allow get the number of rows from the result
     * @return array|int A list with an user list. If $getCount is true then return a the count of registers
     */
    public static function get_users_by_session($id, $status = null, $getCount = false)
    {
        if (empty($id)) {
            return array();
        }
        $id = intval($id);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $table_access_url_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $selectedField = 'u.user_id,lastname, firstname, username, relation_type, access_url_id';

        if ($getCount) {
            $selectedField = 'count(1) AS count';
        }

        $sql = "SELECT $selectedField
                FROM $tbl_user u
                INNER JOIN $tbl_session_rel_user
                ON u.user_id = $tbl_session_rel_user.user_id AND
                $tbl_session_rel_user.session_id = $id
                LEFT OUTER JOIN $table_access_url_user uu
                ON (uu.user_id = u.user_id)
                ";

        $urlId = api_get_current_access_url_id();
        if (isset($status) && $status != '') {
            $status = intval($status);
            $sql .= " WHERE relation_type = $status AND (access_url_id = $urlId OR access_url_id is null )";
        } else {
            $sql .= " WHERE (access_url_id = $urlId OR access_url_id is null )";
        }

        $sql .= " ORDER BY relation_type, ";
        $sql .= api_sort_by_first_name() ? ' firstname, lastname' : '  lastname, firstname';

        $result = Database::query($sql);

        if ($getCount) {
            $count = Database::fetch_assoc($result);

            return $count['count'];
        }

        $return = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $return[] = $row;
        }

        return $return;
    }

    /**
     * The general coach (field: session.id_coach)
     * @param int $user_id user id
     * @param boolean   $asPlatformAdmin The user is platform admin, return everything
     * @return array
     */
    public static function get_sessions_by_general_coach($user_id, $asPlatformAdmin = false)
    {
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        $user_id = intval($user_id);

        // Session where we are general coach
        $sql = "SELECT DISTINCT *
                FROM $session_table";

        if (!$asPlatformAdmin) {
            $sql .= " WHERE id_coach = $user_id";
        }

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();

            $sqlCoach = '';
            if (!$asPlatformAdmin) {
                $sqlCoach = " id_coach = $user_id AND ";
            }

            if ($access_url_id != -1) {
                $sql = 'SELECT DISTINCT session.*
                    FROM ' . $session_table . ' session INNER JOIN ' . $tbl_session_rel_access_url . ' session_rel_url
                    ON (session.id = session_rel_url.session_id)
                    WHERE '.$sqlCoach.' access_url_id = ' . $access_url_id;
            }
        }
        $sql .= ' ORDER by name';
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $user_id
     * @return array
     * @deprecated use get_sessions_by_general_coach()
     */
    public static function get_sessions_by_coach($user_id)
    {
        $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        return Database::select('*', $session_table, array('where' => array('id_coach = ?' => $user_id)));
    }

    /**
     * @param int $user_id
     * @param int $courseId
     * @param int $session_id
     * @return array|bool
     */
    public static function get_user_status_in_course_session($user_id, $courseId, $session_id)
    {
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT session_rcru.status
                FROM $tbl_session_rel_course_rel_user session_rcru, $tbl_user user
                WHERE
                    session_rcru.user_id = user.user_id AND
                    session_rcru.session_id = '" . intval($session_id) . "' AND
                    session_rcru.c_id ='" . intval($courseId) . "' AND
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
     * @param int $user_id
     * @param int $courseId
     * @param $session_id
     * @return int
     * @assert (null,null,null) === false
     */
    public static function get_user_status_in_session($user_id, $courseId, $session_id)
    {
        if (empty($user_id) or empty($courseId) or empty($session_id)) {
            return false;
        }
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT session_rcru.status
                FROM $tbl_session_rel_course_rel_user session_rcru, $tbl_user user
                WHERE session_rcru.user_id = user.user_id AND
                    session_rcru.session_id = '" . intval($session_id) . "' AND
                    session_rcru.c_id ='" . intval($courseId) . "' AND
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
    public static function subscribe_sessions_to_promotion($promotion_id, $list)
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
     * The new copy is not assigned to the same promotion. @see subscribe_sessions_to_promotions() for that
     * @param   int     Session ID
     * @param   bool    Whether to copy the relationship with courses
     * @param   bool    Whether to copy the relationship with users
     * @param   bool    New courses will be created
     * @param   bool    Whether to set exercises and learning paths in the new session to invisible by default
     * @return  int     The new session ID on success, 0 otherwise
     * @todo make sure the extra session fields are copied too
     */
    public static function copy(
        $id,
        $copy_courses = true,
        $copy_users = true,
        $create_new_courses = false,
        $set_exercises_lp_invisible = false
    ) {
        $id = intval($id);
        $s = self::fetch($id);
        // Check all dates before copying
        // Get timestamp for now in UTC - see http://php.net/manual/es/function.time.php#117251
        $now = time() - date('Z');
        // Timestamp in one month
        $inOneMonth = $now + (30*24*3600);
        $inOneMonth = api_get_local_time($inOneMonth);
        if (api_strtotime($s['access_start_date']) < $now) {
            $s['access_start_date'] = api_get_local_time($now);
        }
        if (api_strtotime($s['display_start_date']) < $now) {
            $s['display_start_date'] = api_get_local_time($now);
        }
        if (api_strtotime($s['coach_access_start_date']) < $now) {
            $s['coach_access_start_date'] = api_get_local_time($now);
        }
        if (api_strtotime($s['access_end_date']) < $now) {
            $s['access_end_date'] = $inOneMonth;
        }
        if (api_strtotime($s['display_end_date']) < $now) {
            $s['display_end_date'] = $inOneMonth;
        }
        if (api_strtotime($s['coach_access_end_date']) < $now) {
            $s['coach_access_end_date'] = $inOneMonth;
        }
        // Now try to create the session
        $sid = self::create_session(
            $s['name'] . ' ' . get_lang('CopyLabelSuffix'),
            $s['access_start_date'],
            $s['access_end_date'],
            $s['display_start_date'],
            $s['display_end_date'],
            $s['coach_access_start_date'],
            $s['coach_access_end_date'],
            (int)$s['id_coach'],
            $s['session_category_id'],
            (int)$s['visibility'],
            true
        );

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
                        $course_info = CourseManager::copy_course_simple(
                            $course_data['title'].' '.get_lang(
                                'CopyLabelSuffix'
                            ),
                            $course_data['course_code'],
                            $id,
                            $sid,
                            $params
                        );

                        if ($course_info) {
                            //By default new elements are invisible
                            if ($set_exercises_lp_invisible) {
                                $list = new LearnpathList('', $course_info['code'], $sid);
                                $flat_list = $list->get_flat_list();
                                if (!empty($flat_list)) {
                                    foreach ($flat_list as $lp_id => $data) {
                                        api_item_property_update(
                                            $course_info,
                                            TOOL_LEARNPATH,
                                            $lp_id,
                                            'invisible',
                                            api_get_user_id(),
                                            0,
                                            0,
                                            0,
                                            0,
                                            $sid
                                        );
                                    }
                                }
                                $quiz_table = Database::get_course_table(TABLE_QUIZ_TEST);
                                $course_id = $course_info['real_id'];
                                //@todo check this query
                                $sql = "UPDATE $quiz_table SET active = 0
                                        WHERE c_id = $course_id AND session_id = $sid";
                                Database::query($sql);
                            }
                            $new_short_courses[] = $course_info['real_id'];
                        }
                    }
                } else {
                    foreach ($short_courses as $course_data) {
                        $new_short_courses[] = $course_data['id'];
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
            self::subscribe_users_to_session($sid, $short_users, SESSION_VISIBLE_READ_ONLY, true);
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
     * Protect a session to be edited.
     * @param int $id
     * @param bool $checkSession
     * @return mixed | bool true if pass the check, api_not_allowed otherwise
     */
    public static function protectSession($id, $checkSession = true)
    {
        // api_protect_admin_script(true);
        if (self::allowToManageSessions()) {

            if (api_is_platform_admin()) {
                return true;
            }

            if ($checkSession) {
                if (self::allowed($id)) {
                    return true;
                } else {
                    api_not_allowed(true);
                }
            }
        } else {
            api_not_allowed(true);
        }
    }

    /**
     * @param int $id
     * @return bool
     */
    private static function allowed($id)
    {
        $sessionInfo = self::fetch($id);

        if (empty($sessionInfo)) {
            return false;
        }

        if (api_is_platform_admin()) {
            return true;
        }

        $userId = api_get_user_id();

        if (api_is_session_admin() &&
            api_get_setting('allow_session_admins_to_manage_all_sessions') != 'true'
        ) {
            if ($sessionInfo['session_admin_id'] != $userId) {
                return false;
            }
        }

        if (api_is_teacher() &&
            api_get_setting('allow_teachers_to_create_sessions') == 'true'
        ) {
            if ($sessionInfo['id_coach'] != $userId) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function allowToManageSessions()
    {
        if (self::allowManageAllSessions()) {
            return true;
        }

        $setting = api_get_setting('allow_teachers_to_create_sessions');

        if (api_is_teacher() && $setting == 'true') {

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function allowOnlyMySessions()
    {
        if (self::allowToManageSessions() &&
            !api_is_platform_admin() &&
            api_is_teacher()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function allowManageAllSessions()
    {
        if (api_is_platform_admin() || api_is_session_admin()) {
            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @return bool
     */
    public static function protect_teacher_session_edit($id)
    {
        if (!api_is_coach($id) && !api_is_platform_admin()) {
            api_not_allowed(true);
        } else {
            return true;
        }
    }

    /**
     * @param int $courseId
     * @return array
     */
    public static function get_session_by_course($courseId)
    {
        $table_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $table_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $courseId = intval($courseId);
        $sql = "SELECT name, s.id
                FROM $table_session_course sc
                INNER JOIN $table_session s ON (sc.session_id = s.id)
                WHERE sc.c_id = '$courseId' ";
        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * @param int $user_id
     * @param bool $ignoreVisibilityForAdmins
     * @param bool $ignoreTimeLimit
     *
     * @return array
     */
    public static function get_sessions_by_user($user_id, $ignoreVisibilityForAdmins = false, $ignoreTimeLimit = false)
    {
        $sessionCategories = UserManager::get_sessions_by_category(
            $user_id,
            false,
            $ignoreVisibilityForAdmins,
            $ignoreTimeLimit
        );

        $sessionArray = array();
        if (!empty($sessionCategories)) {
            foreach ($sessionCategories as $category) {
                if (isset($category['sessions'])) {
                    foreach ($category['sessions'] as $session) {
                        $sessionArray[] = $session;
                    }
                }
            }
        }

        return $sessionArray;
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
     * @param int $showDescription
     * @param array $teacherBackupList
     * @param array $groupBackup
     * @return array
     */
    public static function importCSV(
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
        $sessionWithCoursesModifier = false,
        $addOriginalCourseTeachersAsCourseSessionCoaches = true,
        $removeAllTeachersFromCourse = true,
        $showDescription = null,
        &$teacherBackupList = array(),
        &$groupBackup = array()
    ) {
        $content = file($file);

        $error_message = null;
        $session_counter = 0;
        $defaultUserId = empty($defaultUserId) ? api_get_user_id() : (int) $defaultUserId;

        $eol = PHP_EOL;
        if (PHP_SAPI != 'cli') {
            $eol = '<br />';
        }

        $debug = false;
        if (isset($logger)) {
            $debug = true;
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
                        if (isset($enreg[$tag_key])) {
                            $sessions[$key - 1][$tag_name] = $enreg[$tag_key];
                        }
                    }
                } else {
                    foreach ($enreg as $tag_name) {
                        $tag_names[] = api_preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag_name);
                    }
                    if (!in_array('SessionName', $tag_names) ||
                        !in_array('DateStart', $tag_names) ||
                        !in_array('DateEnd', $tag_names)
                    ) {
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

                $session_name = $enreg['SessionName'];
                // Default visibility
                $visibilityAfterExpirationPerSession = $sessionVisibility;

                if (isset($enreg['VisibilityAfterExpiration'])) {
                    $visibility = $enreg['VisibilityAfterExpiration'];
                    switch ($visibility) {
                        case 'read_only':
                            $visibilityAfterExpirationPerSession = SESSION_VISIBLE_READ_ONLY;
                            break;
                        case 'accessible':
                            $visibilityAfterExpirationPerSession = SESSION_VISIBLE;
                            break;
                        case 'not_accessible':
                            $visibilityAfterExpirationPerSession = SESSION_INVISIBLE;
                            break;
                    }
                }

                if (empty($session_name)) {
                    continue;
                }

                // We assume the dates are already in UTC
                $dateStart = explode('/', $enreg['DateStart']);
                $dateEnd = explode('/', $enreg['DateEnd']);
                $dateStart = $dateStart[0].'-'.$dateStart[1].'-'.$dateStart[2].' 00:00:00';
                $dateEnd = $dateEnd[0].'-'.$dateEnd[1].'-'.$dateEnd[2].' 23:59:59';

                $session_category_id = isset($enreg['SessionCategory']) ? $enreg['SessionCategory'] : null;
                $sessionDescription = isset($enreg['SessionDescription']) ? $enreg['SessionDescription'] : null;

                $extraParameters = null;
                if (!is_null($showDescription)) {
                    $extraParameters .= ' , show_description = '.intval($showDescription);
                }

                $coachBefore = '';
                $coachAfter = '';

                if (!empty($daysCoachAccessBeforeBeginning) && !empty($daysCoachAccessAfterBeginning)) {
                    $date = new \DateTime($dateStart);
                    $interval = new DateInterval(
                        'P'.$daysCoachAccessBeforeBeginning.'D'
                    );
                    $date->sub($interval);
                    $coachBefore = $date->format('Y-m-d h:i');
                    $coachBefore = api_get_utc_datetime($coachBefore);

                    $extraParameters .= " , coach_access_start_date = '$coachBefore'";

                    $date = new \DateTime($dateEnd);
                    $interval = new DateInterval('P'.$daysCoachAccessAfterBeginning.'D');
                    $date->add($interval);
                    $coachAfter = $date->format('Y-m-d h:i');

                    $coachAfter = api_get_utc_datetime($coachAfter);
                    $extraParameters .= " , coach_access_end_date = '$coachAfter'";
                }

                $dateStart = api_get_utc_datetime($dateStart);
                $dateEnd = api_get_utc_datetime($dateEnd);

                $extraSessionParameters = null;
                if (!empty($sessionDescription)) {
                    $extraSessionParameters = " , description = '".Database::escape_string($sessionDescription)."'";
                }

                $sessionCondition = '';
                if (!empty($session_category_id)) {
                    $sessionCondition = " , session_category_id = '$session_category_id' ";
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
                        $sql = 'SELECT 1 FROM ' . $tbl_session . '
                                WHERE name="' . Database::escape_string($session_name). $suffix . '"';
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
                            name = '" . Database::escape_string($session_name). "',
                            id_coach = '$coach_id',
                            access_start_date = '$dateStart',
                            access_end_date = '$dateEnd',
                            display_start_date = '$dateStart',
                            display_end_date = '$dateEnd',
                            visibility = '$visibilityAfterExpirationPerSession',                            
                            session_admin_id = " . $defaultUserId . " 
                            $sessionCondition $extraParameters $extraSessionParameters";
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
                        $sessionId = self::getSessionIdFromOriginalId($enreg['extra_'.$extraFieldId], $extraFieldId);
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
                        $sql = "INSERT IGNORE INTO $tbl_session SET
                                name = '$session_name',
                                id_coach = '$coach_id',
                                access_start_date = '$dateStart',
                                access_end_date = '$dateEnd',
                                display_start_date = '$dateStart',
                                display_end_date = '$dateEnd',
                                visibility = '$visibilityAfterExpirationPerSession' 
                                $extraParameters 
                                $extraSessionParameters
                                $sessionCondition
                                ";

                        Database::query($sql);

                        // We get the last insert id.
                        $my_session_result = SessionManager::get_session_by_name($enreg['SessionName']);
                        $session_id = $my_session_result['id'];

                        if ($session_id) {
                            foreach ($enreg as $key => $value) {
                                if (substr($key, 0, 6) == 'extra_') { //an extra field
                                    self::update_session_extra_field_value($session_id, substr($key, 6), $value);
                                }
                            }
                            if ($debug) {
                                $logger->addInfo("Sessions - #$session_id created: $session_name");
                            }

                            // Delete session-user relation only for students
                            $sql = "DELETE FROM $tbl_session_user
                                    WHERE session_id = '$session_id' AND relation_type <> " . SESSION_RELATION_TYPE_RRHH;
                            Database::query($sql);

                            $sql = "DELETE FROM $tbl_session_course WHERE session_id = '$session_id'";
                            Database::query($sql);

                            // Delete session-course-user relationships students and coaches.
                            if ($updateCourseCoaches) {
                                $sql = "DELETE FROM $tbl_session_course_user
                                        WHERE session_id = '$session_id' AND status in ('0', '2')";
                                Database::query($sql);
                            } else {
                                // Delete session-course-user relation ships *only* for students.
                                $sql = "DELETE FROM $tbl_session_course_user
                                        WHERE session_id = '$session_id' AND status <> 2";
                                Database::query($sql);
                            }
                        }
                    } else {
                        // Updating the session.
                        $params = array(
                            'id_coach' => $coach_id,
                            'access_start_date' => $dateStart,
                            'access_end_date' => $dateEnd,
                            'display_start_date' => $dateStart,
                            'display_end_date' => $dateEnd,
                            'visibility' => $visibilityAfterExpirationPerSession,
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
                            $session_id = $sessionId;
                            if (!empty($enreg['SessionName'])) {
                                ///$params['name'] = $enreg['SessionName'];
                                $sessionName = Database::escape_string($enreg['SessionName']);
                                $sql = "UPDATE $tbl_session SET name = '$sessionName' WHERE id = $session_id";
                                Database::query($sql);
                            }
                        } else {
                            $my_session_result = SessionManager::get_session_by_name($session_name);
                            $session_id = $my_session_result['id'];
                        }

                        if ($debug) {
                            $logger->addError("Sessions - Session #$session_id to be updated: '$session_name'");
                        }

                        if ($session_id) {
                            if ($debug) {
                                $logger->addError("Sessions - Session to be updated #$session_id");
                            }

                            $sessionInfo = api_get_session_info($session_id);
                            $params['show_description'] = isset($sessionInfo['show_description']) ? $sessionInfo['show_description'] : intval($showDescription);

                            if (!empty($daysCoachAccessBeforeBeginning) && !empty($daysCoachAccessAfterBeginning)) {
                                if (empty($sessionInfo['nb_days_access_before_beginning']) ||
                                    (!empty($sessionInfo['nb_days_access_before_beginning']) &&
                                        $sessionInfo['nb_days_access_before_beginning'] < $daysCoachAccessBeforeBeginning)
                                ) {
                                    $params['coach_access_start_date'] = $coachBefore;
                                }

                                if (empty($sessionInfo['nb_days_access_after_end']) ||
                                    (!empty($sessionInfo['nb_days_access_after_end']) &&
                                        $sessionInfo['nb_days_access_after_end'] < $daysCoachAccessAfterBeginning)
                                ) {
                                    $params['coach_access_end_date'] = $coachAfter;
                                }
                            }

                            Database::update($tbl_session, $params, array('id = ?' => $session_id));

                            foreach ($enreg as $key => $value) {
                                if (substr($key, 0, 6) == 'extra_') { //an extra field
                                    self::update_session_extra_field_value($session_id, substr($key, 6), $value);
                                }
                            }

                            // Delete session-user relation only for students
                            $sql = "DELETE FROM $tbl_session_user
                                    WHERE session_id = '$session_id' AND relation_type <> " . SESSION_RELATION_TYPE_RRHH;
                            Database::query($sql);

                            $sql = "DELETE FROM $tbl_session_course WHERE session_id = '$session_id'";
                            Database::query($sql);

                            // Delete session-course-user relationships students and coaches.
                            if ($updateCourseCoaches) {
                                $sql = "DELETE FROM $tbl_session_course_user
                                        WHERE session_id = '$session_id' AND status in ('0', '2')";
                                Database::query($sql);
                            } else {
                                // Delete session-course-user relation ships *only* for students.
                                $sql = "DELETE FROM $tbl_session_course_user
                                        WHERE session_id = '$session_id' AND status <> 2";
                                Database::query($sql);
                            }
                        } else {
                            if ($debug) {
                                $logger->addError(
                                    "Sessions - Session not found"
                                );
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
                                    user_id = '$user_id',
                                    session_id = '$session_id',
                                    registered_at = '" . api_get_utc_datetime() . "'";
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

                if ($sessionWithCoursesModifier) {
                    if (count($courses) >= 2) {
                        // Only first teacher in course session;
                        $onlyAddFirstCoachOrTeacher = true;

                        // Remove all teachers from course.
                        $removeAllTeachersFromCourse = false;
                    }
                }

                foreach ($courses as $course) {
                    $courseArray = bracketsToArray($course);
                    $course_code = $courseArray[0];

                    if (CourseManager::course_exists($course_code)) {

                        $courseInfo = api_get_course_info($course_code);
                        $courseId = $courseInfo['real_id'];

                        // Adding the course to a session.
                        $sql = "INSERT IGNORE INTO $tbl_session_course
                                SET c_id = '$courseId', session_id='$session_id'";
                        Database::query($sql);

                        SessionManager::installCourse($session_id, $courseInfo['real_id']);

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
                                SessionManager::subscribe_users_to_session_course(
                                    $userList,
                                    $session_id,
                                    $course_code
                                );
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
                                if ($addOriginalCourseTeachersAsCourseSessionCoaches) {
                                    // Adding course teachers as course session teachers.
                                    $alreadyAddedTeachers = CourseManager::get_teacher_list_from_course_code(
                                        $course_code
                                    );

                                    if (!empty($alreadyAddedTeachers)) {
                                        $teachersToAdd = array();
                                        foreach ($alreadyAddedTeachers as $user) {
                                            $teachersToAdd[] = $user['username'];
                                        }
                                        $course_coaches = array_merge(
                                            $course_coaches,
                                            $teachersToAdd
                                        );
                                    }
                                }

                                foreach ($course_coaches as $course_coach) {
                                    $coach_id = UserManager::get_user_id_from_username($course_coach);
                                    if ($coach_id !== false) {
                                        // Just insert new coaches
                                        SessionManager::updateCoaches($session_id, $courseId, array($coach_id), false);

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
                            // Only one coach is added.
                            if ($onlyAddFirstCoachOrTeacher == true) {

                                foreach ($course_coaches as $course_coach) {
                                    $coach_id = UserManager::get_user_id_from_username($course_coach);
                                    if ($coach_id !== false) {
                                        $teacherToAdd = $coach_id;
                                        break;
                                    }
                                }

                                // Un subscribe everyone that's not in the list.
                                $teacherList = CourseManager::get_teacher_list_from_course_code($course_code);
                                if (!empty($teacherList)) {
                                    foreach ($teacherList as $teacher) {
                                        if ($teacherToAdd != $teacher['user_id']) {

                                            $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                                                    WHERE
                                                        user_id = ".$teacher['user_id']." AND
                                                        course_code = '".$course_code."'
                                                    ";

                                            $result = Database::query($sql);
                                            $userCourseData = Database::fetch_array($result, 'ASSOC');
                                            $teacherBackupList[$teacher['user_id']][$course_code] = $userCourseData;

                                            $sql = "SELECT * FROM ".Database::get_course_table(TABLE_GROUP_USER)."
                                                    WHERE
                                                        user_id = ".$teacher['user_id']." AND
                                                        c_id = '".$courseInfo['real_id']."'
                                                    ";

                                            $result = Database::query($sql);
                                            while ($groupData = Database::fetch_array($result, 'ASSOC')) {
                                                $groupBackup['user'][$teacher['user_id']][$course_code][$groupData['group_id']] = $groupData;
                                            }

                                            $sql = "SELECT * FROM ".Database::get_course_table(TABLE_GROUP_TUTOR)."
                                                    WHERE
                                                        user_id = ".$teacher['user_id']." AND
                                                        c_id = '".$courseInfo['real_id']."'
                                                    ";

                                            $result = Database::query($sql);
                                            while ($groupData = Database::fetch_array($result, 'ASSOC')) {
                                                $groupBackup['tutor'][$teacher['user_id']][$course_code][$groupData['group_id']] = $groupData;
                                            }

                                            CourseManager::unsubscribe_user(
                                                $teacher['user_id'],
                                                $course_code
                                            );
                                        }
                                    }
                                }

                                if (!empty($teacherToAdd)) {
                                    SessionManager::updateCoaches($session_id, $courseId, array($teacherToAdd), true);

                                    $userCourseCategory = '';
                                    if (isset($teacherBackupList[$teacherToAdd]) &&
                                        isset($teacherBackupList[$teacherToAdd][$course_code])
                                    ) {
                                        $courseUserData = $teacherBackupList[$teacherToAdd][$course_code];
                                        $userCourseCategory = $courseUserData['user_course_cat'];
                                    }

                                    CourseManager::subscribe_user(
                                        $teacherToAdd,
                                        $course_code,
                                        COURSEMANAGER,
                                        0,
                                        $userCourseCategory
                                    );

                                    if (isset($groupBackup['user'][$teacherToAdd]) &&
                                        isset($groupBackup['user'][$teacherToAdd][$course_code]) &&
                                        !empty($groupBackup['user'][$teacherToAdd][$course_code])
                                    ) {
                                        foreach ($groupBackup['user'][$teacherToAdd][$course_code] as $data) {
                                            GroupManager::subscribe_users(
                                                $teacherToAdd,
                                                $data['group_id'],
                                                $data['c_id']
                                            );
                                        }
                                    }

                                    if (isset($groupBackup['tutor'][$teacherToAdd]) &&
                                        isset($groupBackup['tutor'][$teacherToAdd][$course_code]) &&
                                        !empty($groupBackup['tutor'][$teacherToAdd][$course_code])
                                    ) {
                                        foreach ($groupBackup['tutor'][$teacherToAdd][$course_code] as $data) {
                                            GroupManager::subscribe_tutors(
                                                $teacherToAdd,
                                                $data['group_id'],
                                                $data['c_id']
                                            );
                                        }
                                    }
                                }
                            }

                            // See BT#6449#note-195
                            // All coaches are added.
                            if ($removeAllTeachersFromCourse) {
                                $teacherToAdd = null;
                                foreach ($course_coaches as $course_coach) {
                                    $coach_id = UserManager::get_user_id_from_username(
                                        $course_coach
                                    );
                                    if ($coach_id !== false) {
                                        $teacherToAdd[] = $coach_id;
                                    }
                                }

                                if (!empty($teacherToAdd)) {
                                    // Deleting all course teachers and adding the only coach as teacher.
                                    $teacherList = CourseManager::get_teacher_list_from_course_code($course_code);

                                    if (!empty($teacherList)) {
                                        foreach ($teacherList as $teacher) {
                                            if (!in_array($teacher['user_id'], $teacherToAdd)) {

                                                $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                                                        WHERE
                                                            user_id = ".$teacher['user_id']." AND
                                                            course_code = '".$course_code."'
                                                        ";

                                                $result = Database::query($sql);
                                                $userCourseData = Database::fetch_array($result, 'ASSOC');
                                                $teacherBackupList[$teacher['user_id']][$course_code] = $userCourseData;

                                                $sql = "SELECT * FROM ".Database::get_course_table(TABLE_GROUP_USER)."
                                                    WHERE
                                                        user_id = ".$teacher['user_id']." AND
                                                        c_id = '".$courseInfo['real_id']."'
                                                    ";

                                                $result = Database::query($sql);
                                                while ($groupData = Database::fetch_array($result, 'ASSOC')) {
                                                    $groupBackup['user'][$teacher['user_id']][$course_code][$groupData['group_id']] = $groupData;
                                                }

                                                $sql = "SELECT * FROM ".Database::get_course_table(TABLE_GROUP_TUTOR)."
                                                        WHERE
                                                            user_id = ".$teacher['user_id']." AND
                                                            c_id = '".$courseInfo['real_id']."'
                                                        ";

                                                $result = Database::query($sql);
                                                while ($groupData = Database::fetch_array($result, 'ASSOC')) {
                                                    $groupBackup['tutor'][$teacher['user_id']][$course_code][$groupData['group_id']] = $groupData;
                                                }

                                                CourseManager::unsubscribe_user(
                                                    $teacher['user_id'],
                                                    $course_code
                                                );
                                            }
                                        }
                                    }

                                    foreach ($teacherToAdd as $teacherId) {
                                        $userCourseCategory = '';
                                        if (isset($teacherBackupList[$teacherId]) &&
                                            isset($teacherBackupList[$teacherId][$course_code])
                                        ) {
                                            $courseUserData = $teacherBackupList[$teacherId][$course_code];
                                            $userCourseCategory = $courseUserData['user_course_cat'];
                                        }

                                        CourseManager::subscribe_user(
                                            $teacherId,
                                            $course_code,
                                            COURSEMANAGER,
                                            0,
                                            $userCourseCategory
                                        );

                                        if (isset($groupBackup['user'][$teacherId]) &&
                                            isset($groupBackup['user'][$teacherId][$course_code]) &&
                                            !empty($groupBackup['user'][$teacherId][$course_code])
                                        ) {
                                            foreach ($groupBackup['user'][$teacherId][$course_code] as $data) {
                                                GroupManager::subscribe_users(
                                                    $teacherId,
                                                    $data['group_id'],
                                                    $data['c_id']
                                                );
                                            }
                                        }

                                        if (isset($groupBackup['tutor'][$teacherId]) &&
                                            isset($groupBackup['tutor'][$teacherId][$course_code]) &&
                                            !empty($groupBackup['tutor'][$teacherId][$course_code])
                                        ) {
                                            foreach ($groupBackup['tutor'][$teacherId][$course_code] as $data) {
                                                GroupManager::subscribe_tutors(
                                                    $teacherId,
                                                    $data['group_id'],
                                                    $data['c_id']
                                                );
                                            }
                                        }
                                    }
                                }
                            }

                            // Continue default behaviour.
                            if ($onlyAddFirstCoachOrTeacher == false) {
                                // Checking one more time see BT#6449#note-149
                                $coaches = SessionManager::getCoachesByCourseSession($session_id, $courseId);
                                // Update coaches if only there's 1 course see BT#6449#note-189
                                if (empty($coaches) || count($courses) == 1) {
                                    foreach ($course_coaches as $course_coach) {
                                        $course_coach = trim($course_coach);
                                        $coach_id = UserManager::get_user_id_from_username($course_coach);
                                        if ($coach_id !== false) {
                                            // Just insert new coaches
                                            SessionManager::updateCoaches(
                                                $session_id,
                                                $courseId,
                                                array($coach_id),
                                                false
                                            );

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
                        $course_users = array_filter($course_users);

                        if (!empty($course_users)) {
                            foreach ($course_users as $user) {
                                $user_id = UserManager::get_user_id_from_username($user);

                                if ($user_id !== false) {
                                    SessionManager::subscribe_users_to_session_course(
                                        array($user_id),
                                        $session_id,
                                        $course_code
                                    );
                                    if ($debug) {
                                        $logger->addInfo("Sessions - Adding student: user #$user_id ($user) to course: '$course_code' and session #$session_id");
                                    }
                                } else {
                                    $error_message .= get_lang('UserDoesNotExist').': '.$user.$eol;
                                }
                            }
                        }

                        $inserted_in_course[$course_code] = $courseInfo['title'];
                    }
                }
                $access_url_id = api_get_current_access_url_id();
                UrlManager::add_session_to_url($session_id, $access_url_id);
                $sql = "UPDATE $tbl_session SET nbr_users = '$user_counter', nbr_courses = '$course_counter' WHERE id = '$session_id'";
                Database::query($sql);
            }
        }

        return array(
            'error_message' => $error_message,
            'session_counter' => $session_counter,
            'session_list' => $sessionList,
        );
    }

    /**
     * @param int $sessionId
     * @param int $courseId
     * @return array
     */
    public static function getCoachesByCourseSession($sessionId, $courseId)
    {
        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);

        $sql = "SELECT user_id FROM $table
                WHERE
                    session_id = '$sessionId' AND
                    c_id = '$courseId' AND
                    status = 2";
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
     * @param int $sessionId
     * @param int $courseId
     * @return string
     */
    public static function getCoachesByCourseSessionToString(
        $sessionId,
        $courseId
    ) {
        $coaches = self::getCoachesByCourseSession($sessionId, $courseId);
        $list = array();
        if (!empty($coaches)) {
            foreach ($coaches as $coachId) {
                $userInfo = api_get_user_info($coachId);
                $list[] = api_get_person_name(
                    $userInfo['firstname'],
                    $userInfo['lastname']
                );
            }
        }

        return array_to_string($list, CourseManager::USER_SEPARATOR);
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

        $sql = "SELECT DISTINCT user_id
                FROM $table
                WHERE session_id = '$sessionId' AND status = 2";
        $result = Database::query($sql);

        $coaches = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $coaches[] = $row['user_id'];
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
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
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

        $courseList = CourseManager::get_courses_followed_by_drh($userId, DRH);
        $courseConditions = ' AND 1 <> 1';
        if (!empty($courseList)) {
            $courseIdList = array_column($courseList, 'id');
            $courseConditions = ' AND c.id IN ("'.implode('","', $courseIdList).'")';
        }

        $userConditionsFromDrh = '';

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
            $userConditionsFromDrh = " AND u.user_id IN (".$studentListSql.") ";
        }

        switch ($status) {
            case 'drh':
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
            case 'session_admin':
                $sessionConditions = " AND s.id_coach = $userId ";
                $userConditionsFromDrh = '';
                break;
            case 'admin':
                break;
            case 'teacher':
                $sessionConditions = " AND s.id_coach = $userId ";
                $userConditionsFromDrh = '';
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

        $userUnion = '';
        if (!empty($userConditionsFromDrh)) {
            $userUnion = "
            UNION (
                $select                    
                FROM $tbl_user u
                INNER JOIN $tbl_user_rel_access_url url ON (url.user_id = u.id)
                $where
                $userConditionsFromDrh
            )";
        }

        $sql = "$masterSelect (
                ($select
                FROM $tbl_session s
                    INNER JOIN $tbl_session_rel_course_rel_user su ON (s.id = su.session_id)
                    INNER JOIN $tbl_user u ON (u.user_id = su.user_id)
                    INNER JOIN $tbl_session_rel_access_url url ON (url.session_id = s.id)
                    $where
                    $sessionConditions
                ) UNION (
                    $select
                    FROM $tbl_course c
                    INNER JOIN $tbl_course_user cu ON (cu.c_id = c.id)
                    INNER JOIN $tbl_user u ON (u.user_id = cu.user_id)
                    INNER JOIN $tbl_course_rel_access_url url ON (url.c_id = c.id)
                    $where
                    $courseConditions
                ) $userUnion
                ) as t1
                ";

        if ($getCount) {
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
     * @param int $courseId
     * @param array $coachList
     * @param bool $deleteCoachesNotInList
     */
    public static function updateCoaches(
        $sessionId,
        $courseId,
        $coachList,
        $deleteCoachesNotInList = false
    ) {
        $currentCoaches = self::getCoachesByCourseSession($sessionId, $courseId);

        if (!empty($coachList)) {
            foreach ($coachList as $userId) {
                self::set_coach_to_course_session($userId, $sessionId, $courseId);
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
                    self::set_coach_to_course_session(
                        $userId,
                        $sessionId,
                        $courseId,
                        true
                    );
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
                            SessionManager::subscribe_users_to_session(
                                $sessionDestinationId,
                                $newUserList,
                                SESSION_VISIBLE_READ_ONLY,
                                false
                            );
                        }
                    } else {
                        $messages[] = Display::return_message(get_lang('NoDestinationSessionProvided'), 'warning');
                    }
                } else {
                    $messages[] = Display::return_message(
                        get_lang('NoStudentsFoundForSession').' #'.$sessionInfo['name'],
                        'warning'
                    );
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
                        $courseInfo,
                        $coachList,
                        false,
                        false,
                        false
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
                $htmlResult .= sprintf(
                    get_lang('CoachesSubscribedAsATeacherInCourseX'),
                    Display::url($courseCode, $url, array('target' => '_blank'))
                );
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
                api_is_student_boss() ? STUDENT_BOSS : COURSEMANAGER,
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
        $teacherListId = array();

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
                        INNER JOIN $sessionCourse src ON c.id = src.c_id
                        INNER JOIN $courseUser cu ON (cu.c_id = c.id)
		                WHERE src.session_id IN ('$sessionToString') AND cu.status = 1";
                $result = Database::query($sql);
                while($row = Database::fetch_array($result, 'ASSOC')) {
                    $teacherListId[$row['user_id']] = $row['user_id'];
                }
            } else {
                $teacherResult = UserManager::get_users_followed_by_drh($userId, COURSEMANAGER);
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
            'courseIntroduction',
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
        $sql = "DELETE FROM $TBL_INTRODUCTION
                WHERE c_id = $courseId AND session_id = $sessionId";
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
     * @param array $userSessionList format see self::importSessionDrhCSV()
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
                self::subscribeSessionsToDrh(
                    $userInfo,
                    $sessionList,
                    $sendEmail,
                    $removeOldRelationShips
                );
            }
        }
    }

    /**
     * @param array $userSessionList format see self::importSessionDrhCSV()
     *
     * @return string
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
     *
     * @return string
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
                        'session_info' => $sessionInfo,
                    );
                    $userSessionList[$userInfo['user_id']]['user_info'] = $userInfo;
                }
            }

            self::subscribeDrhToSessionList($userSessionList, $sendEmail, $removeOldRelationShips);
            return self::checkSubscribeDrhToSessionList($userSessionList);
        }
    }

    /**
     * Courses re-ordering in resume_session.php flag see BT#8316
     */
    public static function orderCourseIsEnabled()
    {
        $sessionCourseOrder = api_get_setting('session_course_ordering');
        if ($sessionCourseOrder === 'true') {
            return true;
        }

        return false;
    }

    /**
     * @param string $direction (up/down)
     * @param int $sessionId
     * @param int $courseId
     * @return bool
     */
    public static function move($direction, $sessionId, $courseId)
    {
        if (!self::orderCourseIsEnabled()) {
            return false;
        }

        $sessionId = intval($sessionId);
        $courseId = intval($courseId);

        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $courseList = self::get_course_list_by_session_id($sessionId, null, 'position');

        $position = array();
        $count = 0;
        foreach ($courseList as $course) {
            if ($course['position'] == '') {
                $course['position'] = $count;
            }
            $position[$course['code']] = $course['position'];
            // Saving current order.
            $sql = "UPDATE $table SET position = $count
                    WHERE session_id = $sessionId AND c_id = '".$course['real_id']."'";
            Database::query($sql);
            $count++;
        }

        // Loading new positions.
        $courseList = self::get_course_list_by_session_id($sessionId, null, 'position');

        $found = false;

        switch ($direction) {
            case 'up':
                $courseList = array_reverse($courseList);
                break;
            case 'down':
                break;
        }

        foreach ($courseList as $course) {
            if ($found) {
                $nextId = $course['real_id'];
                $nextOrder = $course['position'];
                break;
            }

            if ($courseId == $course['real_id']) {
                $thisCourseCode = $course['real_id'];
                $thisOrder = $course['position'];
                $found = true;
            }
        }

        $sql1 = "UPDATE $table SET position = '".intval($nextOrder)."'
                 WHERE session_id = $sessionId AND c_id =  $thisCourseCode";
        Database::query($sql1);

        $sql2 = "UPDATE $table SET position = '".intval($thisOrder)."'
                 WHERE session_id = $sessionId AND c_id = $nextId";
        Database::query($sql2);

        return true;
    }

    /**
     * @param int $sessionId
     * @param int $courseId
     * @return bool
     */
    public static function moveUp($sessionId, $courseId)
    {
        return self::move('up', $sessionId, $courseId);
    }

    /**
     * @param int $sessionId
     * @param string $courseCode
     * @return bool
     */
    public static function moveDown($sessionId, $courseCode)
    {
        return self::move('down', $sessionId, $courseCode);
    }

    /**
     * Use the session duration to allow/block user access see BT#8317
     * Needs these DB changes
     * ALTER TABLE session ADD COLUMN duration int;
     * ALTER TABLE session_rel_user ADD COLUMN duration int;
     */
    public static function durationPerUserIsEnabled()
    {
        return api_get_configuration_value('session_duration_feature');
    }

    /**
     * Returns the number of days the student has left in a session when using
     * sessions durations
     * @param int $userId
     * @param int $sessionId
     * @param int $duration in days
     * @return int
     */
    public static function getDayLeftInSession($sessionId, $userId, $duration)
    {
        // Get an array with the details of the first access of the student to
        // this session
        $courseAccess = CourseManager::getFirstCourseAccessPerSessionAndUser(
            $sessionId,
            $userId
        );

        $currentTime = time();

        // If no previous access, return false
        if (count($courseAccess) == 0) {
            return false;
        }

        $firstAccess = api_strtotime($courseAccess['login_course_date'], 'UTC');

        $endDateInSeconds = $firstAccess + $duration*24*60*60;
        $leftDays = round(($endDateInSeconds- $currentTime) / 60 / 60 / 24);

        return $leftDays;
    }

    /**
     * @param int $duration
     * @param int $userId
     * @param int $sessionId
     */
    public static function editUserSessionDuration($duration, $userId, $sessionId)
    {
        $duration = intval($duration);
        $userId = intval($userId);
        $sessionId = intval($sessionId);

        if (empty($userId) || empty($sessionId)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $parameters = array('duration' => $duration);
        $where = array('session_id = ? AND user_id = ? ' => array($sessionId, $userId));
        Database::update($table, $parameters, $where);
    }

    /**
     * Gets one row from the session_rel_user table
     * @param int $userId
     * @param int $sessionId
     *
     * @return array
     */
    public static function getUserSession($userId, $sessionId)
    {
        $userId = intval($userId);
        $sessionId = intval($sessionId);

        if (empty($userId) || empty($sessionId)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $sql = "SELECT * FROM $table
                WHERE session_id = $sessionId AND user_id = $userId";
        $result = Database::query($sql);
        $values = array();
        if (Database::num_rows($result)) {
            $values = Database::fetch_array($result, 'ASSOC');
        }

        return $values;
    }

    /**
     * Check if user is subscribed inside a session as student
     * @param int $sessionId The session id
     * @param int $userId The user id
     * @return boolean Whether is subscribed
     */
    public static function isUserSubscribedAsStudent($sessionId, $userId)
    {
        $sessionRelUserTable = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $sessionId = intval($sessionId);
        $userId = intval($userId);

        // COUNT(1) actually returns the number of rows from the table (as if
        // counting the results from the first column)
        $sql = "SELECT COUNT(1) AS qty FROM $sessionRelUserTable
                WHERE
                    session_id = $sessionId AND
                    user_id = $userId AND
                    relation_type = 0";

        $result = Database::fetch_assoc(Database::query($sql));

        if (!empty($result) && $result['qty'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the session coached by a user (general coach and course-session coach)
     * @param int $coachId The coach id
     * @param boolean $checkSessionRelUserVisibility Check the session visibility
     * @param boolean $asPlatformAdmin The user is a platform admin and we want all sessions
     * @return array The session list
     */
    public static function getSessionsCoachedByUser($coachId, $checkSessionRelUserVisibility = false, $asPlatformAdmin = false)
    {
        // Get all sessions where $coachId is the general coach
        $sessions = self::get_sessions_by_general_coach($coachId, $asPlatformAdmin);
        // Get all sessions where $coachId is the course - session coach
        $courseSessionList = self::getCoursesListByCourseCoach($coachId);
        $sessionsByCoach = array();
        if (!empty($courseSessionList)) {
            foreach ($courseSessionList as $userCourseSubscription) {
                $session = $userCourseSubscription->getSession();
                $sessionsByCoach[$session->getId()] = api_get_session_info(
                    $session->getId()
                );
            }
        }

        if (!empty($sessionsByCoach)) {
            $sessions = array_merge($sessions, $sessionsByCoach);
        }

        // Remove repeated sessions
        if (!empty($sessions)) {
            $cleanSessions = array();
            foreach ($sessions as $session) {
                $cleanSessions[$session['id']] = $session;
            }
            $sessions = $cleanSessions;
        }

        if ($checkSessionRelUserVisibility) {
            if (!empty($sessions)) {
                $newSessions = array();
                foreach ($sessions as $session) {
                    $visibility = api_get_session_visibility($session['id']);
                    if ($visibility == SESSION_INVISIBLE) {
                        continue;
                    }
                    $newSessions[] = $session;
                }
                $sessions = $newSessions;
            }
        }

        return $sessions;
    }

    /**
     * Check if the course belongs to the session
     * @param int $sessionId The session id
     * @param string $courseCode The course code
     *
     * @return bool
     */
    public static function sessionHasCourse($sessionId, $courseCode)
    {
        $sessionId = intval($sessionId);
        $courseCode = Database::escape_string($courseCode);

        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $sessionRelCourseTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $sql = "SELECT COUNT(1) AS qty
                FROM $courseTable c
                INNER JOIN $sessionRelCourseTable src
                ON c.id = src.c_id
                WHERE src.session_id = $sessionId
                AND c.code = '$courseCode'  ";

        $result = Database::query($sql);

        if ($result !== false) {
            $data = Database::fetch_assoc($result);

            if ($data['qty'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the list of course coaches
     * @return array The list
     */
    public static function getAllCourseCoaches()
    {
        $coaches = array();

        $scuTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $idResult = Database::select('DISTINCT user_id', $scuTable, array(
            'where' => array(
                'status = ?' => 2,
            ),
        ));

        if ($idResult != false) {
            foreach ($idResult as $idData) {
                $userResult = Database::select('user_id, lastname, firstname, username', $userTable, array(
                    'where' => array(
                        'user_id = ?' => $idData['user_id'],
                    ),
                ), 'first');

                if ($userResult != false) {
                    $coaches[] = array(
                        'id' => $userResult['user_id'],
                        'lastname' => $userResult['lastname'],
                        'firstname' => $userResult['firstname'],
                        'username' => $userResult['username'],
                        'completeName' => api_get_person_name(
                            $userResult['firstname'],
                            $userResult['lastname']
                        ),
                    );
                }
            }
        }

        return $coaches;
    }

    /**
     * Calculate the total user time in the platform
     * @param int $userId The user id
     * @param string $from Optional. From date
     * @param string $until Optional. Until date
     * @return string The time (hh:mm:ss)
     */
    public static function getTotalUserTimeInPlatform($userId, $from = '', $until = '')
    {
        $userId = intval($userId);

        $trackLoginTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $whereConditions = array(
            'login_user_id = ? ' => $userId,
        );

        if (!empty($from) && !empty($until)) {
            $whereConditions["AND (login_date >= '?' "] = $from;
            $whereConditions["AND logout_date <= DATE_ADD('?', INTERVAL 1 DAY)) "] = $until;
        }

        $trackResult = Database::select(
            'SEC_TO_TIME(SUM(UNIX_TIMESTAMP(logout_date) - UNIX_TIMESTAMP(login_date))) as total_time',
            $trackLoginTable,
            array(
                'where' => $whereConditions,
            ), 'first'
        );

        if ($trackResult != false) {
            return $trackResult['total_time'] ? $trackResult['total_time'] : '00:00:00';
        }

        return '00:00:00';
    }

    /**
     * Get the courses list by a course coach
     * @param int $coachId The coach id
     * @return array (id, user_id, session_id, c_id, visibility, status, legal_agreement)
     */
    public static function getCoursesListByCourseCoach($coachId)
    {
        $entityManager = Database::getManager();
        $scuRepo = $entityManager->getRepository(
            'ChamiloCoreBundle:SessionRelCourseRelUser'
        );

        return $scuRepo->findBy([
            'user' => $coachId,
            'status' => SessionRelCourseRelUser::STATUS_COURSE_COACH
        ]);
    }

	/**
     * Get the count of user courses in session
     * @param int $sessionId The session id
     * @return array
     */
    public static function getTotalUserCoursesInSession($sessionId)
    {
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableSessionRelCourseRelUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sql = "SELECT COUNT(1) as count, u.id, scu.status status_in_session, u.status user_status
                FROM $tableSessionRelCourseRelUser scu
                INNER JOIN $tableUser u ON scu.user_id = u.id
                WHERE scu.session_id = " . intval($sessionId) ."
                GROUP BY u.id";

        $result = Database::query($sql);

        $list = array();
        while ($data = Database::fetch_assoc($result)) {
            $list[] = $data;
        }

        return $list;
    }


    /**
     * Returns list of a few data from session (name, short description, start
     * date, end date) and the given extra fields if defined based on a
     * session category Id.
     * @param int $categoryId The internal ID of the session category
     * @param string $target Value to search for in the session field values
     * @param array $extraFields A list of fields to be scanned and returned
     * @return mixed
     */
    public static function getShortSessionListAndExtraByCategory($categoryId, $target, $extraFields = null, $publicationDate = null)
    {
        // Init variables
        $categoryId = (int) $categoryId;
        $sessionList = array();
        // Check if categoryId is valid
        if ($categoryId > 0) {
            $target = Database::escape_string($target);
            $sTable = Database::get_main_table(TABLE_MAIN_SESSION);
            $sfTable = Database::get_main_table(TABLE_EXTRA_FIELD);
            $sfvTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
            // Join session field and session field values tables
            $joinTable = $sfTable . ' sf INNER JOIN ' . $sfvTable . ' sfv ON sf.id = sfv.field_id';
            $fieldsArray = array();
            foreach ($extraFields as $field) {
                $fieldsArray[] = Database::escape_string($field);
            }
            $extraFieldType = ExtraField::SESSION_FIELD_TYPE;
            if (isset ($publicationDate)) {
                $publicationDateString = $publicationDate->format('Y-m-d H:i:s');
                $wherePublication = " AND id NOT IN (
                    SELECT sfv.item_id FROM $joinTable
                    WHERE
                        sf.extra_field_type = $extraFieldType AND
                        ((sf.variable = 'publication_start_date' AND sfv.value > '$publicationDateString' and sfv.value != '') OR
                        (sf.variable = 'publication_end_date' AND sfv.value < '$publicationDateString' and sfv.value != ''))
                )";
            }
            // Get the session list from session category and target
            $sessionList = Database::select(
                'id, name, access_start_date, access_end_date',
                $sTable,
                array(
                    'where' => array(
                        "session_category_id = ? AND id IN (
                            SELECT sfv.item_id FROM $joinTable
                            WHERE
                                sf.extra_field_type = $extraFieldType AND
                                sfv.item_id = session.id AND
                                sf.variable = 'target' AND
                                sfv.value = ?
                        ) $wherePublication" => array($categoryId, $target),
                    ),
                )
            );
            $whereFieldVariables = array();
            $whereFieldIds = array();
            if (
                is_array($fieldsArray) &&
                count($fieldsArray) > 0
            ) {
                $whereParams = '?';
                for ($i = 1; $i < count($fieldsArray); $i++) {
                    $whereParams .= ', ?';
                }
                $whereFieldVariables = ' variable IN ( ' . $whereParams .' )';
                $whereFieldIds = 'field_id IN ( ' . $whereParams .  ' )';
            }
            // Get session fields
            $extraField = new ExtraFieldModel('session');
            $questionMarks = substr(str_repeat('?, ', count($fieldsArray)), 0, -2);
            $fieldsList = $extraField->get_all(array(
                ' variable IN ( ' . $questionMarks . ' )' => $fieldsArray,
            ));
            // Index session fields
            foreach ($fieldsList as $field) {
                $fields[$field['id']] = $field['variable'];
            }
            // Get session field values
            $extra = new ExtraFieldValue('session');
            $questionMarksFields = substr(str_repeat('?, ', count($fields)), 0, -2);
            $sessionFieldValueList = $extra->get_all(array ('where' => array('field_id IN ( ' . $questionMarksFields . ' )' => array_keys($fields))));
            // Add session fields values to session list
            foreach ($sessionList as $id => &$session) {
                foreach ($sessionFieldValueList as $sessionFieldValue) {
                    // Match session field values to session
                    if ($sessionFieldValue['item_id'] == $id) {
                        // Check if session field value is set in session field list
                        if (isset($fields[$sessionFieldValue['field_id']])) {
                            // Avoid overwriting the session's ID field
                            if ($fields[$sessionFieldValue['field_id']] != 'id') {
                                $var = $fields[$sessionFieldValue['field_id']];
                                $val = $sessionFieldValue['value'];
                                // Assign session field value to session
                                $session[$var] = $val;
                            }
                        }
                    }
                }
            }
        }

        return $sessionList;
    }

    /**
     * Return the Session Category id searched by name
     * @param string $categoryName Name attribute of session category used for search query
     * @param bool $force boolean used to get even if something is wrong (e.g not unique name)
     * @return int|array If success, return category id (int), else it will return an array
     * with the next structure:
     * array('error' => true, 'errorMessage' => ERROR_MESSAGE)
     */
    public static function getSessionCategoryIdByName($categoryName, $force = false)
    {
        // Start error result
        $errorResult = array('error' => true, 'errorMessage' => get_lang('ThereWasAnError'));
        $categoryName = Database::escape_string($categoryName);
        // Check if is not empty category name
        if (!empty($categoryName)) {
            $sessionCategoryTable = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
            // Get all session category with same name
            $result = Database::select(
                'id',
                $sessionCategoryTable,
                array(
                    'where' => array(
                        'name = ?' => $categoryName,
                    ),
                )
            );
            // Check the result
            if ($result < 1) {
                // If not found any result, update error message
                $errorResult['errorMessage'] = 'Not found any session category name ' . $categoryName;
            } elseif (count($result) > 1 && !$force) {
                // If found more than one result and force is disabled, update error message
                $errorResult['errorMessage'] = 'Found many session categories';
            } elseif (count($result) == 1 || $force) {
                // If found just one session category or force option is enabled

                return key($result);
            }
        } else {
            // category name is empty, update error message
            $errorResult['errorMessage'] = 'Not valid category name';
        }

        return $errorResult;
    }

    /**
     * Return all data from sessions (plus extra field, course and coach data) by category id
     * @param int $sessionCategoryId session category id used to search sessions
     * @return array If success, return session list and more session related data, else it will return an array
     * with the next structure:
     * array('error' => true, 'errorMessage' => ERROR_MESSAGE)
     */
    public static function getSessionListAndExtraByCategoryId($sessionCategoryId)
    {
        // Start error result
        $errorResult = array(
            'error' => true,
            'errorMessage' => get_lang('ThereWasAnError'),
        );

        $sessionCategoryId = intval($sessionCategoryId);
        // Check if session category id is valid
        if ($sessionCategoryId > 0) {
            // Get table names
            $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
            $sessionFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
            $sessionFieldValueTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
            $sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $userTable = Database::get_main_table(TABLE_MAIN_USER);
            $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

            // Get all data from all sessions whit the session category specified
            $sessionList = Database::select(
                '*',
                $sessionTable,
                array(
                    'where' => array(
                        'session_category_id = ?' => $sessionCategoryId,
                    ),
                )
            );

            $extraFieldType = ExtraField::SESSION_FIELD_TYPE;

            // Check if session list query had result
            if (!empty($sessionList)) {
                // implode all session id
                $sessionIdsString = '(' . implode(', ', array_keys($sessionList)) . ')';
                // Get all field variables
                $sessionFieldList = Database::select(
                    'id, variable',
                    $sessionFieldTable,
                    array('extra_field_type = ? ' => array($extraFieldType))
                );

                // Get all field values
                $sql = "SELECT item_id, field_id, value FROM
                        $sessionFieldValueTable v INNER JOIN $sessionFieldTable f
                        ON (f.id = v.field_id)
                        WHERE
                            item_id IN $sessionIdsString AND
                            extra_field_type = $extraFieldType
                ";
                $result = Database::query($sql);
                $sessionFieldValueList = Database::store_result($result, 'ASSOC');

                // Check if session field values had result
                if (!empty($sessionFieldValueList)) {
                    $sessionFieldValueListBySession = array();
                    foreach ($sessionFieldValueList as $key => $sessionFieldValue) {
                        // Create an array to index ids to session id
                        $sessionFieldValueListBySession[$sessionFieldValue['item_id']][] = $key;
                    }
                }
                // Query used to find course-coaches from sessions
                $sql = "SELECT
                        scu.session_id,
                        c.id AS course_id,
                        c.code AS course_code,
                        c.title AS course_title,
                        u.username AS coach_username,
                        u.firstname AS coach_firstname,
                        u.lastname AS coach_lastname
                        FROM $courseTable c
                        INNER JOIN $sessionCourseUserTable scu ON c.id = scu.c_id
                        INNER JOIN $userTable u ON scu.user_id = u.user_id
                        WHERE scu.status = 2 AND scu.session_id IN $sessionIdsString
                        ORDER BY scu.session_id ASC ";
                $res = Database::query($sql);
                $sessionCourseList = Database::store_result($res, 'ASSOC');
                // Check if course list had result
                if (!empty($sessionCourseList)) {
                    foreach ($sessionCourseList as $key => $sessionCourse) {
                        // Create an array to index ids to session_id
                        $sessionCourseListBySession[$sessionCourse['session_id']][] = $key;
                    }
                }
                // Join lists
                if (is_array($sessionList)) {
                    foreach ($sessionList as $id => &$row) {
                        if (
                            !empty($sessionFieldValueListBySession) &&
                            is_array($sessionFieldValueListBySession[$id])
                        ) {
                            // If have an index array for session extra fields, use it to join arrays
                            foreach ($sessionFieldValueListBySession[$id] as $key) {
                                $row['extra'][$key] = array(
                                    'field_name' => $sessionFieldList[$sessionFieldValueList[$key]['field_id']]['variable'],
                                    'value' => $sessionFieldValueList[$key]['value'],
                                );
                            }
                        }
                        if (
                            !empty($sessionCourseListBySession) &&
                            is_array($sessionCourseListBySession[$id])
                        ) {
                            // If have an index array for session course coach, use it to join arrays
                            foreach ($sessionCourseListBySession[$id] as $key) {
                                $row['course'][$key] = array(
                                    'course_id' => $sessionCourseList[$key]['course_id'],
                                    'course_code' => $sessionCourseList[$key]['course_code'],
                                    'course_title' => $sessionCourseList[$key]['course_title'],
                                    'coach_username' => $sessionCourseList[$key]['coach_username'],
                                    'coach_firstname' => $sessionCourseList[$key]['coach_firstname'],
                                    'coach_lastname' => $sessionCourseList[$key]['coach_lastname'],
                                );
                            }
                        }
                    }
                }

                return $sessionList;
            } else {
                // Not found result, update error message
                $errorResult['errorMessage'] = 'Not found any session for session category id ' . $sessionCategoryId;
            }
        }

        return $errorResult;
    }

    /**
     * Return session description from session id
     * @param int $sessionId
     * @return string
     */
    public static function getDescriptionFromSessionId($sessionId)
    {
        // Init variables
        $sessionId = intval($sessionId);
        $description = '';
        // Check if session id is valid
        if ($sessionId > 0) {
            // Select query from session id
            $rows = Database::select(
                'description',
                Database::get_main_table(TABLE_MAIN_SESSION),
                array(
                    'where' => array(
                        'id = ?' => $sessionId,
                    ),
                )
            );

            // Check if select query result is not empty
            if (!empty($rows)) {
                // Get session description
                $description = $rows[0]['description'];
            }
        }

        return $description;
    }

    /**
     * Get a session list filtered by name, description or any of the given extra fields
     * @param string $term The term to search
     * @param array $extraFieldsToInclude Extra fields to include in the session data
     * @return array The list
     */
    public static function searchSession($term, $extraFieldsToInclude = array())
    {
        $sTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $sfvTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $term = Database::escape_string($term);
        $extraFieldType = ExtraField::SESSION_FIELD_TYPE;
        if (is_array($extraFieldsToInclude) && count($extraFieldsToInclude) > 0) {
            $resultData = Database::select('*', $sTable, array(
                'where' => array(
                    "name LIKE %?% " => $term,
                    " OR description LIKE %?% " => $term,
                    " OR id IN (
                    SELECT item_id
                    FROM $sfvTable v INNER JOIN $extraFieldTable e
                    ON (v.field_id = e.id)
                    WHERE value LIKE %?% AND extra_field_type = $extraFieldType
                ) " => $term,
                ),
            ));
        } else {
            $resultData = Database::select('*', $sTable, array(
                'where' => array(
                    "name LIKE %?% " => $term,
                    "OR description LIKE %?% " => $term,
                ),
            ));

            return $resultData;
        }

        foreach ($resultData as $id => &$session) {
            $session['extra'] = self::getFilteredExtraFields($id, $extraFieldsToInclude);
        }

        return $resultData;
    }

    /**
     * @param $sessionId
     * @param array $extraFieldsToInclude
     * @return array
     */
    public static function getFilteredExtraFields($sessionId, $extraFieldsToInclude = array())
    {
        $extraData = array();

        $variables = array();
        $variablePlaceHolders = array();

        foreach ($extraFieldsToInclude as $sessionExtraField) {
            $variablePlaceHolders[] = "?";
            $variables[] = Database::escape_string($sessionExtraField);
        }

        $sessionExtraField = new ExtraFieldModel('session');
        $fieldList = $sessionExtraField->get_all(array(
            "variable IN ( " . implode(", ", $variablePlaceHolders) . " ) " => $variables,
        ));

        $fields = array();

        // Index session fields
        foreach ($fieldList as $field) {
            $fields[$field['id']] = $field['variable'];
        }

        // Get session field values
        $extra = new ExtraFieldValue('session');
        $sessionFieldValueList = $extra->get_all(
            array(
                "field_id IN ( " . implode(", ", $variablePlaceHolders) . " )" => array_keys($fields),
            )
        );

        foreach ($sessionFieldValueList as $sessionFieldValue) {
            // Match session field values to session
            if ($sessionFieldValue['item_id'] != $sessionId) {
                continue;
            }

            // Check if session field value is set in session field list
            if (!isset($fields[$sessionFieldValue['field_id']])) {
                continue;
            }

            $extrafieldVariable = $fields[$sessionFieldValue['field_id']];
            $extrafieldValue = $sessionFieldValue['value'];

            $extraData[] = array(
                'variable' => $extrafieldVariable,
                'value' => $extrafieldValue,
            );
        }

        return $extraData;
    }

    /**
     * @param int $sessionId
     *
     * @return bool
     */
    public static function isValidId($sessionId)
    {
        $sessionId = intval($sessionId);
        if ($sessionId > 0) {
            $rows = Database::select(
                'id',
                Database::get_main_table(TABLE_MAIN_SESSION),
                array('where' => array('id = ?' => $sessionId))
            );
            if (!empty($rows)) {

                return true;
            }
        }

        return false;
    }

    /**
     * Get list of sessions based on users of a group for a group admin
     * @param int $userId The user id
     * @return array
     */
    public static function getSessionsFollowedForGroupAdmin($userId)
    {
        $sessionList = array();
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $sessionUserTable = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $userGroup = new UserGroup();
        $userIdList = $userGroup->getGroupUsersByUser($userId);

        if (empty($userIdList)) {
            return [];
        }

        $sql = "SELECT DISTINCT s.*
                FROM $sessionTable s
                INNER JOIN $sessionUserTable sru ON s.id = sru.id_session
                WHERE
                    (sru.id_user IN (" . implode(', ', $userIdList) . ")
                    AND sru.relation_type = 0
                )";

        if (api_is_multiple_url_enabled()) {
            $sessionAccessUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $accessUrlId = api_get_current_access_url_id();

            if ($accessUrlId != -1) {
                $sql = "SELECT DISTINCT s.*
                        FROM $sessionTable s
                        INNER JOIN $sessionUserTable sru ON s.id = sru.id_session
                        INNER JOIN $sessionAccessUrlTable srau ON s.id = srau.session_id
                        WHERE
                            srau.access_url_id = $accessUrlId
                            AND (
                                sru.id_user IN (" . implode(', ', $userIdList) . ")
                                AND sru.relation_type = 0
                            )";
            }
        }

        $result = Database::query($sql);

        while ($row = Database::fetch_assoc($result)) {
            $sessionList[] = $row;
        }

        return $sessionList;
    }

    /**
     * @param array $sessionInfo
     * @return string
     */
    public static function getSessionVisibility($sessionInfo)
    {
        switch($sessionInfo['visibility']) {
            case 1:
                return get_lang('ReadOnly');
            case 2:
               return get_lang('Visible');
            case 3:
                return api_ucfirst(get_lang('Invisible'));
        }
    }

    /**
     * Converts "start date" and "end date" to "From start date to end date" string
     * @param string $startDate
     * @param string $endDate
     * @param bool $showTime
     * @param bool $dateHuman
     *
     * @return string
     */
    private static function convertSessionDateToString($startDate, $endDate, $showTime, $dateHuman)
    {
        // api_get_local_time returns empty if date is invalid like 0000-00-00 00:00:00
        $startDateToLocal = api_get_local_time($startDate, null, null, true, $showTime, $dateHuman);
        $endDateToLocal = api_get_local_time($endDate, null, null, true, $showTime, $dateHuman);

        $result = '';
        if (!empty($startDateToLocal) && !empty($endDateToLocal)) {
            //$result = sprintf(get_lang('FromDateXToDateY'), $startDateToLocal, $endDateToLocal);
            $result = sprintf(
                get_lang('FromDateXToDateY'),
                api_format_date($startDateToLocal, DATE_TIME_FORMAT_LONG_24H),
                api_format_date($endDateToLocal, DATE_TIME_FORMAT_LONG_24H)
            );
        } else {
            if (!empty($startDateToLocal)) {
                $result = get_lang('From').' '.api_format_date($startDateToLocal, DATE_TIME_FORMAT_LONG_24H);
            }
            if (!empty($endDateToLocal)) {
                $result = get_lang('Until').' '.api_format_date($endDateToLocal, DATE_TIME_FORMAT_LONG_24H);
            }
        }
        if (empty($result)) {
            $result = get_lang('NoTimeLimits');
        }
        return $result;
    }
    /**
     * Returns a human readable string
     * @params array $sessionInfo An array with all the session dates
     * @return string
     */
    public static function parseSessionDates($sessionInfo, $showTime = false)
    {
        $displayDates = self::convertSessionDateToString(
            $sessionInfo['display_start_date'],
            $sessionInfo['display_end_date'],
            $showTime,
            true
        );
        $accessDates = self::convertSessionDateToString(
            $sessionInfo['access_start_date'],
            $sessionInfo['access_end_date'],
            $showTime,
            true
        );

        $coachDates = self::convertSessionDateToString(
            $sessionInfo['coach_access_start_date'],
            $sessionInfo['coach_access_end_date'],
            $showTime,
            true
        );

        $result = [
            'access' => $accessDates,
            'display' => $displayDates,
            'coach' => $coachDates
        ];

        return $result;
    }

    /**
     * @param FormValidator $form
     *
     * @return array
     */
    public static function setForm(FormValidator & $form, $sessionId = 0)
    {
        $categoriesList = SessionManager::get_all_session_category();
        $userInfo = api_get_user_info();

        $categoriesOptions = array(
            '0' => get_lang('None')
        );

        if ($categoriesList != false) {
            foreach ($categoriesList as $categoryItem) {
                $categoriesOptions[$categoryItem['id']] = $categoryItem['name'];
            }
        }

        // Database Table Definitions
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

        $form->addElement('text', 'name', get_lang('SessionName'), array(
            'maxlength' => 50,
        ));
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('name', get_lang('SessionNameAlreadyExists'), 'callback', 'check_session_name');

        if (!api_is_platform_admin() && api_is_teacher()) {
            $form->addElement(
                'select',
                'coach_username',
                get_lang('CoachName'),
                [api_get_user_id() => $userInfo['complete_name']],
                array(
                    'id' => 'coach_username',
                    'style' => 'width:370px;',
                )
            );
        } else {

            $sql = "SELECT COUNT(1) FROM $tbl_user WHERE status = 1";
            $rs = Database::query($sql);
            $countUsers = Database::result($rs, 0, 0);

            if (intval($countUsers) < 50) {
                $orderClause = "ORDER BY ";
                $orderClause .= api_sort_by_first_name() ? "firstname, lastname, username" : "lastname, firstname, username";

                $sql = "SELECT user_id, lastname, firstname, username
                        FROM $tbl_user
                        WHERE status = '1' ".
                        $orderClause;

                if (api_is_multiple_url_enabled()) {
                    $userRelAccessUrlTable = Database::get_main_table(
                        TABLE_MAIN_ACCESS_URL_REL_USER
                    );
                    $accessUrlId = api_get_current_access_url_id();

                    if ($accessUrlId != -1) {
                        $sql = "SELECT user.user_id, username, lastname, firstname
                        FROM $tbl_user user
                        INNER JOIN $userRelAccessUrlTable url_user
                        ON (url_user.user_id = user.user_id)
                        WHERE
                            access_url_id = $accessUrlId AND
                            status = 1 "
                            .$orderClause;
                    }
                }

                $result = Database::query($sql);
                $coachesList = Database::store_result($result);

                $coachesOptions = array();
                foreach ($coachesList as $coachItem) {
                    $coachesOptions[$coachItem['user_id']] =
                        api_get_person_name($coachItem['firstname'], $coachItem['lastname']).' ('.$coachItem['username'].')';
                }

                $form->addElement(
                    'select',
                    'coach_username',
                    get_lang('CoachName'),
                    $coachesOptions
                );
            } else {
                $form->addElement(
                    'select_ajax',
                    'coach_username',
                    get_lang('CoachName'),
                    null,
                    [
                        'url' => api_get_path(WEB_AJAX_PATH) . 'session.ajax.php?a=search_general_coach',
                        'width' => '100%',
                    ]
                );
            }
        }

        $form->addRule('coach_username', get_lang('ThisFieldIsRequired'), 'required');
        $form->addHtml('<div id="ajax_list_coachs"></div>');

        $form->addButtonAdvancedSettings('advanced_params');
        $form->addElement('html','<div id="advanced_params_options" style="display:none">');

        $form->addSelect('session_category', get_lang('SessionCategory'), $categoriesOptions, array(
            'id' => 'session_category'
        ));

        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            array(
                'ToolbarSet' => 'Minimal',
            )
        );

        $form->addElement('checkbox', 'show_description', null, get_lang('ShowDescription'));

        $visibilityGroup = array();
        $visibilityGroup[] = $form->createElement('select', 'session_visibility', null, array(
            SESSION_VISIBLE_READ_ONLY => get_lang('SessionReadOnly'),
            SESSION_VISIBLE => get_lang('SessionAccessible'),
            SESSION_INVISIBLE => api_ucfirst(get_lang('SessionNotAccessible')),
        ));
        $form->addGroup($visibilityGroup, 'visibility_group', get_lang('SessionVisibility'), null, false);

        $options = [
            0 => get_lang('ByDuration'),
            1 => get_lang('ByDates')
        ];

        $form->addSelect('access', get_lang('Access'), $options, array(
            'onchange' => 'accessSwitcher()',
            'id' => 'access'
        ));

        $form->addElement('html', '<div id="duration" style="display:none">');

        $form->addElement(
            'number',
            'duration',
            array(
                get_lang('SessionDurationTitle'),
                get_lang('SessionDurationDescription'),
            ),
            array(
                'maxlength' => 50,
            )
        );

        $form->addElement('html', '</div>');
        $form->addElement('html', '<div id="date_fields" style="display:none">');

        // Dates
        $form->addDateTimePicker(
            'access_start_date',
            array(get_lang('SessionStartDate'), get_lang('SessionStartDateComment')),
            array('id' => 'access_start_date')
        );

        $form->addDateTimePicker(
            'access_end_date',
            array(get_lang('SessionEndDate'), get_lang('SessionEndDateComment')),
            array('id' => 'access_end_date')
        );

        $form->addRule(
            array('access_start_date', 'access_end_date'),
            get_lang('StartDateMustBeBeforeTheEndDate'),
            'compare_datetime_text',
            '< allow_empty'
        );

        $form->addDateTimePicker(
            'display_start_date',
            array(
                get_lang('SessionDisplayStartDate'),
                get_lang('SessionDisplayStartDateComment'),
            ),
            array('id' => 'display_start_date')
        );
        $form->addDateTimePicker(
            'display_end_date',
            array(
                get_lang('SessionDisplayEndDate'),
                get_lang('SessionDisplayEndDateComment'),
            ),
            array('id' => 'display_end_date')
        );

        $form->addRule(
            array('display_start_date', 'display_end_date'),
            get_lang('StartDateMustBeBeforeTheEndDate'),
            'compare_datetime_text',
            '< allow_empty'
        );

        $form->addDateTimePicker(
            'coach_access_start_date',
            array(
                get_lang('SessionCoachStartDate'),
                get_lang('SessionCoachStartDateComment'),
            ),
            array('id' => 'coach_access_start_date')
        );

        $form->addDateTimePicker(
            'coach_access_end_date',
            array(
                get_lang('SessionCoachEndDate'),
                get_lang('SessionCoachEndDateComment'),
            ),
            array('id' => 'coach_access_end_date')
        );

        $form->addRule(
            array('coach_access_start_date', 'coach_access_end_date'),
            get_lang('StartDateMustBeBeforeTheEndDate'),
            'compare_datetime_text',
            '< allow_empty'
        );

        $form->addElement('html', '</div>');

        $form->addCheckBox(
            'send_subscription_notification',
            [
                get_lang('SendSubscriptionNotification'),
                get_lang('SendAnEmailWhenAUserBeingSubscribed')
            ]
        );

        // Extra fields
        $extra_field = new ExtraFieldModel('session');
        $extra = $extra_field->addElements($form, $sessionId);

        $form->addElement('html', '</div>');

        $js = $extra['jquery_ready_content'];

        return ['js' => $js];
    }

    /**
     * Gets the number of rows in the session table filtered through the given
     * array of parameters
     * @param array Array of options/filters/keys
     * @return integer The number of rows, or false on wrong param
     * @assert ('a') === false
     */
    static function get_count_admin_complete($options = array())
    {
        if (!is_array($options)) {
            return false;
        }
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $where = 'WHERE 1 = 1 ';
        $user_id = api_get_user_id();

        if (api_is_session_admin() &&
            api_get_setting('allow_session_admins_to_see_all_sessions') == 'false'
        ) {
            $where.=" WHERE s.session_admin_id = $user_id ";
        }

        if (!empty($options['where'])) {
            $options['where'] = str_replace('course_title', 'c.title', $options['where']);
            $options['where'] = str_replace("( session_active = '0' )", '1=1',  $options['where']);

            $options['where'] = str_replace(
                array("AND session_active = '1'  )", " AND (  session_active = '1'  )"),
                array(') GROUP BY s.name HAVING session_active = 1 ', " GROUP BY s.name HAVING session_active = 1 " )
                , $options['where']
            );

            $options['where'] = str_replace(
                array("AND session_active = '0'  )", " AND (  session_active = '0'  )"),
                array(') GROUP BY s.name HAVING session_active = 0 ', " GROUP BY s.name HAVING session_active = '0' "),
                $options['where']
            );

            if (!empty($options['extra'])) {
                $options['where'] = str_replace(' 1 = 1  AND', '', $options['where']);
                $options['where'] = str_replace('AND', 'OR', $options['where']);

                foreach ($options['extra'] as $extra) {
                    $options['where'] = str_replace($extra['field'], 'fv.field_id = '.$extra['id'].' AND fvo.option_value', $options['where']);
                }
            }
            $where .= ' AND '.$options['where'];
        }

        $today = api_get_utc_datetime();
        $query_rows = "SELECT count(*) as total_rows, c.title as course_title, s.name,
                        IF (
                            (s.access_start_date <= '$today' AND '$today' < s.access_end_date) OR
                            (s.access_start_date = '0000-00-00 00:00:00' AND s.access_end_date = '0000-00-00 00:00:00' ) OR
                            (s.access_start_date IS NULL AND s.access_end_date IS NULL) OR
                            (s.access_start_date <= '$today' AND ('0000-00-00 00:00:00' = s.access_end_date OR s.access_end_date IS NULL )) OR
                            ('$today' < s.access_end_date AND ('0000-00-00 00:00:00' = s.access_start_date OR s.access_start_date IS NULL) )
                        , 1, 0) as session_active
                       FROM $tbl_session s
                       LEFT JOIN  $tbl_session_category sc
                       ON s.session_category_id = sc.id
                       INNER JOIN $tbl_user u
                       ON s.id_coach = u.user_id
                       INNER JOIN $sessionCourseUserTable scu
                       ON s.id = scu.session_id
                       INNER JOIN $courseTable c
                       ON c.id = scu.c_id
                       $where ";

        if (api_is_multiple_url_enabled()) {
            $table_access_url_rel_session= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $where.= " AND ar.access_url_id = $access_url_id ";

                $query_rows = "SELECT count(*) as total_rows
                               FROM $tbl_session s
                               LEFT JOIN  $tbl_session_category sc
                               ON s.session_category_id = sc.id
                               INNER JOIN $tbl_user u
                               ON s.id_coach = u.user_id
                               INNER JOIN $table_access_url_rel_session ar
                               ON ar.session_id = s.id $where ";
            }
        }

        $result = Database::query($query_rows);
        $num = 0;
        if (Database::num_rows($result)) {
            $rows = Database::fetch_array($result);
            $num = $rows['total_rows'];
        }

        return $num;
    }

    /**
     * @param string $list_type
     * @return array
     */
    public static function getGridColumns($list_type = 'simple')
    {
        // Column config
        $operators = array('cn', 'nc');
        $date_operators = array('gt', 'ge', 'lt', 'le');

        switch ($list_type) {
            case 'simple':
                $columns = array(
                    get_lang('Name'),
                    get_lang('Category'),
                    get_lang('SessionDisplayStartDate'),
                    get_lang('SessionDisplayEndDate'),
                    //get_lang('Coach'),
                    //get_lang('Status'),
                    //get_lang('CourseTitle'),
                    get_lang('Visibility'),
                );
                $column_model = array (
                    array('name'=>'name', 'index'=>'s.name', 'width'=>'160',  'align'=>'left', 'search' => 'true', 'searchoptions' => array('sopt' => $operators)),
                    array('name'=>'category_name', 'index'=>'category_name', 'width'=>'40',  'align'=>'left', 'search' => 'true', 'searchoptions' => array('sopt' => $operators)),
                    array('name'=>'display_start_date', 'index'=>'display_start_date', 'width'=>'50',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('dataInit' => 'date_pick_today', 'sopt' => $date_operators)),
                    array('name'=>'display_end_date', 'index'=>'display_end_date', 'width'=>'50',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('dataInit' => 'date_pick_one_month', 'sopt' => $date_operators)),
                    array('name'=>'visibility', 'index'=>'visibility',      'width'=>'40',   'align'=>'left', 'search' => 'false'),
                );
                break;
            case 'complete':
                $columns = array(
                    get_lang('Name'),
                    get_lang('SessionDisplayStartDate'),
                    get_lang('SessionDisplayEndDate'),
                    get_lang('Coach'),
                    get_lang('Status'),
                    get_lang('Visibility'),
                    get_lang('CourseTitle'),
                );
                $column_model = array (
                    array('name'=>'name', 'index'=>'s.name', 'width'=>'200',  'align'=>'left', 'search' => 'true', 'searchoptions' => array('sopt' => $operators)),
                    array('name'=>'display_start_date', 'index'=>'display_start_date', 'width'=>'70',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('dataInit' => 'date_pick_today', 'sopt' => $date_operators)),
                    array('name'=>'display_end_date', 'index'=>'display_end_date', 'width'=>'70',   'align'=>'left', 'search' => 'true', 'searchoptions' => array('dataInit' => 'date_pick_one_month', 'sopt' => $date_operators)),
                    array('name'=>'coach_name', 'index'=>'coach_name',     'width'=>'70',   'align'=>'left', 'search' => 'false', 'searchoptions' => array('sopt' => $operators)),
                    array('name'=>'session_active', 'index'=>'session_active', 'width'=>'25',   'align'=>'left', 'search' => 'true', 'stype'=>'select',
                        // for the bottom bar
                        'searchoptions' => array(
                            'defaultValue'  => '1',
                            'value'         => '1:'.get_lang('Active').';0:'.get_lang('Inactive')),
                        // for the top bar
                        'editoptions' => array('value' => '" ":'.get_lang('All').';1:'.get_lang('Active').';0:'.get_lang('Inactive')),
                    ),
                    array('name'=>'visibility',     'index'=>'visibility',      'width'=>'40',   'align'=>'left', 'search' => 'false'),
                    array('name'=>'course_title',    'index'=>'course_title',   'width'=>'50',   'hidden' => 'true', 'search' => 'true', 'searchoptions' => array('searchhidden' =>'true','sopt' => $operators)),
                );
                break;
        }

        // Inject extra session fields
        $session_field = new ExtraFieldModel('session');
        $rules = $session_field->getRules($columns, $column_model);

        $column_model[] = array('name'=>'actions', 'index'=>'actions', 'width'=>'80',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false', 'search' => 'false');
        $columns[] = get_lang('Actions');

        foreach ($column_model as $col_model) {
            $simple_column_name[] = $col_model['name'];
        }

        $return_array =  array(
            'columns' => $columns,
            'column_model' => $column_model,
            'rules' => $rules,
            'simple_column_name' => $simple_column_name,
        );

        return $return_array;
    }

    /**
     * Converts all dates sent through the param array (given form) to correct dates with timezones
     * @param array The dates The same array, with times converted
     * @param boolean $applyFormat Whether apply the DATE_TIME_FORMAT_SHORT format for sessions
     * @return array The same array, with times converted
     */
    static function convert_dates_to_local($params, $applyFormat = false)
    {
        if (!is_array($params)) {
            return false;
        }
        $params['display_start_date'] = api_get_local_time($params['display_start_date'], null, null, true);
        $params['display_end_date'] = api_get_local_time($params['display_end_date'], null, null, true);

        $params['access_start_date'] = api_get_local_time($params['access_start_date'], null, null, true);
        $params['access_end_date'] = api_get_local_time($params['access_end_date'], null, null, true);

        $params['coach_access_start_date'] = isset($params['coach_access_start_date']) ? api_get_local_time($params['coach_access_start_date'], null, null, true) : null;
        $params['coach_access_end_date'] = isset($params['coach_access_end_date']) ? api_get_local_time($params['coach_access_end_date'], null, null, true) : null;

        if ($applyFormat) {
            if (isset($params['display_start_date'])) {
                $params['display_start_date'] = api_format_date($params['display_start_date'], DATE_TIME_FORMAT_SHORT);
            }

            if (isset($params['display_end_date'])) {
                $params['display_end_date'] = api_format_date($params['display_end_date'], DATE_TIME_FORMAT_SHORT);
            }

            if (isset($params['access_start_date'])) {
                $params[''] = api_format_date($params['access_start_date'], DATE_TIME_FORMAT_SHORT);
            }

            if (isset($params['access_end_date'])) {
                $params['access_end_date'] = api_format_date($params['access_end_date'], DATE_TIME_FORMAT_SHORT);
            }

            if (isset($params['coach_access_start_date'])) {
                $params['coach_access_start_date'] = api_format_date($params['coach_access_start_date'], DATE_TIME_FORMAT_SHORT);
            }

            if (isset($params['coach_access_end_date'])) {
                $params['coach_access_end_date'] = api_format_date($params['coach_access_end_date'], DATE_TIME_FORMAT_SHORT);
            }
        }

        return $params;
    }

    /**
     * Gets the admin session list callback of the session/session_list.php
     * page with all user/details in the right fomat
     * @param array
     * @result array Array of rows results
     * @asset ('a') === false
     */
    public static function get_sessions_admin_complete($options = array())
    {
        if (!is_array($options)) {
            return false;
        }

        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $tbl_session_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tbl_session_field_options = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);

        $where = 'WHERE 1 = 1 ';
        $user_id = api_get_user_id();

        if (!api_is_platform_admin()) {
            if (api_is_session_admin() &&
                api_get_setting('allow_session_admins_to_manage_all_sessions') == 'false'
            ) {
                $where.=" AND s.session_admin_id = $user_id ";
            }
        }

        $coach_name = " CONCAT(u.lastname , ' ', u.firstname) as coach_name ";
        if (api_is_western_name_order()) {
            $coach_name = " CONCAT(u.firstname, ' ', u.lastname) as coach_name ";
        }

        $today = api_get_utc_datetime();
        $inject_extra_fields = null;
        $extra_fields = array();
        $extra_fields_info = array();

        //for now only sessions
        $extra_field = new ExtraFieldModel('session');
        $double_fields = array();

        $extra_field_option = new ExtraFieldOption('session');

        if (isset($options['extra'])) {
            $extra_fields = $options['extra'];
            if (!empty($extra_fields)) {
                foreach ($extra_fields as $extra) {
                    $inject_extra_fields .= " IF (fv.field_id = {$extra['id']}, fvo.option_display_text, NULL ) as {$extra['field']} , ";
                    if (isset($extra_fields_info[$extra['id']])) {
                        $info = $extra_fields_info[$extra['id']];
                    } else {
                        $info = $extra_field->get($extra['id']);
                        $extra_fields_info[$extra['id']] = $info;
                    }

                    if ($info['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                        $double_fields[$info['id']] = $info;
                    }
                }
            }
        }

        $options_by_double = array();
        foreach ($double_fields as $double) {
            $my_options = $extra_field_option->get_field_options_by_field(
                $double['id'],
                true
            );
            $options_by_double['extra_'.$double['field_variable']] = $my_options;
        }

        //sc.name as category_name,
        $select = "
                SELECT * FROM (
                    SELECT DISTINCT
                         IF (
                            (s.access_start_date <= '$today' AND '$today' < s.access_end_date) OR
                            (s.access_start_date = '0000-00-00 00:00:00' AND s.access_end_date = '0000-00-00 00:00:00' ) OR
                            (s.access_start_date IS NULL AND s.access_end_date IS NULL) OR
                            (s.access_start_date <= '$today' AND ('0000-00-00 00:00:00' = s.access_end_date OR s.access_end_date IS NULL )) OR
                            ('$today' < s.access_end_date AND ('0000-00-00 00:00:00' = s.access_start_date OR s.access_start_date IS NULL) )
                        , 1, 0) as session_active,
                s.name,
                s.nbr_courses,
                s.nbr_users,
                s.display_start_date,
                s.display_end_date,
                $coach_name,
                access_start_date,
                access_end_date,
                s.visibility,
                u.user_id,
                $inject_extra_fields
                c.title as course_title,
                s.id ";

        if (!empty($options['where'])) {
            if (!empty($options['extra'])) {
                $options['where'] = str_replace(' 1 = 1  AND', '', $options['where']);
                $options['where'] = str_replace('AND', 'OR', $options['where']);
                foreach ($options['extra'] as $extra) {
                    $options['where'] = str_replace($extra['field'], 'fv.field_id = '.$extra['id'].' AND fvo.option_value', $options['where']);
                }
            }
            $options['where'] = str_replace('course_title', 'c.title', $options['where']);

            $options['where'] = str_replace("( session_active = '0' )", '1=1',  $options['where']);

            $options['where'] = str_replace(
                array("AND session_active = '1'  )", " AND (  session_active = '1'  )"),
                array(') GROUP BY s.name HAVING session_active = 1 ', " GROUP BY s.name HAVING session_active = 1 " )
                , $options['where']
            );

            $options['where'] = str_replace(
                array("AND session_active = '0'  )", " AND (  session_active = '0'  )"),
                array(') GROUP BY s.name HAVING session_active = 0 ', " GROUP BY s.name HAVING session_active = '0' "),
                $options['where']
            );


            $where .= ' AND '.$options['where'];
        }

        if (!empty($options['limit'])) {
            $where .= " LIMIT ".$options['limit'];
        }
        $query = "$select FROM $tbl_session s
                    LEFT JOIN $tbl_session_field_values fv
                    ON (fv.item_id = s.id)
                    LEFT JOIN $extraFieldTable f
                    ON f.id = fv.field_id
                    LEFT JOIN $tbl_session_field_options fvo
                    ON (fv.field_id = fvo.field_id)
                    LEFT JOIN $tbl_session_rel_course src
                    ON (src.session_id = s.id)
                    LEFT JOIN $tbl_course c
                    ON (src.c_id = c.id)
                    LEFT JOIN $tbl_session_category sc
                    ON (s.session_category_id = sc.id)
                    INNER JOIN $tbl_user u
                    ON (s.id_coach = u.user_id) ".
            $where;

        if (api_is_multiple_url_enabled()) {
            $table_access_url_rel_session= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $where.= " AND ar.access_url_id = $access_url_id ";
                $query = "$select
                    FROM $tbl_session s
                    LEFT JOIN $tbl_session_field_values fv ON (fv.session_id = s.id)
                    LEFT JOIN $tbl_session_field_options fvo ON (fv.field_id = fvo.field_id)
                    LEFT JOIN $tbl_session_rel_course src ON (src.id_session = s.id)
                    LEFT JOIN $tbl_course c ON (src.c_id = c.id)
                    LEFT JOIN $tbl_session_category sc ON (s.session_category_id = sc.id)
                    INNER JOIN $tbl_user u ON (s.id_coach = u.user_id)
                    INNER JOIN $table_access_url_rel_session ar ON (ar.session_id = s.id)
                    $where";
            }
        }

        $query .= ") AS session_table";

        if (!empty($options['order'])) {
            $query .= " ORDER BY ".$options['order'];
        }

        //error_log($query);
        //echo $query;

        $result = Database::query($query);
        $formatted_sessions = array();

        if (Database::num_rows($result)) {
            $sessions   = Database::store_result($result, 'ASSOC');
            foreach ($sessions as $session) {
                $session_id = $session['id'];
                $session['name'] = Display::url($session['name'], "resume_session.php?id_session=".$session['id']);
                $session['coach_name'] = Display::url($session['coach_name'], "user_information.php?user_id=".$session['user_id']);
                if ($session['session_active'] == 1) {
                    $session['session_active'] = Display::return_icon('accept.png', get_lang('Active'), array(), ICON_SIZE_SMALL);
                } else {
                    $session['session_active'] = Display::return_icon('error.png', get_lang('Inactive'), array(), ICON_SIZE_SMALL);
                }

                $session = self::convert_dates_to_local($session);

                switch ($session['visibility']) {
                    case SESSION_VISIBLE_READ_ONLY: //1
                        $session['visibility'] =  get_lang('ReadOnly');
                        break;
                    case SESSION_VISIBLE:           //2
                    case SESSION_AVAILABLE:         //4
                        $session['visibility'] =  get_lang('Visible');
                        break;
                    case SESSION_INVISIBLE:         //3
                        $session['visibility'] =  api_ucfirst(get_lang('Invisible'));
                        break;
                }

                // Cleaning double selects
                foreach ($session as $key => &$value) {
                    if (isset($options_by_double[$key]) || isset($options_by_double[$key.'_second'])) {
                        $options = explode('::', $value);
                    }
                    $original_key = $key;

                    if (strpos($key, '_second') === false) {
                    } else {
                        $key = str_replace('_second', '', $key);
                    }

                    if (isset($options_by_double[$key])) {
                        if (isset($options[0])) {
                            if (isset($options_by_double[$key][$options[0]])) {
                                if (strpos($original_key, '_second') === false) {
                                    $value = $options_by_double[$key][$options[0]]['option_display_text'];
                                } else {
                                    $value = $options_by_double[$key][$options[1]]['option_display_text'];
                                }
                            }
                        }
                    }
                }

                // Magic filter
                if (isset($formatted_sessions[$session_id])) {
                    $formatted_sessions[$session_id] = self::compareArraysToMerge($formatted_sessions[$session_id], $session);
                } else {
                    $formatted_sessions[$session_id] = $session;
                }
            }
        }

        return $formatted_sessions;
    }

    /**
     * Compare two arrays
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    static function compareArraysToMerge($array1, $array2)
    {
        if (empty($array2)) {
            return $array1;
        }
        foreach ($array1 as $key => $item) {
            if (!isset($array1[$key])) {
                //My string is empty try the other one
                if (isset($array2[$key]) && !empty($array2[$key])) {
                    $array1[$key] = $array2[$key];
                }
            }
        }
        return $array1;
    }

    /**
     * Get link to the admin page for this session
     * @param   int $id Session ID
     * @return mixed    URL to the admin page to manage the session, or false on error
     */
    public static function getAdminPath($id)
    {
        $id = intval($id);
        $session = self::fetch($id);
        if (empty($session)) {
            return false;
        }
        return api_get_path(WEB_CODE_PATH) . 'session/resume_session.php?id_session=' . $id;
    }

    /**
     * Get link to the user page for this session.
     * If a course is provided, build the link to the course
     * @param   int $id Session ID
     * @param   int $courseId Course ID (optional) in case the link has to send straight to the course
     * @return mixed    URL to the page to use the session, or false on error
     */
    public static function getPath($id, $courseId = 0)
    {
        $id = intval($id);
        $session = self::fetch($id);
        if (empty($session)) {
            return false;
        }
        if (empty($courseId)) {
            return api_get_path(WEB_CODE_PATH) . 'session/index.php?session_id=' . $id;
        } else {
            $courseInfo = api_get_course_info_by_id($courseId);
            if ($courseInfo) {
                return $courseInfo['course_public_url'].'?id_session='.$id;
            }
        }

        return false;
    }

    /**
     * Return an associative array 'id_course' => [id_session1, id_session2...]
     * where course id_course is in sessions id_session1, id_session2
     * for course where user is coach
     * i.e. coach for the course or
     * main coach for a session the course is in
     * for a session category (or woth no session category if empty)
     *
     * @param $userId
     *
     * @return array
     */
    public static function getSessionCourseForUser($userId)
    {
        // list of COURSES where user is COURSE session coach
        $listCourseCourseCoachSession = self::getCoursesForCourseSessionCoach($userId);

        // list of courses where user is MAIN session coach
        $listCourseMainCoachSession = self::getCoursesForMainSessionCoach($userId);

        // merge these 2 array
        $listResCourseSession = $listCourseCourseCoachSession;
        foreach ($listCourseMainCoachSession as $courseId2 => $listSessionId2) {
            if (isset($listResCourseSession[$courseId2])) {
                // if sessionId array exists for this course
                // same courseId, merge the list of session
                foreach ($listCourseMainCoachSession[$courseId2] as $i => $sessionId2) {
                    if (!in_array($sessionId2, $listResCourseSession[$courseId2])) {
                        $listResCourseSession[$courseId2][] = $sessionId2;
                    }
                }
            } else {
                $listResCourseSession[$courseId2] = $listSessionId2;
            }
        }

        return $listResCourseSession;
    }

    /**
     * Return an associative array 'id_course' => [id_session1, id_session2...]
     * where course id_course is in sessions id_session1, id_session2
     * @param $userId
     *
     * @return array
     */
    public static function getCoursesForCourseSessionCoach($userId)
    {
        $listResCourseSession = array();
        $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tblSessionRelCourseRelUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $sql = "SELECT session_id, c_id, c.id
                FROM $tblSessionRelCourseRelUser srcru
                LEFT JOIN $tblCourse c
                ON c.id = srcru.c_id
                WHERE
                    srcru.user_id =".intval($userId)." AND
                    srcru.status = 2";

        $res = Database::query($sql);

        while ($data = Database::fetch_assoc($res)) {
            if (api_get_session_visibility($data['session_id'])) {
                if (!isset($listResCourseSession[$data['id']])) {
                    $listResCourseSession[$data['id']] = array();
                }
                $listResCourseSession[$data['id']][] = $data['session_id'];
            }
        }

        return $listResCourseSession;
    }

    /**
     * Return an associative array 'id_course' => [id_session1, id_session2...]
     * where course id_course is in sessions id_session1, id_session2
     * @param $userId
     *
     * @return array
     */
    public static function getCoursesForMainSessionCoach($userId)
    {
        $listResCourseSession = array();
        $tblSession = Database::get_main_table(TABLE_MAIN_SESSION);

        // list of SESSION where user is session coach
        $sql = "SELECT id FROM $tblSession
                WHERE id_coach = ".intval($userId);
        $res = Database::query($sql);

        while ($data = Database::fetch_assoc($res)) {
            $sessionId = $data['id'];
            $listCoursesInSession = self::getCoursesInSession($sessionId);
            foreach ($listCoursesInSession as $i => $courseId) {
                if (api_get_session_visibility($sessionId)) {
                    if (!isset($listResCourseSession[$courseId])) {
                        $listResCourseSession[$courseId] = array();
                    }
                    $listResCourseSession[$courseId][] = $sessionId;
                }
            }
        }

        return $listResCourseSession;
    }

    /**
     * Return an array of course_id used in session $sessionId
     * @param $sessionId
     *
     * @return array
     */
    public static function getCoursesInSession($sessionId)
    {
        $listResultsCourseId = array();
        $tblSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);

        // list of course in this session
        $sql = "SELECT session_id, c.id
                FROM $tblSessionRelCourse src
                LEFT JOIN $tblCourse c
                ON c.id = src.c_id
                WHERE session_id = ".intval($sessionId);
        $res = Database::query($sql);
        while ($data = Database::fetch_assoc($res)) {
            $listResultsCourseId[] = $data['id'];
        }

        return $listResultsCourseId;
    }

    /**
     * Return an array of courses in session for user
     * and for each courses the list of session that use this course for user
     *
     * [0] => array
     *      userCatId
     *      userCatTitle
     *      courseInUserCatList
     *          [0] => array
     *              courseId
     *              title
     *              courseCode
     *              sessionCatList
     *                  [0] => array
     *                      catSessionId
     *                      catSessionName
     *                      sessionList
     *                          [0] => array
     *                              sessionId
     *                              sessionName
     *
     * @param $userId
     *
     * @return array
     *
     */
    public static function getNamedSessionCourseForCoach($userId)
    {
        $listResults = array();
        $listCourseSession = self::getSessionCourseForUser($userId);
        foreach ($listCourseSession as $courseId => $listSessionId) {
            // Course info
            $courseInfo = api_get_course_info_by_id($courseId);
            $listOneCourse = array();
            $listOneCourse['courseId'] = $courseId;
            $listOneCourse['title'] = $courseInfo['title'];
            //$listOneCourse['courseCode'] = $courseInfo['code'];
            $listOneCourse['course'] = $courseInfo;
            $listOneCourse['sessionCatList'] = array();
            $listCat = array();
            foreach ($listSessionId as $i => $sessionId) {
                // here we got all session for this course
                // lets check there session categories
                $sessionInfo = SessionManager::fetch($sessionId);
                $catId = $sessionInfo['session_category_id'];
                if (!isset($listCat[$catId])) {
                    $listCatInfo = self::get_session_category($catId);
                    $listCat[$catId] = array();
                    $listCat[$catId]['catSessionId'] = $catId;
                    $listCat[$catId]['catSessionName'] = $listCatInfo['name'];
                    $listCat[$catId]['sessionList'] = array();
                }
                $listSessionInfo = SessionManager::fetch($sessionId);
                $listSessionIdName = array(
                    "sessionId" => $sessionId,
                    "sessionName" => $listSessionInfo['name'],
                );
                $listCat[$catId]['sessionList'][] = $listSessionIdName;
            }
            // sort $listCat by catSessionName
            usort($listCat, 'self::compareBySessionName');
            // in each catSession sort sessionList by sessionName
            foreach($listCat as $i => $listCatSessionInfo) {
                $listSessionList = $listCatSessionInfo['sessionList'];
                usort($listSessionList, 'self::compareCatSessionInfo');
                $listCat[$i]['sessionList'] = $listSessionList;
            }

            $listOneCourse['sessionCatList'] = $listCat;

            // user course category
            list($userCatId, $userCatTitle) = CourseManager::getUserCourseCategoryForCourse(
                $userId,
                $courseId
            );

            $userCatId = intval($userCatId);
            $listResults[$userCatId]['courseInUserCategoryId'] =  $userCatId;
            $listResults[$userCatId]['courseInUserCategoryTitle'] =  $userCatTitle;
            $listResults[$userCatId]['courseInUserCatList'][] = $listOneCourse;
        }

        // sort by user course cat
        uasort($listResults, 'self::compareByUserCourseCat');

        // sort by course title
        foreach ($listResults as $userCourseCatId => $tabCoursesInCat) {
            $courseInUserCatList = $tabCoursesInCat['courseInUserCatList'];
            uasort($courseInUserCatList, 'self::compareByCourse');
            $listResults[$userCourseCatId]['courseInUserCatList'] = $courseInUserCatList;
        }

        return $listResults;
    }

    /**
     * @param array $listA
     * @param array $listB
     * @return int
     */
    private static function compareCatSessionInfo($listA, $listB)
    {
        if ($listA['sessionName'] == $listB['sessionName']) {
            return 0;
        } else if($listA['sessionName'] > $listB['sessionName']) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * @param array $listA
     * @param array $listB
     * @return int
     */
    private static function compareBySessionName($listA, $listB)
    {
        if ($listB['catSessionName'] == '') {
            return -1;
        } else if ($listA['catSessionName'] == '') {
            return 1;
        } else if ($listA['catSessionName'] == $listB['catSessionName']) {
            return 0;
        } else if($listA['catSessionName'] > $listB['catSessionName']) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * @param array $listA
     * @param array $listB
     * @return int
     */
    private static function compareByUserCourseCat($listA, $listB)
    {
        if ($listA['courseInUserCategoryTitle'] == $listB['courseInUserCategoryTitle']) {
            return 0;
        } else if($listA['courseInUserCategoryTitle'] > $listB['courseInUserCategoryTitle']) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * @param array $listA
     * @param array $listB
     * @return int
     */
    private static function compareByCourse($listA, $listB)
    {
        if ($listA['title'] == $listB['title']) {
            return 0;
        } else if($listA['title'] > $listB['title']) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Return HTML code for displaying session_course_for_coach
     * @param $userId
     * @return string
     */
    public static function getHtmlNamedSessionCourseForCoach($userId)
    {
        $htmlRes = '';

        $listInfo = self::getNamedSessionCourseForCoach($userId);
        foreach ($listInfo as $i => $listCoursesInfo) {
            $courseInfo = $listCoursesInfo['course'];
            $courseCode = $listCoursesInfo['course']['code'];

            $listParamsCourse = array();
            $listParamsCourse['icon'] = '<div style="float:left">
                <input style="border:none;" type="button" onclick="$(\'#course-'.$courseCode.'\').toggle(\'fast\')" value="+" /></div>'.
                Display::return_icon('blackboard.png', $courseInfo['title'], array(), ICON_SIZE_LARGE);
            $listParamsCourse['link'] = '';
            $listParamsCourse['title'] = Display::tag(
                'a',
                $courseInfo['title'],
                array('href' => $listParamsCourse['link'])
            );
            $htmlCourse = '<div class="well" style="border-color:#27587D">'.
                CourseManager::course_item_html($listParamsCourse, true);
            // for each category of session
            $htmlCatSessions = '';
            foreach ($listCoursesInfo['sessionCatList'] as $j => $listCatSessionsInfo) {
                // we got an array of session categories
                $catSessionId = $listCoursesInfo['sessionCatList'][$j]['catSessionId'];
                $catSessionName = $listCoursesInfo['sessionCatList'][$j]['catSessionName'];

                $listParamsCatSession['icon'] = Display::return_icon('folder_blue.png', $catSessionName, array(), ICON_SIZE_LARGE);
                $listParamsCatSession['link'] = '';
                $listParamsCatSession['title'] = $catSessionName;

                $marginShift = 20;
                if ($catSessionName != '') {
                    $htmlCatSessions .= '<div style="margin-left:'.$marginShift.'px;">' .
                        CourseManager::course_item_html($listParamsCatSession, true) . '</div>';
                    $marginShift = 40;
                }

                // for each sessions
                $listCatSessionSessionList = $listCoursesInfo['sessionCatList'][$j]['sessionList'];
                $htmlSession = '';
                foreach ($listCatSessionSessionList as $k => $listSessionInfo) {
                    // we got an array of session info
                    $sessionId = $listSessionInfo['sessionId'];
                    $sessionName = $listSessionInfo['sessionName'];

                    $listParamsSession['icon'] = Display::return_icon('blackboard_blue.png', $sessionName, array(), ICON_SIZE_LARGE);
                    $listParamsSession['link'] = '';
                    $linkToCourseSession = $courseInfo['course_public_url'].'?id_session='.$sessionId;
                    $listParamsSession['title'] =
                        $sessionName.'<div style="font-weight:normal; font-style:italic">
                            <a href="'.$linkToCourseSession.'">'.get_lang('GoToCourseInsideSession').'</a>
                            </div>';
                    $htmlSession .= '<div style="margin-left:'.$marginShift.'px;">'.
                        CourseManager::course_item_html($listParamsSession, true).'</div>';
                }
                $htmlCatSessions .= $htmlSession;
            }
            $htmlRes .= $htmlCourse.'<div style="display:none" id="course-'.$courseCode.'">'.$htmlCatSessions.'</div></div>';
        }



        return $htmlRes;
    }
}

<?php

/* For licensing terms, see /license.txt */

/**
 * Class SystemAnnouncementManager.
 */
class SystemAnnouncementManager
{
    public const VISIBLE_GUEST = 'visible_guest';
    public const VISIBLE_STUDENT = 'visible_student';
    public const VISIBLE_TEACHER = 'visible_teacher';
    // Requires DB change
    public const VISIBLE_DRH = 'visible_drh';
    public const VISIBLE_SESSION_ADMIN = 'visible_session_admin';
    public const VISIBLE_STUDENT_BOSS = 'visible_boss';

    /**
     * @return array
     */
    public static function getVisibilityList()
    {
        $extraRoles = self::newRolesActivated();

        $visibleToUsers = [
            self::VISIBLE_TEACHER => get_lang('Teacher'),
            self::VISIBLE_STUDENT => get_lang('Student'),
            self::VISIBLE_GUEST => get_lang('Guest'),
        ];

        if ($extraRoles) {
            $visibleToUsers[self::VISIBLE_DRH] = get_lang('DRH');
            $visibleToUsers[self::VISIBLE_SESSION_ADMIN] = get_lang('SessionAdministrator');
            $visibleToUsers[self::VISIBLE_STUDENT_BOSS] = get_lang('StudentBoss');
        }

        return $visibleToUsers;
    }

    /**
     * @param string $visibility
     *
     * @return string
     */
    public static function getVisibilityCondition($visibility)
    {
        $list = self::getVisibilityList();
        $visibilityCondition = " AND ".self::VISIBLE_GUEST." = 1 ";
        if (in_array($visibility, array_keys($list))) {
            $visibilityCondition = " AND $visibility = 1 ";
        }

        return $visibilityCondition;
    }

    /**
     * @param string $visibility
     * @param int    $id
     * @param int    $start
     * @param string $user_id
     *
     * @return string
     */
    public static function displayAllAnnouncements(
        $visibility,
        $id = -1,
        $start = 0,
        $user_id = ''
    ) {
        $user_selected_language = api_get_interface_language();
        $start = (int) $start;
        $userGroup = new UserGroup();
        $tbl_announcement_group = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS_GROUPS);
        $temp_user_groups = $userGroup->get_groups_by_user(api_get_user_id(), 0);
        $groups = [];
        foreach ($temp_user_groups as $user_group) {
            $groups = array_merge($groups, [$user_group['id']]);
            $groups = array_merge($groups, $userGroup->get_parent_groups($user_group['id']));
        }

        // Checks if tables exists to not break platform not updated
        $groups_string = '('.implode($groups, ',').')';

        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $now = api_get_utc_datetime();

        $sql = "SELECT * FROM $table
                WHERE
                    (lang = '$user_selected_language' OR lang IS NULL) AND
                    ( '$now' >= date_start AND '$now' <= date_end) ";

        $sql .= self::getVisibilityCondition($visibility);

        if (count($groups) > 0) {
            $sql .= " OR id IN (
                    SELECT announcement_id FROM $tbl_announcement_group
                    WHERE group_id in $groups_string
                    ) ";
        }

        if (api_is_multiple_url_enabled()) {
            $current_access_url_id = api_get_current_access_url_id();
            $sql .= " AND access_url_id IN ('1', '$current_access_url_id')";
        }

        if (!isset($_GET['start']) || $_GET['start'] == 0) {
            $sql .= " ORDER BY date_start DESC LIMIT ".$start.",20";
        } else {
            $sql .= " ORDER BY date_start DESC LIMIT ".($start + 1).",20";
        }
        $announcements = Database::query($sql);
        $content = '';
        if (Database::num_rows($announcements) > 0) {
            $content .= '<div class="system_announcements">';
            $content .= '<h3>'.get_lang('SystemAnnouncements').'</h3>';
            $content .= '<table align="center">';
            $content .= '<tr>';
            $content .= '<td>';
            $content .= self::display_arrow($user_id);
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '<table align="center" border="0" width="900px">';
            while ($announcement = Database::fetch_object($announcements)) {
                $display_date = api_convert_and_format_date($announcement->display_date, DATE_FORMAT_LONG);
                $content .= '<tr><td>';
                $content .= '<a name="'.$announcement->id.'"></a>
                        <div class="system_announcement">
                        <h2>'.$announcement->title.'</h2>
                        <div class="system_announcement_date">'.$display_date.'</div>
                        <br />
                        <div class="system_announcement_content">'
                            .$announcement->content.'
                        </div>
                      </div><br />';
                $content .= '</tr></td>';
            }
            $content .= '</table>';

            $content .= '<table align="center">';
            $content .= '<tr>';
            $content .= '<td>';
            $content .= self::display_arrow($user_id);
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '</div>';
        }

        return $content;
    }

    /**
     * @param int $user_id
     *
     * @return string
     */
    public static function display_arrow($user_id)
    {
        $start = (int) $_GET['start'];
        $nb_announcement = self::count_nb_announcement($start, $user_id);
        $next = ((int) $_GET['start'] + 19);
        $prev = ((int) $_GET['start'] - 19);
        $content = '';
        if (!isset($_GET['start']) || $_GET['start'] == 0) {
            if ($nb_announcement > 20) {
                $content .= '<a href="news_list.php?start='.$next.'">'.get_lang('NextBis').' >> </a>';
            }
        } else {
            echo '<a href="news_list.php?start='.$prev.'"> << '.get_lang('Prev').'</a>';
            if ($nb_announcement > 20) {
                $content .= '<a href="news_list.php?start='.$next.'">'.get_lang('NextBis').' >> </a>';
            }
        }

        return $content;
    }

    /**
     * @param int    $start
     * @param string $user_id
     *
     * @return int
     */
    public static function count_nb_announcement($start = 0, $user_id = '')
    {
        $start = (int) $start;
        $user_selected_language = api_get_interface_language();
        $db_table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $sql = 'SELECT id FROM '.$db_table.'
                WHERE (lang="'.$user_selected_language.'" OR lang IS NULL) ';

        $visibility = self::getCurrentUserVisibility();
        $sql .= self::getVisibilityCondition($visibility);

        $current_access_url_id = 1;
        if (api_is_multiple_url_enabled()) {
            $current_access_url_id = api_get_current_access_url_id();
        }
        $sql .= " AND access_url_id = '$current_access_url_id' ";
        $sql .= 'LIMIT '.$start.', 21';
        $announcements = Database::query($sql);
        $i = 0;
        while ($rows = Database::fetch_array($announcements)) {
            $i++;
        }

        return $i;
    }

    /**
     * Get all announcements.
     *
     * @return array An array with all available system announcements (as php
     *               objects)
     */
    public static function get_all_announcements()
    {
        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $now = api_get_utc_datetime();
        $sql = "SELECT *, IF ( '$now'  >= date_start AND '$now' <= date_end, '1', '0') AS visible
                FROM $table";

        $current_access_url_id = 1;
        if (api_is_multiple_url_enabled()) {
            $current_access_url_id = api_get_current_access_url_id();
        }
        $sql .= " WHERE access_url_id = '$current_access_url_id' ";
        $sql .= " ORDER BY date_start ASC";

        $result = Database::query($sql);
        $announcements = [];
        while ($announcement = Database::fetch_object($result)) {
            $announcements[] = $announcement;
        }

        return $announcements;
    }

    /**
     * Adds an announcement to the database.
     *
     * @param string $title           Title of the announcement
     * @param string $content         Content of the announcement
     * @param string $date_start      Start date (YYYY-MM-DD HH:II: SS)
     * @param string $date_end        End date (YYYY-MM-DD HH:II: SS)
     * @param array  $visibility
     * @param string $lang            The language for which the announvement should be shown. Leave null for all langages
     * @param int    $send_mail       Whether to send an e-mail to all users (1) or not (0)
     * @param bool   $add_to_calendar
     * @param bool   $sendEmailTest
     * @param int    $careerId
     * @param int    $promotionId
     * @param array  $groups
     *
     * @return mixed insert_id on success, false on failure
     */
    public static function add_announcement(
        $title,
        $content,
        $date_start,
        $date_end,
        $visibility,
        $lang = '',
        $send_mail = 0,
        $add_to_calendar = false,
        $sendEmailTest = false,
        $careerId = 0,
        $promotionId = 0,
        $groups = []
    ) {
        $original_content = $content;
        $a_dateS = explode(' ', $date_start);
        $a_arraySD = explode('-', $a_dateS[0]);
        $a_arraySH = explode(':', $a_dateS[1]);
        $date_start_to_compare = array_merge($a_arraySD, $a_arraySH);

        $a_dateE = explode(' ', $date_end);
        $a_arrayED = explode('-', $a_dateE[0]);
        $a_arrayEH = explode(':', $a_dateE[1]);
        $date_end_to_compare = array_merge($a_arrayED, $a_arrayEH);

        $db_table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);

        if (!checkdate($date_start_to_compare[1], $date_start_to_compare[2], $date_start_to_compare[0])) {
            Display::addFlash(
                Display::return_message(get_lang('InvalidStartDate'), 'warning')
            );

            return false;
        }

        if (($date_end_to_compare[1] ||
            $date_end_to_compare[2] ||
            $date_end_to_compare[0]) &&
            !checkdate($date_end_to_compare[1], $date_end_to_compare[2], $date_end_to_compare[0])
        ) {
            Display::addFlash(
                Display::return_message(get_lang('InvalidEndDate'), 'warning')
            );

            return false;
        }

        if (strlen(trim($title)) == 0) {
            Display::addFlash(
                Display::return_message(get_lang('InvalidTitle'), 'warning')
            );

            return false;
        }

        $start = api_get_utc_datetime($date_start);
        $end = api_get_utc_datetime($date_end);

        //Fixing urls that are sent by email
        //$content = str_replace('src=\"/home/', 'src=\"'.api_get_path(WEB_PATH).'home/', $content);
        //$content = str_replace('file=/home/', 'file='.api_get_path(WEB_PATH).'home/', $content);
        $content = str_replace(
            'src=\"'.api_get_path(REL_HOME_PATH),
            'src=\"'.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );
        $content = str_replace(
            'file='.api_get_path(REL_HOME_PATH),
            'file='.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );
        $lang = is_null($lang) ? '' : $lang;

        $current_access_url_id = 1;
        if (api_is_multiple_url_enabled()) {
            $current_access_url_id = api_get_current_access_url_id();
        }

        $params = [
            'title' => $title,
            'content' => $content,
            'date_start' => $start,
            'date_end' => $end,
            'lang' => $lang,
            'access_url_id' => $current_access_url_id,
        ];

        if (api_get_configuration_value('allow_careers_in_global_announcements') && !empty($careerId)) {
            $params['career_id'] = (int) $careerId;
            $params['promotion_id'] = (int) $promotionId;
        }

        foreach ($visibility as $key => $value) {
            $params[$key] = $value;
        }

        $resultId = Database::insert($db_table, $params);

        if ($resultId) {
            if ($sendEmailTest) {
                self::send_system_announcement_by_email(
                    $resultId,
                    $visibility,
                    true
                );
            } else {
                if ($send_mail == 1) {
                    self::send_system_announcement_by_email(
                        $resultId,
                        $visibility,
                        false,
                        $groups
                    );
                }
            }

            if ($add_to_calendar) {
                $agenda = new Agenda('admin');
                $agenda->addEvent(
                    $date_start,
                    $date_end,
                    false,
                    $title,
                    $original_content,
                    [],
                    false,
                    null,
                    [],
                    [],
                    null,
                    '',
                    [],
                    false,
                    [],
                    $params['career_id'] ?? 0,
                    $params['promotion_id'] ?? 0
                );
            }

            return $resultId;
        }

        return false;
    }

    /**
     * Makes the announcement id visible only for groups in groups_array.
     *
     * @param int   $announcement_id
     * @param array $groups          array of group id
     *
     * @return bool
     */
    public static function announcement_for_groups($announcement_id, $groups)
    {
        $tbl_announcement_group = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS_GROUPS);
        $announcement_id = (int) $announcement_id;

        if (empty($announcement_id)) {
            return false;
        }

        // First delete all group associations for this announcement
        $sql = "DELETE FROM $tbl_announcement_group WHERE announcement_id= $announcement_id";
        Database::query($sql);

        if (!empty($groups)) {
            foreach ($groups as $group_id) {
                if (intval($group_id) != 0) {
                    $sql = "INSERT INTO $tbl_announcement_group SET
                            announcement_id=".$announcement_id.",
                            group_id=".intval($group_id);
                    Database::query($sql);
                }
            }
        }

        return true;
    }

    /**
     * Gets the groups of this announce.
     *
     * @param int announcement id
     *
     * @return array array of group id
     */
    public static function get_announcement_groups($announcement_id)
    {
        $tbl_announcement_group = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS_GROUPS);
        $tbl_group = Database::get_main_table(TABLE_USERGROUP);
        //first delete all group associations for this announcement
        $sql = "SELECT
                    g.id as group_id,
                    g.name as group_name
                FROM $tbl_group g , $tbl_announcement_group ag
                WHERE
                    announcement_id =".intval($announcement_id)." AND
                    ag.group_id = g.id";
        $res = Database::query($sql);

        return Database::store_result($res);
    }

    /**
     * Updates an announcement to the database.
     *
     * @param int    $id            of the announcement
     * @param string $title         title of the announcement
     * @param string $content       content of the announcement
     * @param array  $date_start    start date (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
     * @param array  $date_end      end date of (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
     * @param array  $visibility
     * @param array  $lang
     * @param int    $send_mail
     * @param bool   $sendEmailTest
     * @param int    $careerId
     * @param int    $promotionId
     * @param array  $groups
     *
     * @return bool True on success, false on failure
     */
    public static function update_announcement(
        $id,
        $title,
        $content,
        $date_start,
        $date_end,
        $visibility,
        $lang = null,
        $send_mail = 0,
        $sendEmailTest = false,
        $careerId = 0,
        $promotionId = 0,
        $groups = []
    ) {
        $em = Database::getManager();
        $announcement = $em->find('ChamiloCoreBundle:SysAnnouncement', $id);
        if (!$announcement) {
            return false;
        }

        $a_dateS = explode(' ', $date_start);
        $a_arraySD = explode('-', $a_dateS[0]);
        $a_arraySH = explode(':', $a_dateS[1]);
        $date_start_to_compare = array_merge($a_arraySD, $a_arraySH);

        $a_dateE = explode(' ', $date_end);
        $a_arrayED = explode('-', $a_dateE[0]);
        $a_arrayEH = explode(':', $a_dateE[1]);
        $date_end_to_compare = array_merge($a_arrayED, $a_arrayEH);

        $lang = is_null($lang) ? '' : $lang;

        if (!checkdate($date_start_to_compare[1], $date_start_to_compare[2], $date_start_to_compare[0])) {
            echo Display::return_message(get_lang('InvalidStartDate'));

            return false;
        }

        if (($date_end_to_compare[1] ||
            $date_end_to_compare[2] ||
            $date_end_to_compare[0]) &&
            !checkdate($date_end_to_compare[1], $date_end_to_compare[2], $date_end_to_compare[0])
        ) {
            echo Display::return_message(get_lang('InvalidEndDate'));

            return false;
        }

        if (strlen(trim($title)) == 0) {
            echo Display::return_message(get_lang('InvalidTitle'));

            return false;
        }

        $start = api_get_utc_datetime($date_start);
        $end = api_get_utc_datetime($date_end);

        //Fixing urls that are sent by email
        //$content = str_replace('src=\"/home/', 'src=\"'.api_get_path(WEB_PATH).'home/', $content);
        //$content = str_replace('file=/home/', 'file='.api_get_path(WEB_PATH).'home/', $content);
        $content = str_replace(
            'src=\"'.api_get_path(REL_HOME_PATH),
            'src=\"'.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );
        $content = str_replace(
            'file='.api_get_path(REL_HOME_PATH),
            'file='.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );

        $dateStart = new DateTime($start, new DateTimeZone('UTC'));
        $dateEnd = new DateTime($end, new DateTimeZone('UTC'));

        $announcement
            ->setLang($lang)
            ->setTitle($title)
            ->setContent($content)
            ->setDateStart($dateStart)
            ->setDateEnd($dateEnd)
            //->setVisibleTeacher($visible_teacher)
            //->setVisibleStudent($visible_student)
            //->setVisibleGuest($visible_guest)
            ->setAccessUrlId(api_get_current_access_url_id());

        $em->persist($announcement);
        $em->flush();

        // Update visibility
        $list = self::getVisibilityList();
        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);

        if (api_get_configuration_value('allow_careers_in_global_announcements') && !empty($careerId)) {
            $params = [];
            $params['career_id'] = (int) $careerId;
            $params['promotion_id'] = (int) $promotionId;
            Database::update(
                $table,
                $params,
                ['id = ? ' => $id]
            );
        }

        foreach ($list as $key => $title) {
            $value = isset($visibility[$key]) && $visibility[$key] ? 1 : 0;
            $sql = "UPDATE $table SET $key = '$value' WHERE id = $id";
            Database::query($sql);
        }

        if ($sendEmailTest) {
            self::send_system_announcement_by_email(
                $id,
                $visibility,
                true
            );
        } else {
            if ($send_mail == 1) {
                self::send_system_announcement_by_email(
                    $id,
                    $visibility,
                    false,
                    $groups
                );
            }
        }

        return true;
    }

    /**
     * Deletes an announcement.
     *
     * @param int $id The identifier of the announcement that should be
     *
     * @return bool True on success, false on failure
     */
    public static function delete_announcement($id)
    {
        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $id = (int) $id;
        $sql = "DELETE FROM $table WHERE id =".$id;
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        }

        return true;
    }

    /**
     * Gets an announcement.
     *
     * @param int $id The identifier of the announcement that should be
     *
     * @return object Object of class StdClass or the required class, containing the query result row
     */
    public static function get_announcement($id)
    {
        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $id = (int) $id;
        $sql = "SELECT * FROM ".$table." WHERE id = ".$id;
        $announcement = Database::fetch_object(Database::query($sql));

        return $announcement;
    }

    /**
     * Change the visibility of an announcement.
     *
     * @param int  $id
     * @param int  $user    For who should the visibility be changed
     * @param bool $visible
     *
     * @return bool True on success, false on failure
     */
    public static function set_visibility($id, $user, $visible)
    {
        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $id = (int) $id;
        $list = array_keys(self::getVisibilityList());
        $user = trim($user);
        $visible = (int) $visible;
        if (!in_array($user, $list)) {
            return false;
        }

        $field = $user;
        $sql = "UPDATE $table SET ".$field." = '".$visible."'
                WHERE id='".$id."'";
        $res = Database::query($sql);

        if ($res === false) {
            return false;
        }

        return true;
    }

    /**
     * Send a system announcement by e-mail to all teachers/students depending on parameters.
     *
     * @param int   $id
     * @param array $visibility
     * @param bool  $sendEmailTest
     * @param array $groups
     *
     * @return bool True if the message was sent or there was no destination matching.
     *              False on database or e-mail sending error.
     */
    public static function send_system_announcement_by_email($id, $visibility, $sendEmailTest = false, $groups = [])
    {
        $announcement = self::get_announcement($id);

        if (empty($announcement)) {
            return false;
        }

        $title = $announcement->title;
        $content = $announcement->content;
        $language = $announcement->lang;
        $content = str_replace(['\r\n', '\n', '\r'], '', $content);
        $now = api_get_utc_datetime();
        $teacher = $visibility['visible_teacher'];
        $student = $visibility['visible_student'];
        if ($sendEmailTest) {
            MessageManager::send_message_simple(api_get_user_id(), $title, $content);

            return true;
        }

        $whereUsersInGroup = '';
        $usersId = [];
        foreach ($groups as $groupId) {
            if (0 != $groupId) {
                $tblGroupRelUser = Database::get_main_table(TABLE_USERGROUP_REL_USER);
                $sql = "SELECT user_id FROM $tblGroupRelUser WHERE usergroup_id = $groupId";
                $result = Database::query($sql);
                $data = Database::store_result($result);
                foreach ($data as $userArray) {
                    $usersId[] = $userArray['user_id'];
                }
            }
        }

        if (!empty($usersId)) {
            $usersId = implode(',', $usersId);
            $whereUsersInGroup = " AND u.user_id in ($usersId) ";
        }

        $urlJoin = '';
        $urlCondition = '';
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        if (api_is_multiple_url_enabled()) {
            $current_access_url_id = api_get_current_access_url_id();
            $url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $urlJoin = " INNER JOIN $url_rel_user uu ON uu.user_id = u.user_id ";
            $urlCondition = " AND access_url_id = '".$current_access_url_id."' ";
        }

        if ($teacher != 0 && $student == 0) {
            $sql = "SELECT DISTINCT u.user_id FROM $user_table u $urlJoin
                    WHERE status = '1' $urlCondition $whereUsersInGroup";
        }

        if ($teacher == 0 && $student != 0) {
            $sql = "SELECT DISTINCT u.user_id FROM $user_table u $urlJoin
                    WHERE status = '5' $urlCondition $whereUsersInGroup";
        }

        if ($teacher != 0 && $student != 0) {
            $sql = "SELECT DISTINCT u.user_id FROM $user_table u $urlJoin
                    WHERE 1 = 1 $urlCondition $whereUsersInGroup";
        }

        if (!isset($sql)) {
            return false;
        }

        if (!empty($language)) {
            //special condition because language was already treated for SQL insert before
            $sql .= " AND language = '".Database::escape_string($language)."' ";
        }

        // Sent to active users.
        $sql .= " AND email <>'' AND active = 1 ";

        // Expiration date
        $sql .= " AND (expiration_date = '' OR expiration_date IS NULL OR expiration_date > '$now') ";

        if ((empty($teacher) || $teacher == '0') && (empty($student) || $student == '0')) {
            return true;
        }

        $userListToFilter = [];
        // @todo check if other filters will apply for the career/promotion option.
        if (isset($announcement->career_id) && !empty($announcement->career_id)) {
            $promotion = new Promotion();
            $promotionList = $promotion->get_all_promotions_by_career_id($announcement->career_id);
            if (isset($announcement->promotion_id) && !empty($announcement->promotion_id)) {
                $promotionList = [];
                $promotionList[] = $promotion->get($announcement->promotion_id);
            }

            if (!empty($promotionList)) {
                foreach ($promotionList as $promotion) {
                    $sessionList = SessionManager::get_all_sessions_by_promotion($promotion['id']);
                    foreach ($sessionList as $session) {
                        if ($teacher) {
                            $users = SessionManager::get_users_by_session($session['id'], 2);
                            if (!empty($users)) {
                                $userListToFilter = array_merge($users, $userListToFilter);
                            }
                        }

                        if ($student) {
                            $users = SessionManager::get_users_by_session($session['id'], 0);
                            if (!empty($users)) {
                                $userListToFilter = array_merge($users, $userListToFilter);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($userListToFilter)) {
            $userListToFilter = array_column($userListToFilter, 'user_id');
            $userListToFilterToString = implode("', '", $userListToFilter);
            $sql .= " AND (u.user_id IN ('$userListToFilterToString') ) ";
        }

        $result = Database::query($sql);
        if ($result === false) {
            return false;
        }

        $message_sent = false;
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            MessageManager::send_message_simple($row['user_id'], $title, $content);
            $message_sent = true;
        }

        // Minor validation to clean up the attachment files in the announcement
        if (!empty($_FILES)) {
            $attachments = $_FILES;
            foreach ($attachments as $attachment) {
                unlink($attachment['tmp_name']);
            }
        }

        return $message_sent; //true if at least one e-mail was sent
    }

    /**
     * Returns the group announcements where the user is subscribed.
     *
     * @param $userId
     * @param $visible
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function getAnnouncementsForGroups($userId, $visible)
    {
        $userSelectedLanguage = Database::escape_string(api_get_interface_language());
        $tblSysAnnouncements = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $tblGrpAnnouncements = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS_GROUPS);
        $tblUsrGrp = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $now = api_get_utc_datetime();

        $sql = "SELECT sys_announcement.*
        FROM $tblSysAnnouncements AS sys_announcement
        INNER JOIN $tblGrpAnnouncements AS announcement_rel_group
            ON sys_announcement.id = announcement_rel_group.announcement_id
        INNER JOIN $tblUsrGrp AS usergroup_rel_user
            ON usergroup_rel_user.usergroup_id = announcement_rel_group.group_id
        WHERE
            usergroup_rel_user.user_id = $userId AND
            (sys_announcement.lang = '$userSelectedLanguage' OR sys_announcement.lang = '') AND
            ('$now' >= sys_announcement.date_start AND '$now' <= sys_announcement.date_end)";
        $sql .= self::getVisibilityCondition($visible);
        $result = Database::query($sql);
        $data = Database::store_result($result, 'ASSOC');
        Database::free_result($result);

        return $data;
    }

    public static function isVisibleAnnouncementForUser(
        int $userId,
        string $userVisibility,
        int $careerId,
        int $promotionId
    ): bool {
        $objPromotion = new Promotion();

        $promotionList = [];

        if (!empty($promotionId)) {
            $promotionList[] = $promotionId;
        } else {
            $promotionList = $objPromotion->get_all_promotions_by_career_id($careerId);

            if (!empty($promotionList)) {
                $promotionList = array_column($promotionList, 'id');
            }
        }

        foreach ($promotionList as $promotionId) {
            $sessionList = SessionManager::get_all_sessions_by_promotion($promotionId);

            foreach ($sessionList as $session) {
                $sessionId = $session['id'];
                // Check student
                if (self::VISIBLE_STUDENT == $userVisibility &&
                    SessionManager::isUserSubscribedAsStudent($sessionId, $userId)
                ) {
                    return true;
                }

                if (self::VISIBLE_TEACHER == $userVisibility
                    && SessionManager::user_is_general_coach($userId, $sessionId)
                ) {
                    return true;
                }

                // Check course coach
                $coaches = SessionManager::getCoachesBySession($sessionId);

                if (self::VISIBLE_TEACHER == $userVisibility
                    && in_array($userId, $coaches)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Displays announcements as an slideshow.
     *
     * @param string $visible see self::VISIBLE_* constants
     * @param int    $id      The identifier of the announcement to display
     *
     * @return string
     */
    public static function displayAnnouncementsSlider($visible, $id = null)
    {
        $user_selected_language = Database::escape_string(api_get_interface_language());
        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $tblGrpAnnouncements = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS_GROUPS);

        $cut_size = 500;
        $now = api_get_utc_datetime();
        //Exclude announcement to groups
        $sql = "SELECT sys_announcement.*
            FROM $table as sys_announcement
            LEFT JOIN $tblGrpAnnouncements AS announcement_rel_group
                ON sys_announcement.id = announcement_rel_group.announcement_id
            WHERE
                (sys_announcement.lang = '$user_selected_language' OR sys_announcement.lang = '') AND
                ('$now' >= sys_announcement.date_start AND '$now' <= sys_announcement.date_end) AND
                announcement_rel_group.group_id is null";

        $sql .= self::getVisibilityCondition($visible);

        if (isset($id) && !empty($id)) {
            $id = (int) $id;
            $sql .= " AND id = $id ";
        }

        if (api_is_multiple_url_enabled()) {
            $current_url_id = api_get_current_access_url_id();
            $sql .= " AND access_url_id IN ('1', '$current_url_id') ";
        }

        $checkCareers = api_get_configuration_value('allow_careers_in_global_announcements') === true;

        $userId = api_get_user_id();

        $sql .= ' ORDER BY date_start DESC';
        $result = Database::query($sql);
        $announcements = [];
        if (Database::num_rows($result) > 0) {
            while ($announcement = Database::fetch_object($result)) {
                if ($checkCareers && !empty($announcement->career_id)) {
                    $show = self::isVisibleAnnouncementForUser(
                        $userId,
                        $visible,
                        (int) $announcement->career_id,
                        (int) $announcement->promotion_id
                    );

                    if (false === $show) {
                        continue;
                    }
                }

                $announcementData = [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'readMore' => null,
                ];

                if (empty($id)) {
                    if (api_strlen(strip_tags($announcement->content)) > $cut_size) {
                        //$announcementData['content'] = cut($announcement->content, $cut_size);
                        $announcementData['readMore'] = true;
                    }
                }

                $announcements[] = $announcementData;
            }
        }

        /** Show announcement of group */
        $announcementToGroup = self::getAnnouncementsForGroups($userId, $visible);
        $totalAnnouncementToGroup = count($announcementToGroup);
        for ($i = 0; $i < $totalAnnouncementToGroup; $i++) {
            $announcement = $announcementToGroup[$i];
            $announcementData = [
                'id' => $announcement['id'],
                'title' => $announcement['title'],
                'content' => $announcement['content'],
                'readMore' => null,
            ];
            $content = $announcement['content'];
            if (api_strlen(strip_tags($content)) > $cut_size) {
                //$announcementData['content'] = cut($content, $cut_size);
                $announcementData['readMore'] = true;
            }
            $announcements[] = $announcementData;
        }

        if (count($announcements) === 0) {
            return null;
        }

        $template = new Template(null, false, false);
        $template->assign('announcements', $announcements);
        $layout = $template->get_template('announcement/slider.tpl');

        return $template->fetch($layout);
    }

    /**
     * Get the HTML code for an announcement.
     *
     * @param int $announcementId The announcement ID
     * @param int $visibility     The announcement visibility
     *
     * @return string The HTML code
     */
    public static function displayAnnouncement($announcementId, $visibility)
    {
        $selectedUserLanguage = Database::escape_string(api_get_interface_language());
        $announcementTable = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
        $now = api_get_utc_datetime();
        $announcementId = (int) $announcementId;

        $whereConditions = [
            "(lang = ? OR lang IS NULL OR lang = '') " => $selectedUserLanguage,
            "AND (? >= date_start AND ? <= date_end) " => [$now, $now],
            "AND id = ? " => $announcementId,
        ];

        $condition = self::getVisibilityCondition($visibility);
        $whereConditions[$condition] = 1;

        if (api_is_multiple_url_enabled()) {
            $whereConditions["AND access_url_id IN (1, ?) "] = api_get_current_access_url_id();
        }

        $announcement = Database::select(
            '*',
            $announcementTable,
            [
                'where' => $whereConditions,
                'order' => 'date_start',
            ],
            'first'
        );

        $template = new Template(null, false, false);
        $template->assign('announcement', $announcement);
        $layout = $template->get_template('announcement/view.tpl');

        return $template->fetch($layout);
    }

    /**
     * @return bool
     */
    public static function newRolesActivated()
    {
        /* In order to use this option you need to run this SQL changes :
         ALTER TABLE sys_announcement ADD COLUMN visible_drh INT DEFAULT 0;
         ALTER TABLE sys_announcement ADD COLUMN visible_session_admin INT DEFAULT 0;
         ALTER TABLE sys_announcement ADD COLUMN visible_boss INT DEFAULT 0;
        */
        return api_get_configuration_value('system_announce_extra_roles');
    }

    /**
     * @return string
     */
    public static function getCurrentUserVisibility()
    {
        if (api_is_anonymous()) {
            return SystemAnnouncementManager::VISIBLE_GUEST;
        }

        if (self::newRolesActivated()) {
            if (api_is_student_boss()) {
                return SystemAnnouncementManager::VISIBLE_STUDENT_BOSS;
            }

            if (api_is_session_admin()) {
                return SystemAnnouncementManager::VISIBLE_SESSION_ADMIN;
            }

            if (api_is_drh()) {
                return SystemAnnouncementManager::VISIBLE_DRH;
            }

            if (api_is_teacher()) {
                return SystemAnnouncementManager::VISIBLE_TEACHER;
            } else {
                return SystemAnnouncementManager::VISIBLE_STUDENT;
            }
        } else {
            // Default behaviour
            return api_is_teacher() ? SystemAnnouncementManager::VISIBLE_TEACHER : SystemAnnouncementManager::VISIBLE_STUDENT;
        }
    }
}

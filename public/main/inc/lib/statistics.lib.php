<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\TrackEAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Doctrine\DBAL\ParameterType;

/**
 * This class provides some functions for statistics.
 */
class Statistics
{
    /**
     * Converts a number of bytes in a formatted string.
     *
     * @param int $size
     *
     * @return string Formatted file size or empty string if no match
     */
    public static function makeSizeString(int $size): string
    {
        if ($size < pow(2, 10)) {
            return $size." bytes";
        }
        if ($size >= pow(2, 10) && $size < pow(2, 20)) {
            return round($size / pow(2, 10), 0)." KB";
        }
        if ($size >= pow(2, 20) && $size < pow(2, 30)) {
            return round($size / pow(2, 20), 1)." MB";
        }
        if ($size > pow(2, 30)) {
            return round($size / pow(2, 30), 2)." GB";
        }

        return '';
    }

    /**
     * Count courses.
     *
     * @param string|null $categoryCode Code of a course category.
     *                                  Default: count all courses.
     * @param string|null $dateFrom dateFrom
     * @param string|null $dateUntil dateUntil
     *
     * @return int Number of courses counted
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function countCourses(string $categoryCode = null, string $dateFrom = null, string $dateUntil = null): int
    {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $accessUrlRelCourseTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT COUNT(*) AS number
                    FROM $courseTable AS c, $accessUrlRelCourseTable AS u
                    WHERE u.c_id = c.id AND u.access_url_id = $urlId";
            if (isset($categoryCode)) {
                $sql .= " AND category_code = '".Database::escape_string($categoryCode)."'";
            }
        } else {
            $sql = "SELECT COUNT(*) AS number
                    FROM $courseTable AS c
                    WHERE 1 = 1";
            if (isset($categoryCode)) {
                $sql .= " WHERE c.category_code = '".Database::escape_string($categoryCode)."'";
            }
        }

        if (!empty($dateFrom)) {
            $dateFrom = api_get_utc_datetime("$dateFrom 00:00:00");
            $sql .= " AND c.creation_date >= '$dateFrom' ";
        }
        if (!empty($dateUntil)) {
            $dateUntil = api_get_utc_datetime("$dateUntil 23:59:59");
            $sql .= " AND c.creation_date <= '$dateUntil' ";
        }

        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->number;
    }

    /**
     * Count courses by visibility.
     *
     * @param array|null  $visibility visibility (0 = closed, 1 = private, 2 = open, 3 = public) all courses
     * @param string|null $dateFrom dateFrom
     * @param string|null $dateUntil dateUntil
     *
     * @return int Number of courses counted
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function countCoursesByVisibility(
        array $visibility = null,
        string $dateFrom = null,
        string $dateUntil = null
    ): int
    {
        $visibilityString = '';
        if (empty($visibility)) {
            return 0;
        } else {
            $auxArrayVisibility = [];
            if (!is_array($visibility)) {
                $visibility = [$visibility];
            }
            foreach ($visibility as $item) {
                $auxArrayVisibility[] = (int) $item;
            }
            $visibilityString = implode(',', $auxArrayVisibility);
        }
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $accessUrlRelCourseTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT COUNT(*) AS number
                    FROM $courseTable AS c, $accessUrlRelCourseTable AS u
                    WHERE u.c_id = c.id AND u.access_url_id = $urlId";
        } else {
            $sql = "SELECT COUNT(*) AS number
                    FROM $courseTable AS c
                    WHERE 1 = 1";
        }
        $sql .= " AND visibility IN ($visibilityString) ";
        if (!empty($dateFrom)) {
            $dateFrom = api_get_utc_datetime("$dateFrom 00:00:00");
            $sql .= " AND c.creation_date >= '$dateFrom' ";
        }
        if (!empty($dateUntil)) {
            $dateUntil = api_get_utc_datetime("$dateUntil 23:59:59");
            $sql .= " AND c.creation_date <= '$dateUntil' ";
        }
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->number;
    }

    /**
     * Count users.
     *
     * @param int    $status user status (COURSEMANAGER or STUDENT) if not setted it'll count all users
     * @param string $categoryCode course category code. Default: count only users without filtering category
     * @param bool   $countInvisibleCourses Count invisible courses (todo)
     * @param bool   $onlyActive Count only active users (false to only return currently active users)
     *
     * @return int Number of users counted
     * @throws Exception
     */
    public static function countUsers(
        ?int $status = null,
        ?string $categoryCode = null,
        ?bool $countInvisibleCourses = true,
        ?bool $onlyActive = false
    ): int
    {
        // Database table definitions
        $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $tblCourseCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $tblCourseRelCategory = Database::get_main_table(TABLE_MAIN_COURSE_REL_CATEGORY);

        $conditions = [];
        $conditions[] = "u.active <> " . USER_SOFT_DELETED;
        if ($onlyActive) {
            $conditions[] = "u.active = 1";
        }
        if (isset($status)) {
            $conditions[] = "u.status = " . $status;
        }

        $where = implode(' AND ', $conditions);

        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT COUNT(DISTINCT(u.id)) AS number
                FROM $user_table as u
                INNER JOIN $access_url_rel_user_table as url ON u.id = url.user_id
                WHERE $where AND url.access_url_id = $urlId";

            if (isset($categoryCode)) {
                $categoryCode = Database::escape_string($categoryCode);
                $sql = "SELECT COUNT(DISTINCT(cu.user_id)) AS number
                    FROM $course_user_table cu
                    INNER JOIN $course_table c ON c.id = cu.c_id
                    INNER JOIN $access_url_rel_user_table as url ON cu.user_id = url.user_id
                    INNER JOIN $tblCourseRelCategory crc ON crc.course_id = c.id
                    INNER JOIN $tblCourseCategory cc ON cc.id = crc.course_category_id
                    INNER JOIN $user_table u ON cu.user_id = u.id
                    WHERE $where AND url.access_url_id = $urlId AND cc.code = '$categoryCode'";
            }
        } else {
            $sql = "SELECT COUNT(DISTINCT(id)) AS number
                FROM $user_table u
                WHERE $where";

            if (isset($categoryCode)) {
                $categoryCode = Database::escape_string($categoryCode);
                $sql = "SELECT COUNT(DISTINCT(cu.user_id)) AS number
                    FROM $course_user_table cu
                    INNER JOIN $course_table c ON c.id = cu.c_id
                    INNER JOIN $tblCourseRelCategory crc ON crc.course_id = c.id
                    INNER JOIN $tblCourseCategory cc ON cc.id = crc.course_category_id
                    INNER JOIN $user_table u ON u.id = cu.user_id
                    WHERE $where AND cc.code = '$categoryCode'";
            }
        }

        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->number;
    }

    /**
     * Get courses IDs from courses with some access_date between the two given dates
     * @param string $startDate
     * @param string $endDate
     *
     * @return array
     * @throws Exception
     */
    public static function getCoursesWithActivity(string $startDate, string $endDate): array
    {
        $access_url_rel_course_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $startDate = Database::escape_string($startDate);
        $endDate = Database::escape_string($endDate);

        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT DISTINCT(t.c_id) FROM $table t , $access_url_rel_course_table a
                    WHERE
                        t.c_id = a.c_id AND
                        access_url_id = $urlId AND
                        access_date BETWEEN '$startDate' AND '$endDate'
                    ";
        } else {
            $sql = "SELECT DISTINCT(t.c_id) FROM $table t
                   access_date BETWEEN '$startDate' AND '$endDate' ";
        }

        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * Count activities from track_e_default_table.
     *
     * @return int Number of activities counted
     * @throws Exception
     */
    public static function getNumberOfActivities(mixed $courseId = 0, ?int $sessionId = 0): int
    {
        // Database table definitions
        $track_e_default = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlUtil = Container::getAccessUrlUtil();
        if (is_array($courseId)) {
            // Usually when no param is given, we get an empty array from SortableTable
            $courseId = 0;
        }

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT count(default_id) AS total_number_of_items
                    FROM $track_e_default, $table_user user, $access_url_rel_user_table url
                    WHERE user.active <> ".USER_SOFT_DELETED." AND
                        default_user_id = user.id AND
                        user.id = url.user_id AND
                        access_url_id = $urlId";
        } else {
            $sql = "SELECT count(default_id) AS total_number_of_items
                    FROM $track_e_default, $table_user user
                    WHERE user.active <> ".USER_SOFT_DELETED." AND default_user_id = user.id ";
        }

        if (!empty($courseId)) {
            $courseId = (int) $courseId;
            $sql .= " AND c_id = $courseId";
            $sql .= api_get_session_condition($sessionId);
        }

        if (isset($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (
                        user.username LIKE '%".$keyword."%' OR
                        default_event_type LIKE '%".$keyword."%' OR
                        default_value_type LIKE '%".$keyword."%' OR
                        default_value LIKE '%".$keyword."%') ";
        }
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * Get activities data to display.
     *
     * @param int    $from
     * @param int    $numberOfItems
     * @param int    $column
     * @param string $direction
     * @param ?int   $courseId
     * @param ?int   $sessionId
     *
     * @return array
     * @throws Exception
     */
    public static function getActivitiesData(
        int $from,
        int $numberOfItems,
        int $column,
        string $direction,
        mixed $courseId = 0,
        ?int $sessionId = 0
    ): array
    {
        $track_e_default = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $direction = strtoupper($direction);
        if (is_array($courseId)) {
            // Usually when no param is given, we get an empty array from SortableTable
            $courseId = 0;
        }

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'DESC';
        }

        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT
                        default_event_type  as col0,
                        default_value_type    as col1,
                        default_value        as col2,
                        c_id         as col3,
                        session_id as col4,
                        user.username         as col5,
                        user.id         as col6,
                        default_date         as col7
                    FROM $track_e_default as track_default,
                    $table_user as user,
                    $access_url_rel_user_table as url
                    WHERE
                        user.active <> -1 AND
                        track_default.default_user_id = user.id AND
                        url.user_id = user.id AND
                        access_url_id= $urlId";
        } else {
            $sql = "SELECT
                       default_event_type  as col0,
                       default_value_type    as col1,
                       default_value        as col2,
                       c_id         as col3,
                       session_id as col4,
                       user.username         as col5,
                       user.id         as col6,
                       default_date         as col7
                   FROM $track_e_default track_default, $table_user user
                   WHERE user.active <> ".USER_SOFT_DELETED." AND track_default.default_user_id = user.id ";
        }

        if (!empty($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (user.username LIKE '%".$keyword."%' OR
                        default_event_type LIKE '%".$keyword."%' OR
                        default_value_type LIKE '%".$keyword."%' OR
                        default_value LIKE '%".$keyword."%') ";
        }

        if (!empty($courseId)) {
            $courseId = (int) $courseId;
            $sql .= " AND c_id = $courseId";
            $sql .= api_get_session_condition($sessionId);
        }

        if (!empty($column)) {
            $sql .= " ORDER BY col$column $direction";
        } else {
            $sql .= " ORDER BY col7 DESC ";
        }
        $sql .= " LIMIT $from, $numberOfItems ";

        $res = Database::query($sql);
        $activities = [];
        while ($row = Database::fetch_row($res)) {
            if (false === strpos($row[1], '_object') &&
                false === strpos($row[1], '_array')
            ) {
                $row[2] = $row[2];
            } else {
                if (!empty($row[2])) {
                    $originalData = str_replace('\\', '', $row[2]);
                    $row[2] = UnserializeApi::unserialize('not_allowed_classes', $originalData);
                    if (is_array($row[2]) && !empty($row[2])) {
                        $row[2] = implode_with_key(', ', $row[2]);
                    } else {
                        $row[2] = $originalData;
                    }
                }
            }

            if (!empty($row['default_date'])) {
                $row['default_date'] = api_get_local_time($row['default_date']);
            } else {
                $row['default_date'] = '-';
            }

            if (!empty($row[7])) {
                $row[7] = api_get_local_time($row[7]);
            } else {
                $row[7] = '-';
            }

            if (!empty($row[5])) {
                // Course
                if (!empty($row[3])) {
                    $row[3] = Display::url(
                        $row[3],
                        api_get_path(WEB_CODE_PATH).'admin/course_edit.php?id='.$row[3]
                    );
                } else {
                    $row[3] = '-';
                }

                // session
                if (!empty($row[4])) {
                    $row[4] = Display::url(
                        $row[4],
                        api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$row[4]
                    );
                } else {
                    $row[4] = '-';
                }

                // User id.
                $row[5] = Display::url(
                    $row[5],
                    api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&user_id='.$row[6],
                    ['class' => 'ajax']
                );

                $row[6] = Tracking::get_ip_from_user_event(
                    $row[6],
                    $row[7],
                    true
                );
                if (empty($row[6])) {
                    $row[6] = get_lang('Unknown');
                }
            }
            $activities[] = $row;
        }

        return $activities;
    }

    /**
     * Show statistics.
     *
     * @param string $title      The title
     * @param array  $stats
     * @param ?bool   $showTotal
     * @param ?bool   $isFileSize
     *
     * @return string HTML table
     */
    public static function printStats(
        string $title,
        array $stats,
        ?bool $showTotal = true,
        ?bool $isFileSize = false,
        ?bool $barRelativeToMax = false
    ): string {
        static $cssAdded = false;

        $total = 0.0;
        $max = 0.0;

        foreach ($stats as $subtitle => $number) {
            $number = (float) $number;
            $total += $number;
            if ($number > $max) {
                $max = $number;
            }
        }

        $colspan = $showTotal ? 4 : 3;

        $css = '';
        if (!$cssAdded) {
            $cssAdded = true;
            $css = '<style>
            .ch-stats-table{ width:100%; }
            .ch-statbar-wrap{ display:flex; align-items:center; gap:10px; }
            .ch-statbar{
                flex: 1 1 auto;
                height: 10px;
                background: #e9eef5;
                border-radius: 999px;
                overflow: hidden;
                min-width: 140px;
            }
            .ch-statbar__fill{
                height: 100%;
                width: 0%;
                background: #3b82f6;
                border-radius: 999px;
            }
            .ch-statbar__label{
                font-size: 12px;
                color: #5f6b7a;
                white-space: nowrap;
                min-width: 48px;
                text-align: right;
            }
            .ch-stats-cols th{ vertical-align: middle; }
        </style>';
        }

        $content = $css;
        $content .= '<table class="table table-hover table-striped data_table stats_table ch-stats-table" cellspacing="0" cellpadding="3">
        <thead>
            <tr><th colspan="'.$colspan.'">'.$title.'</th></tr>
            <tr class="ch-stats-cols">
                <th>'.get_lang('Name').'</th>
                <th>'.get_lang('Distribution').'</th>
                <th class="text-end">'.get_lang('Count').'</th>';

        if ($showTotal) {
            $content .= '<th class="text-end">'.get_lang('Percentage').'</th>';
        }

        $content .= '</tr>
        </thead>
        <tbody>';

        $i = 0;
        foreach ($stats as $subtitle => $number) {
            $number = (float) $number;

            $numberLabel = !$isFileSize
                ? number_format($number, 0, ',', '.')
                : self::makeSizeString((int) $number);

            $percentageRaw = ($total > 0) ? (100 * $number / $total) : 0.0;
            $percentageDisplay = ($total > 0) ? number_format($percentageRaw, 1, ',', '.') : '0';

            $barPercent = $barRelativeToMax
                ? (($max > 0) ? (100 * $number / $max) : 0.0)
                : $percentageRaw;

            $barPercent = max(0.0, min(100.0, $barPercent));
            $barHtml = '
            <div class="ch-statbar-wrap" title="'.$percentageDisplay.'%">
                <div class="ch-statbar">
                    <div class="ch-statbar__fill" style="width: '.$barPercent.'%"></div>
                </div>
                <div class="ch-statbar__label">'.$percentageDisplay.'%</div>
            </div>
        ';

            $content .= '<tr class="row_'.(0 == $i % 2 ? 'odd' : 'even').'">
            <td style="vertical-align:top;">'.$subtitle.'</td>
            <td style="vertical-align:middle;">'.$barHtml.'</td>
            <td class="text-end" style="vertical-align:top;">'.$numberLabel.'</td>';

            if ($showTotal) {
                $content .= '<td class="text-end" style="vertical-align:top;">'.$percentageDisplay.'%</td>';
            }

            $content .= '</tr>';
            $i++;
        }

        $content .= '</tbody></table>';

        return $content;
    }

    /**
     * Show some stats about the number of logins.
     *
     * @param string $type month, hour or day
     * @return string HTML block
     * @throws Exception
     */
    public static function printLoginStats(string $type): string
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $table_url = null;
        $where_url = null;
        $now = api_get_utc_datetime();
        $where_url_last = ' WHERE login_date > DATE_SUB("'.$now.'",INTERVAL 1 %s)';
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $table_url = ", $access_url_rel_user_table";
            $where_url = " WHERE login_user_id=user_id AND access_url_id = $urlId";
            $where_url_last = ' AND login_date > DATE_SUB("'.$now.'",INTERVAL 1 %s)';
        }

        $period = get_lang('Month');
        $periodCollection = api_get_months_long();
        $sql = "SELECT
                DATE_FORMAT( login_date, '%Y-%m' ) AS stat_date ,
                count( login_id ) AS number_of_logins
                FROM $table $table_url $where_url
                GROUP BY stat_date
                ORDER BY login_date DESC";
        $sql_last_x = null;

        switch ($type) {
            case 'hour':
                $period = get_lang('Hour');
                $sql = "SELECT
                          DATE_FORMAT( login_date, '%H') AS stat_date,
                          count( login_id ) AS number_of_logins
                        FROM $table $table_url $where_url
                        GROUP BY stat_date
                        ORDER BY stat_date ";
                $sql_last_x = "SELECT
                                DATE_FORMAT( login_date, '%H' ) AS stat_date,
                                count( login_id ) AS number_of_logins
                               FROM $table $table_url $where_url ".sprintf($where_url_last, 'DAY')."
                               GROUP BY stat_date
                               ORDER BY stat_date ";
                break;
            case 'day':
                $periodCollection = api_get_week_days_long();
                $period = get_lang('Day');
                $sql = "SELECT DATE_FORMAT( login_date, '%w' ) AS stat_date ,
                        count( login_id ) AS number_of_logins
                        FROM  $table $table_url $where_url
                        GROUP BY stat_date
                        ORDER BY DATE_FORMAT( login_date, '%w' ) ";
                $sql_last_x = "SELECT
                                DATE_FORMAT( login_date, '%w' ) AS stat_date,
                                count( login_id ) AS number_of_logins
                               FROM $table $table_url $where_url ".sprintf($where_url_last, 'WEEK')."
                               GROUP BY stat_date
                               ORDER BY DATE_FORMAT( login_date, '%w' ) ";
                break;
        }

        $content = '';
        if ($sql_last_x) {
            $res_last_x = Database::query($sql_last_x);
            $result_last_x = [];
            while ($obj = Database::fetch_object($res_last_x)) {
                $stat_date = ('day' === $type) ? $periodCollection[$obj->stat_date] : $obj->stat_date;
                $result_last_x[$stat_date] = $obj->number_of_logins;
            }
            $content .= self::printStats(get_lang('Last logins').' ('.$period.')', $result_last_x, false);
            flush(); //flush web request at this point to see something already while the full data set is loading
            $content .= '<br />';
        }
        $res = Database::query($sql);
        $result = [];
        while ($obj = Database::fetch_object($res)) {
            $stat_date = $obj->stat_date;
            switch ($type) {
                case 'month':
                    $stat_date = explode('-', $stat_date);
                    $stat_date[1] = $periodCollection[(int) $stat_date[1] - 1];
                    $stat_date = implode(' ', $stat_date);
                    break;
                case 'day':
                    $stat_date = $periodCollection[$stat_date];
                    break;
            }
            $result[$stat_date] = $obj->number_of_logins;
        }
        $content .= self::printStats(get_lang('All logins').' ('.$period.')', $result, false);

        return $content;
    }

    /**
     * Print the number of recent logins.
     *
     * @param ?bool  $distinct        whether to only give distinct users stats, or *all* logins
     * @param ?int   $sessionDuration Number of minutes a session must have lasted at a minimum to be taken into account
     * @param ?array $periods         List of number of days we want to query (default: [1, 7, 31] for last 1 day, last 7 days, last 31 days)
     *
     * @throws Exception
     *
     * @return string HTML table
     */
    public static function printRecentLoginStats(?bool $distinct = false, ?int $sessionDuration = 0, ?array $periods = []): string
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $table_url = '';
        $where_url = '';
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $table_url = ", $access_url_rel_user_table";
            $where_url = " AND login_user_id=user_id AND access_url_id = $urlId";
        }

        $now = api_get_utc_datetime();
        $field = 'login_id';
        if ($distinct) {
            $field = 'DISTINCT(login_user_id)';
        }

        if (empty($periods)) {
            $periods = [1, 7, 31];
        }
        $sqlList = [];

        $sessionDuration = (int) $sessionDuration * 60; // convert from minutes to seconds
        foreach ($periods as $day) {
            $date = new DateTime($now);
            $startDate = $date->format('Y-m-d').' 00:00:00';
            $endDate = $date->format('Y-m-d').' 23:59:59';

            if ($day > 1) {
                $startDate = $date->sub(new DateInterval('P'.$day.'D'));
                $startDate = $startDate->format('Y-m-d').' 00:00:00';
            }

            $localDate = api_get_local_time($startDate, null, null, false, false);
            $localEndDate = api_get_local_time($endDate, null, null, false, false);

            $label = sprintf(get_lang('Last %s days'), $day);
            if (1 == $day) {
                $label = get_lang('Today');
            }
            $label .= " <span class=\"muted right\" style=\"float: right; margin-right: 5px;\">[$localDate - $localEndDate]</span>";
            $sql = "SELECT count($field) AS number
                    FROM $table $table_url
                    WHERE ";
            if (0 == $sessionDuration) {
                $sql .= " logout_date != login_date AND ";
            } else {
                $sql .= " UNIX_TIMESTAMP(logout_date) - UNIX_TIMESTAMP(login_date) > $sessionDuration AND ";
            }
            $sql .= "login_date BETWEEN '$startDate' AND '$endDate'
                        $where_url";
            $sqlList[$label] = $sql;
        }

        $sql = "SELECT count($field) AS number
                FROM $table $table_url ";
        if (0 == $sessionDuration) {
            $sql .= " WHERE logout_date != login_date $where_url";
        } else {
            $sql .= " WHERE UNIX_TIMESTAMP(logout_date) - UNIX_TIMESTAMP(login_date) > $sessionDuration $where_url";
        }
        $sqlList[get_lang('Total')] = $sql;
        $totalLogin = [];
        foreach ($sqlList as $label => $query) {
            $res = Database::query($query);
            $obj = Database::fetch_object($res);
            $totalLogin[$label] = $obj->number;
        }

        if ($distinct) {
            $content = self::printStats(get_lang('Distinct users logins'), $totalLogin, false, false, false);
        } else {
            $content = self::printStats(get_lang('Logins'), $totalLogin, false, false, false);
        }

        return $content;
    }

    /**
     * Get the number of recent logins.
     *
     * @param ?bool $distinct            Whether to only give distinct users stats, or *all* logins
     * @param ?int  $sessionDuration     Number of minutes a session must have lasted at a minimum to be taken into account
     * @param ?bool $completeMissingDays Whether to fill the daily gaps (if any) when getting a list of logins
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getRecentLoginStats(?bool $distinct = false, ?int $sessionDuration = 0, ?bool $completeMissingDays = true): array
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $table_url = '';
        $where_url = '';
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $table_url = ", $access_url_rel_user_table";
            $where_url = " AND login_user_id=user_id AND access_url_id = $urlId";
        }

        $now = api_get_utc_datetime();
        $date = new DateTime($now);
        $date->sub(new DateInterval('P31D'));
        $newDate = $date->format('Y-m-d h:i:s');
        $totalLogin = self::buildDatesArray($newDate, $now, true);

        $field = 'login_id';
        if ($distinct) {
            $field = 'DISTINCT(login_user_id)';
        }
        $sessionDuration = (int) $sessionDuration * 60; //Convert from minutes to seconds

        $sql = "SELECT count($field) AS number, date(login_date) as login_date
                FROM $table $table_url
                WHERE ";
        if (0 == $sessionDuration) {
            $sql .= " logout_date != login_date AND ";
        } else {
            $sql .= " UNIX_TIMESTAMP(logout_date) - UNIX_TIMESTAMP(login_date) > $sessionDuration AND ";
        }
        $sql .= " login_date >= '$newDate' $where_url
                GROUP BY date(login_date)";

        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $monthAndDay = substr($row['login_date'], 5, 5);
            $totalLogin[$monthAndDay] = $row['number'];
        }

        return $totalLogin;
    }

    /**
     * Get course tools usage statistics for the whole platform (by URL if multi-url).
     * @throws Exception
     */
    public static function getToolsStats(): array
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $access_url_rel_course_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $tools = [
            'announcement',
            'assignment',
            'calendar_event',
            'chat',
            'course_description',
            'document',
            'dropbox',
            'group',
            'learnpath',
            'link',
            'quiz',
            'student_publication',
            'user',
            'forum',
        ];
        $tool_names = [];
        foreach ($tools as $tool) {
            $tool_names[$tool] = get_lang(ucfirst($tool), '');
        }
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT access_tool, count( access_id ) AS number_of_logins
                    FROM $table t , $access_url_rel_course_table a
                    WHERE
                        access_tool IN ('".implode("','", $tools)."') AND
                        t.c_id = a.c_id AND
                        access_url_id = $urlId
                        GROUP BY access_tool
                    ";
        } else {
            $sql = "SELECT access_tool, count( access_id ) AS number_of_logins
                    FROM $table
                    WHERE access_tool IN ('".implode("','", $tools)."')
                    GROUP BY access_tool ";
        }

        $res = Database::query($sql);
        $result = [];
        while ($obj = Database::fetch_object($res)) {
            $result[$tool_names[$obj->access_tool]] = $obj->number_of_logins;
        }

        return $result;
    }

    /**
     * Show some stats about the accesses to the different course tools.
     *
     * @param array $result If defined, this serves as data. Otherwise, will get the data from getToolsStats()
     *
     * @return string HTML table
     * @throws Exception
     */
    public static function printToolStats($result = null): string
    {
        if (empty($result)) {
            $result = self::getToolsStats();
        }

        return self::printStats(get_lang('Tools access'), $result, false);
    }

    /**
     * Returns some stats about the number of courses per language.
     * @throws Exception
     */
    public static function printCourseByLanguageStats(): array
    {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $access_url_rel_course_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT course_language, count( c.code ) AS number_of_courses
                    FROM $table as c, $access_url_rel_course_table as u
                    WHERE u.c_id = c.id AND access_url_id = $urlId
                    GROUP BY course_language
                    ORDER BY number_of_courses DESC";
        } else {
            $sql = "SELECT course_language, count( code ) AS number_of_courses
                   FROM $table GROUP BY course_language
                   ORDER BY number_of_courses DESC";
        }
        $res = Database::query($sql);
        $result = [];
        while ($obj = Database::fetch_object($res)) {
            $result[$obj->course_language] = $obj->number_of_courses;
        }

        return $result;
    }

    /**
     * Shows the number of users having their picture uploaded in Dokeos.
     * @throws Exception
     */
    public static function printUserPicturesStats(): string
    {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $url_condition = null;
        $url_condition2 = null;
        $table = null;
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $url_condition = ", $access_url_rel_user_table as url WHERE url.user_id=u.id AND access_url_id='".$urlId."'";
            $url_condition2 = " AND url.user_id=u.id AND access_url_id = $urlId";
            $table = ", $access_url_rel_user_table as url ";
        }
        $sql = "SELECT COUNT(*) AS n FROM $user_table as u ".$url_condition;
        $res = Database::query($sql);
        $count1 = Database::fetch_object($res);
        $sql = "SELECT COUNT(*) AS n FROM $user_table as u $table
               WHERE LENGTH(picture_uri) > 0 $url_condition2";

        $sql .= !str_contains($sql, 'WHERE') ? ' WHERE u.active <> '.USER_SOFT_DELETED : ' AND u.active <> '.USER_SOFT_DELETED;

        $res = Database::query($sql);
        $count2 = Database::fetch_object($res);
        // #users without picture
        $result[get_lang('No')] = $count1->n - $count2->n;
        $result[get_lang('Yes')] = $count2->n; // #users with picture

        return self::printStats(get_lang('Number of users').' ('.get_lang('Picture').')', $result, false);
    }

    /**
     * Print important activities report page
     */
    public static function printActivitiesStats(): string
    {
        $keyword = isset($_GET['keyword']) ? (string) $_GET['keyword'] : '';
        $keyword = Security::remove_XSS($keyword);

        $content = '';
        $content .= '
    <style>
        .ch-activities-wrap { margin-top: 10px; }
        .ch-activities-header { display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .ch-activities-title { margin:0; }
        .ch-activities-help { margin:6px 0 0; color:#5f6b7a; font-size:13px; }
        .ch-activities-actions { margin:10px 0 16px; }
        .ch-chip-filter { width: 280px; max-width: 100%; padding: 8px 10px; border: 1px solid #d6dde6; border-radius: 8px; }
        .ch-groups { margin-top: 14px; }
        .ch-group { margin: 14px 0; }
        .ch-group-title { margin: 0 0 8px; font-size: 14px; font-weight: 600; color:#2b3645; }
        .ch-chip-row { display:flex; flex-wrap:wrap; gap:8px; }
        .ch-chip {
            display:inline-flex; align-items:center; gap:8px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #d6dde6;
            background: #f7f9fc;
            color: #1f2d3d;
            text-decoration: none;
            font-size: 13px;
            line-height: 1;
        }
        .ch-chip:hover { background:#eef3fb; border-color:#c8d3e1; text-decoration:none; }
        .ch-chip-code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; color:#51606f; }
        .ch-empty { color:#5f6b7a; font-size:13px; }
        .ch-divider { border-top: 1px solid #e6edf5; margin: 14px 0; }
    </style>
    ';

        $content .= '<div class="ch-activities-wrap">';

        $content .= '
        <div class="ch-activities-header">
            <div>
                <h4 class="ch-activities-title">'.get_lang('Important activities').'</h4>
                <div class="ch-activities-help">
                    <!-- This report lists tracked event records. Select an event type below or search by keyword. -->
                    '.get_lang('Search').' / '.get_lang('Event type').'
                </div>
            </div>
        </div>
    ';
        $form = new FormValidator(
            'search_simple',
            'get',
            api_get_path(WEB_CODE_PATH).'admin/statistics/index.php',
            '',
            ['style' => 'display:inline-block']
        );

        $renderer = &$form->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span> ');

        $form->addHidden('report', 'activities');
        $form->addHidden('activities_direction', 'DESC');
        $form->addHidden('activities_column', '4');

        $form->addElement('text', 'keyword', get_lang('Keyword'));
        $form->addButtonSearch(get_lang('Search'), 'submit');

        $content .= '<div class="ch-activities-actions">'.$form->returnForm().'</div>';
        if (!empty($keyword)) {
            $table = new SortableTable(
                'activities',
                ['Statistics', 'getNumberOfActivities'],
                ['Statistics', 'getActivitiesData'],
                7,
                50,
                'DESC'
            );

            $parameters = [];
            $parameters['report'] = 'activities';
            $parameters['keyword'] = $keyword;

            $table->set_additional_parameters($parameters);
            $table->set_header(0, get_lang('Event type'));
            $table->set_header(1, get_lang('Data type'));
            $table->set_header(2, get_lang('Value'));
            $table->set_header(3, get_lang('Course'));
            $table->set_header(4, get_lang('Session'));
            $table->set_header(5, get_lang('Username'));
            $table->set_header(6, get_lang('IP address'));
            $table->set_header(7, get_lang('Date'));

            $content .= $table->return_table();
            $content .= '<div class="ch-divider"></div>';
        }

        $prefix = 'LOG_';
        $userDefinedConstants = get_defined_constants(true)['user'] ?? [];
        $filteredConstants = array_filter(
            $userDefinedConstants,
            static function ($constantName) use ($prefix) {
                return strpos((string) $constantName, $prefix) === 0;
            },
            ARRAY_FILTER_USE_KEY
        );

        $eventTypes = [];
        foreach (array_keys($filteredConstants) as $constantName) {
            if ($constantName === 'LOG_WS') {
                // Expand WS events based on Rest constants.
                $constantValue = (string) constant($constantName);
                if (class_exists('Rest')) {
                    try {
                        $reflection = new ReflectionClass('Rest');
                        foreach ($reflection->getConstants() as $name => $value) {
                            $eventTypes[] = $constantValue.(string) $value;
                        }
                    } catch (\Throwable $e) {
                        // Ignore reflection issues.
                    }
                }
                continue;
            }
            if (substr($constantName, -3) === '_ID') {
                continue;
            }

            $eventTypes[] = (string) constant($constantName);
        }

        $eventTypes = array_values(array_unique(array_filter($eventTypes)));
        sort($eventTypes);

        // Group event types by prefix to make it easier to scan.
        $groupLabels = [
            'course'    => 'Course',
            'session'   => 'Session',
            'user'      => 'User',
            'soc'       => 'Social',
            'msg'       => 'Message',
            'message'   => 'Message',
            'wiki'      => 'Wiki',
            'resource'  => 'Resource',
            'ws'        => 'Webservice',
            'default'   => 'Other',
        ];

        $groups = [];
        foreach ($eventTypes as $evt) {
            $evt = trim($evt);
            if ($evt === '') {
                continue;
            }

            $first = explode('_', $evt, 2)[0] ?? 'default';
            $key = $groupLabels[$first] ?? $groupLabels['default'];

            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $evt;
        }

        // Chips section: click = open the report with keyword.
        $linkBase = api_get_self().'?report=activities&activities_direction=DESC&activities_column=7&keyword=';

        $content .= '
        <div class="ch-activities-header" style="margin-top:8px;">
            <div>
                <h4 class="ch-activities-title">'.get_lang('Event type').'</h4>
                <div class="ch-activities-help">
                    '.get_lang('Click an event type to filter results.').'
                </div>
            </div>
            <input id="chChipFilter" class="ch-chip-filter" type="text" placeholder="Filter event types...">
        </div>
    ';

        if (empty($groups)) {
            $content .= '<div class="ch-empty">No event types found.</div>';
        } else {
            $content .= '<div class="ch-groups" id="chChipGroups">';
            $preferredOrder = ['Course', 'Session', 'User', 'Social', 'Message', 'Resource', 'Wiki', 'Webservice', 'Other'];
            foreach ($preferredOrder as $label) {
                if (empty($groups[$label])) {
                    continue;
                }

                $content .= '<div class="ch-group">';
                $content .= '<div class="ch-group-title">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').' ('.count($groups[$label]).')</div>';
                $content .= '<div class="ch-chip-row">';

                foreach ($groups[$label] as $evt) {
                    $evtEsc = htmlspecialchars($evt, ENT_QUOTES, 'UTF-8');
                    $human = ucwords(str_replace('_', ' ', $evt));
                    $humanEsc = htmlspecialchars($human, ENT_QUOTES, 'UTF-8');

                    $content .= '<a class="ch-chip" data-chip-text="'.$evtEsc.' '.$humanEsc.'" href="'.$linkBase.$evtEsc.'" title="'.$evtEsc.'">'
                        .$humanEsc.' <span class="ch-chip-code">'.$evtEsc.'</span></a>';
                }

                $content .= '</div></div>';
            }

            $content .= '</div>';
            $content .= '
        <script>
            (function () {
                var input = document.getElementById("chChipFilter");
                var container = document.getElementById("chChipGroups");
                if (!input || !container) { return; }

                input.addEventListener("input", function () {
                    var q = (input.value || "").toLowerCase().trim();
                    var chips = container.querySelectorAll(".ch-chip");

                    chips.forEach(function (chip) {
                        var t = (chip.getAttribute("data-chip-text") || "").toLowerCase();
                        chip.style.display = (!q || t.indexOf(q) !== -1) ? "" : "none";
                    });
                    var groups = container.querySelectorAll(".ch-group");
                    groups.forEach(function (g) {
                        var visible = g.querySelectorAll(".ch-chip:not([style*=none])").length > 0;
                        g.style.display = visible ? "" : "none";
                    });
                });
            })();
        </script>
        ';
        }

        $content .= '</div>';

        return $content;
    }

    /**
     * Shows statistics about the time of last visit to each course.
     * @throws Exception
     */
    public static function printCourseLastVisit(): string
    {
        $access_url_rel_course_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $columns[0] = 'c_id';
        $columns[1] = 'access_date';
        $sql_order[SORT_ASC] = 'ASC';
        $sql_order[SORT_DESC] = 'DESC';
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $page_nr = isset($_GET['page_nr']) ? intval($_GET['page_nr']) : 1;
        $column = isset($_GET['column']) ? intval($_GET['column']) : 0;
        $direction = isset($_GET['direction']) ? $_GET['direction'] : SORT_ASC;

        if (!in_array($direction, [SORT_ASC, SORT_DESC])) {
            $direction = SORT_ASC;
        }
        $form = new FormValidator('courselastvisit', 'get');
        $form->addElement('hidden', 'report', 'courselastvisit');
        $form->addText('date_diff', get_lang('days'), true);
        $form->addRule('date_diff', 'InvalidNumber', 'numeric');
        $form->addButtonSearch(get_lang('Search'), 'submit');
        if (!isset($_GET['date_diff'])) {
            $defaults['date_diff'] = 60;
        } else {
            $defaults['date_diff'] = Security::remove_XSS($_GET['date_diff']);
        }
        $form->setDefaults($defaults);
        $content = $form->returnForm();

        $values = $form->exportValues();
        $date_diff = $values['date_diff'];
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT * FROM $table t , $access_url_rel_course_table a
                   WHERE
                        c_id = a.c_id AND
                        access_url_id = $urlId
                   GROUP BY c_id
                   HAVING c_id <> ''
                   AND DATEDIFF( '".api_get_utc_datetime()."' , access_date ) <= ".$date_diff;
        } else {
            $sql = "SELECT * FROM $table t
                   GROUP BY c_id
                   HAVING c_id <> ''
                   AND DATEDIFF( '".api_get_utc_datetime()."' , access_date ) <= ".$date_diff;
        }
        $sql .= ' ORDER BY `'.$columns[$column].'` '.$sql_order[$direction];
        $from = ($page_nr - 1) * $per_page;
        $sql .= ' LIMIT '.$from.','.$per_page;

        $content .= '<p>'.get_lang('Latest access').' &gt;= '.$date_diff.' '.get_lang('days').'</p>';
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $courses = [];
            while ($obj = Database::fetch_object($res)) {
                $courseInfo = api_get_course_info_by_id($obj->c_id);
                $course = [];
                $course[] = '<a href="'.api_get_path(WEB_COURSE_PATH).$courseInfo['code'].'">'.$courseInfo['code'].' <a>';
                // Allow sort by date hiding the numerical date
                $course[] = '<span style="display:none;">'.$obj->access_date.'</span>'.api_convert_and_format_date($obj->access_date);
                $courses[] = $course;
            }
            $parameters['date_diff'] = $date_diff;
            $parameters['report'] = 'courselastvisit';
            $table_header[] = [get_lang("Course code"), true];
            $table_header[] = [get_lang("Latest access"), true];

            ob_start();
            Display:: display_sortable_table(
                $table_header,
                $courses,
                ['column' => $column, 'direction' => $direction],
                [],
                $parameters
            );
            $content .= ob_get_contents();
            ob_end_clean();
        } else {
            $content = get_lang('No search results');
        }

        return $content;
    }

    /**
     * Displays the statistics of the messages sent and received by each user in the social network.
     *
     * @param string $messageType Type of message: 'sent' or 'received'
     *
     * @return array Message list
     */
    public static function getMessages(string $messageType): array
    {
        $messageTable = Database::get_main_table(TABLE_MESSAGE);
        $messageRelUserTable = Database::get_main_table(TABLE_MESSAGE_REL_USER);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $accessUrlRelUserTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        switch ($messageType) {
            case 'sent':
                $field = 'm.user_sender_id';
                $joinCondition = "m.id = mru.message_id AND mru.receiver_type = " . MessageRelUser::TYPE_SENDER;
                break;
            case 'received':
                $field = 'mru.user_id';
                $joinCondition = "m.id = mru.message_id AND mru.receiver_type = " . MessageRelUser::TYPE_TO;
                break;
        }

        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT u.lastname, u.firstname, u.username, COUNT(DISTINCT m.id) AS count_message
            FROM $messageTable m
            INNER JOIN $messageRelUserTable mru ON $joinCondition
            INNER JOIN $userTable u ON $field = u.id
            INNER JOIN $accessUrlRelUserTable url ON u.id = url.user_id
            WHERE url.access_url_id = $urlId
            AND u.active <> " . USER_SOFT_DELETED . "
            GROUP BY $field
            ORDER BY count_message DESC";
        } else {
            $sql = "SELECT u.lastname, u.firstname, u.username, COUNT(DISTINCT m.id) AS count_message
            FROM $messageTable m
            INNER JOIN $messageRelUserTable mru ON $joinCondition
            INNER JOIN $userTable u ON $field = u.id
            WHERE u.active <> " . USER_SOFT_DELETED . "
            GROUP BY $field
            ORDER BY count_message DESC";
        }
        $res = Database::query($sql);
        $messages_sent = [];
        while ($messages = Database::fetch_array($res)) {
            if (empty($messages['username'])) {
                $messages['username'] = get_lang('Unknown');
            }
            $users = api_get_person_name(
                    $messages['firstname'],
                    $messages['lastname']
                ) . '<br />(' . $messages['username'] . ')';
            $messages_sent[$users] = $messages['count_message'];
        }

        return $messages_sent;
    }

    /**
     * Count the number of friends for each social network users.
     * @throws Exception
     */
    public static function getFriends(): array
    {
        $user_friend_table = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $sql = "SELECT lastname, firstname, username, COUNT(friend_user_id) AS count_friend
                    FROM $access_url_rel_user_table as url, $user_friend_table uf
                    LEFT JOIN $user_table u
                    ON (uf.user_id = u.id) AND u.active <> ".USER_SOFT_DELETED."
                    WHERE
                        uf.relation_type <> '".UserRelUser::USER_RELATION_TYPE_RRHH."' AND
                        uf.user_id = url.user_id AND
                        access_url_id = $urlId
                    GROUP BY uf.user_id
                    ORDER BY count_friend DESC ";
        } else {
            $sql = "SELECT lastname, firstname, username, COUNT(friend_user_id) AS count_friend
                    FROM $user_friend_table uf
                    LEFT JOIN $user_table u
                    ON (uf.user_id = u.id) AND u.active <> ".USER_SOFT_DELETED."
                    WHERE uf.relation_type <> '".UserRelUser::USER_RELATION_TYPE_RRHH."'
                    GROUP BY uf.user_id
                    ORDER BY count_friend DESC ";
        }
        $res = Database::query($sql);
        $list_friends = [];
        while ($friends = Database::fetch_array($res)) {
            $users = api_get_person_name($friends['firstname'], $friends['lastname']).'<br />('.$friends['username'].')';
            $list_friends[$users] = $friends['count_friend'];
        }

        return $list_friends;
    }

    /**
     * Returns the number of users that didn't log in for a certain period of time.
     * @throws Exception
     */
    public static function printUsersNotLoggedInStats(): string
    {
        $totalLogin = [];
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $total = self::countUsers();
        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $table_url = ", $access_url_rel_user_table";
            $where_url = " AND login_user_id=user_id AND access_url_id = $urlId";
        } else {
            $table_url = '';
            $where_url = '';
        }
        $now = api_get_utc_datetime();
        $sql[get_lang('This day')] =
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 1 DAY) >= '$now' $where_url";
        $sql[get_lang('In the last 7 days')] =
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 7 DAY) >= '$now' $where_url";
        $sql[get_lang('In the last 31 days')] =
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 31 DAY) >= '$now' $where_url";
        $sql[sprintf(get_lang('Last %d months'), 6)] =
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 6 MONTH) >= '$now' $where_url";
        $sql[get_lang('Never connected')] =
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url WHERE 1=1 $where_url";
        foreach ($sql as $index => $query) {
            $res = Database::query($query);
            $obj = Database::fetch_object($res);
            $r = $total - $obj->number;
            $totalLogin[$index] = $r < 0 ? 0 : $r;
        }

        return self::printStats(
            get_lang('Not logged in for some time'),
            $totalLogin,
            false
        );
    }

    /**
     * Returns an array with indexes as the 'yyyy-mm-dd' format of each date
     * within the provided range (including limits). Dates are assumed to be
     * given in UTC.
     *
     * @param string $startDate  Start date, in Y-m-d or Y-m-d h:i:s format
     * @param string $endDate    End date, in Y-m-d or Y-m-d h:i:s format
     * @param ?bool   $removeYear Whether to remove the year in the results (for easier reading)
     *
     * @return array|bool False on error in the params, array of [date1 => 0, date2 => 0, ...] otherwise
     */
    public static function buildDatesArray(string $startDate, string $endDate, ?bool $removeYear = false): mixed
    {
        if (strlen($startDate) > 10) {
            $startDate = substr($startDate, 0, 10);
        }
        if (strlen($endDate) > 10) {
            $endDate = substr($endDate, 0, 10);
        }
        if (!preg_match('/\d\d\d\d-\d\d-\d\d/', $startDate)) {
            return false;
        }
        if (!preg_match('/\d\d\d\d-\d\d-\d\d/', $startDate)) {
            return false;
        }
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        $list = [];
        for ($time = $startTimestamp; $time < $endTimestamp; $time += 86400) {
            $datetime = api_get_utc_datetime($time);
            if ($removeYear) {
                $datetime = substr($datetime, 5, 5);
            } else {
                $dateTime = substr($datetime, 0, 10);
            }
            $list[$datetime] = 0;
        }

        return $list;
    }

    /**
     * Builds a Chart.js chart from an Ajax endpoint that returns JSON chart data.
     *
     * Expected JSON format:
     * {
     *   "labels": [...],
     *   "datasets": [...]
     * }
     */
    public static function getJSChartTemplate(
        string $url,
        string $type,
        string $options = '',
        string $canvasId = 'canvas',
        bool $fullSize = false,
        string $onClickHandler = '',
        string $afterInitJs = '',
        array $dimensions = []
    ): string {
        $urlJs = json_encode($url, JSON_UNESCAPED_SLASHES);
        $typeJs = json_encode($type, JSON_UNESCAPED_SLASHES);
        $canvasIdJs = json_encode($canvasId, JSON_UNESCAPED_SLASHES);

        $typeLower = strtolower(trim($type));
        $isCircular = in_array($typeLower, ['pie', 'doughnut', 'polararea'], true);

        // For circular charts, default to a centered medium size (60%) unless overridden.
        $circularScale = isset($dimensions['circular_scale']) ? (float) $dimensions['circular_scale'] : 0.5;
        if ($circularScale <= 0) {
            $circularScale = 0.5;
        }
        if ($circularScale > 1) {
            $circularScale = 1.0;
        }

        // Build options:
        // - For non-circular charts, when fullSize=true, disable aspect ratio so it fills the wrapper.
        // - For circular charts, keep aspect ratio to avoid massive charts on tall wrappers.
        $options = trim($options);
        if ($isCircular) {
            $baseOptions = 'responsive: true, maintainAspectRatio: true,';
        } else {
            $baseOptions = $fullSize
                ? 'responsive: true, maintainAspectRatio: false,'
                : 'responsive: true,';
        }

        $finalOptions = trim($baseOptions.' '.$options);
        $finalOptions = rtrim($finalOptions, ", \n\r\t");
        $optionsJs = '{'.$finalOptions.'}';

        $w = isset($dimensions['width']) ? (int) $dimensions['width'] : 0;
        $h = isset($dimensions['height']) ? (int) $dimensions['height'] : 0;

        $applyDimensionsJs = '';
        if ($w > 0 || $h > 0) {
            $applyDimensionsJs .= '
            var el = document.getElementById(canvasId);
            if (el) {'.($w > 0 ? ' el.width = '.$w.';' : '').($h > 0 ? ' el.height = '.$h.';' : '').'}
        ';
        }

        // Apply a default centered medium size for circular charts (unless explicit width/height provided).
        $applyCircularSizingJs = '';
        if ($isCircular && $w <= 0 && $h <= 0 && $circularScale < 1.0) {
            $percent = (int) round($circularScale * 100);
            $applyCircularSizingJs = '
            // Center and scale down circular charts by default.
            ctxEl.style.display = "block";
            ctxEl.style.marginLeft = "auto";
            ctxEl.style.marginRight = "auto";
            ctxEl.style.width = "'.$percent.'%";
            ctxEl.style.maxWidth = "'.$percent.'%";
            ctxEl.style.height = "auto";
        ';
        } elseif ($isCircular) {
            // Always center circular charts even if scale is 100% or dimensions were provided.
            $applyCircularSizingJs = '
            ctxEl.style.display = "block";
            ctxEl.style.marginLeft = "auto";
            ctxEl.style.marginRight = "auto";
        ';
        }

        $bindOnClickJs = '';
        if (!empty(trim($onClickHandler))) {
            // "chart" variable is available in the handler
            $bindOnClickJs = '
            options.onClick = function(evt) {
                '.$onClickHandler.'
            };
        ';
        }

        $afterInitJs = trim($afterInitJs);
        $afterInitBlock = $afterInitJs !== '' ? $afterInitJs : '';

        return <<<JS
        <script>
            $(function () {
                var url = $urlJs;
                var canvasId = $canvasIdJs;
                var ctxEl = document.getElementById(canvasId);

                if (!ctxEl) {
                    // Canvas not found: nothing to render.
                    return;
                }

                $applyCircularSizingJs
                $applyDimensionsJs

                var ctx = ctxEl.getContext('2d');
                var chart = null;

                $.ajax({
                    url: url,
                    dataType: "json"
                }).done(function (payload) {
                    var data = payload;

                    // If backend returns a JSON string, parse it.
                    if (typeof data === "string") {
                        try { data = JSON.parse(data); } catch (e) { data = null; }
                    }

                    if (!data || !data.labels || !data.datasets) {
                        // Invalid dataset: do not crash the page.
                        return;
                    }

                    var options = $optionsJs;

                    $bindOnClickJs

                    chart = new Chart(ctx, {
                        type: $typeJs,
                        data: data,
                        options: options
                    });

                    $afterInitBlock
                });
            });
        </script>
        JS;
    }

    /**
     * Builds a Chart.js chart from a PHP array (no Ajax call).
     */
    public static function getJSChartTemplateWithData(
        array $chartData,
        string $type,
        string $options = '',
        string $canvasId = 'canvas',
        bool $fullSize = false,
        string $onClickHandler = '',
        string $afterInitJs = '',
        array $dimensions = []
    ): string {
        $dataJs = json_encode($chartData, JSON_UNESCAPED_SLASHES);
        $typeJs = json_encode($type, JSON_UNESCAPED_SLASHES);
        $canvasIdJs = json_encode($canvasId, JSON_UNESCAPED_SLASHES);

        $typeLower = strtolower(trim($type));
        $isCircular = in_array($typeLower, ['pie', 'doughnut', 'polararea'], true);

        // For circular charts, default to a centered medium size (60%) unless overridden.
        $circularScale = isset($dimensions['circular_scale']) ? (float) $dimensions['circular_scale'] : 0.5;
        if ($circularScale <= 0) {
            $circularScale = 0.5;
        }
        if ($circularScale > 1) {
            $circularScale = 1.0;
        }

        $options = trim($options);
        if ($isCircular) {
            $baseOptions = 'responsive: true, maintainAspectRatio: true,';
        } else {
            $baseOptions = $fullSize
                ? 'responsive: true, maintainAspectRatio: false,'
                : 'responsive: true,';
        }

        $finalOptions = trim($baseOptions.' '.$options);
        $finalOptions = rtrim($finalOptions, ", \n\r\t");
        $optionsJs = '{'.$finalOptions.'}';

        $w = isset($dimensions['width']) ? (int) $dimensions['width'] : 0;
        $h = isset($dimensions['height']) ? (int) $dimensions['height'] : 0;

        $applyDimensionsJs = '';
        if ($w > 0 || $h > 0) {
            $applyDimensionsJs .= '
            var el = document.getElementById(canvasId);
            if (el) {'.($w > 0 ? ' el.width = '.$w.';' : '').($h > 0 ? ' el.height = '.$h.';' : '').'}
        ';
        }

        $applyCircularSizingJs = '';
        if ($isCircular && $w <= 0 && $h <= 0 && $circularScale < 1.0) {
            $percent = (int) round($circularScale * 100);
            $applyCircularSizingJs = '
            // Center and scale down circular charts by default.
            ctxEl.style.display = "block";
            ctxEl.style.marginLeft = "auto";
            ctxEl.style.marginRight = "auto";
            ctxEl.style.width = "'.$percent.'%";
            ctxEl.style.maxWidth = "'.$percent.'%";
            ctxEl.style.height = "auto";
        ';
        } elseif ($isCircular) {
            $applyCircularSizingJs = '
            ctxEl.style.display = "block";
            ctxEl.style.marginLeft = "auto";
            ctxEl.style.marginRight = "auto";
        ';
        }

        $bindOnClickJs = '';
        if (!empty(trim($onClickHandler))) {
            $bindOnClickJs = '
            options.onClick = function(evt) {
                '.$onClickHandler.'
            };
        ';
        }

        $afterInitJs = trim($afterInitJs);
        $afterInitBlock = $afterInitJs !== '' ? $afterInitJs : '';

        return <<<JS
            <script>
                $(function () {
                    var canvasId = $canvasIdJs;
                    var ctxEl = document.getElementById(canvasId);

                    if (!ctxEl) {
                        return;
                    }

                    $applyCircularSizingJs
                    $applyDimensionsJs

                    var ctx = ctxEl.getContext('2d');
                    var chart = null;

                    var data = $dataJs;
                    var options = $optionsJs;

                    $bindOnClickJs

                    chart = new Chart(ctx, {
                        type: $typeJs,
                        data: data,
                        options: options
                    });

                    $afterInitBlock
                });
            </script>
            JS;
    }

    public static function buildJsChartData(array $all, string $chartName): array
    {
        $list = [];
        $palette = ChamiloHelper::getColorPalette(true, true);
        foreach ($all as $tick => $tock) {
            $list['labels'][] = $tick;
        }

        $list['datasets'][0]['label'] = $chartName;
        $list['datasets'][0]['borderColor'] = 'rgba(255,255,255,1)';

        $i = 0;
        foreach ($all as $tick => $tock) {
            $j = $i % count($palette);
            $list['datasets'][0]['data'][] = $tock;
            $list['datasets'][0]['backgroundColor'][] = $palette[$j];
            $i++;
        }

        $scoreDisplay = ScoreDisplay::instance();
        $table = new HTML_Table(['class' => 'data_table stats_table']);
        $headers = [
            get_lang('Name'),
            get_lang('Count'),
            get_lang('Percentage'),
        ];
        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }

        $total = 0;
        foreach ($all as $name => $value) {
            $total += $value;
        }
        $row++;
        foreach ($all as $name => $value) {
            $table->setCellContents($row, 0, $name);
            $table->setCellContents($row, 1, $value);
            $table->setCellContents($row, 2, $scoreDisplay->display_score([$value, $total], SCORE_PERCENT));
            $row++;
        }
        $table = Display::page_subheader2($chartName).$table->toHtml();

        return ['chart' => $list, 'table' => $table];
    }

    /**
     * Display the Logins By Date report and allow export its result to XLS.
     */
    public static function printLoginsByDate(): mixed
    {
        if (isset($_GET['export']) && 'xls' === $_GET['export']) {
            $result = self::getLoginsByDate($_GET['start'], $_GET['end']);
            $data = [[get_lang('Username'), get_lang('First name'), get_lang('Last name'), get_lang('Total time')]];

            foreach ($result as $i => $item) {
                $data[] = [
                    $item['username'],
                    $item['firstname'],
                    $item['lastname'],
                    api_time_to_hms($item['time_count']),
                ];
            }

            Export::arrayToXls($data);
            exit;
        }

        $content = Display::page_header(get_lang('Logins by date'));

        $actions = '';
        $form = new FormValidator('frm_logins_by_date', 'get');
        $form->addDateRangePicker(
            'daterange',
            get_lang('Date range'),
            true,
            ['format' => 'YYYY-MM-DD', 'timePicker' => 'false', 'validate_format' => 'Y-m-d']
        );
        $form->addHidden('report', 'logins_by_date');
        $form->addButtonFilter(get_lang('Search'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $result = self::getLoginsByDate($values['daterange_start'], $values['daterange_end']);

            if (!empty($result)) {
                $actions = Display::url(
                    Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to XLS')),
                    api_get_self().'?'.http_build_query(
                        [
                            'report' => 'logins_by_date',
                            'export' => 'xls',
                            'start' => Security::remove_XSS($values['daterange_start']),
                            'end' => Security::remove_XSS($values['daterange_end']),
                        ]
                    )
                );
            }

            $table = new HTML_Table(['class' => 'data_table stats_table']);
            $table->setHeaderContents(0, 0, get_lang('Username'));
            $table->setHeaderContents(0, 1, get_lang('First name'));
            $table->setHeaderContents(0, 2, get_lang('Last name'));
            $table->setHeaderContents(0, 3, get_lang('Total time'));

            foreach ($result as $i => $item) {
                $table->setCellContents($i + 1, 0, $item['username']);
                $table->setCellContents($i + 1, 1, $item['firstname']);
                $table->setCellContents($i + 1, 2, $item['lastname']);
                $table->setCellContents($i + 1, 3, api_time_to_hms($item['time_count']));
            }

            $table->setColAttributes(0, ['class' => 'text-center']);
            $table->setColAttributes(3, ['class' => 'text-center']);
            $content = $table->toHtml();
        }

        $content .= $form->returnForm();

        if (!empty($actions)) {
            $content .= Display::toolbarAction('logins_by_date_toolbar', [$actions]);
        }

        return $content;
    }

    /**
     * Return HTML table for the student boss role, for the given user ID
     * @param int $bossId
     * @return string
     */
    public static function getBossTable(int $bossId): string
    {
        $students = UserManager::getUsersFollowedByStudentBoss(
            $bossId,
            0,
            false,
            false,
            false,
            null,
            null,
            null,
            null,
            1
        );

        if (!empty($students)) {
            $table = new HTML_Table(['class' => 'table table-responsive', 'id' => 'table_'.$bossId]);
            $headers = [
                get_lang('Name'),
            ];
            $row = 0;
            $column = 0;
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;
            foreach ($students as $student) {
                $column = 0;
                $content = api_get_person_name($student['firstname'], $student['lastname']);
                $content = '<div style="width: 200px; overflow-wrap: break-word;">'.$content.'</div>';
                $table->setCellContents(
                    $row,
                    $column++,
                    $content
                );
                $row++;
            }

            return $table->toHtml();
        }

        return '<table id="table_'.$bossId.'"></table>';
    }

    /**
     * @param string $startDate
     * @param string $endDate
     *
     * @return array
     * @throws Exception
     */
    public static function getLoginsByDate(string $startDate, string $endDate): array
    {
        $startDate = api_get_utc_datetime("$startDate 00:00:00");
        $endDate = api_get_utc_datetime("$endDate 23:59:59");

        if (empty($startDate) || empty($endDate)) {
            return [];
        }

        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblLogin = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $urlJoin = '';
        $urlWhere = '';

        $accessUrlUtil = Container::getAccessUrlUtil();

        if ($accessUrlUtil->isMultiple()) {
            $accessUrl = $accessUrlUtil->getCurrent();
            $urlId = $accessUrl->getId();
            $tblUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

            $urlJoin = "INNER JOIN $tblUrlUser au ON u.id = au.user_id";
            $urlWhere = "AND au.access_url_id = $urlId";
        }

        $sql = "SELECT u.id,
                    u.firstname,
                    u.lastname,
                    u.username,
                    SUM(TIMESTAMPDIFF(SECOND, l.login_date, l.logout_date)) AS time_count
                FROM $tblUser u
                INNER JOIN $tblLogin l
                ON u.id = l.login_user_id
                $urlJoin
                WHERE u.active <> ".USER_SOFT_DELETED." AND l.login_date BETWEEN '$startDate' AND '$endDate'
                $urlWhere
                GROUP BY u.id";

        $stmt = Database::query($sql);

        return Database::store_result($stmt, 'ASSOC');
    }

    /**
     * Gets the number of new users registered between two dates.
     * @throws Exception
     */
    public static function getNewUserRegistrations(string $startDate, string $endDate): array
    {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as reg_date, COUNT(*) as user_count
            FROM user
            WHERE created_at BETWEEN '$startDate' AND '$endDate'
            GROUP BY reg_date";

        $result = Database::query($sql);
        $data = [];
        while ($row = Database::fetch_array($result)) {
            $userCount = is_numeric($row['user_count']) ? (int) $row['user_count'] : 0;
            $data[] = ['date' => $row['reg_date'], 'count' => $userCount];
        }

        return $data;
    }

    /**
     * Gets the number of users registered by creator (creator_id) between two dates.
     * @throws Exception
     */
    public static function getUserRegistrationsByCreator(string $startDate, string $endDate): array
    {
        $sql = "SELECT u.creator_id, COUNT(u.id) as user_count, c.firstname, c.lastname
                FROM user u
                LEFT JOIN user c ON u.creator_id = c.id
                WHERE u.created_at BETWEEN '$startDate' AND '$endDate'
                AND u.creator_id IS NOT NULL
                GROUP BY u.creator_id";

        $result = Database::query($sql);
        $data = [];
        while ($row = Database::fetch_array($result)) {
            $userCount = is_numeric($row['user_count']) ? (int) $row['user_count'] : 0;
            $name = trim($row['firstname'] . ' ' . $row['lastname']);
            if (!empty($name)) {
                $data[] = [
                    'name' => $name,
                    'count' => $userCount
                ];
            }
        }

        return $data;
    }

    /**
     * Initializes an array with dates between two given dates, setting each date's value to 0.
     * @throws Exception
     */
    public static function initializeDateRangeArray(string $startDate, string $endDate): array
    {
        $dateRangeArray = [];
        $currentDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);

        // Loop through the date range and initialize each date with 0
        while ($currentDate <= $endDate) {
            $formattedDate = $currentDate->format('Y-m-d');
            $dateRangeArray[$formattedDate] = 0;
            $currentDate->modify('+1 day');
        }

        return $dateRangeArray;
    }

    /**
     * Checks if the difference between two dates is more than one month.
     * @throws Exception
     */
    public static function isMoreThanAMonth(string $dateStart, string $dateEnd): bool
    {
        $startDate = new DateTime($dateStart);
        $endDate = new DateTime($dateEnd);

        $diff = $startDate->diff($endDate);

        if ($diff->y >= 1) {
            return true;
        }

        if ($diff->m > 1) {
            return true;
        }

        if ($diff->m == 1) {
            return $diff->d > 0;
        }

        return false;
    }

    /**
     * Groups registration data by month.
     * @throws Exception
     */
    public static function groupByMonth(array $registrations): array
    {
        $groupedData = [];

        foreach ($registrations as $registration) {
            $monthYear = (new DateTime($registration['date']))->format('Y-m');
            if (isset($groupedData[$monthYear])) {
                $groupedData[$monthYear] += $registration['count'];
            } else {
                $groupedData[$monthYear] = $registration['count'];
            }
        }

        return $groupedData;
    }

    /**
     * Retrieves the available tools using the repository.
     */
    public static function getAvailableTools(): array
    {
        $em = Database::getManager();
        $repo = $em->getRepository(ResourceLink::class);

        return $repo->getAvailableTools();
    }

    /**
     * Generates a report of tool usage based on the provided tool IDs.
     */
    public static function getToolUsageReportByTools(array $toolIds): array
    {
        $em = Database::getManager();
        $repo = $em->getRepository(ResourceLink::class);

        return $repo->getToolUsageReportByTools($toolIds);
    }

    /**
     * Return de number of certificates generated.
     * This function is resource intensive.
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function countCertificatesByQuarter(string $dateFrom = null, string $dateUntil = null): int
    {
        $tableGradebookCertificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $condition = "";
        if (!empty($dateFrom) && !empty($dateUntil)) {
            $dateFrom = api_get_utc_datetime("$dateFrom 00:00:00");
            $dateUntil = api_get_utc_datetime("$dateUntil 23:59:59");
            $condition = "WHERE (created_at BETWEEN '$dateFrom' AND '$dateUntil')";
        } elseif (!empty($dateFrom)) {
            $dateFrom = api_get_utc_datetime("$dateFrom 00:00:00");
            $condition = "WHERE created_at >= '$dateFrom'";
        } elseif (!empty($dateUntil)) {
            $dateUntil = api_get_utc_datetime("$dateUntil 23:59:59");
            $condition = "WHERE created_at <= '$dateUntil'";
        }
        $sql = "
            SELECT count(*) AS count
            FROM $tableGradebookCertificate
            $condition
        ";
        $response = Database::query($sql);
        $obj = Database::fetch_object($response);
        return $obj->count;
    }

    /**
     * Get the number of logins by dates.
     * This function is resource intensive.
     * @throws Exception
     */
    public static function getSessionsByDuration(string $dateFrom, string $dateUntil): array
    {
        $results = [
            '0' => 0,
            '5' => 0,
            '10' => 0,
            '15' => 0,
            '30' => 0,
            '60' => 0,
        ];
        if (!empty($dateFrom) && !empty($dateUntil)) {
            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
            $accessUrlRelUserTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $urlId = api_get_current_access_url_id();
            $tableUrl = '';
            $whereUrl = '';
            $dateFrom = api_get_utc_datetime("$dateFrom 00:00:00");
            $dateUntil = api_get_utc_datetime("$dateUntil 23:59:59");
            $accessUrlUtil = Container::getAccessUrlUtil();

            if ($accessUrlUtil->isMultiple()) {
                $accessUrl = $accessUrlUtil->getCurrent();
                $urlId = $accessUrl->getId();
                $tableUrl = ", $accessUrlRelUserTable";
                $whereUrl = " AND login_user_id = user_id AND access_url_id = $urlId";
            }
            $sql = "SELECT login_id, TIMESTAMPDIFF(SECOND, login_date, logout_date) AS duration
            FROM $table $tableUrl
            WHERE login_date >= '$dateFrom'
            AND logout_date <= '$dateUntil'
            $whereUrl
            ";
            $res = Database::query($sql);
            while ($session = Database::fetch_array($res)) {
                if ($session['duration'] > 3600) {
                    $results['60']++;
                } elseif ($session['duration'] > 1800) {
                    $results['30']++;
                } elseif ($session['duration'] > 900) {
                    $results['15']++;
                } elseif ($session['duration'] > 600) {
                    $results['10']++;
                } elseif ($session['duration'] > 300) {
                    $results['5']++;
                } else {
                    $results['0']++;
                }
            }
        }
        return $results;
    }

    /**
     * Returns the number of user subscriptions grouped by day.
     */
    public static function getSubscriptionsByDay(string $startDate, string $endDate): array
    {
        $conn = Database::getManager()->getConnection();
        $sql = "
        SELECT DATE(default_date) AS date, COUNT(default_id) AS count
        FROM track_e_default
        WHERE default_event_type = :eventType
        AND default_date BETWEEN :start AND :end
        GROUP BY DATE(default_date)
        ORDER BY DATE(default_date)
    ";

        return $conn->executeQuery($sql, [
            'eventType' => 'user_subscribed',
            'start' => $startDate.' 00:00:00',
            'end' => $endDate.' 23:59:59',
        ])->fetchAllAssociative();
    }

    /**
     * Returns the number of user unsubscriptions grouped by day.
     */
    public static function getUnsubscriptionsByDay(string $startDate, string $endDate): array
    {
        $conn = Database::getManager()->getConnection();
        $sql = "
        SELECT DATE(default_date) AS date, COUNT(default_id) AS count
        FROM track_e_default
        WHERE default_event_type IN (:eventType1, :eventType2)
        AND default_date BETWEEN :start AND :end
        GROUP BY DATE(default_date)
        ORDER BY DATE(default_date)
    ";

        return $conn->executeQuery($sql, [
            'eventType1' => 'user_unsubscribed',
            'eventType2' => 'session_user_deleted',
            'start' => $startDate.' 00:00:00',
            'end' => $endDate.' 23:59:59',
        ], [
            'eventType1' => ParameterType::STRING,
            'eventType2' => ParameterType::STRING,
        ])->fetchAllAssociative();
    }

    /**
     * Users with activity in this course but not officially enrolled (optionally in a session).
     */
    public static function getNonRegisteredActiveUsersInCourse(int $courseId, int $sessionId = 0): array
    {
        $em = Database::getManager();
        $conn = $em->getConnection();

        if ($sessionId > 0) {
            // When working inside a session
            $sql = '
            SELECT
                u.id AS id,
                u.firstname AS firstname,
                u.lastname AS lastname,
                u.email AS email,
                MAX(t.access_date) AS lastAccess
            FROM track_e_access t
            INNER JOIN user u ON u.id = t.access_user_id
            LEFT JOIN session_rel_course_rel_user scru
                ON scru.user_id = u.id
                AND scru.c_id = :courseId
                AND scru.session_id = :sessionId
            WHERE
                t.c_id = :courseId
                AND t.session_id = :sessionId
                AND scru.id IS NULL
            GROUP BY
                u.id, u.firstname, u.lastname, u.email
            ORDER BY
                lastAccess DESC
        ';
        } else {
            // When not in session (regular course access)
            $sql = '
            SELECT
                u.id AS id,
                u.firstname AS firstname,
                u.lastname AS lastname,
                u.email AS email,
                MAX(t.access_date) AS lastAccess
            FROM track_e_access t
            INNER JOIN user u ON u.id = t.access_user_id
            LEFT JOIN course_rel_user cu
                ON cu.user_id = u.id
                AND cu.c_id = :courseId
            WHERE
                t.c_id = :courseId
                AND cu.id IS NULL
            GROUP BY
                u.id, u.firstname, u.lastname, u.email
            ORDER BY
                lastAccess DESC
        ';
        }

        // Execute SQL safely
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('courseId', $courseId);
        if ($sessionId > 0) {
            $stmt->bindValue('sessionId', $sessionId);
        }

        $rows = $stmt->executeQuery()->fetchAllAssociative();

        // Format date results nicely
        foreach ($rows as &$r) {
            $r['lastAccess'] = !empty($r['lastAccess'])
                ? (new \DateTime($r['lastAccess']))->format('Y-m-d H:i:s')
                : '';
        }

        return $rows;
    }

    public static function statistics_render_menu(array $tools): string
    {
        if (empty($tools)) {
            return '';
        }

        $baseUrl = api_get_self();
        $current = $_GET;
        $cols = min(count($tools), 5);

        $parseQuery = static function (string $query): array {
            $decoded = html_entity_decode($query, ENT_QUOTES);
            $params = [];
            parse_str($decoded, $params);

            return is_array($params) ? $params : [];
        };

        $isActiveItem = static function (array $params) use ($current): bool {
            if (empty($params)) {
                return false;
            }

            foreach ($params as $k => $v) {
                if (!array_key_exists($k, $current)) {
                    return false;
                }
                if ((string) $current[$k] !== (string) $v) {
                    return false;
                }
            }

            return true;
        };

        $buildUrl = static function (array $params) use ($baseUrl): string {
            if (empty($params)) {
                return $baseUrl;
            }

            $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

            return str_contains($baseUrl, '?')
                ? ($baseUrl . '&' . $query)
                : ($baseUrl . '?' . $query);
        };

        $activeInfo = null;
        $out = '<nav class="w-full">';
        static $cssPrinted = false;
        if (!$cssPrinted) {
            $cssPrinted = true;

            $out .= '<style>
        .stats-menu-wrap{ overflow-x:auto; padding-bottom:2px; }
        .stats-menu-grid{
          --stats-cols: 5;
          display:grid;
          gap:1rem;
          align-items:start;
          grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .p-inputtext, .p-select { width: auto !important; }
        @media (min-width:640px){
          .stats-menu-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width:768px){
          .stats-menu-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width:1024px){
          .stats-menu-grid{ grid-template-columns: repeat(var(--stats-cols), minmax(240px, 1fr)); }
        }
        </style>';
        }

        $out .= '<div class="stats-menu-wrap">';
        $out .= '<div class="stats-menu-grid" style="--stats-cols:' . (int) $cols . '">';

        foreach ($tools as $section => $items) {
            $section = (string) $section;

            $sectionHasActive = false;
            foreach ($items as $key => $_label) {
                $params = $parseQuery((string) $key);
                if ($isActiveItem($params)) {
                    $sectionHasActive = true;
                    break;
                }
            }

            $sectionCardClass = $sectionHasActive
                ? 'border-primary/30 ring-1 ring-primary/20'
                : 'border-gray-25';

            $dotClass = $sectionHasActive ? 'bg-primary' : 'bg-gray-50';

            $out .= '<section class="self-start h-fit min-w-0 rounded-2xl border ' . $sectionCardClass . ' bg-white p-4 shadow-sm">';
            $out .= '  <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-90">
            <span class="h-2 w-2 rounded-full ' . $dotClass . '"></span>
            ' . htmlspecialchars($section, ENT_QUOTES) . '
        </h3>';

            $out .= '  <ul class="mt-3 space-y-1">';

            foreach ($items as $key => $label) {
                $params = $parseQuery((string) $key);
                $url = $buildUrl($params);
                $active = $isActiveItem($params);

                if ($active) {
                    $activeInfo = ['section' => $section, 'label' => (string) $label];
                }
                $aClass = $active
                    ? 'bg-primary/10 text-primary ring-1 ring-primary/25'
                    : 'text-gray-50 hover:bg-gray-15 hover:text-gray-90';

                $itemDotClass = $active ? 'bg-primary' : 'bg-gray-50 group-hover:bg-primary/60';

                $out .= '<li>
            <a href="' . htmlspecialchars($url, ENT_QUOTES) . '"
               ' . ($active ? 'aria-current="page"' : '') . '
               class="group flex items-start justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium transition
                      focus:outline-none focus:ring-2 focus:ring-primary/30 ' . $aClass . '">
              <span class="flex items-start gap-2 min-w-0">
                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full ' . $itemDotClass . '"></span>
                <span class="leading-5 break-words">' . htmlspecialchars((string) $label, ENT_QUOTES) . '</span>
              </span>';

                if ($active) {
                    $out .= '<span class="inline-flex items-center rounded-full bg-primary/15 px-2 py-0.5 text-xs font-semibold text-primary">'
                        . htmlspecialchars(get_lang('Active'), ENT_QUOTES) .
                        '</span>';
                }

                $out .= '</a></li>';
            }

            $out .= '  </ul>';
            $out .= '</section>';
        }

        $out .= '</div></div>';

        if (!empty($activeInfo)) {
            $out .= '<div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-50">
        <span class="font-semibold">' . htmlspecialchars(get_lang('You are here'), ENT_QUOTES) . ':</span>
        <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">'
                . htmlspecialchars($activeInfo['section'], ENT_QUOTES) . ' · ' . htmlspecialchars($activeInfo['label'], ENT_QUOTES) .
                '</span>
    </div>';
        }

        $out .= '</nav>';
        $out .= '<div class="my-6 h-px w-full bg-gray-25"></div>';

        return $out;
    }
}

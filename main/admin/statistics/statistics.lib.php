<?php
/* For licensing terms, see /license.txt */

/**
* This class provides some functions for statistics
* @package chamilo.statistics
*/
class Statistics {
    /**
     * Converts a number of bytes in a formatted string
     * @param int $size
     * @return string Formatted file size
     */
    function make_size_string($size) {
        if ($size < pow(2,10)) return $size." bytes";
        if ($size >= pow(2,10) && $size < pow(2,20)) return round($size / pow(2,10), 0)." KB";
        if ($size >= pow(2,20) && $size < pow(2,30)) return round($size / pow(2,20), 1)." MB";
        if ($size > pow(2,30)) return round($size / pow(2,30), 2)." GB";
    }
    /**
     * Count courses
     * @param string $category_code  Code of a course category. Default: count
     * all courses.
     * @return int Number of courses counted
     */
    function count_courses($category_code = NULL) {
        global $_configuration;
        $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $access_url_rel_course_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $current_url_id = api_get_current_access_url_id();
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT COUNT(*) AS number FROM ".$course_table." as c, ".$access_url_rel_course_table." as u WHERE u.course_code=c.code AND access_url_id='".$current_url_id."'";
            if (isset ($category_code)) {
                $sql .= " AND category_code = '".Database::escape_string($category_code)."'";
            }
        } else {
            $sql = "SELECT COUNT(*) AS number FROM ".$course_table." ";
            if (isset ($category_code)) {
                $sql .= " WHERE category_code = '".Database::escape_string($category_code)."'";
            }
        }
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);
        return $obj->number;
    }

    /**
     * Count users
     * @param int      optional, user status (COURSEMANAGER or STUDENT), if it's not setted it'll count all users.
     * @param string optional, code of a course category. Default: count only users without filtering category
     * @param bool count invisible courses (todo)
     * @param bool count only active users (false to only return currently active users)
     * @return int Number of users counted
     */
    function count_users($status = null, $category_code = null, $count_invisible_courses = true, $only_active = false) {

        global $_configuration;
        // Database table definitions
        $course_user_table     = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $course_table         = Database :: get_main_table(TABLE_MAIN_COURSE);
        $user_table         = Database :: get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id = api_get_current_access_url_id();
        $active_filter = $only_active?' AND active=1':'';
        $status_filter = isset($status)?' AND status = '.intval($status):'';

        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT COUNT(DISTINCT(u.user_id)) AS number FROM $user_table as u, $access_url_rel_user_table as url WHERE u.user_id=url.user_id AND access_url_id='".$current_url_id."' $status_filter $active_filter";
            if (isset ($category_code)) {
                $sql = "SELECT COUNT(DISTINCT(cu.user_id)) AS number FROM $course_user_table cu, $course_table c, $access_url_rel_user_table as url WHERE c.code = cu.course_code AND c.category_code = '".Database::escape_string($category_code)."' AND cu.user_id=url.user_id AND access_url_id='".$current_url_id."' $status_filter $active_filter";
            }
        } else {
            $sql = "SELECT COUNT(DISTINCT(user_id)) AS number FROM $user_table WHERE 1=1 $status_filter $active_filter";
            if (isset ($category_code)) {
                $status_filter = isset($status)?' AND status = '.intval($status):'';
                $sql = "SELECT COUNT(DISTINCT(cu.user_id)) AS number FROM $course_user_table cu, $course_table c WHERE c.code = cu.course_code AND c.category_code = '".Database::escape_string($category_code)."' $status_filter $active_filter";
            }
        }

        $res = Database::query($sql);
        $obj = Database::fetch_object($res);
        return $obj->number;
    }

    /**
     * Count activities from track_e_default_table
     * @return int Number of activities counted
     */
    function get_number_of_activities() {
        // Database table definitions
        global $_configuration;
        $track_e_default  = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id = api_get_current_access_url_id();
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT count(default_id) AS total_number_of_items FROM $track_e_default, $table_user user, $access_url_rel_user_table url WHERE default_user_id = user.user_id AND user.user_id=url.user_id AND access_url_id='".$current_url_id."'";
        } else {
            $sql = "SELECT count(default_id) AS total_number_of_items FROM $track_e_default, $table_user user WHERE default_user_id = user.user_id ";
        }

        if (isset($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (user.username LIKE '%".$keyword."%' OR default_event_type LIKE '%".$keyword."%' OR default_value_type LIKE '%".$keyword."%' OR default_value LIKE '%".$keyword."%') ";
        }

        $res = Database::query($sql);
        $obj = Database::fetch_object($res);
        return $obj->total_number_of_items;
    }
    /**
     * Get activities data to display
     */
    function get_activities_data($from, $number_of_items, $column, $direction) {
        global $dateTimeFormatLong, $_configuration;
        $track_e_default    		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $table_user 				= Database::get_main_table(TABLE_MAIN_USER);        
        $access_url_rel_user_table	= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id 			= api_get_current_access_url_id();

        $column          = intval($column);
        $from            = intval($from);
        $number_of_items = intval($number_of_items);

        if (!in_array($direction, array('ASC','DESC'))) {
			$direction = 'DESC';
        }
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT ".
                "default_event_type  as col0, ".
                "default_value_type    as col1, ".
                "default_value        as col2, ".
                "user.username         as col3, ".
                "default_date         as col4 ".
                "FROM $track_e_default as track_default, $table_user as user, $access_url_rel_user_table as url ".
                "WHERE track_default.default_user_id = user.user_id AND url.user_id=user.user_id AND access_url_id='".$current_url_id."'";
        } else {
            $sql = "SELECT ".
                   "default_event_type  as col0, ".
                   "default_value_type    as col1, ".
                   "default_value        as col2, ".
                   "user.username         as col3, ".
                   "default_date         as col4 ".
                   "FROM $track_e_default track_default, $table_user user ".
                   "WHERE track_default.default_user_id = user.user_id ";
        }

        if (isset($_GET['keyword'])) {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (user.username LIKE '%".$keyword."%' OR default_event_type LIKE '%".$keyword."%' OR default_value_type LIKE '%".$keyword."%' OR default_value LIKE '%".$keyword."%') ";
        }

        if (!empty($column) && !empty($direction)) {
            $sql .= " ORDER BY col$column $direction";
        } else {
            $sql .= " ORDER BY col4 DESC ";
        }
        $sql .=    " LIMIT $from,$number_of_items ";

        $res = Database::query($sql);
        $activities = array ();
        while ($row = Database::fetch_row($res)) {            
            if (strpos($row[1], '_object') === false) {
                $row[2] = $row[2];
            } else {
                if (!empty($row[2])) {
                    $row[2] = unserialize($row[2]);
                    if (is_array($row[2]) && !empty($row[2])) {
                        $row[2] = implode_with_key(', ', $row[2]);
                    }
                }
            }
        	if (!empty($row['default_date']) && $row['default_date'] != '0000-00-00 00:00:00') {        	
            	$row['default_date'] = api_get_local_time($row['default_date']);
        	} else {
        		$row['default_date'] = '-';
        	}
            $activities[] = $row;
        }
        return $activities;
    }

    /**
     * Get all course categories
     * @return array All course categories (code => name)
     */
    function get_course_categories() {
        $category_table = Database :: get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT code, name FROM $category_table ORDER BY tree_pos";
        $res = Database::query($sql);
        $categories = array ();
        while ($category = Database::fetch_object($res)) {
            $categories[$category->code] = $category->name;
        }
        return $categories;
    }
    /**
     * Rescale data
     * @param array $data The data that should be rescaled
     * @param int $max The maximum value in the rescaled data (default = 500);
     * @return array The rescaled data, same key as $data
     */
    function rescale($data, $max = 500) {
        $data_max = 1;
        foreach ($data as $index => $value) {
            $data_max = ($data_max < $value ? $value : $data_max);
        }
        reset($data);
        $result = array ();
        $delta = $max / $data_max;
        foreach ($data as $index => $value) {
            $result[$index] = (int) round($value * $delta);
        }
        return $result;
    }
    /**
     * Show statistics
     * @param string $title The title
     * @param array $stats
     * @param bool $show_total
     * @param bool $is_file_size
     */
    function print_stats($title, $stats, $show_total = true, $is_file_size = false) {
        $total = 0;
        $data = Statistics::rescale($stats);

        echo '<table class="data_table" cellspacing="0" cellpadding="3">
                <tr><th colspan="'.($show_total ? '4' : '3').'">'.$title.'</th></tr>';
        $i = 0;
        foreach ($stats as $subtitle => $number) {
            $total += $number;
        }
        
        foreach ($stats as $subtitle => $number) {            
            if (!$is_file_size) {
                $number_label = number_format($number, 0, ',', '.');
            } else {
                $number_label = Statistics::make_size_string($number);
            }            
            $percentage = ($total>0?number_format(100*$number/$total, 1, ',', '.'):'0');

            echo '<tr class="row_'.($i%2 == 0 ? 'odd' : 'even').'">
                    <td width="150">'.$subtitle.'</td>
                    <td width="550">'.Display::bar_progress($percentage, false).'</td>
                    <td align="right">'.$number_label.'</td>';
            if ($show_total) {
                echo '<td align="right"> '.$percentage.'%</td>';
            }
            echo '</tr>';
            $i ++;
        }
        if ($show_total) {
            if (!$is_file_size) {
                $total_label = number_format($total, 0, ',', '.');
            } else {
                $total_label = Statistics::make_size_string($total);
            }
            echo '<tr><th  colspan="4" align="right">'.get_lang('Total').': '.$total_label.'</td></tr>';
        }
        echo '</table>';
    }
    /**
     * Show some stats about the number of logins
     * @param string $type month, hour or day
     */
    function print_login_stats($type) {
        global $_configuration;
        $table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id = api_get_current_access_url_id();
        if ($_configuration['multiple_access_urls']) {
            $table_url = ", $access_url_rel_user_table";
            $where_url = " WHERE login_user_id=user_id AND access_url_id='".$current_url_id."'";
            $where_url_last = ' AND login_date > DATE_SUB(NOW(),INTERVAL 1 %s)';
        } else {
            $table_url = '';
            $where_url = '';
            $where_url_last = ' WHERE login_date > DATE_SUB(NOW(),INTERVAL 1 %s)';
        }
        switch ($type) {
            case 'month':
                $months = api_get_months_long();
                $period = get_lang('PeriodMonth');
                $sql = "SELECT DATE_FORMAT( login_date, '%Y-%m' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table.$table_url.$where_url." GROUP BY stat_date ORDER BY login_date ";
                $sql_last_x = "SELECT DATE_FORMAT( login_date, '%Y-%m' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table.$table_url.$where_url.sprintf($where_url_last,'YEAR')." GROUP BY stat_date ORDER BY login_date ";
                break;
            case 'hour':
                $period = get_lang('PeriodHour');
                $sql = "SELECT DATE_FORMAT( login_date, '%H' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table.$table_url.$where_url." GROUP BY stat_date ORDER BY stat_date ";
                $sql_last_x = "SELECT DATE_FORMAT( login_date, '%H' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table.$table_url.$where_url.sprintf($where_url_last,'DAY')." GROUP BY stat_date ORDER BY stat_date ";
                break;
            case 'day':
                $week_days = api_get_week_days_long();
                $period = get_lang('PeriodDay');
                $sql = "SELECT DATE_FORMAT( login_date, '%w' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table.$table_url.$where_url." GROUP BY stat_date ORDER BY DATE_FORMAT( login_date, '%w' ) ";
                $sql_last_x = "SELECT DATE_FORMAT( login_date, '%w' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table.$table_url.$where_url.sprintf($where_url_last,'WEEK')." GROUP BY stat_date ORDER BY DATE_FORMAT( login_date, '%w' ) ";
                break;
        }
        $res_last_x = Database::query($sql_last_x);
        $result_last_x = array();
        while ($obj = Database::fetch_object($res_last_x)) {
            $stat_date = $obj->stat_date;
            switch ($type) {
                case 'month':
                    $stat_date = explode('-', $stat_date);
                    $stat_date[1] = $months[$stat_date[1] - 1];
                    $stat_date = implode(' ', $stat_date);
                    break;
                case 'day':
                    $stat_date = $week_days[$stat_date];
                    break;
            }
            $result_last_x[$stat_date] = $obj->number_of_logins;
        }
        Statistics::print_stats(get_lang('LastLogins').' ('.$period.')', $result_last_x, true);
        flush(); //flush web request at this point to see something already while the full data set is loading
        echo '<br />';
        $res = Database::query($sql);
        $result = array();
        while ($obj = Database::fetch_object($res)) {
            $stat_date = $obj->stat_date;
            switch ($type) {
                case 'month':
                    $stat_date = explode('-', $stat_date);
                    $stat_date[1] = $months[$stat_date[1] - 1];
                    $stat_date = implode(' ', $stat_date);
                    break;
                case 'day':
                    $stat_date = $week_days[$stat_date];
                    break;
            }
            $result[$stat_date] = $obj->number_of_logins;
        }
        Statistics::print_stats(get_lang('AllLogins').' ('.$period.')', $result, true);
    }
    /**
     * Print the number of recent logins
     */
    function print_recent_login_stats() {
        global $_configuration;
        $total_logins = array();
        $table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id = api_get_current_access_url_id();
        if ($_configuration['multiple_access_urls']) {
            $table_url = ", $access_url_rel_user_table";
            $where_url = " AND login_user_id=user_id AND access_url_id='".$current_url_id."'";
        } else {
            $table_url = '';
            $where_url='';
        }
        $sql[get_lang('Thisday')]      = "SELECT count(login_user_id) AS number FROM $table $table_url WHERE DATE_ADD(login_date, INTERVAL 1 DAY) >= NOW() $where_url";
        $sql[get_lang('Last7days')]  = "SELECT count(login_user_id) AS number  FROM $table $table_url WHERE DATE_ADD(login_date, INTERVAL 7 DAY) >= NOW() $where_url";
        $sql[get_lang('Last31days')] = "SELECT count(login_user_id) AS number  FROM $table $table_url WHERE DATE_ADD(login_date, INTERVAL 31 DAY) >= NOW() $where_url";
        $sql[get_lang('Total')]      = "SELECT count(login_user_id) AS number  FROM $table $table_url WHERE 1=1 $where_url";
        foreach ($sql as $index => $query) {
            $res = Database::query($query);
            $obj = Database::fetch_object($res);
            $total_logins[$index] = $obj->number;
        }
        Statistics::print_stats(get_lang('Logins'),$total_logins,false);
    }
    /**
     * Show some stats about the accesses to the different course tools
     */
    function print_tool_stats() {
        global $_configuration;
        $table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $access_url_rel_course_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $current_url_id = api_get_current_access_url_id();

        $tools = array('announcement','assignment','calendar_event',
            'chat','conference','course_description','document',
            'dropbox','group','learnpath','link','quiz',
            'student_publication','user','forum');
        $tool_names = array();
        foreach ($tools as $tool) {
            $tool_names[$tool] = get_lang(ucfirst($tool), '');
        }
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT access_tool, count( access_id ) ".
                   "AS number_of_logins FROM $table, $access_url_rel_course_table ".
                   "WHERE access_tool IN ('".implode("','",$tools)."') AND  course_code = access_cours_code AND access_url_id='".$current_url_id."' ".
                   "GROUP BY access_tool ";
        } else {
            $sql = "SELECT access_tool, count( access_id ) ".
                "AS number_of_logins FROM $table ".
                "WHERE access_tool IN ('".implode("','",$tools)."') ".
                "GROUP BY access_tool ";
        }
        $res = Database::query($sql);
        $result = array();
        while ($obj = Database::fetch_object($res)) {
            $result[$tool_names[$obj->access_tool]] = $obj->number_of_logins;
        }
        Statistics::print_stats(get_lang('PlatformToolAccess'),$result,true);
    }
    /**
     * Show some stats about the number of courses per language
     */
    function print_course_by_language_stats() {
        global $_configuration;
        $table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $access_url_rel_course_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $current_url_id = api_get_current_access_url_id();
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT course_language, count( c.code ) AS number_of_courses ".
                   "FROM $table as c, $access_url_rel_course_table as u 
            		WHERE u.course_code=c.code AND access_url_id='".$current_url_id."' 
            		GROUP BY course_language ORDER BY number_of_courses DESC";
        } else {
            $sql = "SELECT course_language, count( code ) AS number_of_courses ".
                   "FROM $table GROUP BY course_language ORDER BY number_of_courses DESC";
        }
        $res = Database::query($sql);
        $result = array();
        while ($obj = Database::fetch_object($res)) {
            $result[$obj->course_language] = $obj->number_of_courses;
        }
        Statistics::print_stats(get_lang('CountCourseByLanguage'),$result,true);
    }
    /**
     * Shows the number of users having their picture uploaded in Dokeos.
     */
    function print_user_pictures_stats() {
        global $_configuration;
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id = api_get_current_access_url_id();
        if ($_configuration['multiple_access_urls']) {
            $url_condition =  ", $access_url_rel_user_table as url WHERE url.user_id=u.user_id AND access_url_id='".$current_url_id."'";
            $url_condition2 = " AND url.user_id=u.user_id AND access_url_id='".$current_url_id."'";
            $table = ", $access_url_rel_user_table as url ";
        }
        $sql = "SELECT COUNT(*) AS n FROM $user_table as u ".$url_condition;
        $res = Database::query($sql);
        $count1 = Database::fetch_object($res);
        $sql = "SELECT COUNT(*) AS n FROM $user_table as u $table ".
               "WHERE LENGTH(picture_uri) > 0 $url_condition2";
        $res = Database::query($sql);
        $count2 = Database::fetch_object($res);
        // #users without picture
        $result[get_lang('No')] = $count1->n - $count2->n;
        $result[get_lang('Yes')] = $count2->n; // #users with picture
        Statistics::print_stats(get_lang('CountUsers').' ('.get_lang('UserPicture').')',$result,true);
    }

    function print_activities_stats() {

        echo '<h4>'.get_lang('ImportantActivities').'</h4>';

        // Create a search-box
        $form = new FormValidator('search_simple','get',api_get_path(WEB_CODE_PATH).'admin/statistics/index.php?action=activities','','width=200px',false);
        $renderer =& $form->defaultRenderer();
        $renderer->setElementTemplate('<span>{element}</span> ');
        $form->addElement('hidden','action','activities');
        $form->addElement('hidden','activities_direction','DESC');
        $form->addElement('hidden','activities_column','4');
        $form->addElement('text','keyword',get_lang('keyword'));
        $form->addElement('style_submit_button', 'submit', get_lang('Search'),'class="search"');
        echo '<div class="actions">';
            $form->display();
        echo '</div>';
        
        $table = new SortableTable('activities', array('Statistics','get_number_of_activities'), array('Statistics','get_activities_data'),4,50,'DESC');
        $parameters = array();

        $parameters['action'] = 'activities';
        if (isset($_GET['keyword'])) {
            $parameters['keyword'] = Security::remove_XSS($_GET['keyword']);
        }

        $table->set_additional_parameters($parameters);
        $table->set_header(0, get_lang('EventType'));
        $table->set_header(1, get_lang('DataType'));
        $table->set_header(2, get_lang('Value'));
        $table->set_header(3, get_lang('UserName'));
        $table->set_header(4, get_lang('Date'));
        $table->display();
    }

    /**
     * Shows statistics about the time of last visit to each course.
     */
    function print_course_last_visit() {
        global $_configuration;
        $access_url_rel_course_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $current_url_id = api_get_current_access_url_id();

        $columns[0] = 'access_cours_code';
        $columns[1] = 'access_date';
        $sql_order[SORT_ASC] = 'ASC';
        $sql_order[SORT_DESC] = 'DESC';
        $per_page = isset($_GET['per_page'])?intval($_GET['per_page']) : 10;
        $page_nr = isset($_GET['page_nr'])?intval($_GET['page_nr']) : 1;
        $column = isset($_GET['column'])?intval($_GET['column']) : 0;
        $date_diff = isset($_GET['date_diff'])?intval($_GET['date_diff']) : 60;
            if (!in_array($_GET['direction'],array(SORT_ASC,SORT_DESC))) {
                $direction = SORT_ASC;
            } else {
                $direction = isset($_GET['direction']) ? $_GET['direction'] : SORT_ASC;
            }
        $form = new FormValidator('courselastvisit','get');
        $form->addElement('hidden','action','courselastvisit');
        $form->add_textfield('date_diff',get_lang('Days'),true);
        $form->addRule('date_diff','InvalidNumber','numeric');
        $form->addElement('style_submit_button', 'submit', get_lang('Search'),'class="search"');
        if (!isset($_GET['date_diff'])) {
            $defaults['date_diff'] = 60;
        } else {
            $defaults['date_diff'] = Security::remove_XSS($_GET['date_diff']);
        }
        $form->setDefaults($defaults);
        $form->display();
        $values = $form->exportValues();
        $date_diff = $values['date_diff'];
        $table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT * FROM $table, $access_url_rel_course_table WHERE course_code = access_cours_code AND access_url_id='".$current_url_id."' ".
                   "GROUP BY access_cours_code ".
                   "HAVING access_cours_code <> '' ".
                   "AND DATEDIFF( '".date('Y-m-d h:i:s')."' , access_date ) <= ". $date_diff;
        } else {
            $sql = "SELECT * FROM $table ".
                   "GROUP BY access_cours_code ".
                   "HAVING access_cours_code <> '' ".
                   "AND DATEDIFF( '".date('Y-m-d h:i:s')."' , access_date ) <= ". $date_diff;
        }
        $res = Database::query($sql);
        $number_of_courses = Database::num_rows($res);
        $sql .= ' ORDER BY '.$columns[$column].' '.$sql_order[$direction];
        $from = ($page_nr -1) * $per_page;
        $sql .= ' LIMIT '.$from.','.$per_page;
        echo '<p>'.get_lang('LastAccess').' &gt;= '.$date_diff.' '.get_lang('Days').'</p>';
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $courses = array ();
            while ($obj = Database::fetch_object($res)) {
                $course = array ();
                $course[]= '<a href="'.api_get_path(WEB_PATH).'courses/'.$obj->access_cours_code.'">'.$obj->access_cours_code.' <a>';
                                //Allow sort by date hiding the numerical date
                $course[] = '<span style="display:none;">'.$obj->access_date.'</span>'.api_convert_and_format_date($obj->access_date);
                $courses[] = $course;
            }
            $parameters['action'] = 'courselastvisit';
            $parameters['date_diff'] = $date_diff;
            $parameters['action'] = 'courselastvisit';
            $table_header[] = array (get_lang("CourseCode"), true);
            $table_header[] = array (get_lang("LastAccess"), true);
            Display :: display_sortable_table($table_header, $courses, array ('column'=>$column,'direction'=>$direction), array (), $parameters);
        } else {
            echo get_lang('NoSearchResults');
        }
    }

    /**
     * Displays the statistics of the messages sent and received by each user in the social network
     * @param string    Type of message: 'sent' or 'received'
     * @return array    Message list
     */
    function get_messages($message_type) {
        global $_configuration;
        $message_table 				= Database::get_main_table(TABLE_MAIN_MESSAGE);
        $user_table 				= Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        
        $current_url_id 			= api_get_current_access_url_id();
        
        switch ($message_type) {
            case 'sent':
                $field = 'user_sender_id';
                break;
            case 'received':
                $field = 'user_receiver_id';
                break;
        }
        if ($_configuration['multiple_access_urls']) {
        	
            $sql = "SELECT lastname, firstname, username, COUNT($field) AS count_message ".
                "FROM ".$access_url_rel_user_table." as url, ".$message_table." m ".
                "LEFT JOIN ".$user_table." u ON m.$field = u.user_id ".
                "WHERE  url.user_id = m.$field AND  access_url_id='".$current_url_id."' ".
                "GROUP BY m.$field ORDER BY count_message DESC ";
        } else {
            $sql = "SELECT lastname, firstname, username, COUNT($field) AS count_message ".
                "FROM ".$message_table." m ".
                "LEFT JOIN ".$user_table." u ON m.$field = u.user_id ".
                "GROUP BY m.$field ORDER BY count_message DESC ";
        }
        $res = Database::query($sql);
        $messages_sent = array();
        while ($messages = Database::fetch_array($res)) {
            if (empty($messages['username'])) {
                $messages['username'] = get_lang('Unknown');
            }
            $users = api_get_person_name($messages['firstname'], $messages['lastname']).'<br />('.$messages['username'].')';
            $messages_sent[$users] = $messages['count_message'];
        }
        return $messages_sent;
    }

    /**
     * Count the number of friends for social network users
     */
    function get_friends() {
        global $_configuration;
        $user_friend_table = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id = api_get_current_access_url_id();
        
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT lastname, firstname, username, COUNT(friend_user_id) AS count_friend ".
                "FROM ".$access_url_rel_user_table." as url, ".$user_friend_table." uf ".
                "LEFT JOIN ".$user_table." u ON uf.user_id = u.user_id ".
                "WHERE uf.relation_type <> '".USER_RELATION_TYPE_RRHH."' AND uf.user_id = url.user_id AND  access_url_id='".$current_url_id."' ".
                "GROUP BY uf.user_id ORDER BY count_friend DESC ";
        } else {
            $sql = "SELECT lastname, firstname, username, COUNT(friend_user_id) AS count_friend ".
                "FROM ".$user_friend_table." uf ".
                "LEFT JOIN ".$user_table." u ON uf.user_id = u.user_id ".
                "WHERE uf.relation_type <> '".USER_RELATION_TYPE_RRHH."' ".
                "GROUP BY uf.user_id ORDER BY count_friend DESC ";
        }
        $res = Database::query($sql);
        $list_friends = array();
        while ($friends = Database::fetch_array($res)) {
            $users = api_get_person_name($friends['firstname'], $friends['lastname']).'<br />('.$friends['username'].')';
            $list_friends[$users] = $friends['count_friend'];
        }
        return $list_friends;
    }
    /**
     * Print the number of users that didn't login for a certain period of time
     */
    function print_users_not_logged_in_stats() {
        global $_configuration;
        $total_logins = array();
        $table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $current_url_id = api_get_current_access_url_id();
        $total = self::count_users();
        if ($_configuration['multiple_access_urls']) {
            $table_url = ", $access_url_rel_user_table";
            $where_url = " AND login_user_id=user_id AND access_url_id='".$current_url_id."'";
        } else {
            $table_url = '';
            $where_url='';
        }
        $sql[get_lang('Thisday')]    = 
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 1 DAY) >= NOW() $where_url";
        $sql[get_lang('Last7days')]  = 
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 7 DAY) >= NOW() $where_url";
        $sql[get_lang('Last31days')] = 
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 31 DAY) >= NOW() $where_url";
        $sql[sprintf(get_lang('LastXMonths'),6)] = 
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url ".
            " WHERE DATE_ADD(login_date, INTERVAL 6 MONTH) >= NOW() $where_url";
        $sql[get_lang('NeverConnected')]      = 
            "SELECT count(distinct(login_user_id)) AS number ".
            " FROM $table $table_url WHERE 1=1 $where_url";
        foreach ($sql as $index => $query) {
            $res = Database::query($query);
            $obj = Database::fetch_object($res);
            $r = $total - $obj->number;
            $total_logins[$index] = $r < 0 ? 0 : $r;
        }
        Statistics::print_stats(get_lang('StatsUsersDidNotLoginInLastPeriods'),$total_logins,false);
    }
}

<?php
/* For licensing terms, see /license.txt */

/**
 * Provides functions for thematic option inside attendance tool.
 * It's also used like model to thematic_controller (MVC pattern)
 * Thematic class can be used to instanciate objects or as a library for thematic control.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> SQL fixes
 */
class Thematic
{
    private $session_id;
    private $thematic_id;
    private $thematic_title;
    private $thematic_content;
    private $thematic_plan_id;
    private $thematic_plan_title;
    private $thematic_plan_description;
    private $thematic_plan_description_type;
    private $thematic_advance_id;
    private $attendance_id;
    private $thematic_advance_content;
    private $start_date;
    private $duration;
    private $course_int_id;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->course_int_id = api_get_course_int_id();
    }

    /**
     * Get the total number of thematic inside current course and current session.
     *
     * @see SortableTable#get_total_number_of_items()
     */
    public function get_number_of_thematics()
    {
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $condition_session = '';
        if (!api_get_session_id()) {
            $condition_session = api_get_session_condition(0);
        }
        $course_id = api_get_course_int_id();
        $sql = "SELECT COUNT(id) AS total_number_of_items
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session ";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * Get the thematics to display on the current page (fill the sortable-table).
     *
     * @param   int     offset of first user to recover
     * @param   int     Number of users to get
     * @param   int     Column to sort on
     * @param   string  Order (ASC,DESC)
     *
     * @return array
     *
     * @see SortableTable#get_table_data($from)
     */
    public function get_thematic_data($from, $number_of_items, $column, $direction)
    {
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $condition_session = '';
        if (!api_get_session_id()) {
            $condition_session = api_get_session_condition(0);
        }
        $column = intval($column);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $course_id = api_get_course_int_id();

        $sql = "SELECT id AS col0, title AS col1, display_order AS col2, session_id
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session
                ORDER BY col2
                LIMIT $from,$number_of_items ";
        $res = Database::query($sql);

        $thematics = [];
        $user_info = api_get_user_info(api_get_user_id());
        while ($thematic = Database::fetch_row($res)) {
            $session_star = '';
            if (api_get_session_id() == $thematic[3]) {
                $session_star = api_get_session_image(api_get_session_id(), $user_info['status']);
            }
            $thematic[1] = '<a href="index.php?'.api_get_cidreq().'&action=thematic_details&thematic_id='.$thematic[0].'">'.
                Security::remove_XSS($thematic[1], STUDENT).$session_star.'</a>';
            if (api_is_allowed_to_edit(null, true)) {
                $actions = '';

                if (api_get_session_id()) {
                    if (api_get_session_id() == $thematic[3]) {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('lesson_plan.png', get_lang('ThematicPlan'), '', ICON_SIZE_SMALL).'</a>&nbsp;';
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('lesson_plan_calendar.png', get_lang('ThematicAdvance'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
                        $actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= Display::return_icon(
                            'lesson_plan_na.png',
                            get_lang('ThematicPlan'),
                            '',
                            ICON_SIZE_SMALL
                        ).'&nbsp;';
                        $actions .= Display::return_icon(
                            'lesson_plan_calendar_na.png',
                            get_lang('ThematicAdvance'),
                            '',
                            ICON_SIZE_SMALL
                        ).'&nbsp;';
                        $actions .= Display::return_icon('edit_na.png', get_lang('Edit'), '', ICON_SIZE_SMALL);
                        $actions .= Display::return_icon(
                            'delete_na.png',
                            get_lang('Delete'),
                            '',
                            ICON_SIZE_SMALL
                        ).'&nbsp;';
                        $actions .= Display::url(
                            Display::return_icon('cd.gif', get_lang('Copy')),
                            'index.php?'.api_get_cidreq().'&action=thematic_copy&thematic_id='.$thematic[0]
                        );
                    }
                } else {
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('lesson_plan.png', get_lang('ThematicPlan'), '', ICON_SIZE_SMALL).'</a>&nbsp;';
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('lesson_plan_calendar.png', get_lang('ThematicAdvance'), '', ICON_SIZE_SMALL).'</a>&nbsp;';

                    if ($thematic[2] > 1) {
                        $actions .= '<a href="'.api_get_self().'?action=moveup&'.api_get_cidreq().'&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('up.png', get_lang('Up'), '', ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= Display::return_icon('up_na.png', '&nbsp;', '', ICON_SIZE_SMALL);
                    }
                    if ($thematic[2] < self::get_max_thematic_item()) {
                        $actions .= '<a href="'.api_get_self().'?action=movedown&a'.api_get_cidreq().'&thematic_id='.$thematic[0].'">'.
                            Display::return_icon('down.png', get_lang('Down'), '', ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= Display::return_icon('down_na.png', '&nbsp;', '', ICON_SIZE_SMALL);
                    }
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
                    $actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$thematic[0].'">'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                }
                $thematics[] = [$thematic[0], $thematic[1], $actions];
            }
        }

        return $thematics;
    }

    /**
     * Get the maximum display order of the thematic item.
     *
     * @param bool $use_session
     *
     * @return int Maximum display order
     */
    public function get_max_thematic_item($use_session = true)
    {
        // Database table definition
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $session_id = api_get_session_id();
        if ($use_session) {
            $condition_session = api_get_session_condition($session_id);
        } else {
            $condition_session = '';
        }
        $course_id = api_get_course_int_id();
        $sql = "SELECT MAX(display_order)
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        return $row[0];
    }

    /**
     * Move a thematic.
     *
     * @param string $direction   (up, down)
     * @param int    $thematic_id
     */
    public function move_thematic($direction, $thematic_id)
    {
        // Database table definition
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);

        // sort direction
        if ($direction == 'up') {
            $sortorder = 'DESC';
        } else {
            $sortorder = 'ASC';
        }
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        $sql = "SELECT id, display_order
                FROM $tbl_thematic
                WHERE c_id = $course_id AND active = 1 $condition_session
                ORDER BY display_order $sortorder";
        $res = Database::query($sql);
        $found = false;

        // Variable definition
        $current_id = 0;
        $next_id = 0;
        while ($row = Database::fetch_array($res)) {
            if ($found && empty($next_id)) {
                $next_id = intval($row['id']);
                $next_display_order = intval($row['display_order']);
            }

            if ($row['id'] == $thematic_id) {
                $current_id = intval($thematic_id);
                $current_display_order = intval($row['display_order']);
                $found = true;
            }
        }

        // get last done thematic advance before move thematic list
        $last_done_thematic_advance = $this->get_last_done_thematic_advance();

        if (!empty($next_display_order) && !empty($current_id)) {
            $sql = "UPDATE $tbl_thematic SET display_order = $next_display_order
                    WHERE c_id = $course_id AND id = $current_id ";
            Database::query($sql);
        }
        if (!empty($current_display_order) && !empty($next_id)) {
            $sql = "UPDATE $tbl_thematic SET
                    display_order = $current_display_order
                    WHERE c_id = $course_id AND id = $next_id ";
            Database::query($sql);
        }

        // update done advances with de current thematic list
        $this->update_done_thematic_advances($last_done_thematic_advance);
    }

    /**
     * Get thematic list.
     *
     * @param int    $thematic_id Thematic id (optional), get list by id
     * @param string $course_code
     * @param int    $session_id
     *
     * @return array Thematic data
     */
    public static function get_thematic_list(
        $thematic_id = null,
        $course_code = null,
        $session_id = null
    ) {
        // set current course and session
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        if (isset($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }

        $data = [];
        if (isset($thematic_id)) {
            $thematic_id = intval($thematic_id);
            $condition = " WHERE id = $thematic_id AND active = 1 ";
        } else {
            if (empty($session_id)) {
                $condition_session = api_get_session_condition(0);
            } else {
                $condition_session = api_get_session_condition($session_id, true, true);
            }
            $condition = " WHERE active = 1 $condition_session ";
        }
        $sql = "SELECT *
                FROM $tbl_thematic $condition AND c_id = $course_id
                ORDER BY display_order ";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            if (!empty($thematic_id)) {
                $data = Database::fetch_array($res, 'ASSOC');
            } else {
                while ($row = Database::fetch_array($res, 'ASSOC')) {
                    $data[$row['id']] = $row;
                }
            }
        } else {
            return false;
        }

        return $data;
    }

    /**
     * Insert or update a thematic.
     *
     * @return int last thematic id
     */
    public function thematic_save()
    {
        $_course = api_get_course_info();
        // definition database table
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);

        // protect data
        $id = intval($this->thematic_id);
        $title = $this->thematic_title;
        $content = $this->thematic_content;
        $session_id = intval($this->session_id);
        $user_id = api_get_user_id();

        // get the maximum display order of all the glossary items
        $max_thematic_item = $this->get_max_thematic_item(false);

        if (empty($id)) {
            // insert
            $params = [
                'c_id' => $this->course_int_id,
                'title' => $title,
                'content' => $content,
                'active' => 1,
                'display_order' => intval($max_thematic_item) + 1,
                'session_id' => $session_id,
            ];
            $last_id = Database::insert($tbl_thematic, $params);
            if ($last_id) {
                $sql = "UPDATE $tbl_thematic SET id = iid WHERE iid = $last_id";
                Database::query($sql);
                api_item_property_update(
                    $_course,
                    'thematic',
                    $last_id,
                    "ThematicAdded",
                    $user_id
                );
            }
        } else {
            // Update
            $params = [
                'title' => $title,
                'content' => $content,
                'session_id' => $session_id,
            ];

            Database::update(
                $tbl_thematic,
                $params,
                ['id  = ? AND c_id = ?' => [$id, $this->course_int_id]]
            );

            $last_id = $id;

            // save inside item property table
            api_item_property_update(
                $_course,
                'thematic',
                $last_id,
                "ThematicUpdated",
                $user_id
            );
        }

        return $last_id;
    }

    /**
     * Delete logically (set active field to 0) a thematic.
     *
     * @param int|array One or many thematic ids
     *
     * @return int Affected rows
     */
    public function delete($thematic_id)
    {
        $_course = api_get_course_info();
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $affected_rows = 0;
        $user_id = api_get_user_id();
        $course_id = api_get_course_int_id();

        if (is_array($thematic_id)) {
            foreach ($thematic_id as $id) {
                $id = intval($id);
                $sql = "UPDATE $tbl_thematic SET active = 0
                        WHERE c_id = $course_id AND id = $id";
                $result = Database::query($sql);
                $affected_rows += Database::affected_rows($result);
                if (!empty($affected_rows)) {
                    // update row item property table
                    api_item_property_update(
                        $_course,
                        'thematic',
                        $id,
                        "ThematicDeleted",
                        $user_id
                    );
                }
            }
        } else {
            $thematic_id = intval($thematic_id);
            $sql = "UPDATE $tbl_thematic SET active = 0
                    WHERE c_id = $course_id AND id = $thematic_id";
            $result = Database::query($sql);
            $affected_rows = Database::affected_rows($result);
            if (!empty($affected_rows)) {
                // update row item property table
                api_item_property_update(
                    $_course,
                    'thematic',
                    $thematic_id,
                    "ThematicDeleted",
                    $user_id
                );
            }
        }

        return $affected_rows;
    }

    /**
     * @param int $thematic_id
     */
    public function copy($thematic_id)
    {
        $thematic = self::get_thematic_list($thematic_id, api_get_course_id(), 0);
        $thematic_copy = new Thematic();
        $thematic_copy->set_thematic_attributes(
            '',
            $thematic['title'].' - '.get_lang('Copy'),
            $thematic['content'],
            api_get_session_id()
        );

        $new_thematic_id = $thematic_copy->thematic_save();
        if (!empty($new_thematic_id)) {
            $thematic_advanced = self::get_thematic_advance_by_thematic_id($thematic_id);
            if (!empty($thematic_advanced)) {
                foreach ($thematic_advanced as $item) {
                    $thematic = new Thematic();
                    $thematic->set_thematic_advance_attributes(
                        0,
                        $new_thematic_id,
                        0,
                        $item['content'],
                        $item['start_date'],
                        $item['duration']
                    );
                    $thematic->thematic_advance_save();
                }
            }
            $thematic_plan = self::get_thematic_plan_data($thematic_id);
            if (!empty($thematic_plan)) {
                foreach ($thematic_plan as $item) {
                    $thematic = new Thematic();
                    $thematic->set_thematic_plan_attributes(
                        $new_thematic_id,
                        $item['title'],
                        $item['description'],
                        $item['description_type']
                    );
                    $thematic->thematic_plan_save();
                }
            }
        }
    }

    /**
     * Get the total number of thematic advance inside current course.
     *
     * @see SortableTable#get_total_number_of_items()
     */
    public static function get_number_of_thematic_advances()
    {
        global $thematic_id;
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $course_id = api_get_course_int_id();
        $thematic_id = (int) $thematic_id;

        $sql = "SELECT COUNT(id) AS total_number_of_items
                FROM $table
                WHERE c_id = $course_id AND thematic_id = $thematic_id ";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * Get the thematic advances to display on the current page (fill the sortable-table).
     *
     * @param   int     offset of first user to recover
     * @param   int     Number of users to get
     * @param   int     Column to sort on
     * @param   string  Order (ASC,DESC)
     *
     * @return array
     *
     * @see SortableTable#get_table_data($from)
     */
    public static function get_thematic_advance_data($from, $number_of_items, $column, $direction)
    {
        global $thematic_id;
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $column = intval($column);
        $from = intval($from);
        $number_of_items = intval($number_of_items);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        $data = [];
        $course_id = api_get_course_int_id();
        $thematic_id = (int) $thematic_id;
        if (api_is_allowed_to_edit(null, true)) {
            $sql = "SELECT id AS col0, start_date AS col1, duration AS col2, content AS col3
                    FROM $table
                    WHERE c_id = $course_id AND thematic_id = $thematic_id
                    ORDER BY col$column $direction
                    LIMIT $from,$number_of_items ";

            $list = api_get_item_property_by_tool(
                'thematic_advance',
                api_get_course_id(),
                api_get_session_id()
            );

            $elements = [];
            foreach ($list as $value) {
                $elements[] = $value['ref'];
            }

            $res = Database::query($sql);
            $i = 1;
            while ($thematic_advance = Database::fetch_row($res)) {
                if (in_array($thematic_advance[0], $elements)) {
                    $thematic_advance[1] = api_get_local_time($thematic_advance[1]);
                    $thematic_advance[1] = api_format_date($thematic_advance[1], DATE_TIME_FORMAT_LONG);
                    $actions = '';
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_edit&thematic_id='.$thematic_id.'&thematic_advance_id='.$thematic_advance[0].'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), '', 22).'</a>';
                    $actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_advance_delete&thematic_id='.$thematic_id.'&thematic_advance_id='.$thematic_advance[0].'">'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', 22).'</a></center>';
                    $data[] = [$i, $thematic_advance[1], $thematic_advance[2], $thematic_advance[3], $actions];
                    $i++;
                }
            }
        }

        return $data;
    }

    /**
     * get thematic advance data by thematic id.
     *
     * @param int    $thematic_id
     * @param string $course_code Course code (optional)
     *
     * @return array data
     */
    public function get_thematic_advance_by_thematic_id($thematic_id, $course_code = null)
    {
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        // set current course
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $thematic_id = (int) $thematic_id;
        $data = [];
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id AND thematic_id = $thematic_id ";

        $elements = [];
        $list = api_get_item_property_by_tool(
            'thematic_advance',
            $course_info['code'],
            api_get_session_id()
        );
        foreach ($list as $value) {
            $elements[] = $value['ref'];
        }

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                if (in_array($row['id'], $elements)) {
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function get_thematic_advance_div($data)
    {
        $return_array = [];
        $uinfo = api_get_user_info();

        foreach ($data as $thematic_id => $thematic_advance_data) {
            foreach ($thematic_advance_data as $key => $thematic_advance) {
                $session_star = '';
                if (api_is_allowed_to_edit(null, true)) {
                    if ($thematic_advance['session_id'] != 0) {
                        $session_star = api_get_session_image(api_get_session_id(), $uinfo['status']);
                    }
                }
                // DATE_TIME_FORMAT_LONG
                $thematic_advance_item = '<div><strong>'.
                    api_convert_and_format_date($thematic_advance['start_date'], DATE_TIME_FORMAT_LONG).
                    $session_star.'</strong></div>';
                $thematic_advance_item .= '<div>'.$thematic_advance['duration'].' '.get_lang('HourShort').'</div>';
                $thematic_advance_item .= '<div>'.Security::remove_XSS($thematic_advance['content'], STUDENT).'</div>';
                $return_array[$thematic_id][$thematic_advance['id']] = $thematic_advance_item;
            }
        }

        return $return_array;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function get_thematic_plan_array($data)
    {
        $final_return = [];
        $uinfo = api_get_user_info();

        foreach ($data as $thematic_id => $thematic_plan_data) {
            $new_thematic_plan_data = [];
            foreach ($thematic_plan_data as $thematic_item) {
                $thematic_simple_list[] = $thematic_item['description_type'];
                $new_thematic_plan_data[$thematic_item['description_type']] = $thematic_item;
            }

            if (!empty($thematic_simple_list)) {
                foreach ($thematic_simple_list as $item) {
                    $default_thematic_plan_title[$item] = $new_thematic_plan_data[$item]['title'];
                }
            }

            $session_star = '';
            $return = [];
            if (!empty($default_thematic_plan_title)) {
                foreach ($default_thematic_plan_title as $id => $title) {
                    //avoid others
                    if ($title == 'Others' && empty($data[$thematic_id][$id]['description'])) {
                        continue;
                    }
                    if (!empty($data[$thematic_id][$id]['title']) &&
                        !empty($data[$thematic_id][$id]['description'])
                    ) {
                        if (api_is_allowed_to_edit(null, true)) {
                            if ($data[$thematic_id][$id]['session_id'] != 0) {
                                $session_star = api_get_session_image(api_get_session_id(), $uinfo['status']);
                            }
                        }

                        $return[$id]['title'] = Security::remove_XSS($data[$thematic_id][$id]['title'], STUDENT).$session_star;
                        $return[$id]['description'] = Security::remove_XSS($data[$thematic_id][$id]['description'], STUDENT);
                    }
                }
            }
            $final_return[$thematic_id] = $return;
        }

        return $final_return;
    }

    /**
     * Get thematic advance list.
     *
     * @param int    $thematic_advance_id Thematic advance id (optional), get data by thematic advance list
     * @param string $course_code         Course code (optional)
     * @param bool   $force_session_id    Force to have a session id
     * @param bool   $withLocalTime       Force start_date to local time
     *
     * @return array $data
     */
    public function get_thematic_advance_list(
        $thematic_advance_id = null,
        $course_code = null,
        $force_session_id = false,
        $withLocalTime = false
    ) {
        $course_info = api_get_course_info($course_code);
        $tbl_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $data = [];
        $condition = '';
        $thematic_advance_id = (int) $thematic_advance_id;

        if (!empty($thematic_advance_id)) {
            $condition = " AND a.id = $thematic_advance_id ";
        }

        $course_id = $course_info['real_id'];

        $sql = "SELECT * FROM $tbl_thematic_advance a
                WHERE c_id = $course_id $condition
                ORDER BY start_date ";

        $elements = [];
        if ($force_session_id) {
            $list = api_get_item_property_by_tool(
                'thematic_advance',
                $course_info['code'],
                api_get_session_id()
            );
            foreach ($list as $value) {
                $elements[$value['ref']] = $value;
            }
        }

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            if (!empty($thematic_advance_id)) {
                $data = Database::fetch_array($res);
            } else {
                // group all data group by thematic id
                $tmp = [];
                while ($row = Database::fetch_array($res, 'ASSOC')) {
                    if ($withLocalTime == true) {
                        $row['start_date'] = api_get_local_time($row['start_date']);
                    }
                    $tmp[] = $row['thematic_id'];
                    if (in_array($row['thematic_id'], $tmp)) {
                        if ($force_session_id) {
                            if (in_array($row['id'], array_keys($elements))) {
                                $row['session_id'] = $elements[$row['id']]['session_id'];
                                $data[$row['thematic_id']][$row['id']] = $row;
                            }
                        } else {
                            $data[$row['thematic_id']][$row['id']] = $row;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * insert or update a thematic advance.
     *
     * @todo problem
     *
     * @return int last thematic advance id
     */
    public function thematic_advance_save()
    {
        $_course = api_get_course_info();
        // definition database table
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);

        // protect data
        $id = intval($this->thematic_advance_id);
        $thematic_id = intval($this->thematic_id);
        $attendance_id = intval($this->attendance_id);
        $content = $this->thematic_advance_content;
        $start_date = $this->start_date;
        $duration = intval($this->duration);
        $user_id = api_get_user_id();

        $last_id = null;
        if (empty($id)) {
            // Insert
            $params = [
                'c_id' => $this->course_int_id,
                'thematic_id' => $thematic_id,
                'attendance_id' => $attendance_id,
                'content' => $content,
                'start_date' => api_get_utc_datetime($start_date),
                'duration' => $duration,
                'done_advance' => 0,
            ];
            $last_id = Database::insert($table, $params);

            if ($last_id) {
                $sql = "UPDATE $table SET id = iid WHERE iid = $last_id";
                Database::query($sql);

                api_item_property_update(
                    $_course,
                    'thematic_advance',
                    $last_id,
                    'ThematicAdvanceAdded',
                    $user_id
                );
            }
        } else {
            $params = [
                'thematic_id' => $thematic_id,
                'attendance_id' => $attendance_id,
                'content' => $content,
                'start_date' => api_get_utc_datetime($start_date),
                'duration' => $duration,
            ];

            Database::update(
                $table,
                $params,
                ['id = ? AND c_id = ?' => [$id, $this->course_int_id]]
            );

            api_item_property_update(
                $_course,
                'thematic_advance',
                $id,
                'ThematicAdvanceUpdated',
                $user_id
            );
        }

        return $last_id;
    }

    /**
     * delete  thematic advance.
     *
     * @param int $id Thematic advance id
     *
     * @return int Affected rows
     */
    public function thematic_advance_destroy($id)
    {
        $_course = api_get_course_info();
        $course_id = api_get_course_int_id();

        // definition database table
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);

        // protect data
        $id = intval($id);
        $user_id = api_get_user_id();

        $sql = "DELETE FROM $table
                WHERE c_id = $course_id AND id = $id ";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);
        if ($affected_rows) {
            api_item_property_update(
                $_course,
                'thematic_advance',
                $id,
                'ThematicAdvanceDeleted',
                $user_id
            );
        }

        return $affected_rows;
    }

    /**
     * get thematic plan data.
     *
     * @param int Thematic id (optional), get data by thematic id
     * @param int Thematic plan description type (optional), get data by description type
     *
     * @return array Thematic plan data
     */
    public function get_thematic_plan_data($thematic_id = null, $description_type = null)
    {
        // definition database table
        $tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);
        $tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
        $course_id = api_get_course_int_id();

        $data = [];
        $condition = '';
        if (isset($thematic_id)) {
            $thematic_id = intval($thematic_id);
            $condition .= " AND thematic_id = $thematic_id ";
        }
        if (isset($description_type)) {
            $description_type = intval($description_type);
            $condition .= " AND description_type = $description_type ";
        }

        $items_from_course = api_get_item_property_by_tool(
            'thematic_plan',
            api_get_course_id(),
            0
        );
        $items_from_session = api_get_item_property_by_tool(
            'thematic_plan',
            api_get_course_id(),
            api_get_session_id()
        );

        $thematic_plan_complete_list = [];
        $thematic_plan_id_list = [];

        if (!empty($items_from_course)) {
            foreach ($items_from_course as $item) {
                $thematic_plan_id_list[] = $item['ref'];
                $thematic_plan_complete_list[$item['ref']] = $item;
            }
        }

        if (!empty($items_from_session)) {
            foreach ($items_from_session as $item) {
                $thematic_plan_id_list[] = $item['ref'];
                $thematic_plan_complete_list[$item['ref']] = $item;
            }
        }
        if (!empty($thematic_plan_id_list)) {
            $sql = "SELECT
                        tp.id, thematic_id, tp.title, description, description_type, t.session_id
                    FROM $tbl_thematic_plan tp
                    INNER JOIN $tbl_thematic t
                    ON (t.id = tp.thematic_id AND t.c_id = tp.c_id)
                    WHERE
                        t.c_id = $course_id AND
                        tp.c_id = $course_id
                        $condition AND
                        tp.id IN (".implode(', ', $thematic_plan_id_list).") ";

            $rs = Database::query($sql);

            if (Database::num_rows($rs)) {
                if (!isset($thematic_id) && !isset($description_type)) {
                    // group all data group by thematic id
                    $tmp = [];
                    while ($row = Database::fetch_array($rs, 'ASSOC')) {
                        $tmp[] = $row['thematic_id'];
                        if (in_array($row['thematic_id'], $tmp)) {
                            $row['session_id'] = $thematic_plan_complete_list[$row['id']];
                            $data[$row['thematic_id']][$row['description_type']] = $row;
                        }
                    }
                } else {
                    while ($row = Database::fetch_array($rs, 'ASSOC')) {
                        $row['session_id'] = $thematic_plan_complete_list[$row['id']];
                        $data[] = $row;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * insert or update a thematic plan.
     *
     * @return int affected rows
     */
    public function thematic_plan_save()
    {
        $_course = api_get_course_info();
        // definition database table
        $tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

        // protect data
        $thematic_id = intval($this->thematic_id);
        $title = $this->thematic_plan_title;
        $description = $this->thematic_plan_description;
        $description_type = intval($this->thematic_plan_description_type);
        $user_id = api_get_user_id();
        $course_id = api_get_course_int_id();
        $list = api_get_item_property_by_tool(
            'thematic_plan',
            api_get_course_id(),
            api_get_session_id()
        );

        $elements_to_show = [];
        foreach ($list as $value) {
            $elements_to_show[] = $value['ref'];
        }
        $condition = '';
        if (!empty($elements_to_show)) {
            $condition = "AND id IN (".implode(',', $elements_to_show).") ";
        }
        // check thematic plan type already exists
        $sql = "SELECT id FROM $tbl_thematic_plan
                WHERE
                    c_id = $course_id AND
                    thematic_id = $thematic_id AND
                    description_type = '$description_type'";
        $rs = Database::query($sql);

        $affected_rows = 0;
        if (Database::num_rows($rs) > 0) {
            $row_thematic_plan = Database::fetch_array($rs);
            $thematic_plan_id = $row_thematic_plan['id'];
            $update = false;
            if (in_array($thematic_plan_id, $elements_to_show)) {
                $update = true;
            }

            if ($update) {
                // update
                $params = [
                    'title' => $title,
                    'description' => $description,
                ];
                Database::update(
                    $tbl_thematic_plan,
                    $params,
                    ['c_id = ? AND id = ?' => [$course_id, $thematic_plan_id]]
                );

                api_item_property_update(
                    $_course,
                    'thematic_plan',
                    $thematic_plan_id,
                    'ThematicPlanUpdated',
                    $user_id
                );
            } else {
                // insert
                $params = [
                    'c_id' => $this->course_int_id,
                    'thematic_id' => $thematic_id,
                    'title' => $title,
                    'description' => $description,
                    'description_type' => $description_type,
                ];
                $last_id = Database::insert($tbl_thematic_plan, $params);
                if ($last_id) {
                    $sql = "UPDATE $tbl_thematic_plan SET id = iid WHERE iid = $last_id";
                    Database::query($sql);
                    api_item_property_update(
                        $_course,
                        'thematic_plan',
                        $last_id,
                        'ThematicPlanAdded',
                        $user_id
                    );
                }
            }
        } else {
            // insert
            $params = [
                'c_id' => $this->course_int_id,
                'thematic_id' => $thematic_id,
                'title' => $title,
                'description' => $description,
                'description_type' => $description_type,
            ];
            $last_id = Database::insert($tbl_thematic_plan, $params);

            if ($last_id) {
                $sql = "UPDATE $tbl_thematic_plan SET id = iid WHERE iid = $last_id";
                Database::query($sql);
                api_item_property_update(
                    $_course,
                    'thematic_plan',
                    $last_id,
                    'ThematicPlanAdded',
                    $user_id
                );
            }
        }

        return $affected_rows;
    }

    /**
     * Delete a thematic plan description.
     *
     * @param int $thematic_id      Thematic id
     * @param int $description_type Description type
     *
     * @return int Affected rows
     */
    public function thematic_plan_destroy($thematic_id, $description_type)
    {
        $_course = api_get_course_info();
        // definition database table
        $tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

        // protect data
        $thematic_id = intval($thematic_id);
        $description_type = intval($description_type);
        $user_id = api_get_user_id();
        $course_info = api_get_course_info();
        $course_id = $course_info['real_id'];

        // get thematic plan id
        $thematic_plan_data = $this->get_thematic_plan_data($thematic_id, $description_type);
        $thematic_plan_id = $thematic_plan_data[0]['id'];

        // delete
        $sql = "DELETE FROM $tbl_thematic_plan
                WHERE
                    c_id = $course_id AND
                    thematic_id = $thematic_id AND
                    description_type = $description_type ";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);
        if ($affected_rows) {
            api_item_property_update(
                $_course,
                'thematic_plan',
                $thematic_plan_id,
                'ThematicPlanDeleted',
                $user_id
            );
        }

        return $affected_rows;
    }

    /**
     * Get next description type for a new thematic plan description (option 'others').
     *
     * @param int $thematic_id Thematic id
     *
     * @return int New Description type
     */
    public function get_next_description_type($thematic_id)
    {
        // definition database table
        $tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

        // protect data
        $thematic_id = intval($thematic_id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT MAX(description_type) as max
                FROM $tbl_thematic_plan
                WHERE
                    c_id = $course_id AND
                    thematic_id = $thematic_id AND
                    description_type >= ".ADD_THEMATIC_PLAN;
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        $last_description_type = $row['max'];

        if (isset($last_description_type)) {
            $next_description_type = $last_description_type + 1;
        } else {
            $next_description_type = ADD_THEMATIC_PLAN;
        }

        return $next_description_type;
    }

    /**
     * update done thematic advances from thematic details interface.
     *
     * @param int $thematic_advance_id
     *
     * @return int Affected rows
     */
    public function update_done_thematic_advances($thematic_advance_id)
    {
        $_course = api_get_course_info();
        $thematic_data = self::get_thematic_list(null, api_get_course_id());
        $thematic_advance_data = $this->get_thematic_advance_list(
            null,
            api_get_course_id(),
            true
        );
        $table = Database::get_course_table(TABLE_THEMATIC_ADVANCE);

        $affected_rows = 0;
        $user_id = api_get_user_id();

        $all = [];
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                if (!empty($thematic_advance_data[$thematic['id']])) {
                    foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
                        $all[] = $thematic_advance['id'];
                    }
                }
            }
        }
        $error = null;
        $a_thematic_advance_ids = [];
        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();

        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                $my_affected_rows = 0;
                $thematic_id = $thematic['id'];
                if (!empty($thematic_advance_data[$thematic['id']])) {
                    foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
                        $item_info = api_get_item_property_info(
                            api_get_course_int_id(),
                            'thematic_advance',
                            $thematic_advance['id'],
                            $sessionId
                        );

                        if ($item_info['session_id'] == $sessionId) {
                            $a_thematic_advance_ids[] = $thematic_advance['id'];
                            // update done thematic for previous advances ((done_advance = 1))
                            $upd = "UPDATE $table SET
                                    done_advance = 1
                                    WHERE c_id = $course_id AND id = ".$thematic_advance['id']." ";
                            $result = Database::query($upd);
                            $my_affected_rows = Database::affected_rows($result);
                            $affected_rows += $my_affected_rows;
                            //if ($my_affected_rows) {
                            api_item_property_update(
                                $_course,
                                'thematic_advance',
                                $thematic_advance['id'],
                                "ThematicAdvanceDone",
                                $user_id
                            );
                            //}
                            if ($thematic_advance['id'] == $thematic_advance_id) {
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // Update done thematic for others advances (done_advance = 0)
        if (!empty($a_thematic_advance_ids) && count($a_thematic_advance_ids) > 0) {
            $diff = array_diff($all, $a_thematic_advance_ids);
            if (!empty($diff)) {
                $upd = "UPDATE $table SET done_advance = 0
                        WHERE c_id = $course_id AND id IN(".implode(',', $diff).") ";
                Database::query($upd);
            }

            // update item_property
            $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
            $sql = "SELECT ref FROM $tbl_item_property
                    WHERE
                        c_id = $course_id AND
                        tool='thematic_advance' AND
                        lastedit_type='ThematicAdvanceDone' AND
                        session_id = $sessionId ";
            // get all thematic advance done
            $rs_thematic_done = Database::query($sql);
            if (Database::num_rows($rs_thematic_done) > 0) {
                while ($row_thematic_done = Database::fetch_array($rs_thematic_done)) {
                    $ref = $row_thematic_done['ref'];
                    if (in_array($ref, $a_thematic_advance_ids)) {
                        continue;
                    }
                    // update items
                    $sql = "UPDATE $tbl_item_property SET
                                lastedit_date='".api_get_utc_datetime()."',
                                lastedit_type='ThematicAdvanceUpdated',
                                lastedit_user_id = $user_id
                            WHERE
                                c_id = $course_id AND
                                tool='thematic_advance' AND
                                ref=$ref AND
                                session_id = $sessionId  ";
                    Database::query($sql);
                }
            }
        }

        return $affected_rows;
    }

    /**
     * Get last done thematic advance from thematic details interface.
     *
     * @return int Last done thematic advance id
     */
    public function get_last_done_thematic_advance()
    {
        $thematic_data = self::get_thematic_list();
        $thematic_advance_data = $this->get_thematic_advance_list(
            null,
            api_get_course_id(),
            true
        );

        $a_thematic_advance_ids = [];
        $last_done_advance_id = 0;
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                if (!empty($thematic_advance_data[$thematic['id']])) {
                    foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
                        if ($thematic_advance['done_advance'] == 1) {
                            $a_thematic_advance_ids[] = $thematic_advance['id'];
                        }
                    }
                }
            }
        }
        if (!empty($a_thematic_advance_ids)) {
            $last_done_advance_id = array_pop($a_thematic_advance_ids);
            $last_done_advance_id = intval($last_done_advance_id);
        }

        return $last_done_advance_id;
    }

    /**
     * Get next thematic advance not done from thematic details interface.
     *
     * @param   int Offset (if you want to get an item that is not directly the next)
     *
     * @return int next thematic advance not done
     */
    public function get_next_thematic_advance_not_done($offset = 1)
    {
        $thematic_data = self::get_thematic_list();
        $thematic_advance_data = $this->get_thematic_advance_list();
        $a_thematic_advance_ids = [];
        $next_advance_not_done = 0;
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                if (!empty($thematic_advance_data[$thematic['id']])) {
                    foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
                        if ($thematic_advance['done_advance'] == 0) {
                            $a_thematic_advance_ids[] = $thematic_advance['id'];
                        }
                    }
                }
            }
        }

        if (!empty($a_thematic_advance_ids)) {
            for ($i = 0; $i < $offset; $i++) {
                $next_advance_not_done = array_shift($a_thematic_advance_ids);
            }
            $next_advance_not_done = intval($next_advance_not_done);
        }

        return $next_advance_not_done;
    }

    /**
     * Get total average of thematic advances.
     *
     * @param string $course_code (optional)
     * @param int    $session_id  (optional)
     *
     * @return float Average of thematic advances
     */
    public function get_total_average_of_thematic_advances($course_code = null, $session_id = null)
    {
        if (empty($course_code)) {
            $course_code = api_get_course_id();
        }
        if (api_get_session_id()) {
            $thematic_data = self::get_thematic_list(null, $course_code);
        } else {
            $thematic_data = self::get_thematic_list(null, $course_code, 0);
        }
        $new_thematic_data = [];
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $item) {
                $new_thematic_data[] = $item;
            }
            $thematic_data = $new_thematic_data;
        }

        $a_average_of_advances_by_thematic = [];
        $total_average = 0;
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                $thematic_id = $thematic['id'];
                $a_average_of_advances_by_thematic[$thematic_id] = $this->get_average_of_advances_by_thematic(
                    $thematic_id,
                    $course_code
                );
            }
        }

        // calculate total average
        if (!empty($a_average_of_advances_by_thematic)) {
            $count_tematics = count($thematic_data);
            $score = array_sum($a_average_of_advances_by_thematic);
            $total_average = round(($score * 100) / ($count_tematics * 100));
        }

        return $total_average;
    }

    /**
     * Get average of advances by thematic.
     *
     * @param int Thematic id
     * @param string $course_code
     *
     * @return float Average of thematic advances
     */
    public function get_average_of_advances_by_thematic($thematic_id, $course_code = null)
    {
        $thematic_advance_data = $this->get_thematic_advance_by_thematic_id($thematic_id, $course_code);
        $average = 0;
        if (!empty($thematic_advance_data)) {
            // get all done advances by thematic
            $advances = [];
            $count_done_advances = 0;
            foreach ($thematic_advance_data as $thematic_advance) {
                if ($thematic_advance['done_advance'] == 1) {
                    $count_done_advances++;
                }
                $advances[] = $thematic_advance['done_advance'];
            }
            // calculate average by thematic
            $count_total_advances = count($advances);
            $average = round(($count_done_advances * 100) / $count_total_advances);
        }

        return $average;
    }

    /**
     * set attributes for fields of thematic table.
     *
     * @param    int        Thematic id
     * @param    string    Thematic title
     * @param    string    Thematic content
     * @param    int        Session id
     */
    public function set_thematic_attributes($id = null, $title = '', $content = '', $session_id = 0)
    {
        $this->thematic_id = $id;
        $this->thematic_title = $title;
        $this->thematic_content = $content;
        $this->session_id = $session_id;
    }

    /**
     * set attributes for fields of thematic_plan table.
     *
     * @param    int        Thematic id
     * @param    string    Thematic plan title
     * @param    string    Thematic plan description
     * @param    int        Thematic plan description type
     */
    public function set_thematic_plan_attributes(
        $thematic_id = 0,
        $title = '',
        $description = '',
        $description_type = 0
    ) {
        $this->thematic_id = $thematic_id;
        $this->thematic_plan_title = $title;
        $this->thematic_plan_description = $description;
        $this->thematic_plan_description_type = $description_type;
    }

    /**
     * set attributes for fields of thematic_advance table.
     *
     * @param int $id Thematic advance id
     * @param    int        Thematic id
     * @param    int        Attendance id
     * @param    string    Content
     * @param    string    Date and time
     * @param    int        Duration in hours
     */
    public function set_thematic_advance_attributes(
        $id = null,
        $thematic_id = 0,
        $attendance_id = 0,
        $content = '',
        $start_date = null,
        $duration = 0
    ) {
        $this->thematic_advance_id = $id;
        $this->thematic_id = $thematic_id;
        $this->attendance_id = $attendance_id;
        $this->thematic_advance_content = $content;
        $this->start_date = $start_date;
        $this->duration = $duration;
    }

    /**
     * set thematic id.
     *
     * @param    int     Thematic id
     */
    public function set_thematic_id($thematic_id)
    {
        $this->thematic_id = $thematic_id;
    }

    /**
     * get thematic id.
     *
     * @return int
     */
    public function get_thematic_id()
    {
        return $this->thematic_id;
    }

    /**
     * Get thematic plan titles by default.
     *
     * @return array
     */
    public function get_default_thematic_plan_title()
    {
        $default_thematic_plan_titles = [];
        $default_thematic_plan_titles[1] = get_lang('Objectives');
        $default_thematic_plan_titles[2] = get_lang('SkillToAcquire');
        $default_thematic_plan_titles[3] = get_lang('Methodology');
        $default_thematic_plan_titles[4] = get_lang('Infrastructure');
        $default_thematic_plan_titles[5] = get_lang('Assessment');
        $default_thematic_plan_titles[6] = get_lang('Others');

        return $default_thematic_plan_titles;
    }

    /**
     * Get thematic plan icons by default.
     *
     * @return array
     */
    public function get_default_thematic_plan_icon()
    {
        $default_thematic_plan_icon = [];
        $default_thematic_plan_icon[1] = 'icons/32/objective.png';
        $default_thematic_plan_icon[2] = 'icons/32/skills.png';
        $default_thematic_plan_icon[3] = 'icons/32/strategy.png';
        $default_thematic_plan_icon[4] = 'icons/32/laptop.png';
        $default_thematic_plan_icon[5] = 'icons/32/assessment.png';
        $default_thematic_plan_icon[6] = 'icons/32/wizard.png';

        return $default_thematic_plan_icon;
    }

    /**
     * Get questions by default for help.
     *
     * @return array
     */
    public function get_default_question()
    {
        $question = [];
        $question[1] = get_lang('ObjectivesQuestions');
        $question[2] = get_lang('SkillToAcquireQuestions');
        $question[3] = get_lang('MethodologyQuestions');
        $question[4] = get_lang('InfrastructureQuestions');
        $question[5] = get_lang('AssessmentQuestions');

        return $question;
    }
}

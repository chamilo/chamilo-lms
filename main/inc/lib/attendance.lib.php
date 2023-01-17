<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like library, provides functions for attendance tool.
 * It's also used like model to attendance_controller (MVC pattern).
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> improvements
 *
 * @package chamilo.attendance
 */

use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceResultComment;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;

class Attendance
{
    // constants
    public const DONE_ATTENDANCE_LOG_TYPE = 'done_attendance_sheet';
    public const UPDATED_ATTENDANCE_LOG_TYPE = 'updated_attendance_sheet';
    public const LOCKED_ATTENDANCE_LOG_TYPE = 'locked_attendance_sheet';
    public $category_id;
    private $session_id;
    private $course_id;
    private $date_time;
    private $name;
    private $description;
    private $attendance_qualify_title;
    private $attendance_weight;
    private $course_int_id;

    /**
     * Constructor.
     */
    public function __construct()
    {
        //$this->course_int_id = api_get_course_int_id();
    }

    /**
     * Get the total number of attendance inside current course and current session.
     *
     * @return int
     *
     * @see SortableTable#get_total_number_of_items()
     */
    public static function getNumberOfAttendances()
    {
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);
        $course_id = api_get_course_int_id();

        $active_plus = '';

        if ((isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'true') ||
            !api_is_allowed_to_edit(null, true)
        ) {
            $active_plus = ' AND att.active = 1';
        }

        $sql = "SELECT COUNT(att.id) AS total_number_of_items
                FROM $tbl_attendance att
                WHERE
                      c_id = $course_id AND
                      active <> 2 $active_plus $condition_session  ";
        /*$active = (int) $active;
        if ($active === 1 || $active === 0) {
            $sql .= "AND att.active = $active";
        }*/
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * Get attendance list only the id, name and attendance_qualify_max fields.
     *
     * @param int $course_id  course db name (optional)
     * @param int $session_id session id (optional)
     *
     * @return array attendances list
     */
    public function get_attendances_list($course_id = 0, $session_id = 0)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE);
        $course_id = (int) $course_id;
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $session_id = !empty($session_id) ? (int) $session_id : api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        // Get attendance data
        $sql = "SELECT id, name, attendance_qualify_max
                FROM $table
                WHERE c_id = $course_id AND active = 1 $condition_session ";
        $result = Database::query($sql);
        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $data[$row['id']] = $row;
            }
        }

        return $data;
    }

    /**
     * Get the attendances to display on the current page (fill the sortable-table).
     *
     * @param   int     offset of first user to recover
     * @param   int     Number of users to get
     * @param   int     Column to sort on
     * @param   string  Order (ASC,DESC)
     *
     * @see SortableTable#get_table_data($from)
     *
     * @return array
     */
    public static function get_attendance_data(
        $from,
        $number_of_items,
        $column,
        $direction
    ) {
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);
        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $active_plus = '';
        if ((isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'true') ||
            !api_is_allowed_to_edit(null, true)
        ) {
            $active_plus = ' AND att.active = 1';
        }

        $sql = "SELECT
                    att.id AS col0,
                    att.name AS col1,
                    att.description AS col2,
                    att.attendance_qualify_max AS col3,
                    att.locked AS col4,
                    att.active AS col5,
                    att.session_id
                FROM $tbl_attendance att
                WHERE
                    att.active <> 2 AND
                    c_id = $course_id $active_plus $condition_session
                ORDER BY col$column $direction
                LIMIT $from,$number_of_items ";

        $res = Database::query($sql);
        $attendances = [];
        $user_info = api_get_user_info();
        $allowDelete = api_get_setting('allow_delete_attendance');

        $student_param = '';
        $studentRequestId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
        if (api_is_drh() && !empty($studentRequestId)) {
            $student_param = '&student_id='.$studentRequestId;
        }

        while ($attendance = Database::fetch_row($res)) {
            $session_star = '';
            if ($session_id == $attendance[6]) {
                $session_star = api_get_session_image($session_id, $user_info['status']);
            }

            if ($attendance[5] == 1) {
                $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
                    api_get_user_id(),
                    api_get_course_info()
                ) || api_is_drh();
                if (api_is_allowed_to_edit(null, true) || $isDrhOfCourse) {
                    // Link to edit
                    $attendance[1] = '<a
                        href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance[0].$student_param.'">'.
                        Security::remove_XSS($attendance[1]).
                        '</a>'.
                        $session_star;
                } else {
                    // Link to view
                    $attendance[1] = '<a
                        href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list_no_edit&attendance_id='.$attendance[0].$student_param.'">'.
                        Security::remove_XSS($attendance[1]).
                        '</a>'.
                        $session_star;
                }
            } else {
                $attendance[1] = '<a
                    class="muted"
                    href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance[0].$student_param.'">'.
                    Security::remove_XSS($attendance[1]).
                    '</a>'.
                    $session_star;
            }

            if ($attendance[5] == 1) {
                $attendance[3] = '<center>'.$attendance[3].'</center>';
            } else {
                $attendance[3] = '<center><span class="muted">'.$attendance[3].'</span></center>';
            }

            $attendance[3] = '<center>'.$attendance[3].'</center>';
            if (api_is_allowed_to_edit(null, true)) {
                $actions = '<center>';
                if (api_is_platform_admin()) {
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_edit&attendance_id='.$attendance[0].'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>&nbsp;';
                    // Visible
                    if ($attendance[5] == 1) {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_set_invisible&attendance_id='.$attendance[0].'">'.
                            Display::return_icon('visible.png', get_lang('Hide'), [], ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_set_visible&attendance_id='.$attendance[0].'">'.
                            Display::return_icon('invisible.png', get_lang('Show'), [], ICON_SIZE_SMALL).'</a>';
                        $attendance[2] = '<span class="muted">'.$attendance[2].'</span>';
                    }
                    if ($allowDelete === 'true') {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_delete&attendance_id='.$attendance[0].'">'.
                            Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';
                    }
                } else {
                    $is_locked_attendance = self::is_locked_attendance($attendance[0]);
                    if ($is_locked_attendance) {
                        $actions .= Display::return_icon('edit_na.png', get_lang('Edit')).'&nbsp;';
                        $actions .= Display::return_icon('visible.png', get_lang('Hide'));
                    } else {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_edit&attendance_id='.$attendance[0].'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>&nbsp;';

                        if ($attendance[5] == 1) {
                            $actions .= ' <a href="index.php?'.api_get_cidreq().'&action=attendance_set_invisible&attendance_id='.$attendance[0].'">'.
                                Display::return_icon('visible.png', get_lang('Hide'), [], ICON_SIZE_SMALL).'</a>';
                        } else {
                            $actions .= ' <a href="index.php?'.api_get_cidreq().'&action=attendance_set_visible&attendance_id='.$attendance[0].'">'.
                                Display::return_icon('invisible.png', get_lang('Show'), [], ICON_SIZE_SMALL).'</a>';
                            $attendance[2] = '<span class="muted">'.$attendance[2].'</span>';
                        }
                        if ($allowDelete === 'true') {
                            $actions .= ' <a href="index.php?'.api_get_cidreq().'&action=attendance_delete&attendance_id='.$attendance[0].'">'.
                                Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';
                        }
                    }
                }

                // display lock/unlock icon
                $is_done_all_calendar = self::is_all_attendance_calendar_done($attendance[0]);

                if ($is_done_all_calendar) {
                    $locked = $attendance[4];
                    if ($locked == 0) {
                        if (api_is_platform_admin()) {
                            $message_alert = get_lang('AreYouSureToLockTheAttendance');
                        } else {
                            $message_alert = get_lang('UnlockMessageInformation');
                        }
                        $actions .= '&nbsp;<a onclick="javascript:if(!confirm(\''.$message_alert.'\')) return false;" href="index.php?'.api_get_cidreq().'&action=lock_attendance&attendance_id='.$attendance[0].'">'.
                            Display::return_icon('lock-open.png', get_lang('LockAttendance')).'</a>';
                    } else {
                        if (api_is_platform_admin()) {
                            $actions .= '&nbsp;<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToUnlockTheAttendance').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=unlock_attendance&attendance_id='.$attendance[0].'">'.
                                    Display::return_icon('lock-closed.png', get_lang('UnlockAttendance')).'</a>';
                        } else {
                            $actions .= '&nbsp;'.Display::return_icon('locked_na.png', get_lang('LockedAttendance'));
                        }
                    }
                }
                $actions .= '</center>';

                $attendances[] = [
                    $attendance[0],
                    $attendance[1],
                    Security::remove_XSS($attendance[2]),
                    $attendance[3],
                    $actions,
                ];
            } else {
                $attendance[0] = '&nbsp;';
                $attendances[] = [
                    $attendance[0],
                    $attendance[1],
                    Security::remove_XSS($attendance[2]),
                    $attendance[3],
                ];
            }
        }

        return $attendances;
    }

    /**
     * Get the attendances by id to display on the current page.
     *
     * @param int $attendanceId
     *
     * @return array attendance data
     */
    public function get_attendance_by_id($attendanceId)
    {
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $attendanceId = (int) $attendanceId;
        $course_id = api_get_course_int_id();
        $attendance_data = [];
        $sql = "SELECT * FROM $tbl_attendance
                WHERE c_id = $course_id AND id = '$attendanceId'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $attendance_data = $row;
            }
        }

        return $attendance_data;
    }

    /**
     * Add attendances sheet inside table. This is the *list of* dates, not
     * a specific date in itself.
     *
     * @param  bool   true for adding link in gradebook or false otherwise (optional)
     *
     * @return int last attendance id
     */
    public function attendance_add($link_to_gradebook = false)
    {
        $_course = api_get_course_info();
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $table_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $session_id = api_get_session_id();
        $user_id = api_get_user_id();
        $course_code = $_course['code'];
        $course_id = $_course['real_id'];
        $title_gradebook = $this->attendance_qualify_title;
        $value_calification = 0;
        $weight_calification = api_float_val($this->attendance_weight);

        $params = [
            'c_id' => $course_id,
            'name' => $this->name,
            'description' => $this->description,
            'attendance_qualify_title' => $title_gradebook,
            'attendance_weight' => $weight_calification,
            'session_id' => $session_id,
            'active' => 1,
            'attendance_qualify_max' => 0,
            'locked' => 0,
        ];
        $last_id = Database::insert($tbl_attendance, $params);

        if (!empty($last_id)) {
            $sql = "UPDATE $tbl_attendance SET id = iid WHERE iid = $last_id";
            Database::query($sql);

            api_item_property_update(
                $_course,
                TOOL_ATTENDANCE,
                $last_id,
                "AttendanceAdded",
                $user_id
            );
        }
        // add link to gradebook
        if ($link_to_gradebook && !empty($this->category_id)) {
            $description = '';
            $link_info = GradebookUtils::isResourceInCourseGradebook(
                $course_code,
                7,
                $last_id,
                $session_id
            );
            $link_id = $link_info['id'];
            if (!$link_info) {
                GradebookUtils::add_resource_to_course_gradebook(
                    $this->category_id,
                    $course_code,
                    7,
                    $last_id,
                    $title_gradebook,
                    $weight_calification,
                    $value_calification,
                    $description,
                    1,
                    $session_id
                );
            } else {
                Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE id='.$link_id.'');
            }
        }

        return $last_id;
    }

    /**
     * edit attendances inside table.
     *
     * @param int attendance id
     * @param bool true for adding link in gradebook or false otherwise (optional)
     *
     * @return int last id
     */
    public function attendance_edit($attendanceId, $link_to_gradebook = false)
    {
        $_course = api_get_course_info();
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $table_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

        $session_id = api_get_session_id();
        $user_id = api_get_user_id();
        $attendanceId = (int) $attendanceId;
        $course_code = $_course['code'];
        $course_id = $_course['real_id'];
        $title_gradebook = $this->attendance_qualify_title;
        $value_calification = 0;
        $weight_calification = api_float_val($this->attendance_weight);

        if (!empty($attendanceId)) {
            $params = [
                'name' => $this->name,
                'description' => $this->description,
                'attendance_qualify_title' => $title_gradebook,
                'attendance_weight' => $weight_calification,
            ];
            Database::update(
                $tbl_attendance,
                $params,
                ['c_id = ? AND id = ?' => [$course_id, $attendanceId]]
            );

            api_item_property_update(
                $_course,
                TOOL_ATTENDANCE,
                $attendanceId,
                "AttendanceUpdated",
                $user_id
            );

            // add link to gradebook
            if ($link_to_gradebook && !empty($this->category_id)) {
                $description = '';
                $link_info = GradebookUtils::isResourceInCourseGradebook(
                    $course_code,
                    7,
                    $attendanceId,
                    $session_id
                );
                if (!$link_info) {
                    GradebookUtils::add_resource_to_course_gradebook(
                        $this->category_id,
                        $course_code,
                        7,
                        $attendanceId,
                        $title_gradebook,
                        $weight_calification,
                        $value_calification,
                        $description,
                        1,
                        $session_id
                    );
                } else {
                    Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE id='.$link_info['id'].'');
                }
            }

            return $attendanceId;
        }

        return null;
    }

    /**
     * Restore attendance.
     *
     * @param int|array one or many attendances id
     *
     * @return int affected rows
     */
    public function attendance_restore($attendanceId)
    {
        $_course = api_get_course_info();
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $user_id = api_get_user_id();
        $course_id = $_course['real_id'];
        if (is_array($attendanceId)) {
            foreach ($attendanceId as $id) {
                $id = (int) $id;
                $sql = "UPDATE $tbl_attendance SET active = 1
                        WHERE c_id = $course_id AND id = '$id'";
                $result = Database::query($sql);
                $affected_rows = Database::affected_rows($result);
                if (!empty($affected_rows)) {
                    // update row item property table
                    api_item_property_update(
                        $_course,
                        TOOL_ATTENDANCE,
                        $id,
                        'restore',
                        $user_id
                    );
                }
            }
        } else {
            $attendanceId = (int) $attendanceId;
            $sql = "UPDATE $tbl_attendance SET active = 1
                    WHERE c_id = $course_id AND id = '$attendanceId'";
            $result = Database::query($sql);
            $affected_rows = Database::affected_rows($result);
            if (!empty($affected_rows)) {
                // update row item property table
                api_item_property_update(
                    $_course,
                    TOOL_ATTENDANCE,
                    $attendanceId,
                    'restore',
                    $user_id
                );
            }
        }

        return $affected_rows;
    }

    /**
     * Delete attendances.
     *
     * @param int|array $attendanceId one or many attendances id
     *
     * @return int affected rows
     */
    public function attendance_delete($attendanceId)
    {
        $_course = api_get_course_info();
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $user_id = api_get_user_id();
        $course_id = $_course['real_id'];

        if (is_array($attendanceId)) {
            foreach ($attendanceId as $id) {
                $id = intval($id);
                $sql = "UPDATE $tbl_attendance SET active = 2
                        WHERE c_id = $course_id AND id = '$id'";
                $result = Database::query($sql);
                $affected_rows = Database::affected_rows($result);
                if (!empty($affected_rows)) {
                    // update row item property table
                    api_item_property_update(
                        $_course,
                        TOOL_ATTENDANCE,
                        $id,
                        "delete",
                        $user_id
                    );
                }
            }
        } else {
            $attendanceId = intval($attendanceId);
            $sql = "UPDATE $tbl_attendance SET active = 2
                    WHERE c_id = $course_id AND id = '$attendanceId'";

            $result = Database::query($sql);
            $affected_rows = Database::affected_rows($result);
            if (!empty($affected_rows)) {
                // update row item property table
                api_item_property_update(
                    $_course,
                    TOOL_ATTENDANCE,
                    $attendanceId,
                    "delete",
                    $user_id
                );
            }
        }

        return $affected_rows;
    }

    /**
     * Changes visibility.
     *
     * @param int|array $attendanceId one or many attendances id
     * @param int       $status
     *
     * @return int affected rows
     */
    public function changeVisibility($attendanceId, $status = 1)
    {
        $_course = api_get_course_info();
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $user_id = api_get_user_id();
        $course_id = $_course['real_id'];
        $status = (int) $status;

        $action = 'visible';
        if ($status == 0) {
            $action = 'invisible';
        }

        if (is_array($attendanceId)) {
            foreach ($attendanceId as $id) {
                $id = (int) $id;
                $sql = "UPDATE $tbl_attendance SET active = $status
                        WHERE c_id = $course_id AND id = '$id'";
                $result = Database::query($sql);
                $affected_rows = Database::affected_rows($result);
                if (!empty($affected_rows)) {
                    // update row item property table
                    api_item_property_update(
                        $_course,
                        TOOL_ATTENDANCE,
                        $id,
                        $action,
                        $user_id
                    );
                }
            }
        } else {
            $attendanceId = (int) $attendanceId;
            $sql = "UPDATE $tbl_attendance SET active = $status
                    WHERE c_id = $course_id AND id = '$attendanceId'";
            $result = Database::query($sql);
            $affected_rows = Database::affected_rows($result);
            if (!empty($affected_rows)) {
                // update row item property table
                api_item_property_update(
                    $_course,
                    TOOL_ATTENDANCE,
                    $attendanceId,
                    $action,
                    $user_id
                );
            }
        }

        return $affected_rows;
    }

    /**
     * Lock or unlock an attendance.
     *
     * @param   int     attendance id
     * @param   bool    True to lock or false otherwise
     *
     * @return int
     */
    public function lock_attendance($attendanceId, $lock = true)
    {
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $course_id = api_get_course_int_id();
        $attendanceId = (int) $attendanceId;
        $locked = $lock ? 1 : 0;
        $upd = "UPDATE $tbl_attendance SET locked = $locked
                WHERE c_id = $course_id AND id = $attendanceId";
        $result = Database::query($upd);
        $affected_rows = Database::affected_rows($result);
        if ($affected_rows && $lock) {
            // Save attendance sheet log
            $this->saveAttendanceSheetLog(
                $attendanceId,
                api_get_utc_datetime(),
                self::LOCKED_ATTENDANCE_LOG_TYPE,
                api_get_user_id()
            );
        }

        return $affected_rows;
    }

    /**
     * Get registered users inside current course.
     *
     * @param int $attendanceId attendance id for showing attendance result field (optional)
     * @param int $groupId
     *
     * @return array users data
     */
    public function get_users_rel_course($attendanceId = 0, $groupId = 0)
    {
        $current_session_id = api_get_session_id();
        $current_course_id = api_get_course_id();
        $currentCourseIntId = api_get_course_int_id();
        $studentInGroup = [];

        if (!empty($current_session_id)) {
            $a_course_users = CourseManager::get_user_list_from_course_code(
                $current_course_id,
                $current_session_id,
                '',
                'lastname'
            );
        } else {
            $a_course_users = CourseManager::get_user_list_from_course_code(
                $current_course_id,
                0,
                '',
                'lastname'
            );
        }

        if (!empty($groupId)) {
            $groupInfo = GroupManager::get_group_properties($groupId);
            $students = GroupManager::getStudents($groupInfo['iid']);
            if (!empty($students)) {
                foreach ($students as $student) {
                    $studentInGroup[$student['user_id']] = true;
                }
            }
        }

        // get registered users inside current course
        $a_users = [];
        foreach ($a_course_users as $key => $user_data) {
            $value = [];
            $uid = $user_data['user_id'];
            $userInfo = api_get_user_info($uid);
            $status = $user_data['status'];

            if (!empty($groupId)) {
                if (!isset($studentInGroup[$uid])) {
                    continue;
                }
            }

            $user_status_in_session = null;
            $user_status_in_course = null;

            if (api_get_session_id()) {
                $user_status_in_session = SessionManager::get_user_status_in_course_session(
                    $uid,
                    $currentCourseIntId,
                    $current_session_id
                );
            } else {
                $user_status_in_course = CourseManager::getUserInCourseStatus(
                    $uid,
                    $currentCourseIntId
                );
            }

            // Not taking into account DRH or COURSEMANAGER
            if ($uid <= 1 ||
                $status == DRH ||
                $user_status_in_course == COURSEMANAGER ||
                $user_status_in_session == 2
            ) {
                continue;
            }

            if (!empty($attendanceId)) {
                $user_faults = $this->get_faults_of_user(
                    $uid,
                    $attendanceId,
                    $groupId
                );
                $value['attendance_result'] = $user_faults['faults'].'/'.$user_faults['total'].' ('.$user_faults['faults_porcent'].'%)';
                $value['result_color_bar'] = $user_faults['color_bar'];
            }

            $photo = Display::img(
                $userInfo['avatar_small'],
                $userInfo['complete_name'],
                [],
                false
            );

            $value['photo'] = $photo;
            $value['firstname'] = $user_data['firstname'];
            $value['lastname'] = $user_data['lastname'];
            $value['username'] = $user_data['username'];
            $value['user_id'] = $uid;

            // Sending only 5 items in the array instead of 60
            $a_users[$key] = $value;
        }

        return $a_users;
    }

    /**
     * add attendances sheet inside table.
     *
     * @param int   $calendar_id   attendance calendar id
     * @param array $users_present present users during current class
     * @param int   $attendanceId
     *
     * @return int affected rows
     */
    public function attendance_sheet_add($calendar_id, $users_present, $attendanceId)
    {
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);

        $calendar_id = (int) $calendar_id;
        $attendanceId = (int) $attendanceId;
        $users = $this->get_users_rel_course();
        $course_id = api_get_course_int_id();

        $user_ids = array_keys($users);
        $users_absent = array_diff($user_ids, $users_present);
        $affected_rows = 0;

        // get last edit type
        $calendar_data = $this->get_attendance_calendar_by_id($calendar_id);
        $lastedit_type = self::DONE_ATTENDANCE_LOG_TYPE;
        if ($calendar_data['done_attendance']) {
            $lastedit_type = self::UPDATED_ATTENDANCE_LOG_TYPE;
        }

        // save users present in class
        foreach ($users_present as $user_present) {
            $uid = (int) $user_present;
            // check if user already was registered with the $calendar_id
            $sql = "SELECT user_id FROM $tbl_attendance_sheet
                    WHERE c_id = $course_id AND user_id='$uid' AND attendance_calendar_id = '$calendar_id'";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) == 0) {
                $sql = "INSERT INTO $tbl_attendance_sheet SET
                        c_id = $course_id,
                        user_id = '$uid',
                        attendance_calendar_id = '$calendar_id',
                        presence = 1";
                $result = Database::query($sql);

                $affected_rows += Database::affected_rows($result);
            } else {
                $sql = "UPDATE $tbl_attendance_sheet SET presence = 1
                        WHERE
                            c_id = $course_id AND
                            user_id ='$uid' AND
                            attendance_calendar_id = '$calendar_id'
                        ";
                $result = Database::query($sql);
                $affected_rows += Database::affected_rows($result);
            }
        }

        // save users absent in class
        foreach ($users_absent as $user_absent) {
            $uid = intval($user_absent);
            // check if user already was registered with the $calendar_id
            $sql = "SELECT user_id FROM $tbl_attendance_sheet
                    WHERE c_id = $course_id AND user_id='$uid' AND attendance_calendar_id = '$calendar_id'";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) == 0) {
                $sql = "INSERT INTO $tbl_attendance_sheet SET
                        c_id = $course_id,
                        user_id ='$uid',
                        attendance_calendar_id = '$calendar_id',
                        presence = 0";
                $result = Database::query($sql);

                Database::insert_id();

                $affected_rows += Database::affected_rows($result);
            } else {
                $sql = "UPDATE $tbl_attendance_sheet SET presence = 0
                        WHERE
                            c_id = $course_id AND
                            user_id ='$uid' AND
                            attendance_calendar_id = '$calendar_id'";
                $result = Database::query($sql);
                $affected_rows += Database::affected_rows($result);
            }
        }

        // update done_attendance inside attendance calendar table
        $sql = "UPDATE $tbl_attendance_calendar SET done_attendance = 1
                WHERE  c_id = $course_id AND id = '$calendar_id'";
        Database::query($sql);

        // save users' results
        $this->updateUsersResults($user_ids, $attendanceId);

        if ($affected_rows) {
            //save attendance sheet log
            $this->saveAttendanceSheetLog(
                $attendanceId,
                api_get_utc_datetime(),
                $lastedit_type,
                api_get_user_id(),
                $calendar_data['date_time']
            );
        }

        return $affected_rows;
    }

    /**
     * update users' attendance results.
     *
     * @param array $user_ids     registered users inside current course
     * @param int   $attendanceId
     */
    public function updateUsersResults($user_ids, $attendanceId)
    {
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $course_id = api_get_course_int_id();
        $attendanceId = intval($attendanceId);
        // fill results about presence of students
        $attendance_calendar = $this->get_attendance_calendar(
            $attendanceId,
            'all',
            null,
            null,
            true
        );
        $calendar_ids = [];
        // get all dates from calendar by current attendance
        foreach ($attendance_calendar as $cal) {
            $calendar_ids[] = $cal['id'];
        }

        // get count of presences by users inside current attendance and save like results
        if (count($user_ids) > 0) {
            foreach ($user_ids as $uid) {
                $uid = (int) $uid;
                $count_presences = 0;
                if (count($calendar_ids) > 0) {
                    $sql = "SELECT count(presence) as count_presences
                            FROM $tbl_attendance_sheet
                            WHERE
                                c_id = $course_id AND
                                user_id = '$uid' AND
                                attendance_calendar_id IN (".implode(',', $calendar_ids).") AND
                                presence = 1";
                    $rs_count = Database::query($sql);
                    $row_count = Database::fetch_array($rs_count);
                    $count_presences = $row_count['count_presences'];
                }

                // save results
                $sql = "SELECT id FROM $tbl_attendance_result
                        WHERE
                            c_id = $course_id AND
                            user_id = '$uid' AND
                            attendance_id = '$attendanceId' ";
                $rs_check_result = Database::query($sql);

                if (Database::num_rows($rs_check_result) > 0) {
                    // update result
                    $sql = "UPDATE $tbl_attendance_result SET
                            score = '$count_presences'
                            WHERE
                                c_id = $course_id AND
                                user_id='$uid' AND
                                attendance_id='$attendanceId'";
                    Database::query($sql);
                } else {
                    // insert new result
                    $sql = "INSERT INTO $tbl_attendance_result SET
                            c_id = $course_id ,
                            user_id			= '$uid',
                            attendance_id 	= '$attendanceId',
                            score			= '$count_presences'";
                    Database::query($sql);

                    $insertId = Database::insert_id();
                    if ($insertId) {
                        $sql = "UPDATE $tbl_attendance_result SET id = iid WHERE iid = $insertId";
                        Database::query($sql);
                    }
                }
            }
        }

        // update attendance qualify max
        $count_done_calendar = self::get_done_attendance_calendar($attendanceId);
        $sql = "UPDATE $tbl_attendance SET
                    attendance_qualify_max = '$count_done_calendar'
                WHERE c_id = $course_id AND id = '$attendanceId'";
        Database::query($sql);
    }

    /**
     * update attendance_sheet_log table, is used as history of an attendance sheet.
     *
     * @param   int     Attendance id
     * @param   string  Last edit datetime
     * @param   string  Event type ('locked_attendance', 'done_attendance_sheet' ...)
     * @param   int     Last edit user id
     * @param   string  Calendar datetime value (optional, when event type is 'done_attendance_sheet')
     *
     * @return int Affected rows
     */
    public function saveAttendanceSheetLog(
        $attendanceId,
        $lastedit_date,
        $lastedit_type,
        $lastedit_user_id,
        $calendar_date_value = null
    ) {
        $course_id = api_get_course_int_id();

        // define table
        $tbl_attendance_sheet_log = Database::get_course_table(TABLE_ATTENDANCE_SHEET_LOG);

        // protect data
        $attendanceId = intval($attendanceId);
        $lastedit_user_id = intval($lastedit_user_id);

        if (isset($calendar_date_value)) {
            $calendar_date_value = $calendar_date_value;
        } else {
            $calendar_date_value = '';
        }

        // save data
        $params = [
            'c_id' => $course_id,
            'attendance_id' => $attendanceId,
            'lastedit_date' => $lastedit_date,
            'lastedit_type' => $lastedit_type,
            'lastedit_user_id' => $lastedit_user_id,
            'calendar_date_value' => $calendar_date_value,
        ];
        $insertId = Database::insert($tbl_attendance_sheet_log, $params);
        if ($insertId) {
            $sql = "UPDATE $tbl_attendance_sheet_log SET id = iid WHERE iid = $insertId";
            Database::query($sql);
        }

        return $insertId;
    }

    /**
     * Get number of done attendances inside current sheet.
     *
     * @param int attendance id
     *
     * @return int number of done attendances
     */
    public static function get_done_attendance_calendar($attendanceId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $attendanceId = intval($attendanceId);
        $course_id = api_get_course_int_id();
        $sql = "SELECT count(done_attendance) as count
                FROM $table
                WHERE
                    c_id = $course_id AND
                    attendance_id = '$attendanceId' AND
                    done_attendance = 1
                ";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        $count = $row['count'];

        return $count;
    }

    /**
     * Get results of faults (absents) by user.
     *
     * @param int $user_id
     * @param int $attendanceId
     * @param int $groupId
     *
     * @return array results containing number of faults, total done attendance,
     *               percent of faults and color depend on result (red, orange)
     */
    public function get_faults_of_user($user_id, $attendanceId, $groupId = null)
    {
        $user_id = intval($user_id);
        $attendanceId = intval($attendanceId);
        $results = [];
        $calendar_count = self::get_number_of_attendance_calendar(
            $attendanceId,
            $groupId,
            null,
            $user_id
        );
        // $total_done_attendance 	= $attendance_data['attendance_qualify_max'];
        $total_done_attendance = self::get_number_of_attendance_calendar(
            $attendanceId,
            $groupId,
            true,
            $user_id
        );
        $attendance_user_score = $this->get_user_score(
            $user_id,
            $attendanceId,
            $groupId
        );

        //This is the main change of the BT#1381
        //$total_done_attendance = $calendar_count;

        // calculate results
        $faults = $total_done_attendance - $attendance_user_score;

        if (empty($calendar_count)) {
            $faults = 0;
        }

        $faults = $faults > 0 ? $faults : 0;
        $faults_porcent = $calendar_count > 0 ? round(($faults * 100) / $calendar_count, 0) : 0;
        $results['faults'] = $faults;
        $results['total'] = $calendar_count;
        $results['faults_porcent'] = $faults_porcent;
        $color_bar = '';

        if ($faults_porcent > 25) {
            $color_bar = '#f28989';
        } elseif ($faults_porcent > 10) {
            $color_bar = '#F90';
        }
        $results['color_bar'] = $color_bar;

        return $results;
    }

    /**
     * Get results of faults average for all courses by user.
     *
     * @param int $user_id
     *
     * @return array results containing number of faults, total done attendance,
     *               percentage of faults and color depend on result (red, orange)
     */
    public function get_faults_average_inside_courses($user_id)
    {
        // get all courses of current user
        $courses = CourseManager::get_courses_list_by_user_id($user_id, true);
        $user_id = intval($user_id);
        $results = [];
        $total_faults = $total_weight = $porcent = 0;
        foreach ($courses as $course) {
            //$course_code = $course['code'];
            //$course_info = api_get_course_info($course_code);
            $course_id = $course['real_id'];
            $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
            $attendances_by_course = $this->get_attendances_list($course_id);

            foreach ($attendances_by_course as $attendance) {
                // get total faults and total weight
                $total_done_attendance = $attendance['attendance_qualify_max'];
                $sql = "SELECT score
                        FROM $tbl_attendance_result
                        WHERE
                            c_id = $course_id AND
                            user_id = $user_id AND
                            attendance_id = ".$attendance['id'];
                $rs = Database::query($sql);
                $score = 0;
                if (Database::num_rows($rs) > 0) {
                    $row = Database::fetch_array($rs);
                    $score = $row['score'];
                }
                $faults = $total_done_attendance - $score;
                $faults = $faults > 0 ? $faults : 0;
                $total_faults += $faults;
                $total_weight += $total_done_attendance;
            }
        }

        $porcent = $total_weight > 0 ? round(($total_faults * 100) / $total_weight, 0) : 0;
        $results['faults'] = $total_faults;
        $results['total'] = $total_weight;
        $results['porcent'] = $porcent;

        return $results;
    }

    /**
     * Get results of faults average by course.
     *
     * @param int    $user_id
     * @param string $course_code
     * @param int Session id (optional)
     *
     * @return array results containing number of faults,
     *               total done attendance, porcent of faults and color depend on result (red, orange)
     */
    public function get_faults_average_by_course(
        $user_id,
        $course_code,
        $session_id = null
    ) {
        // Database tables and variables
        $course_info = api_get_course_info($course_code);
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $user_id = intval($user_id);
        $results = [];
        $total_faults = $total_weight = $porcent = 0;
        $attendances_by_course = $this->get_attendances_list(
            $course_info['real_id'],
            $session_id
        );

        foreach ($attendances_by_course as $attendance) {
            // Get total faults and total weight
            $total_done_attendance = $attendance['attendance_qualify_max'];
            $sql = "SELECT score FROM $tbl_attendance_result
                    WHERE
                        c_id = {$course_info['real_id']} AND
                        user_id = $user_id AND
                        attendance_id=".$attendance['id'];
            $rs = Database::query($sql);
            $score = 0;
            if (Database::num_rows($rs) > 0) {
                $row = Database::fetch_array($rs);
                $score = $row['score'];
            }
            $faults = $total_done_attendance - $score;
            $faults = $faults > 0 ? $faults : 0;
            $total_faults += $faults;
            $total_weight += $total_done_attendance;
        }

        $porcent = $total_weight > 0 ? round(($total_faults * 100) / $total_weight, 0) : 0;
        $results['faults'] = $total_faults;
        $results['total'] = $total_weight;
        $results['porcent'] = $porcent;

        return $results;
    }

    /**
     * Get registered users' attendance sheet inside current course or course by id.
     *
     * @param int      $attendanceId
     * @param int      $user_id      for showing data for only one user (optional)
     * @param int      $groupId
     * @param int      $course_id    if id = 0 get the current course
     * @param DateTime $startDate    Filter atttendance sheet with a start date
     * @param DateTime $endDate      Filter atttendance sheet with a end date
     *
     * @return array users attendance sheet data
     */
    public function get_users_attendance_sheet(
        $attendanceId,
        $user_id = 0,
        $groupId = 0,
        $course_id = 0,
        DateTime $startDate = null,
        DateTime $endDate = null
    ) {
        //Get actual course or by course_id
        $course_id = (0 == $course_id) ? api_get_course_int_id() : (int) $course_id;
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $attendance_calendar = $this->get_attendance_calendar(
            $attendanceId,
            'all',
            null,
            $groupId,
            true,
            $course_id,
            $startDate,
            $endDate
        );
        $calendar_ids = [];
        // get all dates from calendar by current attendance
        foreach ($attendance_calendar as $cal) {
            $calendar_ids[] = $cal['id'];
        }

        $whereDate = '';
        if (!empty($startDate)) {
            $whereDate .= " AND cal.date_time >= '".$startDate->format('Y-m-d H:i:s')."'";
        }
        if (!empty($endDate)) {
            $whereDate .= " AND cal.date_time <= '".$endDate->format('Y-m-d H:i:s')."'";
        }

        // moved at start of this function
        // $course_id = api_get_course_int_id();

        $data = [];
        if (empty($user_id)) {
            // get all registered users inside current course
            $users = $this->get_users_rel_course();
            $user_ids = array_keys($users);
            if (count($calendar_ids) > 0 && count($user_ids) > 0) {
                foreach ($user_ids as $uid) {
                    $uid = (int) $uid;
                    $sql = "SELECT * FROM $tbl_attendance_sheet
                            WHERE
                                c_id = $course_id AND
                                user_id = '$uid' AND
                                attendance_calendar_id IN(".implode(',', $calendar_ids).")
                            ";
                    $res = Database::query($sql);
                    if (Database::num_rows($res) > 0) {
                        while ($row = Database::fetch_array($res)) {
                            $data[$uid][$row['attendance_calendar_id']]['presence'] = $row['presence'];
                        }
                    }
                }
            }
        } else {
            // Get attendance for current user
            $user_id = (int) $user_id;
            if (count($calendar_ids) > 0) {
                $sql = "SELECT cal.date_time, att.presence, cal.iid as calendar_id
                        FROM $tbl_attendance_sheet att
                        INNER JOIN  $tbl_attendance_calendar cal
                        ON cal.id = att.attendance_calendar_id
                        WHERE
                            att.c_id = $course_id AND
                            cal.c_id =  $course_id AND
                            att.user_id = '$user_id' AND
                            att.attendance_calendar_id IN (".implode(',', $calendar_ids).")
                            $whereDate
                        ORDER BY date_time";
                $res = Database::query($sql);
                if (Database::num_rows($res) > 0) {
                    while ($row = Database::fetch_array($res)) {
                        $row['duration'] = Attendance::getAttendanceCalendarExtraFieldValue('duration', $row['calendar_id']);
                        $row['date_time'] = api_convert_and_format_date($row['date_time'], null, date_default_timezone_get());
                        $data[$user_id][] = $row;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get next attendance calendar without presences (done attendances).
     *
     * @param int    attendance id
     *
     * @return int attendance calendar id
     */
    public function get_next_attendance_calendar_id($attendanceId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $attendanceId = (int) $attendanceId;
        $course_id = api_get_course_int_id();

        $sql = "SELECT id FROM $table
                WHERE
                    c_id = $course_id AND
                    attendance_id = '$attendanceId' AND
                    done_attendance = 0
                ORDER BY date_time
                LIMIT 1";
        $rs = Database::query($sql);
        $next_calendar_id = 0;
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs);
            $next_calendar_id = $row['id'];
        }

        return $next_calendar_id;
    }

    /**
     * Get next attendance calendar datetime without presences (done attendances).
     *
     * @param int    attendance id
     *
     * @return int UNIX time format datetime
     */
    public function getNextAttendanceCalendarDatetime($attendanceId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $course_id = api_get_course_int_id();
        $attendanceId = (int) $attendanceId;
        $sql = "SELECT id, date_time FROM $table
                WHERE
                    c_id = $course_id AND
                    attendance_id = '$attendanceId' AND
                    done_attendance = 0
                ORDER BY date_time
                LIMIT 1";
        $rs = Database::query($sql);
        $next_calendar_datetime = 0;
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs);
            $next_calendar_datetime = api_get_local_time($row['date_time']);
        }

        return $next_calendar_datetime;
    }

    /**
     * Get user's score from current attendance.
     *
     * @param int $user_id
     * @param int $attendanceId
     * @param int $groupId
     *
     * @return int score
     */
    public function get_user_score($user_id, $attendanceId, $groupId = 0)
    {
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
        $tbl_attendance_cal_rel_group = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);
        $tbl_attendance_cal = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $user_id = intval($user_id);
        $attendanceId = intval($attendanceId);
        $groupId = (int) $groupId;
        $course_id = api_get_course_int_id();

        if (empty($groupId)) {
            $sql = "SELECT score FROM $tbl_attendance_result
                    WHERE
                        c_id = $course_id AND
                        user_id='$user_id' AND
                        attendance_id='$attendanceId'";
        } else {
            $sql = "SELECT count(presence) as score FROM $tbl_attendance_sheet
                    WHERE
                        c_id = $course_id AND
                        user_id='$user_id' AND
                        presence = 1 AND
                        attendance_calendar_id IN (
                            SELECT calendar_id FROM $tbl_attendance_cal_rel_group crg
                            INNER JOIN $tbl_attendance_cal c
                            ON (crg.calendar_id = c.id)
                            WHERE
                                crg.c_id = $course_id AND
                                crg.group_id = $groupId AND
                                c.attendance_id = $attendanceId
                        )
                    ";
        }
        $rs = Database::query($sql);
        $score = 0;
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs);
            $score = $row['score'];
        }

        return $score;
    }

    /**
     * Get attendance calendar data by id.
     *
     * @param int    attendance calendar id
     *
     * @return array attendance calendar data
     */
    public function get_attendance_calendar_by_id($calendar_id)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $calendar_id = intval($calendar_id);
        $course_id = api_get_course_int_id();
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id AND id = '$calendar_id' ";
        $rs = Database::query($sql);
        $data = [];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $row['date_time'] = api_get_local_time($row['date_time']);
                $data = $row;
            }
        }

        return $data;
    }

    /**
     * Get all attendance calendar data inside current attendance or by course id.
     *
     * @param int      $attendanceId
     * @param string   $type
     * @param int      $calendar_id
     * @param int      $groupId
     * @param bool     $showAll      = false show group calendar items or not
     * @param int      $course_id
     * @param DateTime $startDate    Filter calendar with a start date
     * @param DateTime $endDate      Filter calendar with a end date
     *
     * @return array attendance calendar data
     */
    public function get_attendance_calendar(
        $attendanceId,
        $type = 'all',
        $calendar_id = null,
        $groupId = 0,
        $showAll = false,
        $course_id = 0,
        DateTime $startDate = null,
        DateTime $endDate = null
    ) {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $tbl_acrg = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);
        $attendanceId = intval($attendanceId);
        $course_id = (0 == $course_id) ? api_get_course_int_id() : (int) $course_id;
        $whereDate = '';
        if (!empty($startDate)) {
            $whereDate .= " AND c.date_time >= '".$startDate->format('Y-m-d H:i:s')."'";
        }
        if (!empty($endDate)) {
            $whereDate .= " AND c.date_time <= '".$endDate->format('Y-m-d H:i:s')."'";
        }
        if ($showAll) {
            $sql = "SELECT * FROM $tbl_attendance_calendar c
                    WHERE c_id = $course_id
                      AND attendance_id = '$attendanceId'
                        $whereDate";
        } else {
            $sql = "SELECT * FROM $tbl_attendance_calendar c
                    WHERE
                        c_id = $course_id AND
                        attendance_id = '$attendanceId' AND
                        id NOT IN (
                            SELECT calendar_id FROM $tbl_acrg
                            WHERE c_id = $course_id AND group_id != 0 AND group_id IS NOT NULL
                        )
                        $whereDate
                    ";
        }

        if (!empty($groupId)) {
            $groupId = intval($groupId);
            $sql = "SELECT c.* FROM $tbl_attendance_calendar c
                    INNER JOIN $tbl_acrg g
                    ON c.c_id = g.c_id AND c.id = g.calendar_id
                    WHERE
                        c.c_id = $course_id AND
                        g.group_id = '$groupId' AND
                        c.attendance_id = '$attendanceId'
                   ";
        }

        if (!in_array($type, ['today', 'all', 'all_done', 'all_not_done', 'calendar_id'])) {
            $type = 'all';
        }

        switch ($type) {
            case 'calendar_id':
                $calendar_id = intval($calendar_id);
                if (!empty($calendar_id)) {
                    $sql .= " AND c.id = $calendar_id";
                }
                break;
            case 'today':
                //$sql .= ' AND DATE_FORMAT(date_time,"%d-%m-%Y") = DATE_FORMAT("'.api_get_utc_datetime().'", "%d-%m-%Y" )';
                break;
            case 'all_done':
                $sql .= " AND done_attendance = 1 ";
                break;
            case 'all_not_done':
                $sql .= " AND done_attendance = 0 ";
                break;
            case 'all':
            default:
                break;
        }
        $sql .= " ORDER BY date_time ";

        $rs = Database::query($sql);
        $data = [];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                $row['db_date_time'] = $row['date_time'];
                $row['date_time'] = api_get_local_time($row['date_time']);
                $row['date'] = api_format_date($row['date_time'], DATE_FORMAT_SHORT);
                $row['time'] = api_format_date($row['date_time'], TIME_NO_SEC_FORMAT);
                $row['groups'] = $this->getGroupListByAttendanceCalendar($row['id'], $course_id);
                $row['duration'] = Attendance::getAttendanceCalendarExtraFieldValue('duration', $row['id']);
                if ($type == 'today') {
                    if (date('d-m-Y', api_strtotime($row['date_time'], 'UTC')) == date('d-m-Y', time())) {
                        $data[] = $row;
                    }
                } else {
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    /**
     * Get number of attendance calendar inside current attendance.
     *
     * @param int $attendanceId
     * @param int $groupId
     * @param $done_attendance
     * @param int $userId
     *
     * @return int number of dates in attendance calendar
     */
    public static function get_number_of_attendance_calendar(
        $attendanceId,
        $groupId = 0,
        $done_attendance = null,
        $userId = 0
    ) {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $calendarRelGroup = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);
        $tbl_groupRelUser = Database::get_course_table(TABLE_GROUP_USER);
        $attendanceId = intval($attendanceId);
        $groupId = intval($groupId);
        $course_id = api_get_course_int_id();

        $where_attendance = '';
        if ($done_attendance) {
            $where_attendance = ' done_attendance = 1 AND ';
        }
        if (empty($userId)) {
            if (empty($groupId)) {
                $sql = "SELECT count(a.id)
                        FROM $tbl_attendance_calendar a
                        WHERE
                            c_id = $course_id AND
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            id NOT IN (
                                SELECT calendar_id FROM $calendarRelGroup
                                WHERE
                                    c_id = $course_id AND
                                    group_id != 0 AND
                                    group_id IS NOT NULL
                            )
                        ";
            } else {
                $sql = "SELECT count(a.id)
                        FROM $tbl_attendance_calendar a
                        INNER JOIN $calendarRelGroup g
                        ON (a.id = g.calendar_id AND a.c_id = g.c_id)
                        WHERE
                            a.c_id = $course_id AND
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            group_id = $groupId
                        ";
            }
        } else {
            if (empty($groupId)) {
                $sql = "SELECT count(a.id)
                        FROM $tbl_attendance_calendar a
                        WHERE
                            c_id = $course_id AND
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            id NOT IN (
                                SELECT calendar_id FROM $calendarRelGroup
                                WHERE
                                    c_id = $course_id AND
                                    group_id != 0 AND
                                    group_id IS NOT NULL AND
                                    group_id NOT IN (
                                        SELECT group_id
                                        FROM $tbl_groupRelUser
                                        WHERE user_id = $userId
                                    )
                            )
                        ";
            } else {
                $sql = "SELECT count(a.id)
                        FROM $tbl_attendance_calendar a
                        INNER JOIN $calendarRelGroup g
                        ON (a.id = g.calendar_id AND a.c_id = g.c_id)
                        WHERE
                            a.c_id = $course_id AND
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            group_id = $groupId
                        ";
            }
        }

        $rs = Database::query($sql);
        $row = Database::fetch_row($rs);
        $count = $row[0];

        return $count;
    }

    /**
     * Get count dates inside attendance calendar by attendance id.
     *
     * @param int $attendanceId
     *
     * @return int count of dates
     */
    public static function get_count_dates_inside_attendance_calendar($attendanceId)
    {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $attendanceId = intval($attendanceId);
        $course_id = api_get_course_int_id();
        $sql = "SELECT count(id) FROM $tbl_attendance_calendar
                WHERE
                    c_id = $course_id AND
                    attendance_id = '$attendanceId'";
        $rs = Database::query($sql);
        $count = 0;
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_row($rs);
            $count = $row[0];
        }

        return $count;
    }

    /**
     * check if all calendar of an attendance is done.
     *
     * @param int $attendanceId
     *
     * @return bool True if all calendar is done, otherwise false
     */
    public static function is_all_attendance_calendar_done($attendanceId)
    {
        $attendanceId = (int) $attendanceId;
        $done_calendar = self::get_done_attendance_calendar($attendanceId);
        $count_dates_in_calendar = self::get_count_dates_inside_attendance_calendar($attendanceId);
        $number_of_dates = self::get_number_of_attendance_calendar($attendanceId);

        $result = false;
        if ($number_of_dates && intval($count_dates_in_calendar) == intval($done_calendar)) {
            $result = true;
        }

        return $result;
    }

    /**
     * check if an attendance is locked.
     *
     * @param int $attendanceId
     * @param bool
     *
     * @return bool
     */
    public static function is_locked_attendance($attendanceId)
    {
        //  Use gradebook lock
        $result = api_resource_is_locked_by_gradebook($attendanceId, LINK_ATTENDANCE);

        return $result;
    }

    /**
     * Add new datetime inside attendance calendar table.
     *
     * @param int   $attendanceId
     * @param array $groupList
     * @param array $extraValues
     *
     * @return int affected rows
     */
    public function attendance_calendar_add($attendanceId, $groupList = [], $extraValues = [])
    {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $affected_rows = 0;
        $attendanceId = intval($attendanceId);
        $course_id = api_get_course_int_id();
        // check if datetime already exists inside the table
        /*$sql = "SELECT id FROM $tbl_attendance_calendar
                WHERE
                    c_id = $course_id AND
                    date_time='".Database::escape_string($this->date_time)."' AND
                    attendance_id = '$attendanceId'";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) == 0) {*/
        $params = [
            'c_id' => $course_id,
            'date_time' => $this->date_time,
            'attendance_id' => $attendanceId,
            'done_attendance' => 0,
        ];
        $id = Database::insert($tbl_attendance_calendar, $params);

        if ($id) {
            $sql = "UPDATE $tbl_attendance_calendar SET id = iid WHERE iid = $id";
            Database::query($sql);
            $affected_rows++;

            if (!empty($extraValues)) {
                // It saves extra fields values
                $extraFieldValue = new ExtraFieldValue('attendance_calendar');
                $extraValues['item_id'] = $id;
                $extraFieldValue->saveFieldValues($extraValues);
            }
        }
        $this->addAttendanceCalendarToGroup($id, $course_id, $groupList);
        //}

        // update locked attendance
        $is_all_calendar_done = self::is_all_attendance_calendar_done($attendanceId);
        if (!$is_all_calendar_done) {
            self::lock_attendance($attendanceId, false);
        } else {
            self::lock_attendance($attendanceId);
        }

        return $affected_rows;
    }

    /**
     * @param int   $calendarId
     * @param int   $courseId
     * @param array $groupList
     *
     * @return bool
     */
    public function addAttendanceCalendarToGroup($calendarId, $courseId, $groupList)
    {
        if (empty($groupList)) {
            return false;
        }

        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);
        foreach ($groupList as $groupId) {
            if (empty($groupId)) {
                continue;
            }

            $result = $this->getAttendanceCalendarGroup(
                $calendarId,
                $courseId,
                $groupId
            );

            if (empty($result)) {
                $params = [
                    'calendar_id' => $calendarId,
                    'c_id' => $courseId,
                    'group_id' => $groupId,
                ];
                Database::insert($table, $params);
            }
        }

        return true;
    }

    /**
     * @param int $calendarId
     * @param int $courseId
     *
     * @return array
     */
    public function getGroupListByAttendanceCalendar($calendarId, $courseId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);

        return Database::select(
            '*',
            $table,
            [
                'where' => [
                    'calendar_id = ? AND c_id = ?' => [$calendarId, $courseId],
                ],
            ]
        );
    }

    /**
     * @param int $calendarId
     * @param int $courseId
     * @param int $groupId
     *
     * @return array
     */
    public function getAttendanceCalendarGroup($calendarId, $courseId, $groupId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);

        return Database::select(
            '*',
            $table,
            [
                'where' => [
                    'calendar_id = ? AND c_id = ? AND group_id = ?' => [$calendarId, $courseId, $groupId],
                ],
            ]
        );
    }

    /**
     * @param int $calendarId
     * @param int $courseId
     */
    public function deleteAttendanceCalendarGroup($calendarId, $courseId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);
        Database::delete(
            $table,
            [
                'calendar_id = ? AND c_id = ?' => [$calendarId, $courseId],
            ]
        );
    }

    /**
     * save repeated date inside attendance calendar table.
     *
     * @param int    $attendanceId
     * @param int    $start_date   start date in tms
     * @param int    $end_date     end date in tms
     * @param string $repeat_type  daily, weekly, biweekly, xdays, monthlyByDate
     * @param array  $groupList
     */
    public function attendance_repeat_calendar_add(
        $attendanceId,
        $start_date,
        $end_date,
        $repeat_type,
        $groupList = [],
        $extraValues = []
    ) {
        $attendanceId = intval($attendanceId);
        // save start date
        $datetimezone = api_get_utc_datetime($start_date);
        $this->set_date_time($datetimezone);
        $this->attendance_calendar_add($attendanceId, $groupList, $extraValues);

        // 86400 = 24 hours in seconds
        // 604800 = 1 week in seconds
        // 1296000 = 2 weeks in seconds
        // 2419200 = 1 month in seconds
        // 86400 x xdays = interval by x days (in seconds)
        // Saves repeated dates
        $seconds = 0;
        switch ($repeat_type) {
            case 'daily':
                $seconds = 86400;
                break;
            case 'weekly':
                $seconds = 604800;
                break;
            case 'biweekly':
                $seconds = 1296000;
                break;
            case 'monthlyByDate':
                $seconds = 2419200;
                break;
            case 'xdays':
                $seconds = isset($extraValues['xdays_number']) ? (int) $extraValues['xdays_number'] * 86400 : 0;
                break;
        }

        if ($seconds > 0) {
            $j = 1;
            for ($i = $start_date + $seconds; ($i <= $end_date); $i += $seconds) {
                $datetimezone = api_get_utc_datetime($i);
                $this->set_date_time($datetimezone);
                $this->attendance_calendar_add($attendanceId, $groupList, $extraValues);
                $j++;
            }
        }
    }

    /**
     * Gets the value of a attendance calendar extra field. Returns null if it was not found.
     *
     * @param string $variable   Name of the extra field
     * @param string $calendarId Calendar id
     *
     * @return string Value
     */
    public static function getAttendanceCalendarExtraFieldValue($variable, $calendarId)
    {
        if (true !== api_get_configuration_value('attendance_calendar_set_duration')) {
            return false;
        }

        $extraFieldValues = new ExtraFieldValue('attendance_calendar');
        $result = $extraFieldValues->get_values_by_handler_and_field_variable($calendarId, $variable);
        if (!empty($result['value'])) {
            return $result['value'];
        }

        return null;
    }

    /**
     * edit a datetime inside attendance calendar table.
     *
     * @param int	attendance calendar id
     * @param int	attendance id
     * @param array extra values
     *
     * @return int affected rows
     */
    public function attendance_calendar_edit($calendar_id, $attendanceId, $extraValues = [])
    {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $affected_rows = 0;
        $attendanceId = intval($attendanceId);
        $course_id = api_get_course_int_id();
        // check if datetime already exists inside the table
        $sql = "SELECT id FROM $tbl_attendance_calendar
                WHERE
                    c_id = $course_id AND
                    date_time = '".Database::escape_string($this->date_time)."' AND
                    attendance_id = '$attendanceId'";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) == 0) {
            $sql = "UPDATE $tbl_attendance_calendar
                    SET date_time='".Database::escape_string($this->date_time)."'
                    WHERE c_id = $course_id AND id = '".intval($calendar_id)."'";
            Database::query($sql);
        }

        // It saves extra fields values
        $extraFieldValue = new ExtraFieldValue('attendance_calendar');
        $extraValues['item_id'] = $calendar_id;
        $extraFieldValue->saveFieldValues($extraValues);

        // update locked attendance
        $is_all_calendar_done = self::is_all_attendance_calendar_done($attendanceId);
        if (!$is_all_calendar_done) {
            self::lock_attendance($attendanceId, false);
        } else {
            self::lock_attendance($attendanceId);
        }

        return $affected_rows;
    }

    /**
     * delete a datetime from attendance calendar table.
     *
     * @param	int		attendance calendar id
     * @param	int		attendance id
     * @param	bool true for removing all calendar inside current attendance, false for removing by calendar id
     *
     * @return int affected rows
     */
    public function attendance_calendar_delete(
        $calendar_id,
        $attendanceId,
        $all_delete = false
    ) {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);

        $attendanceId = intval($attendanceId);
        $calendar_id = (int) $calendar_id;

        // get all registered users inside current course
        $users = $this->get_users_rel_course();
        $user_ids = array_keys($users);
        $course_id = api_get_course_int_id();
        $affected_rows = 0;
        if ($all_delete) {
            $attendance_calendar = $this->get_attendance_calendar($attendanceId);
            // get all dates from calendar by current attendance
            if (!empty($attendance_calendar)) {
                foreach ($attendance_calendar as $cal) {
                    // delete all data from attendance sheet
                    $sql = "DELETE FROM $tbl_attendance_sheet
                            WHERE c_id = $course_id AND attendance_calendar_id = '".intval($cal['id'])."'";
                    Database::query($sql);
                    // delete data from attendance calendar
                    $sql = "DELETE FROM $tbl_attendance_calendar
                            WHERE c_id = $course_id AND id = '".intval($cal['id'])."'";
                    Database::query($sql);

                    $this->deleteAttendanceCalendarGroup($cal['id'], $course_id);
                    $affected_rows++;
                }
            }
        } else {
            // delete just one row from attendance sheet by the calendar id
            $sql = "DELETE FROM $tbl_attendance_sheet
                    WHERE c_id = $course_id AND attendance_calendar_id = '".$calendar_id."'";
            Database::query($sql);
            // delete data from attendance calendar
            $sql = "DELETE FROM $tbl_attendance_calendar
                    WHERE c_id = $course_id AND id = '".$calendar_id."'";
            Database::query($sql);

            $this->deleteAttendanceCalendarGroup($calendar_id, $course_id);
            $affected_rows++;
        }

        // update users' results
        $this->updateUsersResults($user_ids, $attendanceId);

        return $affected_rows;
    }

    public function set_session_id($sessionId)
    {
        $this->session_id = $sessionId;
    }

    public function set_course_id($course_id)
    {
        $this->course_id = $course_id;
    }

    public function set_date_time($datetime)
    {
        $this->date_time = $datetime;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function set_description($description)
    {
        $this->description = $description;
    }

    public function set_attendance_qualify_title($attendance_qualify_title)
    {
        $this->attendance_qualify_title = $attendance_qualify_title;
    }

    public function set_attendance_weight($attendance_weight)
    {
        $this->attendance_weight = $attendance_weight;
    }

    /** Getters for fields of attendances tables */
    public function get_session_id()
    {
        return $this->session_id;
    }

    public function get_course_id()
    {
        return $this->course_id;
    }

    public function get_date_time()
    {
        return $this->date_time;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_description()
    {
        return $this->description;
    }

    public function get_attendance_qualify_title()
    {
        return $this->attendance_qualify_title;
    }

    public function get_attendance_weight()
    {
        return $this->attendance_weight;
    }

    /**
     * @param string $startDate in UTC time
     * @param string $endDate   in UTC time
     *
     * @return array
     */
    public function getAttendanceLogin($startDate, $endDate)
    {
        if (empty($startDate) ||
            $startDate == '0000-00-00' ||
            $startDate == '0000-00-00 00:00:00' ||
            empty($endDate) ||
            $endDate == '0000-00-00' ||
            $endDate == '0000-00-00 00:00:00'
        ) {
            return false;
        }

        $sessionId = api_get_session_id();
        $courseCode = api_get_course_id();
        $courseId = api_get_course_int_id();

        if (!empty($sessionId)) {
            $users = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                '',
                'lastname',
                0
            );
        } else {
            $users = CourseManager::get_user_list_from_course_code(
                $courseCode,
                0,
                '',
                'lastname',
                STUDENT
            );
        }

        $dateTimeStartOriginal = new DateTime($startDate);
        $dateTimeStart = new DateTime($startDate);
        $dateTimeEnd = new DateTime($endDate);
        $interval = $dateTimeStart->diff($dateTimeEnd);
        $days = intval($interval->format('%a'));

        $dateList = [$dateTimeStart->format('Y-m-d')];
        $headers = [
            get_lang('User'),
            $dateTimeStart->format('Y-m-d'),
        ];

        for ($i = 0; $i < $days; $i++) {
            $dateTimeStart = $dateTimeStart->add(new DateInterval('P1D'));
            $date = $dateTimeStart->format('Y-m-d');
            $dateList[] = $date;
            $headers[] = $date;
        }

        $accessData = CourseManager::getCourseAccessPerCourseAndSession(
            $courseId,
            $sessionId,
            $dateTimeStartOriginal->format('Y-m-d H:i:s'),
            $dateTimeEnd->format('Y-m-d H:i:s')
        );

        $results = [];
        if (!empty($accessData)) {
            foreach ($accessData as $data) {
                $onlyDate = substr($data['login_course_date'], 0, 10);
                $results[$data['user_id']][$onlyDate] = true;
            }
        }

        return [
            'users' => $users,
            'dateList' => $dateList,
            'headers' => $headers,
            'results' => $results,
        ];
    }

    /**
     * @param string $startDate in UTC time
     * @param string $endDate   in UTC time
     *
     * @return string
     */
    public function getAttendanceLoginTable($startDate, $endDate)
    {
        $data = $this->getAttendanceLogin($startDate, $endDate);
        if (!$data) {
            return null;
        }

        $headers = $data['headers'];
        $dateList = $data['dateList'];
        $users = $data['users'];
        $results = $data['results'];

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }
        $row = 1;
        foreach ($users as $user) {
            $table->setCellContents(
                $row,
                0,
                $user['lastname'].' '.$user['firstname'].' ('.$user['username'].')'
            );
            $row++;
        }

        $column = 1;
        $row = 1;
        foreach ($users as $user) {
            foreach ($dateList as $date) {
                $status = null;
                if (isset($results[$user['user_id']]) &&
                    isset($results[$user['user_id']][$date])
                ) {
                    $status = 'X';
                }
                $table->setCellContents($row, $column, $status);
                $column++;
            }
            $row++;
            $column = 1;
        }

        return $table->toHtml();
    }

    /**
     * @param string $startDate in UTC time
     * @param string $endDate   in UTC time
     *
     * @return string
     */
    public function exportAttendanceLogin($startDate, $endDate)
    {
        $data = $this->getAttendanceLogin($startDate, $endDate);

        if (!$data) {
            return null;
        }
        $users = $data['users'];
        $results = $data['results'];

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaderContents(0, 0, get_lang('User'));
        $table->setHeaderContents(0, 1, get_lang('Date'));

        $row = 1;
        foreach ($users as $user) {
            $table->setCellContents(
                $row,
                0,
                $user['lastname'].' '.$user['firstname'].' ('.$user['username'].')'
            );
            $row++;
        }
        $table->setColAttributes(0, ['style' => 'width:28%']);

        $row = 1;
        foreach ($users as $user) {
            if (isset($results[$user['user_id']]) &&
                !empty($results[$user['user_id']])
            ) {
                $dates = implode(', ', array_keys($results[$user['user_id']]));
                $table->setCellContents($row, 1, $dates);
            }
            $row++;
        }

        $tableToString = $table->toHtml();
        $params = [
            'filename' => get_lang('Attendance').'_'.api_get_utc_datetime(),
            'pdf_title' => get_lang('Attendance'),
            'course_code' => api_get_course_id(),
            'show_real_course_teachers' => true,
        ];
        $pdf = new PDF('A4', null, $params);
        $pdf->html_to_pdf_with_template($tableToString);
    }

    /**
     * Return all course for a student between dates and order by key date (Y-m-d).
     *
     * @param int       $studentId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param bool      $orderDesc
     *
     * @return array
     */
    public function getCoursesWithAttendance(
        $studentId = 0,
        DateTime $startDate = null,
        DateTime $endDate = null,
        $orderDesc = false
    ) {
        // Lang variables
        $presentString = get_lang('Present');
        //$absentString = get_lang('Absent');
        $absentString = '-';
        // Lang variables
        $attendanceLib = new Attendance();
        $data = [];
        /*
         $specialCourses = CourseManager::returnSpecialCourses(
            $studentId, false, false
        );
         */
        $courses = CourseManager::returnCourses(
            $studentId,
            false,
            false
        );
        /* Get course with (in_category) and without (not_category) category */
        foreach ($courses as $courseData) {
            /*
             * $coursesKey can be in_category or not_category for courses
             * */
            $totalCoursesNoCategory = count($courseData);
            for ($i = 0; $i < $totalCoursesNoCategory; $i++) {
                $courseItem = $courseData[$i];
                $courseId = $courseItem['course_id'];

                /* Get all attendance by courses*/
                $attenances = $attendanceLib->get_attendances_list($courseId);
                $temp = [];
                $sheetsProccessed = [];
                $tempDate = [];
                foreach ($attenances as $attendanceData) {
                    $attendanceId = $attendanceData['id'];
                    $sheets = $attendanceLib->get_users_attendance_sheet(
                        $attendanceId,
                        $studentId,
                        0,
                        $courseId,
                        $startDate,
                        $endDate
                    );

                    $sheetsProccessed[] = [];
                    foreach ($sheets as $sheetData) {
                        $totalb = count($sheetData);
                        $tempDate = [];
                        for ($ii = 0; $ii < $totalb; $ii++) {
                            $attendancesProccess = $sheetData[$ii];
                            if (!empty($attendancesProccess)) {
                                $dateTemp = $attendancesProccess['0'];
                                $attendancesProccess[0] = $attendancesProccess[1];
                                $attendancesProccess[1] = $dateTemp;

                                $attendancesProccess[2] = $courseItem['title'];
                                $attendancesProccess['courseTitle'] = $courseItem['title'];

                                $attendancesProccess[3] = $courseItem['real_id'];
                                $attendancesProccess['courseId'] = $courseItem['real_id'];

                                $attendancesProccess[4] = $attendanceData['name'];
                                $attendancesProccess['attendanceName'] = $attendanceData['name'];
                                $attendancesProccess['courseCode'] = $courseItem['course_code'];

                                $attendancesProccess[5] = $attendanceData['id'];
                                $attendancesProccess['attendanceId'] = $attendanceData['id'];
                                if ($attendancesProccess['presence'] == 1) {
                                    $attendancesProccess['presence'] = $presentString;
                                    $attendancesProccess[0] = 1;
                                } else {
                                    $attendancesProccess['presence'] = $absentString;
                                    $attendancesProccess[0] = 0;
                                }
                                $attendancesProccess['session'] = 0;
                                $attendancesProccess['sessionName'] = '';
                                $tempDate[] = $attendancesProccess;
                                $dateKey = new DateTime($dateTemp);
                                /*
                                $attendancesProccess['teacher'] = '';
                                if(isset($courseItem['teachers']) and isset($courseItem['teachers'][0])){
                                    $attendancesProccess['teacher'] = $courseItem['teachers'][0]['fullname'];
                                }
                                */
                                $data[$dateKey->format('Y-m-d')][] = $attendancesProccess;
                            }
                        }
                    }
                    $sheetsProccessed[] = $tempDate;
                    $temp[] = $sheetsProccessed;
                }
                $courses['not_category'][$i]['attendanceSheet'] = $temp;
            }
        }

        /* Sessions */
        $studentId = (int) $studentId;
        $sql = "
            SELECT
                session_id,
                c_id
            FROM
                session_rel_course_rel_user
            WHERE
                user_id = $studentId";

        $rs = Database::query($sql);
        // get info from sessions
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $courseId = $row['c_id'];
            $sessionId = $row['session_id'];
            $courseItem = api_get_course_info_by_id($courseId);

            $attenances = $attendanceLib->get_attendances_list(
                $courseId,
                $sessionId
            );
            $temp = [];
            $sheetsProccessed = [];
            $tempDate = [];
            foreach ($attenances as $attendanceData) {
                $attendanceId = $attendanceData['id'];
                $sheets = $attendanceLib->get_users_attendance_sheet(
                    $attendanceId,
                    $studentId,
                    0,
                    $courseId,
                    $startDate,
                    $endDate
                );

                $sheetsProccessed[] = [];
                foreach ($sheets as $sheetData) {
                    $totalb = count($sheetData);
                    $tempDate = [];
                    for ($ii = 0; $ii < $totalb; $ii++) {
                        $work = $sheetData[$ii];
                        $attendancesProccess = $work;
                        if (!empty($attendancesProccess)) {
                            $dateTemp = $attendancesProccess['0'];
                            $attendancesProccess[0] = $attendancesProccess[1];
                            $attendancesProccess[1] = $dateTemp;
                            $attendancesProccess[2] = $courseItem['title'];
                            $attendancesProccess['courseTitle'] = $courseItem['title'];
                            $attendancesProccess[3] = $courseItem['real_id'];
                            $attendancesProccess['courseId'] = $courseItem['real_id'];
                            $attendancesProccess[4] = $attendanceData['name'];
                            $attendancesProccess['attendanceName'] = $attendanceData['name'];
                            $attendancesProccess[5] = $attendanceData['id'];
                            $attendancesProccess['attendanceId'] = $attendanceData['id'];
                            $attendancesProccess['courseCode'] = $courseItem['official_code'];
                            if ($attendancesProccess['presence'] == 1) {
                                $attendancesProccess['presence'] = $presentString;
                                $attendancesProccess[0] = 1;
                            } else {
                                $attendancesProccess['presence'] = $absentString;
                                $attendancesProccess[0] = 0;
                            }
                            $attendancesProccess['session'] = $sessionId;
                            $attendancesProccess['sessionName'] = api_get_session_name($sessionId);

                            $tempDate[] = $attendancesProccess;
                            $dateKey = new DateTime($dateTemp);
                            /*
                            $attendancesProccess['teacher'] = '';
                            if(isset($courseItem['tutor_name']) ){
                                $attendancesProccess['teacher'] = $courseItem['tutor_name'];
                            }
                            */
                            $data[$dateKey->format('Y-m-d')][] = $attendancesProccess;
                        }
                    }
                }
                $sheetsProccessed[] = $tempDate;
                $temp[] = $sheetsProccessed;
            }
            $courses['session'][$i]['attendanceSheet'] = $temp;
        }

        /* Order desc by date,  by default */
        if ($orderDesc == true) {
            ksort($data);
        } else {
            krsort($data);
        }

        return $data;
    }

    /**
     * Clean a sing of a user in attendance sheet.
     *
     * @param $userId
     * @param $attendanceCalendarId
     *
     * @return false or void when it is deleted.
     */
    public function deleteSignature(
        $userId,
        $attendanceCalendarId,
        $attendanceId
    ) {
        $allowSignature = api_get_configuration_value('enable_sign_attendance_sheet');
        if (!$allowSignature) {
            return false;
        }

        $courseId = api_get_course_int_id();
        $em = Database::getManager();
        $criteria = [
            'userId' => $userId,
            'attendanceCalendarId' => $attendanceCalendarId,
            'cId' => $courseId,
        ];

        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceSheet');
        $attendanceSheet = $repo->findOneBy($criteria);

        if ($attendanceSheet) {
            /** @var CAttendanceSheet $attendanceSheet */
            $attendanceSheet->setPresence(0);
            $attendanceSheet->setSignature('');

            $em->persist($attendanceSheet);
            $em->flush();
            $this->updateUsersResults([$userId], $attendanceId);
        }
    }

    /**
     * It checks if the calendar in a attendance sheet is blocked.
     *
     * @param $calendarId
     *
     * @return bool
     */
    public function isCalendarBlocked($calendarId)
    {
        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceCalendar');

        $blocked = 0;
        $attendanceCalendar = $repo->find($calendarId);
        /** @var CAttendanceCalendar $attendanceCalendar */
        if ($attendanceCalendar) {
            $blocked = (int) $attendanceCalendar->getBlocked();
        }

        $isBlocked = (1 === $blocked);

        return $isBlocked;
    }

    /**
     * It blocks/unblocks the calendar in attendance sheet.
     *
     * @param $calendarId
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateCalendarBlocked($calendarId)
    {
        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceCalendar');
        $attendanceCalendar = $repo->find($calendarId);

        /** @var CAttendanceCalendar $attendanceCalendar */
        if ($attendanceCalendar) {
            $blocked = !$this->isCalendarBlocked($calendarId);
            $attendanceCalendar->setBlocked($blocked);
            $em->persist($attendanceCalendar);
            $em->flush();
        }
    }

    /**
     * Get the user sign in attendance sheet.
     *
     * @param $userId
     * @param $attendanceCalendarId
     *
     * @return false|string
     */
    public function getSignature(
        $userId,
        $attendanceCalendarId
    ) {
        $allowSignature = api_get_configuration_value('enable_sign_attendance_sheet');
        if (!$allowSignature) {
            return false;
        }

        $courseId = api_get_course_int_id();
        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceSheet');

        $criteria = [
            'userId' => $userId,
            'attendanceCalendarId' => $attendanceCalendarId,
            'cId' => $courseId,
        ];
        $attendanceSheet = $repo->findOneBy($criteria);

        $signature = "";
        if ($attendanceSheet) {
            /** @var CAttendanceSheet $attendanceSheet */
            $signature = $attendanceSheet->getSignature();
        }

        return $signature;
    }

    /**
     * Exports the attendance sheet to Xls format.
     */
    public function exportAttendanceSheetToXls(
        int $attendanceId,
        int $studentId,
        string $courseCode,
        int $groupId,
        string $filterType,
        ?int $myCalendarId
    ) {
        $users = $this->get_users_rel_course($attendanceId, $groupId);
        $calendar = $this->get_attendance_calendar(
            $attendanceId,
            $filterType,
            $myCalendarId,
            $groupId
        );

        if (!empty($studentId)) {
            $userId = $studentId;
        } else {
            $userId = api_get_user_id();
        }
        $courseInfo = api_get_course_info($courseCode);

        $userPresence = [];
        if (api_is_allowed_to_edit(null, true) || api_is_drh()) {
            $userPresence = $this->get_users_attendance_sheet($attendanceId, 0, $groupId);
        } else {
            $userPresence = $this->get_users_attendance_sheet($attendanceId, $userId, $groupId);
        }

        // Set headers pdf.
        $teacherInfo = CourseManager::get_teacher_list_from_course_code($courseInfo['code']);
        $teacherName = null;
        foreach ($teacherInfo as $teacherData) {
            if ($teacherName != null) {
                $teacherName = $teacherName." / ";
            }
            $teacherName .= api_get_person_name($teacherData['firstname'], $teacherData['lastname']);
        }

        // Get data table
        $dataTable = [];
        $headTable = ['#', get_lang('Name')];
        foreach ($calendar as $classDay) {
            $labelDuration = !empty($classDay['duration']) ? get_lang('Duration').' : '.$classDay['duration'] : '';
            $headTable[] =
                api_format_date($classDay['date_time'], DATE_FORMAT_NUMBER_NO_YEAR).' '.
                api_format_date($classDay['date_time'], TIME_NO_SEC_FORMAT).' '.
                $labelDuration;
        }
        $dataTable[] = $headTable;
        $dataUsersPresence = $userPresence;
        $count = 1;

        if (!empty($users)) {
            foreach ($users as $user) {
                $cols = 1;
                $result = [];
                $result['count'] = $count;
                $result['full_name'] = api_get_person_name($user['firstname'], $user['lastname']);
                foreach ($calendar as $classDay) {
                    $commentInfo = $this->getComment($user['user_id'], $classDay['id']);
                    $comment = '';
                    if (!empty($commentInfo['comment'])) {
                        $comment .= $commentInfo['comment'];
                    }
                    if (!empty($commentInfo['author'])) {
                        $comment .= ' - '.get_lang('Author').': '.$commentInfo['author'];
                    }
                    $txtComment = !empty($comment) ? '[comment]'.$comment : '';
                    if (1 == (int) $classDay['done_attendance']) {
                        if (1 == (int) $dataUsersPresence[$user['user_id']][$classDay['id']]['presence']) {
                            $result[$classDay['id']] = get_lang('UserAttendedSymbol').$txtComment;
                        } else {
                            $result[$classDay['id']] = get_lang('UserNotAttendedSymbol').$txtComment;
                        }
                    } else {
                        $result[$classDay['id']] = ' ';
                    }
                    $cols++;
                }
                $count++;
                $dataTable[] = $result;
            }
        }

        $filename = get_lang('Attendance').'-'.api_get_local_time();
        Export::arrayToXlsAndComments($dataTable, $filename);

        exit;
    }

    /**
     * Get the user comment in attendance sheet.
     *
     * @return false|array The comment text and the author.
     */
    public function getComment(
        int $userId,
        int $attendanceCalendarId
    ) {
        $allowComment = api_get_configuration_value('attendance_allow_comments');
        if (!$allowComment) {
            return false;
        }

        $courseId = api_get_course_int_id();
        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceSheet');

        $criteria = [
            'userId' => $userId,
            'attendanceCalendarId' => $attendanceCalendarId,
            'cId' => $courseId,
        ];
        $attendanceSheet = $repo->findOneBy($criteria);

        $comment = "";
        if ($attendanceSheet) {
            /** @var CAttendanceSheet $attendanceSheet */
            $attendanceSheetId = $attendanceSheet->getIid();

            $criteria = [
                'userId' => $userId,
                'attendanceSheetId' => $attendanceSheetId,
            ];

            $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceResultComment');
            $attendanceResultComment = $repo->findOneBy($criteria);

            $author = '';
            /** @var CAttendanceResultComment $attendanceResultComment */
            if ($attendanceResultComment) {
                $comment = $attendanceResultComment->getComment();
                $authorId = $attendanceResultComment->getAuthorUserId();
                if (!empty($authorId)) {
                    $authorInfo = api_get_user_info($authorId);
                    $author = api_get_person_name($authorInfo['firstname'], $authorInfo['lastname']);
                }
            }
        }

        $commentInfo = [
            'comment' => $comment,
            'author' => $author,
        ];

        return $commentInfo;
    }

    /**
     * Saves the user comment from attendance sheet.
     *
     * @return false
     */
    public function saveComment(
        int $userId,
        int $attendanceCalendarId,
        string $comment,
        int $attendanceId
    ) {
        $allowComment = api_get_configuration_value('attendance_allow_comments');
        if (!$allowComment) {
            return false;
        }

        $courseId = api_get_course_int_id();
        $em = Database::getManager();
        $criteria = [
            'userId' => $userId,
            'attendanceCalendarId' => $attendanceCalendarId,
            'cId' => $courseId,
        ];

        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceSheet');
        $attendanceSheet = $repo->findOneBy($criteria);

        /** @var CAttendanceSheet $attendanceSheet */
        if (!$attendanceSheet) {
            $attendanceSheet = new CAttendanceSheet();
            $attendanceSheet
                ->setCId($courseId)
                ->setPresence(0)
                ->setUserId($userId)
                ->setAttendanceCalendarId($attendanceCalendarId);

            $em->persist($attendanceSheet);
            $em->flush();
        }
        $this->updateUsersResults([$userId], $attendanceId);

        $attendanceSheetId = $attendanceSheet->getIid();

        // It saves the comment in the current attendance sheet
        $criteria = [
            'userId' => $userId,
            'attendanceSheetId' => $attendanceSheetId,
        ];

        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceResultComment');
        $attendanceResultComment = $repo->findOneBy($criteria);

        /** @var CAttendanceResultComment $attendanceResultComment */
        if ($attendanceResultComment) {
            $attendanceResultComment->setComment($comment);
            $attendanceResultComment->setUpdatedAt(api_get_utc_datetime(null, false, true));
            $attendanceResultComment->setAuthorUserId(api_get_user_id());
            $em->persist($attendanceResultComment);
            $em->flush();
        } else {
            $attendanceResultComment = new CAttendanceResultComment();
            $attendanceResultComment
                ->setAttendanceSheetId($attendanceSheetId)
                ->setUserId($userId)
                ->setComment($comment)
                ->setAuthorUserId(api_get_user_id());

            $em->persist($attendanceResultComment);
            $em->flush();
        }
    }

    /**
     * It saves the user sign from attendance sheet.
     *
     * @param $userId
     * @param $attendanceCalendarId
     * @param $file string in base64
     *
     * @return false or void when it is saved.
     */
    public function saveSignature(
        $userId,
        $attendanceCalendarId,
        $file,
        $attendanceId
    ) {
        $allowSignature = api_get_configuration_value('enable_sign_attendance_sheet');
        if (!$allowSignature) {
            return false;
        }

        $courseId = api_get_course_int_id();
        $em = Database::getManager();
        $criteria = [
            'userId' => $userId,
            'attendanceCalendarId' => $attendanceCalendarId,
            'cId' => $courseId,
        ];

        $repo = $em->getRepository('ChamiloCourseBundle:CAttendanceSheet');
        $attendanceSheet = $repo->findOneBy($criteria);

        /** @var CAttendanceSheet $attendanceSheet */
        if ($attendanceSheet) {
            $attendanceSheet->setPresence(1);
            $attendanceSheet->setSignature($file);
            $em->persist($attendanceSheet);
            $em->flush();
        } else {
            $attendanceSheet = new CAttendanceSheet();
            $attendanceSheet
                ->setCId($courseId)
                ->setPresence(1)
                ->setUserId($userId)
                ->setAttendanceCalendarId($attendanceCalendarId)
                ->setSignature($file);

            $em->persist($attendanceSheet);
            $em->flush();
        }
        $this->updateUsersResults([$userId], $attendanceId);
    }
}

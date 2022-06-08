<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceSheetLog;
use Doctrine\Common\Collections\Criteria;

class Attendance
{
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

    /**
     * Get attendance list only the id, name and attendance_qualify_max fields.
     *
     * @return CAttendance[]
     */
    public function getAttendanceList(Course $course, Session $session = null)
    {
        $repo = Container::getAttendanceRepository();
        $qb = $repo->getResourcesByCourse($course, $session, null);
        //$qb->select('resource');
        $qb->andWhere('resource.active = 1');

        return $qb->getQuery()->getResult();

        /*$table = Database::get_course_table(TABLE_ATTENDANCE);
        $course_id = (int) $course_id;
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $session_id = !empty($session_id) ? (int) $session_id : api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        // Get attendance data
        $sql = "SELECT iid, name, attendance_qualify_max
                FROM $table
                WHERE active = 1 $condition_session ";
        $result = Database::query($sql);
        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $data[$row['iid']] = $row;
            }
        }

        return $data;*/
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
        $repo = Container::getAttendanceRepository();

        $course = api_get_course_entity(api_get_course_int_id());
        $session = api_get_session_entity(api_get_session_id());
        $group = api_get_group_entity(api_get_group_id());

        $qb = $repo->getResourcesByCourse($course, $session, $group);
        $qb->select('count(resource)');
        $qb->andWhere('resource.active <> 2');

        if ((isset($_GET['isStudentView']) && 'true' == $_GET['isStudentView']) ||
            !api_is_allowed_to_edit(null, true)
        ) {
            $qb
                ->andWhere('resource.active = :active')
                ->setParameter('active', 1);
            //$active_plus = ' AND att.active = 1';
        }

        return $qb->getQuery()->getSingleScalarResult();

        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $session_id = api_get_session_id();
        $condition_session = '';

        $active_plus = '';
        if ((isset($_GET['isStudentView']) && 'true' == $_GET['isStudentView']) ||
            !api_is_allowed_to_edit(null, true)
        ) {
            $active_plus = ' AND att.active = 1';
        }

        $sql = "SELECT COUNT(att.iid) AS total_number_of_items
                FROM $tbl_attendance att
                WHERE
                      active <> 2 $active_plus $condition_session  ";
        /*$active = (int) $active;
        if ($active === 1 || $active === 0) {
            $sql .= "AND att.active = $active";
        }*/
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return (int) $obj->total_number_of_items;
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
    public static function getAttendanceData(
        $from,
        $number_of_items,
        $column,
        $direction
    ) {
        $repo = Container::getAttendanceRepository();

        $course = api_get_course_entity(api_get_course_int_id());
        $session = api_get_session_entity(api_get_session_id());
        $group = api_get_group_entity(api_get_group_id());

        $qb = $repo->getResourcesByCourse($course, $session, $group);
        $qb->select('resource');
        $qb->andWhere('resource.active <> 2');

        if ((isset($_GET['isStudentView']) && 'true' == $_GET['isStudentView']) ||
            !api_is_allowed_to_edit(null, true)
        ) {
            $qb
                ->andWhere('resource.active = :active')
                ->setParameter('active', 1);
            //$active_plus = ' AND att.active = 1';
        }

        $qb
            ->setFirstResult($from)
            ->setMaxResults($number_of_items)
        ;

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $attendances = $qb->getQuery()->getResult();

        $allowDelete = api_get_setting('allow_delete_attendance');
        $student_param = '';
        $studentRequestId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
        if (api_is_drh() && !empty($studentRequestId)) {
            $student_param = '&student_id='.$studentRequestId;
        }

        $list = [];
        /** @var CAttendance $attendance */
        foreach ($attendances as $attendance) {
            $row = [];
            $id = $attendance->getIid();
            $name = $attendance->getName();
            $active = $attendance->getActive();
            $session_star = '';
            /*if ($session_id == $attendance[6]) {
                $session_star = api_get_session_image($session_id, $user_info['status']);
            }*/
            if (1 == $active) {
                $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
                    api_get_user_id(),
                    api_get_course_info()
                ) || api_is_drh();
                if ($isDrhOfCourse || api_is_allowed_to_edit(null, true)) {
                    // Link to edit
                    $row[1] = '<a href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$id.$student_param.'">'.$name.'</a>'.$session_star;
                } else {
                    // Link to view
                    $row[1] = '<a href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list_no_edit&attendance_id='.$id.$student_param.'">'.$name.'</a>'.$session_star;
                }
            } else {
                $row[1] = '<a class="muted" href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$id.$student_param.'">'.$name.'</a>'.$session_star;
            }

            if (1 == $active) {
                $row[3] = '<center>'.$attendance->getAttendanceQualifyMax().'</center>';
            } else {
                $row[3] = '<center><span class="muted">'.$attendance->getAttendanceQualifyMax().'</span></center>';
            }
            $row[2] = '';
            $row[3] = '<center>'.$row[3].'</center>';
            if (api_is_allowed_to_edit(null, true)) {
                $actions = '';
                $actions .= '<center>';
                if (api_is_platform_admin()) {
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_edit&attendance_id='.$id.'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>&nbsp;';
                    // Visible
                    if (1 == $active) {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_set_invisible&attendance_id='.$id.'">'.
                            Display::return_icon('visible.png', get_lang('Hide'), [], ICON_SIZE_SMALL).'</a>';
                    } else {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_set_visible&attendance_id='.$id.'">'.
                            Display::return_icon('invisible.png', get_lang('Show'), [], ICON_SIZE_SMALL).'</a>';
                        $row[2] = '<span class="muted">'.$attendance->getDescription().'</span>';
                    }
                    if ('true' === $allowDelete) {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_delete&attendance_id='.$id.'">'.
                            Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';
                    }
                } else {
                    $is_locked_attendance = self::is_locked_attendance($id);
                    if ($is_locked_attendance) {
                        $actions .= Display::return_icon('edit_na.png', get_lang('Edit')).'&nbsp;';
                        $actions .= Display::return_icon('visible.png', get_lang('Hide'));
                    } else {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_edit&attendance_id='.$id.'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>&nbsp;';

                        if (1 == $active) {
                            $actions .= ' <a href="index.php?'.api_get_cidreq().'&action=attendance_set_invisible&attendance_id='.$id.'">'.
                                Display::return_icon('visible.png', get_lang('Hide'), [], ICON_SIZE_SMALL).'</a>';
                        } else {
                            $actions .= ' <a href="index.php?'.api_get_cidreq().'&action=attendance_set_visible&attendance_id='.$id.'">'.
                                Display::return_icon('invisible.png', get_lang('Show'), [], ICON_SIZE_SMALL).'</a>';
                            $row[2] = '<span class="muted">'.$attendance->getDescription().'</span>';
                        }
                        if ('true' === $allowDelete) {
                            $actions .= ' <a href="index.php?'.api_get_cidreq().'&action=attendance_delete&attendance_id='.$id.'">'.
                                Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';
                        }
                    }
                }

                // display lock/unlock icon
                $is_done_all_calendar = self::is_all_attendance_calendar_done($attendance);

                if ($is_done_all_calendar) {
                    $locked = $attendance->getLocked();
                    if (0 == $locked) {
                        if (api_is_platform_admin()) {
                            $message_alert = get_lang('Are you sure you want to lock the attendance?');
                        } else {
                            $message_alert = get_lang('The attendance is not locked, which means your teacher is still able to modify it.');
                        }
                        $actions .= '&nbsp;<a
                            onclick="javascript:if(!confirm(\''.$message_alert.'\')) return false;"
                            href="index.php?'.api_get_cidreq().'&action=lock_attendance&attendance_id='.$id.'">'.
                            Display::return_icon('unlock.png', get_lang('Lock attendance')).'</a>';
                    } else {
                        if (api_is_platform_admin()) {
                            $actions .= '&nbsp;<a
                            onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to unlock the attendance?').'\')) return false;"
                            href="index.php?'.api_get_cidreq().'&action=unlock_attendance&attendance_id='.$id.'">'.
                                    Display::return_icon('lock.png', get_lang('Unlock attendance')).'</a>';
                        } else {
                            $actions .= '&nbsp;'.Display::return_icon('locked_na.png', get_lang('Locked attendance'));
                        }
                    }
                }
                $actions .= '</center>';

                $list[] = [
                    $id,
                    $row[1],
                    $row[2],
                    $row[3],
                    $actions,
                ];
            } else {
                $id = '&nbsp;';
                $list[] = [
                    $id,
                    $row[1],
                    $row[2],
                    $row[3],
                ];
            }
        }

        return $list;
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

        $attendance_data = [];
        $sql = "SELECT * FROM $tbl_attendance
                WHERE iid = $attendanceId";
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
        $table_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $session_id = api_get_session_id();
        $course_code = $_course['code'];
        $title_gradebook = $this->attendance_qualify_title;
        $value_calification = 0;
        $weight_calification = api_float_val($this->attendance_weight);
        $course = api_get_course_entity();
        $attendance = new CAttendance();
        $attendance
            ->setName($this->name)
            ->setDescription($this->description)
            ->setAttendanceQualifyTitle($title_gradebook)
            ->setAttendanceWeight($weight_calification)
            ->setParent($course)
            ->addCourseLink($course, api_get_session_entity())
        ;

        $repo = Container::getAttendanceRepository();
        $repo->create($attendance);
        $last_id = $attendance->getIid();

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
                Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE iid='.$link_id.'');
            }
        }

        return $last_id;
    }

    /**
     * edit attendances inside table.
     *
     * @param CAttendance $attendance
     * @param bool true for adding link in gradebook or false otherwise (optional)
     *
     * @return int last id
     */
    public function attendance_edit($attendance, $link_to_gradebook = false)
    {
        $_course = api_get_course_info();
        $table_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $session_id = api_get_session_id();
        $course_code = $_course['code'];
        $title_gradebook = $this->attendance_qualify_title;
        $value_calification = 0;
        $weight_calification = api_float_val($this->attendance_weight);

        if ($attendance) {
            $attendanceId = $attendance->getIid();
            $attendance
                ->setName($this->name)
                ->setDescription($this->description)
                ->setAttendanceQualifyTitle($title_gradebook)
                ->setAttendanceWeight($weight_calification)
            ;

            $repo = Container::getAttendanceRepository();
            $repo->update($attendance);

            /*$params = [
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
            );*/

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
                    Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE iid='.$link_info['id']);
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
                        WHERE iid = '$id'";
                $result = Database::query($sql);
                $affected_rows = Database::affected_rows($result);
                if (!empty($affected_rows)) {
                    // update row item property table
                    /*api_item_property_update(
                        $_course,
                        TOOL_ATTENDANCE,
                        $id,
                        'restore',
                        $user_id
                    );*/
                }
            }
        } else {
            $attendanceId = (int) $attendanceId;
            $sql = "UPDATE $tbl_attendance SET active = 1
                    WHERE iid = '$attendanceId'";
            $result = Database::query($sql);
            $affected_rows = Database::affected_rows($result);
            if (!empty($affected_rows)) {
                // update row item property table
                /*api_item_property_update(
                    $_course,
                    TOOL_ATTENDANCE,
                    $attendanceId,
                    'restore',
                    $user_id
                );*/
            }
        }

        return $affected_rows;
    }

    /**
     * Delete attendances.
     *
     * @param CAttendance $attendance one or many attendances id
     *
     * @return bool
     */
    public function attendance_delete(CAttendance $attendance)
    {
        $allowDeleteAttendance = api_get_setting('allow_delete_attendance');
        if ('true' !== $allowDeleteAttendance) {
            return false;
        }

        $repo = Container::getAttendanceRepository();
        $attendance->setActive(2);
        $repo->update($attendance);

        SkillModel::deleteSkillsFromItem($attendance->getIid(), ITEM_TYPE_ATTENDANCE);

        return true;

        /*$_course = api_get_course_info();
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $user_id = api_get_user_id();
        $course_id = $_course['real_id'];

        $attendanceId = (int) $attendanceId;
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
        }return $affected_rows;
        */
    }

    /**
     * Changes visibility.
     *
     * @param CAttendance $attendance one or many attendances id
     * @param int         $status
     *
     * @return int affected rows
     */
    public function changeVisibility(CAttendance $attendance, $status = 1)
    {
        $status = (int) $status;

        $repo = Container::getAttendanceRepository();
        $attendance->setActive($status);
        $repo->update($attendance);

        return true;
        /*

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

        return $affected_rows;
        */
    }

    /**
     * Lock or unlock an attendance.
     *
     * @param bool    True to lock or false otherwise
     *
     * @return bool
     */
    public function lock(CAttendance $attendance, $lock = true)
    {
        $repo = Container::getAttendanceRepository();
        $attendance->setLocked($lock);
        $repo->update($attendance);

        $this->saveAttendanceSheetLog(
            $attendance,
            api_get_utc_datetime(time(), false, true),
            self::LOCKED_ATTENDANCE_LOG_TYPE,
            api_get_user_entity()
        );

        return true;
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
            $a_course_users = CourseManager:: get_user_list_from_course_code(
                $current_course_id,
                $current_session_id,
                '',
                'lastname'
            );
        } else {
            $a_course_users = CourseManager:: get_user_list_from_course_code(
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
                DRH == $status ||
                COURSEMANAGER == $user_status_in_course ||
                2 == $user_status_in_session
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
     *
     * @return int affected rows
     */
    public function attendance_sheet_add($calendar_id, $users_present, CAttendance $attendance)
    {
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);

        $calendar_id = (int) $calendar_id;
        $users = $this->get_users_rel_course();
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
                    WHERE user_id = $uid AND attendance_calendar_id = $calendar_id";
            $rs = Database::query($sql);
            if (0 == Database::num_rows($rs)) {
                $sql = "INSERT INTO $tbl_attendance_sheet SET
                        user_id = '$uid',
                        attendance_calendar_id = '$calendar_id',
                        presence = 1";
                $result = Database::query($sql);

                $affected_rows += Database::affected_rows($result);
            } else {
                $sql = "UPDATE $tbl_attendance_sheet
                        SET presence = 1
                        WHERE
                            user_id = $uid AND
                            attendance_calendar_id = $calendar_id
                        ";
                $result = Database::query($sql);
                $affected_rows += Database::affected_rows($result);
            }
        }

        // save users absent in class
        foreach ($users_absent as $user_absent) {
            $uid = (int) $user_absent;
            // check if user already was registered with the $calendar_id
            $sql = "SELECT user_id FROM $tbl_attendance_sheet
                    WHERE user_id = $uid AND attendance_calendar_id = '$calendar_id'";
            $rs = Database::query($sql);
            if (0 == Database::num_rows($rs)) {
                $sql = "INSERT INTO $tbl_attendance_sheet SET
                        user_id ='$uid',
                        attendance_calendar_id = '$calendar_id',
                        presence = 0";
                $result = Database::query($sql);
                $affected_rows += Database::affected_rows($result);
            } else {
                $sql = "UPDATE $tbl_attendance_sheet SET presence = 0
                        WHERE
                            user_id ='$uid' AND
                            attendance_calendar_id = '$calendar_id'";
                $result = Database::query($sql);
                $affected_rows += Database::affected_rows($result);
            }
        }

        // update done_attendance inside attendance calendar table
        $sql = "UPDATE $tbl_attendance_calendar SET done_attendance = 1
                WHERE  iid = '$calendar_id'";
        Database::query($sql);

        // save users' results
        $this->updateUsersResults($user_ids, $attendance);

        if ($affected_rows) {
            //save attendance sheet log
            $this->saveAttendanceSheetLog(
                $attendance,
                api_get_utc_datetime(time(), false, true),
                $lastedit_type,
                api_get_user_entity(),
                api_get_utc_datetime($calendar_data['date_time'], true, true)
            );
        }

        return $affected_rows;
    }

    /**
     * update users' attendance results.
     *
     * @param array $user_ids registered users inside current course
     */
    public function updateUsersResults($user_ids, CAttendance $attendance)
    {
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $attendanceId = $attendance->getIid();
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
            $calendar_ids[] = $cal['iid'];
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
                                user_id = $uid AND
                                attendance_calendar_id IN (".implode(',', $calendar_ids).') AND
                                presence = 1';
                    $rs_count = Database::query($sql);
                    $row_count = Database::fetch_array($rs_count);
                    $count_presences = $row_count['count_presences'];
                }

                // save results
                $sql = "SELECT iid FROM $tbl_attendance_result
                        WHERE
                            user_id = '$uid' AND
                            attendance_id = '$attendanceId' ";
                $rs_check_result = Database::query($sql);

                if (Database::num_rows($rs_check_result) > 0) {
                    // update result
                    $sql = "UPDATE $tbl_attendance_result SET
                            score = '$count_presences'
                            WHERE
                                user_id='$uid' AND
                                attendance_id='$attendanceId'";
                    Database::query($sql);
                } else {
                    // insert new result
                    $sql = "INSERT INTO $tbl_attendance_result SET
                            user_id			= '$uid',
                            attendance_id 	= '$attendanceId',
                            score			= '$count_presences'";
                    Database::query($sql);
                    $insertId = Database::insert_id();
                }
            }
        }

        // update attendance qualify max
        $count_done_calendar = self::get_done_attendance_calendar($attendance);
        $sql = "UPDATE $tbl_attendance SET
                    attendance_qualify_max = '$count_done_calendar'
                WHERE iid = '$attendanceId'";
        Database::query($sql);
    }

    /**
     * update attendance_sheet_log table, is used as history of an attendance sheet.
     */
    public function saveAttendanceSheetLog(
        CAttendance $attendance,
        DateTime $lastEditDate,
        string $lastEditType,
        User $user,
        ?DateTime $dateValue = null
    ) {
        $log = new CAttendanceSheetLog();
        $log
            ->setAttendance($attendance)
            ->setUser($user)
            ->setCalendarDateValue($dateValue)
            ->setLasteditDate($lastEditDate)
            ->setLasteditType($lastEditType)
        ;
        $em = Database::getManager();
        $em->persist($log);
        $em->flush();
    }

    /**
     * Get number of done attendances inside current sheet.
     *
     * @return int number of done attendances
     */
    public static function get_done_attendance_calendar(CAttendance $attendance)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('doneAttendance', 1)
        );

        if (!$attendance->getCalendars()) {
            return 0;
        }

        return $attendance->getCalendars()->matching($criteria)->count();

        /*$table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $attendanceId = (int) $attendanceId;
        $course_id = api_get_course_int_id();
        $sql = "SELECT count(done_attendance) as count
                FROM $table
                WHERE
                    attendance_id = '$attendanceId' AND
                    done_attendance = 1
                ";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        $count = $row['count'];

        return $count;*/
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
        $user_id = (int) $user_id;
        $attendanceId = (int) $attendanceId;
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
        $attendance_user_score = $this->get_user_score($user_id, $attendanceId, $groupId);

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
        $user_id = (int) $user_id;
        $results = [];
        $total_faults = $total_weight = $porcent = 0;
        foreach ($courses as $course) {
            //$course_code = $course['code'];
            //$course_info = api_get_course_info($course_code);
            $course_id = $course['real_id'];
            $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
            $attendances = $this->getAttendanceList(api_get_course_entity($course_id));

            foreach ($attendances as $attendance) {
                $attendanceId = $attendance->getIid();
                // get total faults and total weight
                $total_done_attendance = $attendance->getAttendanceQualifyMax();
                $sql = "SELECT score
                        FROM $tbl_attendance_result
                        WHERE
                            user_id = $user_id AND
                            attendance_id = ".$attendanceId;
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

        $percent = $total_weight > 0 ? round(($total_faults * 100) / $total_weight, 0) : 0;
        $results['faults'] = $total_faults;
        $results['total'] = $total_weight;
        $results['percent'] = $percent;

        return $results;
    }

    /**
     * Get results of faults average by course.
     *
     * @return array results containing number of faults,
     *               total done attendance, percent of faults and color depend on result (red, orange)
     */
    public function get_faults_average_by_course($user_id, Course $course, Session $session = null)
    {
        // Database tables and variables
        $courseId = $course->getId();
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $user_id = (int) $user_id;
        $results = [];
        $total_faults = $total_weight = 0;
        $attendances = $this->getAttendanceList($course, $session);

        foreach ($attendances as $attendance) {
            $attendanceId = $attendance->getIid();
            // Get total faults and total weight
            $total_done_attendance = $attendance->getAttendanceQualifyMax();
            $sql = "SELECT score FROM $tbl_attendance_result
                    WHERE
                        user_id = $user_id AND
                        attendance_id=".$attendanceId;
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

        $percent = $total_weight > 0 ? round(($total_faults * 100) / $total_weight, 0) : 0;
        $results['faults'] = $total_faults;
        $results['total'] = $total_weight;
        $results['percent'] = $percent;

        return $results;
    }

    /**
     * Get registered users' attendance sheet inside current course.
     *
     * @param int $attendanceId
     * @param int $user_id      for showing data for only one user (optional)
     * @param int $groupId
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
            $calendar_ids[] = $cal['iid'];
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
                                user_id = '$uid' AND
                                attendance_calendar_id IN(".implode(',', $calendar_ids).')
                            ';
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
                $sql = "SELECT cal.date_time, att.presence
                        FROM $tbl_attendance_sheet att
                        INNER JOIN  $tbl_attendance_calendar cal
                        ON cal.iid = att.attendance_calendar_id
                        WHERE
                            att.user_id = $user_id AND
                            att.attendance_calendar_id IN (".implode(',', $calendar_ids).")
                            $whereDate
                        ORDER BY date_time";
                $res = Database::query($sql);
                if (Database::num_rows($res) > 0) {
                    while ($row = Database::fetch_array($res)) {
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

        $sql = "SELECT iid FROM $table
                WHERE
                    attendance_id = '$attendanceId' AND
                    done_attendance = 0
                ORDER BY date_time
                LIMIT 1";
        $rs = Database::query($sql);
        $next_calendar_id = 0;
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs);
            $next_calendar_id = $row['iid'];
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
        $sql = "SELECT iid, date_time FROM $table
                WHERE
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
        $user_id = (int) $user_id;
        $attendanceId = (int) $attendanceId;
        $groupId = (int) $groupId;
        $course_id = api_get_course_int_id();

        if (empty($groupId)) {
            $sql = "SELECT score FROM $tbl_attendance_result
                    WHERE
                        user_id='$user_id' AND
                        attendance_id='$attendanceId'";
        } else {
            $sql = "SELECT count(presence) as score
                    FROM $tbl_attendance_sheet
                    WHERE
                        user_id = '$user_id' AND
                        presence = 1 AND
                        attendance_calendar_id IN (
                            SELECT calendar_id FROM $tbl_attendance_cal_rel_group crg
                            INNER JOIN $tbl_attendance_cal c
                            ON (crg.calendar_id = c.iid)
                            WHERE
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
        $calendar_id = (int) $calendar_id;
        $course_id = api_get_course_int_id();
        $sql = "SELECT * FROM $table
                WHERE iid = '$calendar_id' ";
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
     * Get all attendance calendar data inside current attendance.
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
        $attendanceId = (int) $attendanceId;
        $course_id = 0 == $course_id ? api_get_course_int_id() : (int) $course_id;
        $whereDate = '';
        if (!empty($startDate)) {
            $whereDate .= " AND c.date_time >= '".$startDate->format('Y-m-d H:i:s')."'";
        }
        if (!empty($endDate)) {
            $whereDate .= " AND c.date_time <= '".$endDate->format('Y-m-d H:i:s')."'";
        }
        if ($showAll) {
            $sql = "SELECT * FROM $tbl_attendance_calendar c
                    WHERE
                        attendance_id = '$attendanceId'
                        $whereDate";
        } else {
            $sql = "SELECT * FROM $tbl_attendance_calendar c
                    WHERE
                        attendance_id = '$attendanceId' AND
                        iid NOT IN (
                            SELECT calendar_id FROM $tbl_acrg
                            WHERE attendance_id = '$attendanceId'  AND group_id != 0 AND group_id IS NOT NULL
                        )
                        $whereDate
                    ";
        }

        if (!empty($groupId)) {
            $groupId = (int) $groupId;
            $sql = "SELECT c.* FROM $tbl_attendance_calendar c
                    INNER JOIN $tbl_acrg g
                    ON c.iid = g.calendar_id
                    WHERE
                        g.group_id = '$groupId' AND
                        c.attendance_id = '$attendanceId'
                   ";
        }

        if (!in_array($type, ['today', 'all', 'all_done', 'all_not_done', 'calendar_id'])) {
            $type = 'all';
        }

        switch ($type) {
            case 'calendar_id':
                $calendar_id = (int) $calendar_id;
                if (!empty($calendar_id)) {
                    $sql .= " AND c.iid = $calendar_id";
                }
                break;
            case 'today':
                //$sql .= ' AND DATE_FORMAT(date_time,"%d-%m-%Y") = DATE_FORMAT("'.api_get_utc_datetime().'", "%d-%m-%Y" )';
                break;
            case 'all_done':
                $sql .= ' AND done_attendance = 1 ';
                break;
            case 'all_not_done':
                $sql .= ' AND done_attendance = 0 ';
                break;
            case 'all':
            default:
                break;
        }
        $sql .= ' ORDER BY date_time ';

        $rs = Database::query($sql);
        $data = [];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                $row['db_date_time'] = $row['date_time'];
                $row['date_time'] = api_get_local_time($row['date_time']);
                $row['date'] = api_format_date($row['date_time'], DATE_FORMAT_SHORT);
                $row['time'] = api_format_date($row['date_time'], TIME_NO_SEC_FORMAT);
                $row['groups'] = $this->getGroupListByAttendanceCalendar($row['iid'], $course_id);
                if ('today' === $type) {
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
        $attendanceId = (int) $attendanceId;
        $groupId = (int) $groupId;
        $course_id = api_get_course_int_id();

        $where_attendance = '';
        if ($done_attendance) {
            $where_attendance = ' done_attendance = 1 AND ';
        }
        if (empty($userId)) {
            if (empty($groupId)) {
                $sql = "SELECT count(a.iid)
                        FROM $tbl_attendance_calendar a
                        WHERE
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            iid NOT IN (
                                SELECT calendar_id FROM $calendarRelGroup
                                WHERE
                                    attendance_id = $attendanceId AND
                                    group_id != 0 AND
                                    group_id IS NOT NULL
                            )
                        ";
            } else {
                $sql = "SELECT count(a.iid)
                        FROM $tbl_attendance_calendar a
                        INNER JOIN $calendarRelGroup g
                        ON (a.iid = g.calendar_id)
                        WHERE
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            group_id = $groupId
                        ";
            }
        } else {
            if (empty($groupId)) {
                $sql = "SELECT count(a.iid)
                        FROM $tbl_attendance_calendar a
                        WHERE
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            iid NOT IN (
                                SELECT calendar_id FROM $calendarRelGroup
                                WHERE
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
                $sql = "SELECT count(a.iid)
                        FROM $tbl_attendance_calendar a
                        INNER JOIN $calendarRelGroup g
                        ON (a.iid = g.calendar_id)
                        WHERE
                            $where_attendance
                            attendance_id = '$attendanceId' AND
                            group_id = $groupId
                        ";
            }
        }

        $rs = Database::query($sql);
        $row = Database::fetch_row($rs);

        return $row[0];
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
        $attendanceId = (int) $attendanceId;
        $course_id = api_get_course_int_id();
        $sql = "SELECT count(iid) FROM $tbl_attendance_calendar
                WHERE
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
     * @return bool True if all calendar is done, otherwise false
     */
    public static function is_all_attendance_calendar_done(CAttendance $attendance)
    {
        $done_calendar = self::get_done_attendance_calendar($attendance);
        //$count_dates_in_calendar = self::get_count_dates_inside_attendance_calendar($attendanceId);
        $count_dates_in_calendar = 0;
        if ($attendance->getCalendars()) {
            $count_dates_in_calendar = $attendance->getCalendars()->count();
        }

        //$number_of_dates = self::get_number_of_attendance_calendar($attendanceId);

        $result = false;
        if ($count_dates_in_calendar == $done_calendar) {
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
        return api_resource_is_locked_by_gradebook($attendanceId, LINK_ATTENDANCE);
    }

    /**
     * Add new datetime inside attendance calendar table.
     *
     * @param CAttendance $attendance
     * @param array       $groupList
     *
     * @return int affected rows
     */
    public function attendance_calendar_add($attendance, $groupList = [])
    {
        $course_id = api_get_course_int_id();
        $calendar = new CAttendanceCalendar();
        $calendar
            ->setAttendance($attendance)
            ->setDateTime(new Datetime($this->date_time))
            ->setDoneAttendance(false);

        $em = Database::getManager();
        $em->persist($calendar);
        $em->flush();

        $this->addAttendanceCalendarToGroup($calendar->getIid(), $course_id, $groupList);

        // update locked attendance
        $is_all_calendar_done = $this->is_all_attendance_calendar_done($attendance);
        if (!$is_all_calendar_done) {
            $this->lock($attendance, false);
        } else {
            $this->lock($attendance);
        }

        return true;
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
                    'group_id' => $groupId,
                ];
                Database::insert($table, $params);
            }
        }

        return true;
    }

    /**
     * @param int $calendarId
     *
     * @return array
     */
    public function getGroupListByAttendanceCalendar($calendarId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);

        return Database::select(
            '*',
            $table,
            [
                'where' => [
                    'calendar_id = ? ' => [$calendarId],
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
    public function getAttendanceCalendarGroup($calendarId, $groupId)
    {
        $table = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR_REL_GROUP);

        return Database::select(
            '*',
            $table,
            [
                'where' => [
                    'calendar_id = ? AND group_id = ?' => [$calendarId, $groupId],
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
                'calendar_id = ?' => [$calendarId, $courseId],
            ]
        );
    }

    /**
     * save repeated date inside attendance calendar table.
     *
     * @param CAttendance $attendance
     * @param int         $start_date  start date in tms
     * @param int         $end_date    end date in tms
     * @param string      $repeat_type daily, weekly, monthlyByDate
     * @param array       $groupList
     */
    public function attendance_repeat_calendar_add(
        $attendance,
        $start_date,
        $end_date,
        $repeat_type,
        $groupList = []
    ) {
        $attendanceId = $attendance->getIid();
        // save start date
        $this->set_date_time(api_get_utc_datetime($start_date));
        $this->attendance_calendar_add($attendance, $groupList);

        // 86400 = 24 hours in seconds
        // 604800 = 1 week in seconds
        // Saves repeated dates
        switch ($repeat_type) {
            case 'daily':
                $j = 1;
                for ($i = $start_date + 86400; ($i <= $end_date); $i += 86400) {
                    $this->set_date_time(api_get_utc_datetime($i));
                    $this->attendance_calendar_add($attendance, $groupList);
                    $j++;
                }
                break;
            case 'weekly':
                $j = 1;
                for ($i = $start_date + 604800; ($i <= $end_date); $i += 604800) {
                    $this->set_date_time(api_get_utc_datetime($i));
                    $this->attendance_calendar_add($attendance, $groupList);
                    $j++;
                }
                break;
            case 'monthlyByDate':
                $j = 1;
                //@todo fix bug with february
                for ($i = $start_date + 2419200; ($i <= $end_date); $i += 2419200) {
                    $this->set_date_time(api_get_utc_datetime($i));
                    $this->attendance_calendar_add($attendanceId, $groupList);
                    $j++;
                }
                break;
        }
    }

    /**
     * edit a datetime inside attendance calendar table.
     *
     * @param int         $calendar_id
     * @param CAttendance $attendance
     *
     * @return int affected rows
     */
    public function attendance_calendar_edit($calendar_id, $attendance)
    {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $affected_rows = 0;
        $attendanceId = $attendance->getIid();

        $course_id = api_get_course_int_id();
        // check if datetime already exists inside the table
        $sql = "SELECT iid FROM $tbl_attendance_calendar
                WHERE
                    date_time = '".Database::escape_string($this->date_time)."' AND
                    attendance_id = '$attendanceId'";
        $rs = Database::query($sql);

        if (0 == Database::num_rows($rs)) {
            $sql = "UPDATE $tbl_attendance_calendar
                    SET date_time='".Database::escape_string($this->date_time)."'
                    WHERE iid = '".$calendar_id."'";
            Database::query($sql);
        }

        // update locked attendance
        $is_all_calendar_done = self::is_all_attendance_calendar_done($attendance);
        if (!$is_all_calendar_done) {
            $this->lock($attendance, false);
        } else {
            $this->lock($attendance);
        }

        return $affected_rows;
    }

    /**
     * delete a datetime from attendance calendar table.
     *
     * @param int        attendance calendar id
     * @param CAttendance        attendance
     * @param bool true for removing all calendar inside current attendance, false for removing by calendar id
     *
     * @return int affected rows
     */
    public function attendance_calendar_delete($calendar_id, CAttendance $attendance, $all_delete = false)
    {
        $tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);

        $attendanceId = $attendance->getIid();
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
                            WHERE attendance_calendar_id = '".intval($cal['iid'])."'";
                    Database::query($sql);
                    // delete data from attendance calendar
                    $sql = "DELETE FROM $tbl_attendance_calendar
                            WHERE iid = '".intval($cal['iid'])."'";
                    Database::query($sql);

                    $this->deleteAttendanceCalendarGroup($cal['iid'], $course_id);
                    $affected_rows++;
                }
            }
        } else {
            // delete just one row from attendance sheet by the calendar id
            $sql = "DELETE FROM $tbl_attendance_sheet
                    WHERE attendance_calendar_id = '".$calendar_id."'";
            Database::query($sql);
            // delete data from attendance calendar
            $sql = "DELETE FROM $tbl_attendance_calendar
                    WHERE iid = '".$calendar_id."'";
            Database::query($sql);

            $this->deleteAttendanceCalendarGroup($calendar_id, $course_id);
            $affected_rows++;
        }

        // update users' results
        $this->updateUsersResults($user_ids, $attendance);

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
            '0000-00-00' === $startDate ||
            '0000-00-00 00:00:00' === $startDate ||
            empty($endDate) ||
            '0000-00-00' === $endDate ||
            '0000-00-00 00:00:00' === $endDate
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
        $days = (int) $interval->format('%a');

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
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param bool      $orderDesc
     *
     * @return array
     */
    public function getCoursesWithAttendance(
        int $studentId,
        DateTime $startDate = null,
        DateTime $endDate = null,
        $orderDesc = false
    ) {
        $presentString = get_lang('Present');
        $absentString = '-';
        $attendanceLib = new Attendance();
        $data = [];
        $courses = CourseManager::get_courses_list_by_user_id($studentId);
        /* Get course with (in_category) and without (not_category) category */
        $i = 0;
        foreach ($courses as $courseItem) {
            $i++;
            $courseId = $courseItem['real_id'];

            /* Get all attendance by courses*/
            $attendanceList = $attendanceLib->getAttendanceList(api_get_course_entity($courseId));
            $temp = [];
            $sheetsProcessed = [];
            $tempDate = [];
            foreach ($attendanceList as $attendanceData) {
                $attendanceId = $attendanceData->getIid();
                $sheets = $attendanceLib->get_users_attendance_sheet(
                    $attendanceId,
                    $studentId,
                    0,
                    $courseId,
                    $startDate,
                    $endDate
                );

                $sheetsProcessed[] = [];
                foreach ($sheets as $sheetData) {
                    $totalb = count($sheetData);
                    $tempDate = [];
                    for ($ii = 0; $ii < $totalb; $ii++) {
                        $attendancesProcess = $sheetData[$ii];
                        if (!empty($attendancesProcess)) {
                            $dateTemp = $attendancesProcess['0'];
                            $attendancesProcess[0] = $attendancesProcess[1];
                            $attendancesProcess[1] = $dateTemp;

                            $attendancesProcess[2] = $courseItem['title'];
                            $attendancesProcess['courseTitle'] = $courseItem['title'];

                            $attendancesProcess[3] = $courseItem['real_id'];
                            $attendancesProcess['courseId'] = $courseItem['real_id'];

                            $attendancesProcess[4] = $attendanceData->getName();
                            $attendancesProcess['attendanceName'] = $attendanceData->getName();
                            $attendancesProcess['courseCode'] = $courseItem['course_code'];

                            $attendancesProcess[5] = $attendanceId;
                            $attendancesProcess['attendanceId'] = $attendanceId;
                            if (1 == $attendancesProcess['presence']) {
                                $attendancesProcess['presence'] = $presentString;
                                $attendancesProcess[0] = 1;
                            } else {
                                $attendancesProcess['presence'] = $absentString;
                                $attendancesProcess[0] = 0;
                            }
                            $attendancesProcess['session'] = 0;
                            $attendancesProcess['sessionName'] = '';
                            $tempDate[] = $attendancesProcess;
                            $dateKey = new DateTime($dateTemp);
                            /*
                            $attendancesProcess['teacher'] = '';
                            if(isset($courseItem['teachers']) and isset($courseItem['teachers'][0])){
                                $attendancesProcess['teacher'] = $courseItem['teachers'][0]['fullname'];
                            }
                            */
                            $data[$dateKey->format('Y-m-d')][] = $attendancesProcess;
                        }
                    }
                }
                $sheetsProcessed[] = $tempDate;
                $temp[] = $sheetsProcessed;
            }
            $courses['not_category'][$i]['attendanceSheet'] = $temp;
        }

        $sql = "SELECT session_id, c_id FROM session_rel_course_rel_user
                WHERE user_id = $studentId";

        $rs = Database::query($sql);
        // get info from sessions
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $courseId = $row['c_id'];
            $sessionId = $row['session_id'];
            $courseItem = api_get_course_info_by_id($courseId);
            $attendanceList = $attendanceLib->getAttendanceList(
            api_get_course_entity($courseId),
            api_get_session_entity($sessionId)
            );
            $temp = [];
            $sheetsProcessed = [];
            $tempDate = [];
            foreach ($attendanceList as $attendanceData) {
                $attendanceId = $attendanceData->getIid();
                $sheets = $attendanceLib->get_users_attendance_sheet(
                    $attendanceId,
                    $studentId,
                    0,
                    $courseId,
                    $startDate,
                    $endDate
                );

                $sheetsProcessed[] = [];
                foreach ($sheets as $sheetData) {
                    $totalb = count($sheetData);
                    $tempDate = [];
                    for ($ii = 0; $ii < $totalb; $ii++) {
                        $work = $sheetData[$ii];
                        $attendancesProcess = $work;
                        if (!empty($attendancesProcess)) {
                            $dateTemp = $attendancesProcess['0'];
                            $attendancesProcess[0] = $attendancesProcess[1];
                            $attendancesProcess[1] = $dateTemp;
                            $attendancesProcess[2] = $courseItem['title'];
                            $attendancesProcess['courseTitle'] = $courseItem['title'];
                            $attendancesProcess[3] = $courseItem['real_id'];
                            $attendancesProcess['courseId'] = $courseItem['real_id'];
                            $attendancesProcess[4] = $attendanceData->getName();
                            $attendancesProcess['attendanceName'] = $attendanceData->getName();
                            $attendancesProcess[5] = $attendanceId;
                            $attendancesProcess['attendanceId'] = $attendanceId;
                            $attendancesProcess['courseCode'] = $courseItem['official_code'];
                            if (1 == $attendancesProcess['presence']) {
                                $attendancesProcess['presence'] = $presentString;
                                $attendancesProcess[0] = 1;
                            } else {
                                $attendancesProcess['presence'] = $absentString;
                                $attendancesProcess[0] = 0;
                            }
                            $attendancesProcess['session'] = $sessionId;
                            $attendancesProcess['sessionName'] = api_get_session_name($sessionId);

                            $tempDate[] = $attendancesProcess;
                            $dateKey = new DateTime($dateTemp);
                            /*
                            $attendancesProcess['teacher'] = '';
                            if(isset($courseItem['tutor_name']) ){
                                $attendancesProcess['teacher'] = $courseItem['tutor_name'];
                            }
                            */
                            $data[$dateKey->format('Y-m-d')][] = $attendancesProcess;
                        }
                    }
                }
                $sheetsProcessed[] = $tempDate;
                $temp[] = $sheetsProcessed;
            }
            $courses['session'][$i]['attendanceSheet'] = $temp;
        }

        /* Order desc by date,  by default */
        if (true == $orderDesc) {
            ksort($data);
        } else {
            krsort($data);
        }

        return $data;
    }

    public function setAttendanceForm(FormValidator $form, CAttendance $attendance = null)
    {
        $skillList = [];
        $header = get_lang('Create a new attendance list');
        if ($attendance) {
            $header = get_lang('Edit');
            $form->addHidden('attendance_id', $attendance->getIid());
        }
        $form->addHeader($header);

        //$form->addElement('hidden', 'sec_token', $token);
        $form->addText('title', get_lang('Title'), true);
        $form->applyFilter('title', 'html_filter');
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            ['ToolbarSet' => 'Basic', 'Width' => '100%', 'Height' => '150']
        );

        $sessionId = api_get_session_id();

        // Advanced Parameters
        if ((0 != $sessionId && Gradebook::is_active()) || 0 == $sessionId) {
            $form->addButtonAdvancedSettings('id_qualify');
            $form->addHtml('<div id="id_qualify_options" style="display:none">');

            // Qualify Attendance for gradebook option
            $form->addCheckBox(
                'attendance_qualify_gradebook',
                '',
                get_lang('Grade the attendance list in the assessment tool'),
                ['onclick' => '"javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"']
            );
            $form->addElement('html', '<div id="options_field" style="display:none">');

            GradebookUtils::load_gradebook_select_in_tool($form);

            $form->addElement('text', 'attendance_qualify_title', get_lang('Column header in Competences Report'));
            $form->applyFilter('attendance_qualify_title', 'html_filter');
            $form->addElement(
                'text',
                'attendance_weight',
                get_lang('Weight in Report'),
                'value="0.00" Style="width:40px" onfocus="javascript: this.select();"'
            );
            $form->applyFilter('attendance_weight', 'html_filter');
            $form->addElement('html', '</div>');

            $skillList = SkillModel::addSkillsToForm($form, ITEM_TYPE_ATTENDANCE, $attendance ? $attendance->getIid() : 0);

            $form->addElement('html', '</div>');
        }

        if ($attendance) {
            $form->addButtonUpdate(get_lang('Update'));
        } else {
            $form->addButtonCreate(get_lang('Save'));
        }

        if ($attendance) {
            $default = [];
            $default['title'] = Security::remove_XSS($attendance->getName());
            $default['description'] = Security::remove_XSS($attendance->getDescription(), STUDENT);
            $default['attendance_qualify_title'] = $attendance->getAttendanceQualifyTitle();
            $default['attendance_weight'] = $attendance->getAttendanceWeight();
            $default['skills'] = array_keys($skillList);

            $link_info = GradebookUtils::isResourceInCourseGradebook(
                api_get_course_id(),
                7,
                $attendance->getIid(),
                $sessionId
            );
            if ($link_info) {
                $default['category_id'] = $link_info['category_id'];
            }
            $form->setDefaults($default);
        }

        return $form;
    }

    public function getCalendarSheet($edit, CAttendance $attendance, $student_id)
    {
        $attendanceId = $attendance->getIid();
        $content = '';
        $groupId = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : null;
        $filter_type = 'today';
        if (!empty($_REQUEST['filter'])) {
            $filter_type = $_REQUEST['filter'];
        }

        $users_in_course = $this->get_users_rel_course($attendanceId, $groupId);
        $is_locked_attendance = $this->is_locked_attendance($attendanceId);

        $attendant_calendar_all = $this->get_attendance_calendar(
            $attendanceId,
            'all',
            null,
            $groupId
        );
        $attendant_calendar = $this->get_attendance_calendar(
            $attendanceId,
            $filter_type,
            null,
            $groupId
        );

        $allowToEdit = api_is_allowed_to_edit(null, true);

        $isDrhOfCourse = api_is_drh() || CourseManager::isUserSubscribedInCourseAsDrh(
                api_get_user_id(),
                api_get_course_info()
            );
        if ($edit) {
            if ($isDrhOfCourse || $allowToEdit) {
                $users_presence = $this->get_users_attendance_sheet(
                    $attendanceId,
                    0,
                    $groupId
                );
            }
        } else {
            if (!empty($student_id)) {
                $user_id = (int) $student_id;
            } else {
                $user_id = api_get_user_id();
            }

            if ($isDrhOfCourse ||
                $allowToEdit ||
                api_is_coach(api_get_session_id(), api_get_course_int_id())
            ) {
                $users_presence = $this->get_users_attendance_sheet($attendanceId, 0, $groupId);
            } else {
                $users_presence = $this->get_users_attendance_sheet($attendanceId, $user_id, $groupId);
            }
            $faults = $this->get_faults_of_user($user_id, $attendanceId, $groupId);
        }

        $next_attendance_calendar_id = $this->get_next_attendance_calendar_id($attendanceId);
        $next_attendance_calendar_datetime = $this->getNextAttendanceCalendarDatetime($attendanceId);

        if ($isDrhOfCourse ||
            $allowToEdit ||
            api_is_coach(api_get_session_id(), api_get_course_int_id())
        ) {
            $form = new FormValidator(
                'filter',
                'post',
                'index.php?action=attendance_sheet_list&'.api_get_cidreq().'&attendance_id='.$attendanceId,
                null,
                [],
                'inline'
            );

            $values = [
                'all' => get_lang('All'),
                'today' => get_lang('Today'),
                'all_done' => get_lang('All done'),
                'all_not_done' => get_lang('All not done'),
            ];
            $today = api_convert_and_format_date(null, DATE_FORMAT_SHORT);
            $exists_attendance_today = false;

            if (!empty($attendant_calendar_all)) {
                $values[''] = '---------------';
                foreach ($attendant_calendar_all as $attendance_date) {
                    $includeCalendar = true;
                    if (isset($attendance_date['groups']) && !empty($groupId)) {
                        foreach ($attendance_date['groups'] as $group) {
                            if ($groupId == $group['group_id']) {
                                $includeCalendar = true;

                                break;
                            } else {
                                $includeCalendar = false;
                            }
                        }
                    }

                    if ($today == $attendance_date['date']) {
                        $exists_attendance_today = true;
                    }
                    if ($includeCalendar) {
                        $values[$attendance_date['iid']] = $attendance_date['date_time'];
                    }
                }
            }

            if (!$exists_attendance_today) {
                $content .= Display::return_message(
                    get_lang(
                        'There is no class scheduled today, try picking another day or add your attendance entry yourself using the action icons.'
                    ),
                    'warning'
                );
            }

            $form->addSelect(
                'filter',
                get_lang('Filter'),
                $values,
                ['id' => 'filter_id', 'onchange' => 'submit();']
            );

            $groupList = GroupManager::get_group_list(null, null, 1);
            $groupIdList = ['--'];
            foreach ($groupList as $group) {
                $groupIdList[$group['iid']] = $group['name'];
            }

            if (!empty($groupList)) {
                $form->addSelect('group_id', get_lang('Group'), $groupIdList);
            }

            $default_filter = 'today';
            if (isset($_REQUEST['filter'])) {
                if (in_array($_REQUEST['filter'], array_keys($values))) {
                    $default_filter = $_REQUEST['filter'];
                }
            }
            $renderer = $form->defaultRenderer();
            $renderer->setCustomElementTemplate(
                '<div class="col-md-2">{label}</div><div class="col-md-10"> {element} </div>'
            );

            $form->setDefaults(
                [
                    'filter' => $default_filter,
                    'group_id' => $groupId,
                ]
            );

            if (!$is_locked_attendance || api_is_platform_admin()) {
                $actionsLeft = '<a
                    style="float:left;"
                    href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendanceId.'">'.
                    Display::return_icon('attendance_calendar.png', get_lang('Attendance calendar'), '', ICON_SIZE_MEDIUM).
                    '</a>';
                $actionsLeft .= '<a
                    id="pdf_export" style="float:left;"
                    href="index.php?'.api_get_cidreq().'&action=attendance_sheet_export_to_pdf&attendance_id='.$attendanceId.'&filter='.$default_filter.'&group_id='.$groupId.'">'.
                    Display::return_icon('pdf.png', get_lang('Export to PDF'), '', ICON_SIZE_MEDIUM).'</a>';

                $actionsRight = $form->returnForm();
                $content .= Display::toolbarAction('toolbar-attendance', [$actionsLeft, $actionsRight]);
            }

            $message_information = get_lang(
                'The attendance sheets allow you to specify a list of dates in which you will report attendance to your courses'
            );
            if (!empty($message_information)) {
                $message = '<strong>'.get_lang('Information').'</strong><br />';
                $message .= $message_information;
                $content .= Display::return_message($message, 'normal', false);
            }

            if ($is_locked_attendance) {
                $content .= Display::return_message(get_lang('The attendance sheet is locked.'), 'warning', false);
            }

            $param_filter = '&filter='.Security::remove_XSS($default_filter).'&group_id='.$groupId;

            if (count($users_in_course) > 0) {
                $form = '
                <form method="post" action="index.php?action=attendance_sheet_add&'.api_get_cidreq().$param_filter.'&attendance_id='.$attendanceId.'">
                    <div
                        class="attendance-sheet-content"
                        style="width:100%;background-color:#E1E1E1;margin-top:20px;">
                        <div
                            class="divTableWithFloatingHeader attendance-users-table"
                            style="width:45%;float:left;margin:0px;padding:0px;">
                            <table class="tableWithFloatingHeader data_table" width="100%">
                                <thead>
                                <tr class="tableFloatingHeader"
                                    style="position: absolute; top: 0px; left: 0px; visibility: hidden; margin:0px;padding:0px" >
                                    <th width="10px">#</th>
                                    <th width="10px">'.get_lang('Photo').'</th>
                                    <th width="100px">'.get_lang('Last name').'</th>
                                    <th width="100px">'.get_lang('First name').'</th>
                                    <th width="100px">'.get_lang('Not attended').'</th>
                                </tr>
                                <tr class="tableFloatingHeaderOriginal">
                                    <th width="10px">#</th>
                                    <th width="10px">'.get_lang('Photo').'</th>
                                    <th width="150px">'.get_lang('Last name').'</th>
                                    <th width="140px">'.get_lang('First name').'</th>
                                    <th width="100px">'.get_lang('Not attended').'</th>
                                </tr>
                                </thead>
                                <tbody>';
                $i = 1;
                foreach ($users_in_course as $data) {
                    $faults = 0;
                    $class = 'row_even';
                    if (0 == $i % 2) {
                        $class = 'row_odd';
                    }
                    $username = api_htmlentities(
                        sprintf(get_lang('Login: %s'), $data['username']),
                        ENT_QUOTES
                    );

                    $form .= '<tr class="'.$class.'">
                                <td><center>'.$i.'</center></td>
                                <td>'.$data['photo'].'</td>
                                <td><span title="'.$username.'">'.$data['lastname'].'</span></td>
                                <td>'.$data['firstname'].'</td>
                                <td>
                                    <div class="attendance-faults-bar"
                                    style="background-color:'.(!empty($data['result_color_bar']) ? $data['result_color_bar'] : 'none').'">
                                        '.$data['attendance_result'].'
                                    </div>
                                </td>
                            </tr>';
                    $i++;
                }
                $form .= '</tbody>
                            </table>
                        </div>';

                $form .= '<div
                    class="divTableWithFloatingHeader attendance-calendar-table"
                    style="margin:0px;padding:0px;float:left;width:55%;overflow:auto;overflow-y:hidden;">';
                $form .= '<table class="tableWithFloatingHeader data_table" width="100%">';
                $form .= '<thead>';
                $result = null;
                if (count($attendant_calendar) > 0) {
                    foreach ($attendant_calendar as $calendar) {
                        $calendarId = $calendar['iid'];
                        $date = $calendar['date'];
                        $time = $calendar['time'];
                        $datetime = '<div class="grey">'.$date.' - '.$time.'</div>';

                        $img_lock = Display::return_icon(
                            'lock-closed.png',
                            get_lang('Unlock date'),
                            ['class' => 'img_lock', 'id' => 'datetime_column_'.$calendarId]
                        );

                        if (!empty($calendar['done_attendance'])) {
                            $datetime = '<div class="blue">'.$date.' - '.$time.'</div>';
                        }
                        $disabled_check = 'disabled = "true"';
                        $input_hidden = '<input
                            type="hidden" id="hidden_input_'.$calendarId.'" name="hidden_input[]" value="" disabled />';
                        if ($next_attendance_calendar_id == $calendarId) {
                            $input_hidden = '<input
                                type="hidden"
                                id="hidden_input_'.$calendarId.'" name="hidden_input[]"
                                value="'.$calendarId.'" />';
                            $disabled_check = '';
                            $img_lock = Display::return_icon(
                                'lock-closed.png',
                                get_lang('Lock date'),
                                ['class' => 'img_unlock', 'id' => 'datetime_column_'.$calendarId]
                            );
                        }

                        $result .= '<th>';
                        $result .= '<div class="date-attendance">'.$datetime.'&nbsp;';
                        if ($allowToEdit) {
                            $result .= '<span
                                id="attendance_lock"
                                style="cursor:pointer">'.
                                (!$is_locked_attendance || api_is_platform_admin() ? $img_lock : '').
                                '</span>';
                        }

                        if (false == $is_locked_attendance) {
                            if ($allowToEdit) {
                                $result .= '<input
                                        type="checkbox"
                                        class="checkbox_head_'.$calendarId.'"
                                        id="checkbox_head_'.$calendarId.'" '.$disabled_check.' checked="checked" />'.
                                    $input_hidden.'</div></th>';
                            }
                        }
                    }
                } else {
                    $result = '<th width="2000px"><span>
                    <a href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendanceId.'">';
                    $result .= Display::return_icon(
                            'attendance_calendar.png',
                            get_lang('Attendance calendar'),
                            '',
                            ICON_SIZE_MEDIUM
                        ).' '.get_lang('Go to the attendance calendar');
                    $result .= '</a></span></th>';
                }

                $form .= '<tr
                        class="tableFloatingHeader row_odd"
                        style="position: absolute; top: 0px; left: 0px; visibility: hidden; margin:0px;padding:0px">';
                $form .= $result;
                $form .= '</tr>';
                $form .= '<tr class="tableWithFloatingHeader row_odd tableFloatingHeaderOriginal">';
                $form .= $result;
                $form .= '</tr>';
                $form .= '</thead>';
                $form .= '<tbody>';
                $i = 0;
                foreach ($users_in_course as $user) {
                    $class = 'row_odd';
                    if (0 == $i % 2) {
                        $class = 'row_even';
                    }
                    $form .= '<tr class="'.$class.'">';
                    if (count($attendant_calendar) > 0) {
                        foreach ($attendant_calendar as $calendar) {
                            $checked = 'checked';
                            $presence = -1;
                            if (isset($users_presence[$user['user_id']][$calendar['iid']]['presence'])) {
                                $presence = $users_presence[$user['user_id']][$calendar['iid']]['presence'];

                                $checked = '';
                                if (1 == (int) $presence) {
                                    $checked = 'checked';
                                }
                            } else {
                                //if the user wasn't registered at that time, consider unchecked
                                if (0 == $next_attendance_calendar_datetime ||
                                    $calendar['date_time'] < $next_attendance_calendar_datetime
                                ) {
                                    $checked = '';
                                }
                            }
                            $disabled = 'disabled';
                            $style_td = '';
                            if ($next_attendance_calendar_id == $calendar['iid']) {
                                if (0 == $i % 2) {
                                    $style_td = 'background-color:#eee;';
                                } else {
                                    $style_td = 'background-color:#dcdcdc;';
                                }
                                $disabled = '';
                            }

                            $form .= '<td style="'.$style_td.'" class="checkboxes_col_'.$calendar['iid'].'">';
                            $form .= '<div class="check">';
                            if ($allowToEdit) {
                                if (!$is_locked_attendance || api_is_platform_admin()) {
                                    $form .= '<input
                                    type="checkbox"
                                    name="check_presence['.$calendar['iid'].'][]"
                                    value="'.$user['user_id'].'" '.$disabled.' '.$checked.' />';
                                    $form .= '<span class="anchor_'.$calendar['iid'].'"></span>';
                                } else {
                                    $form .= $presence ? Display::return_icon(
                                        'checkbox_on.png',
                                        get_lang('Assistance'),
                                        null,
                                        ICON_SIZE_TINY
                                    ) : Display::return_icon(
                                        'checkbox_off.png',
                                        get_lang('Assistance'),
                                        null,
                                        ICON_SIZE_TINY
                                    );
                                }
                            } else {
                                switch ($presence) {
                                    case 1:
                                        $form .= Display::return_icon('accept.png', get_lang('Attended'));

                                        break;
                                    case 0:
                                        $form .= Display::return_icon('exclamation.png', get_lang('Not attended'));

                                        break;
                                    case -1:
                                        break;
                                }
                            }

                            $form .= '</div>';
                            $form .= '</td>';
                        }
                    } else {
                        $calendarClass = null;
                        if (isset($calendar)) {
                            $calendarClass = 'checkboxes_col_'.$calendar['iid'];
                        }
                        $form .= '<td class="'.$calendarClass.'">';
                        $form .= '<div>';
                        $form .= '<center>&nbsp;</center>
                                </div>
                                </td>';
                    }
                    echo '</tr>';
                    $i++;
                }
                $form .= '</tbody></table>';
                $form .= '</div></div>';

                $form .= '<div class="row">
                            <div class="col-md-12">';
                if (!$is_locked_attendance || api_is_platform_admin()) {
                    if ($allowToEdit) {
                        $form .= '<button type="submit" class="btn btn--primary">'.get_lang('Save').'</button>';
                    }
                }
                $form .= '</div>
                        </div>
                </form>';
                $content .= $form;
            } else {
                $content .= Display::return_message(
                    '<a href="'.api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'">'.
                    get_lang('There are no registered learners inside the course').'</a>',
                    'warning',
                    false
                );
            }
        } else {
            $content .= Display::page_header(get_lang('Report of attendance sheets'));
            if (!empty($users_presence)) {
                $content .= '
                <div>
                    <table width="250px;">
                        <tr>
                            <td>'.get_lang('To attend').': </td>
                            <td>
                                <center>
                                <div
                                    class="attendance-faults-bar"
                                    style="background-color:'.(!empty($faults['color_bar']) ? $faults['color_bar'] : 'none').'">
                                        '.$faults['faults'].'/'.$faults['total'].' ('.$faults['faults_porcent'].'%)
                                </div>
                                </center>
                            </td>
                        </tr>
                    </table>
                </div>';
            }

            $content .= '<table class="data_table">
                <tr class="row_odd" >
                    <th>'.get_lang('Attendance').'</th>
                </tr>';

            if (!empty($users_presence)) {
                $i = 0;
                foreach ($users_presence[$user_id] as $presence) {
                    $class = '';
                    if (0 == $i % 2) {
                        $class = 'row_even';
                    } else {
                        $class = 'row_odd';
                    }

                    $check = $presence['presence'] ? Display::return_icon('checkbox_on.png', get_lang('Assistance'), null, ICON_SIZE_TINY) : Display::return_icon('checkbox_off.png', get_lang('Assistance'), null, ICON_SIZE_TINY);
                    $content .= '<tr class="'.$class.'">
                            <td>
                                '.$check.'&nbsp; '.$presence['date_time'].'
                            </td>
                        </tr>';
                }
            } else {
                $content .= '
                        <tr>
                            <td>
                            <center>'.get_lang('You do not have attendances').'</center>
                            </td>
                        </tr>';
            }
            $content .= ' </table>';
        }

        return $content;
    }

    /**
     * It's used to print attendance sheet.
     *
     * @param int $attendance_id
     */
    public function attendance_sheet_export_to_pdf(
        $attendance_id,
        $student_id = 0,
        $course_id = ''
    ) {
        $courseInfo = api_get_course_info($course_id);
        $this->set_course_id($courseInfo['code']);
        $groupId = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : null;
        $data_array = [];
        $data_array['attendance_id'] = $attendance_id;
        $data_array['users_in_course'] = $this->get_users_rel_course($attendance_id, $groupId);

        $filter_type = 'today';

        if (!empty($_REQUEST['filter'])) {
            $filter_type = $_REQUEST['filter'];
        }

        $my_calendar_id = null;
        if (is_numeric($filter_type)) {
            $my_calendar_id = $filter_type;
            $filter_type = 'calendar_id';
        }

        $data_array['attendant_calendar'] = $this->get_attendance_calendar(
            $attendance_id,
            $filter_type,
            $my_calendar_id,
            $groupId
        );

        if (api_is_allowed_to_edit(null, true) || api_is_drh()) {
            $data_array['users_presence'] = $this->get_users_attendance_sheet($attendance_id, 0, $groupId);
        } else {
            if (!empty($student_id)) {
                $user_id = (int) $student_id;
            } else {
                $user_id = api_get_user_id();
            }
            $data_array['users_presence'] = $this->get_users_attendance_sheet($attendance_id, $user_id, $groupId);
            $data_array['faults'] = $this->get_faults_of_user($user_id, $attendance_id, $groupId);
            $data_array['user_id'] = $user_id;
        }

        $data_array['next_attendance_calendar_id'] = $this->get_next_attendance_calendar_id($attendance_id);

        // Set headers pdf.
        $courseCategory = CourseManager::get_course_category($courseInfo['categoryCode']);
        $teacherInfo = CourseManager::get_teacher_list_from_course_code($courseInfo['code']);
        $teacherName = null;
        foreach ($teacherInfo as $teacherData) {
            if (null != $teacherName) {
                $teacherName .= ' / ';
            }
            $teacherName .= api_get_person_name($teacherData['firstname'], $teacherData['lastname']);
        }

        // Get data table
        $data_table = [];
        $head_table = ['#', get_lang('Name')];
        foreach ($data_array['attendant_calendar'] as $class_day) {
            $head_table[] =
                api_format_date($class_day['date_time'], DATE_FORMAT_NUMBER_NO_YEAR).' '.
                api_format_date($class_day['date_time'], TIME_NO_SEC_FORMAT);
        }
        $data_table[] = $head_table;
        $data_attendant_calendar = $data_array['attendant_calendar'];
        $data_users_presence = $data_array['users_presence'];
        $count = 1;

        if (!empty($data_array['users_in_course'])) {
            foreach ($data_array['users_in_course'] as $user) {
                $cols = 1;
                $result = [];
                $result['count'] = $count;
                $result['full_name'] = api_get_person_name($user['firstname'], $user['lastname']);
                foreach ($data_array['attendant_calendar'] as $class_day) {
                    if (1 == $class_day['done_attendance']) {
                        if (1 == $data_users_presence[$user['user_id']][$class_day['iid']]['presence']) {
                            $result[$class_day['iid']] = get_lang('P');
                        } else {
                            $result[$class_day['iid']] = '<span style="color:red">'.get_lang('NP').'</span>';
                        }
                    } else {
                        $result[$class_day['iid']] = ' ';
                    }
                    $cols++;
                }
                $count++;
                $data_table[] = $result;
            }
        }
        $max_cols_per_page = 12; //10 dates + 2 name and number
        $max_dates_per_page = $max_dates_per_page_original = $max_cols_per_page - 2; //10
        $rows = count($data_table);

        if ($cols > $max_cols_per_page) {
            $number_tables = round(($cols - 2) / $max_dates_per_page);
            $headers = $data_table[0];
            $all = [];
            $tables = [];
            $changed = 1;

            for ($i = 0; $i <= $rows; $i++) {
                $row = isset($data_table[$i]) ? $data_table[$i] : null;
                $key = 1;
                $max_dates_per_page = 10;
                $item = isset($data_table[$i]) ? $data_table[$i] : null;
                $count_j = 0;

                if (!empty($item)) {
                    foreach ($item as $value) {
                        if ($count_j >= $max_dates_per_page) {
                            $key++;
                            $max_dates_per_page = $max_dates_per_page_original * $key;
                            //magic hack
                            $tables[$key][$i][] = $tables[1][$i][0];
                            $tables[$key][$i][] = $tables[1][$i][1];
                        }
                        $tables[$key][$i][] = $value;
                        $count_j++;
                    }
                }
            }

            $content = null;
            if (!empty($tables)) {
                foreach ($tables as $sub_table) {
                    $content .= Export::convert_array_to_html($sub_table).'<br /><br />';
                }
            }
        } else {
            $content = Export::convert_array_to_html(
                $data_table,
                ['header_attributes' => ['align' => 'center']]
            );
        }

        $params = [
            'filename' => get_lang('Attendance').'-'.api_get_local_time(),
            'pdf_title' => $courseInfo['title'],
            'course_code' => $courseInfo['code'],
            'add_signatures' => ['Drh', 'Teacher', 'Date'],
            'pdf_teachers' => $teacherName,
            'pdf_course_category' => $courseCategory ? $courseCategory['name'] : '',
            'format' => 'A4-L',
            'orientation' => 'L',
        ];
        Export::export_html_to_pdf($content, $params);
        exit;
    }

    /**
     * Gets attendance base in the table:
     * TABLE_STATISTIC_TRACK_E_COURSE_ACCESS.
     *
     * @param bool $showForm
     * @param bool $exportToPdf
     */
    public function getAttendanceBaseInLogin($showForm = false, $exportToPdf = true)
    {
        $table = null;
        $formToDisplay = null;
        $startDate = null;
        $endDate = null;

        $sessionId = api_get_session_id();
        if ($showForm) {
            $form = new FormValidator(
                'search',
                'post',
                api_get_self().'?'.api_get_cidreq().'&action=calendar_logins'
            );
            $form->addDateRangePicker('range', get_lang('Date range'));
            $form->addButton('submit', get_lang('Submit'));

            if ($form->validate()) {
                $values = $form->getSubmitValues();
                $startDate = api_get_utc_datetime($values['range_start']);
                $endDate = api_get_utc_datetime($values['range_end']);
            }
            $formToDisplay = $form->returnForm();
        } else {
            if (!empty($sessionId)) {
                $sessionInfo = api_get_session_info($sessionId);
                $startDate = $sessionInfo['access_start_date'];
                $endDate = $sessionInfo['access_end_date'];
            }
        }

        if ($exportToPdf) {
            $result = $this->exportAttendanceLogin($startDate, $endDate);
            if (empty($result)) {
                return false;
                //api_not_allowed(true, get_lang('No data available'));
            }
        }

        $table = $this->getAttendanceLoginTable($startDate, $endDate);

        return [
            'form' => $formToDisplay,
            'table' => $table,
        ];
    }
}

<?php
/* For licensing terms, see /license.txt */

/**
 * Gradebook link to attendance item.
 *
 * @author Christian Fasanando (christian1827@gmail.com)
 */
class AttendanceLink extends AbstractLink
{
    private $attendance_table = null;
    private $itemprop_table = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_ATTENDANCE);
    }

    /**
     * @return string
     */
    public function get_type_name()
    {
        return get_lang('Attendance');
    }

    /**
     * @return bool
     */
    public function is_allowed_to_change_name()
    {
        return false;
    }

    /**
     * Generate an array of all attendances available.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links()
    {
        if (empty($this->course_code)) {
            return [];
        }
        $tbl_attendance = $this->get_attendance_table();
        $sessionId = $this->get_session_id();

        $sql = 'SELECT att.id, att.name, att.attendance_qualify_title
                FROM '.$tbl_attendance.' att
                WHERE
                    att.c_id = '.$this->course_id.' AND
                    att.active = 1 AND
                    att.session_id = '.$sessionId;

        $result = Database::query($sql);

        while ($data = Database::fetch_array($result)) {
            if (isset($data['attendance_qualify_title']) && '' != $data['attendance_qualify_title']) {
                $cats[] = [$data['id'], $data['attendance_qualify_title']];
            } else {
                $cats[] = [$data['id'], $data['name']];
            }
        }
        $my_cats = isset($cats) ? $cats : [];

        return $my_cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results()
    {
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $sessionId = $this->get_session_id();

        $sql = 'SELECT count(*) AS number FROM '.$tbl_attendance_result."
                WHERE
                    session_id = $sessionId AND
                    c_id = '.$this->course_id.' AND
                    attendance_id = '".$this->get_ref_id()."'";
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    /**
     * @param int $stud_id
     *
     * @return array|null
     */
    public function calc_score($stud_id = null, $type = null)
    {
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $sessionId = $this->get_session_id();

        // get attendance qualify max
        $sql = 'SELECT att.attendance_qualify_max
                FROM '.$this->get_attendance_table().' att
                WHERE
                    att.c_id = '.$this->course_id.' AND
                    att.id = '.$this->get_ref_id().' AND
                    att.session_id = '.$sessionId;
        $query = Database::query($sql);
        $attendance = Database::fetch_array($query, 'ASSOC');

        // Get results
        $sql = 'SELECT *
                FROM '.$tbl_attendance_result.'
                WHERE c_id = '.$this->course_id.' AND attendance_id = '.$this->get_ref_id();
        if (isset($stud_id)) {
            $sql .= ' AND user_id = '.intval($stud_id);
        }
        $scores = Database::query($sql);
        // for 1 student
        if (isset($stud_id)) {
            if ($data = Database::fetch_array($scores, 'ASSOC')) {
                return [
                    $data['score'],
                    $attendance['attendance_qualify_max'],
                ];
            } else {
                //We sent the 0/attendance_qualify_max instead of null for correct calculations
                return [0, $attendance['attendance_qualify_max']];
            }
        } else {
            // all students -> get average
            $students = []; // user list, needed to make sure we only
            // take first attempts into account
            $rescount = 0;
            $sum = 0;
            $sumResult = 0;
            $bestResult = 0;

            while ($data = Database::fetch_array($scores)) {
                if (!(array_key_exists($data['user_id'], $students))) {
                    if (0 != $attendance['attendance_qualify_max']) {
                        $students[$data['user_id']] = $data['score'];
                        $rescount++;
                        $sum += $data['score'] / $attendance['attendance_qualify_max'];
                        $sumResult += $data['score'];
                        if ($data['score'] > $bestResult) {
                            $bestResult = $data['score'];
                        }
                        $weight = $attendance['attendance_qualify_max'];
                    }
                }
            }

            if (0 == $rescount) {
                return [null, null];
            } else {
                switch ($type) {
                    case 'best':
                        return [$bestResult, $weight];
                        break;
                    case 'average':
                        return [$sumResult / $rescount, $weight];
                        break;
                    case 'ranking':
                        return AbstractLink::getCurrentUserRanking($stud_id, $students);
                        break;
                    default:
                        return [$sum, $rescount];
                        break;
                }
            }
        }
    }

    public function needs_name_and_description()
    {
        return false;
    }

    public function needs_max()
    {
        return false;
    }

    public function needs_results()
    {
        return false;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        $this->get_attendance_data();
        $attendance_title = isset($this->attendance_data['name']) ? $this->attendance_data['name'] : '';
        $attendance_qualify_title = isset($this->attendance_data['attendance_qualify_title']) ? $this->attendance_data['attendance_qualify_title'] : '';
        if (isset($attendance_qualify_title) && '' != $attendance_qualify_title) {
            return $this->attendance_data['attendance_qualify_title'];
        } else {
            return $attendance_title;
        }
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return '';
    }

    /**
     * Check if this still links to an exercise.
     */
    public function is_valid_link()
    {
        $sql = 'SELECT count(att.id) FROM '.$this->get_attendance_table().' att
                 WHERE att.c_id = '.$this->course_id.' AND att.id = '.$this->get_ref_id();
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    public function get_link()
    {
        // it was extracts the attendance id
        $sessionId = $this->get_session_id();
        $sql = 'SELECT * FROM '.$this->get_attendance_table().' att
                WHERE att.c_id = '.$this->course_id.' AND att.id = '.$this->get_ref_id();
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');
        $attendance_id = $row['id'];
        $url = api_get_path(WEB_PATH).'main/attendance/index.php?action=attendance_sheet_list&gradebook=view&attendance_id='.$attendance_id.'&'.api_get_cidreq_params($this->get_course_code(), $sessionId);

        return $url;
    }

    /**
     * @return string
     */
    public function get_icon_name()
    {
        return 'attendance';
    }

    /**
     * Lazy load function to get the database table of the student publications.
     */
    private function get_attendance_table()
    {
        $this->attendance_table = Database::get_course_table(TABLE_ATTENDANCE);

        return $this->attendance_table;
    }

    /**
     * @return array|bool
     */
    private function get_attendance_data()
    {
        $tbl_name = $this->get_attendance_table();
        if ('' == $tbl_name) {
            return false;
        } elseif (!isset($this->attendance_data)) {
            $sql = 'SELECT * FROM '.$this->get_attendance_table().' att
                    WHERE att.c_id = '.$this->course_id.' AND att.id = '.$this->get_ref_id();
            $query = Database::query($sql);
            $this->attendance_data = Database::fetch_array($query);
        }

        return $this->attendance_data;
    }
}

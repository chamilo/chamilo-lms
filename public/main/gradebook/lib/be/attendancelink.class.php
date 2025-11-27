<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;

/**
 * Gradebook link to attendance item.
 *
 * @author Christian Fasanando (christian1827@gmail.com)
 */
class AttendanceLink extends AbstractLink
{
    private $attendance_table = null;

    private $attendance_data = array();

    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_ATTENDANCE);
    }

    /**
     * @return string
     */
    public function get_type_name(): string
    {
        return get_lang('Attendance');
    }

    /**
     * @return bool
     */
    public function is_allowed_to_change_name(): bool
    {
        return false;
    }

    /**
     * Generate an array of all attendances available.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links(): array
    {
        if (empty($this->getCourseId())) {
            return [];
        }
        $sessionId = $this->get_session_id();
        $repo = Container::getAttendanceRepository();
        $qb = $repo->getResourcesByCourse(api_get_course_entity($this->course_id), api_get_session_entity($sessionId));
        $qb->andWhere('resource.active = 1');
        $links = $qb->getQuery()->getResult();
        $cats = [];
        /** @var CAttendance $link */
        foreach ($links as $link) {
            $title = $link->getAttendanceQualifyTitle();
            if (!empty($title)) {
                $cats[] = [$link->getIid(), $title];
            } else {
                $cats[] = [$link->getIid(), $link->getTitle()];
            }
        }

        return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     * @throws Exception
     */
    public function has_results(): bool
    {
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $sessionId = $this->get_session_id();

        $sql = 'SELECT count(*) AS number FROM '.$tbl_attendance_result."
                WHERE attendance_id = ".$this->get_ref_id();
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    /**
     * @param ?int $studentId
     * @param ?string $type
     * @return array
     * @throws Exception
     */
    public function calc_score(?int $studentId = null, ?string $type = null): array
    {
        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $sessionId = $this->get_session_id();

        // get attendance qualify max
        $sql = 'SELECT attendance_qualify_max
                FROM '.$this->get_attendance_table().'
                WHERE iid = '.$this->get_ref_id();
        $query = Database::query($sql);
        $attendance = Database::fetch_assoc($query);

        // Get results
        $sql = 'SELECT *
                FROM '.$tbl_attendance_result.'
                WHERE attendance_id = '.$this->get_ref_id();
        if (isset($studentId)) {
            $sql .= ' AND user_id = '.intval($studentId);
        }
        $scores = Database::query($sql);
        // for 1 student
        if (isset($studentId)) {
            if ($data = Database::fetch_assoc($scores)) {
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
            $resultCount = 0;
            $sum = 0;
            $sumResult = 0;
            $bestResult = 0;

            while ($data = Database::fetch_array($scores)) {
                if (!(array_key_exists($data['user_id'], $students))) {
                    if (0 != $attendance['attendance_qualify_max']) {
                        $students[$data['user_id']] = $data['score'];
                        $resultCount++;
                        $sum += $data['score'] / $attendance['attendance_qualify_max'];
                        $sumResult += $data['score'];
                        if ($data['score'] > $bestResult) {
                            $bestResult = $data['score'];
                        }
                        $weight = $attendance['attendance_qualify_max'];
                    }
                }
            }

            if (0 == $resultCount) {
                return [null, null];
            } else {
                switch ($type) {
                    case 'best':
                        return [$bestResult, $weight];
                        break;
                    case 'average':
                        return [$sumResult / $resultCount, $weight];
                        break;
                    case 'ranking':
                        return AbstractLink::getCurrentUserRanking($studentId, $students);
                        break;
                    default:
                        return [$sum, $resultCount];
                        break;
                }
            }
        }
    }

    public function needs_name_and_description(): bool
    {
        return false;
    }

    public function needs_max(): bool
    {
        return false;
    }

    public function needs_results(): bool
    {
        return false;
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\Exception
     */
    public function get_name(): string
    {
        $this->get_attendance_data();
        $attendance_title = $this->attendance_data['name'] ?? '';
        $attendance_qualify_title = $this->attendance_data['attendance_qualify_title'] ?? '';
        if ('' != $attendance_qualify_title) {
            return $this->attendance_data['attendance_qualify_title'];
        } else {
            return $attendance_title;
        }
    }

    /**
     * @return string
     */
    public function get_description(): string
    {
        return '';
    }

    /**
     * Check if this still links to an exercise.
     * @throws Exception
     */
    public function is_valid_link(): bool
    {
        $sql = 'SELECT count(iid) FROM '.$this->get_attendance_table().'
                WHERE iid = '.$this->get_ref_id();
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    /**
     * @throws Exception
     */
    public function get_link(): string
    {
        // it was extracts the attendance id
        $sessionId = $this->get_session_id();
        $sql = 'SELECT * FROM '.$this->get_attendance_table().'
                WHERE iid = '.$this->get_ref_id();
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);
        $id = $row['iid'];

        return api_get_path(WEB_CODE_PATH).
            'attendance/index.php?action=attendance_sheet_list&gradebook=view&attendance_id='.$id.'&'.
            api_get_cidreq_params($this->getCourseId(), $sessionId);
    }

    /**
     * @return string
     */
    public function get_icon_name(): string
    {
        return 'attendance';
    }

    /**
     * Lazy load function to get the database table of the student publications.
     */
    private function get_attendance_table(): string
    {
        $this->attendance_table = Database::get_course_table(TABLE_ATTENDANCE);

        return $this->attendance_table;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    private function get_attendance_data(): array
    {
        if (!isset($this->attendance_data)) {
            $sql = 'SELECT * FROM '.$this->get_attendance_table().' att
                    WHERE att.iid = '.$this->get_ref_id();
            $query = Database::query($sql);
            $this->attendance_data = Database::fetch_array($query);
        }

        return $this->attendance_data;
    }
}

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

    /** @var array|null */
    private ?array $attendance_data = null;

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
        $attendanceId = (int) $this->get_ref_id();

        $tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
        $tbl_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $tbl_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);

        // Load attendance settings (qualify max + require unique mode)
        $sql = 'SELECT attendance_qualify_max, require_unique
        FROM '.$this->get_attendance_table().'
        WHERE iid = '.$attendanceId;
        $query = Database::query($sql);
        $attendance = Database::fetch_assoc($query);

        $qualifyMax = (float) ($attendance['attendance_qualify_max'] ?? 0);
        $requireUnique = !empty($attendance['require_unique']);

        // Avoid gradebook showing 0/0 in require-unique mode when qualify max was never set.
        if ($requireUnique && $qualifyMax <= 0) {
            // Using 100 matches the UI expectation ("100% when present at least once").
            $qualifyMax = 100.0;
        }

        // ------------------------------------------------------------
        // Require-unique mode: 100% score if present at least once
        // This is calculated from attendance sheets to avoid relying on
        // potentially ambiguous aggregated scores in c_attendance_result.
        // Presence states considered "present":
        // 1 = Present, 2 = Late < 15 min, 3 = Late > 15 min
        // ------------------------------------------------------------
        if ($requireUnique) {
            // Single student
            if (null !== $studentId) {
                $studentId = (int) $studentId;

                $sqlHasPresence = 'SELECT 1
                FROM '.$tbl_sheet.' s
                INNER JOIN '.$tbl_calendar.' c ON c.iid = s.attendance_calendar_id
                WHERE c.attendance_id = '.$attendanceId.'
                  AND s.user_id = '.$studentId.'
                  AND s.presence IN (1, 2, 3)
                LIMIT 1';

                $hasPresence = Database::num_rows(Database::query($sqlHasPresence)) > 0;
                if ('default' === $type) {
                    return [$hasPresence ? 1.0 : 0.0, 1];
                }

                return [$hasPresence ? $qualifyMax : 0.0, $qualifyMax];
            }

            // All students (average / best / ranking / default)
            $students = [];      // user_id => final score for that user
            $sumRatio = 0.0;     // sum(score/max) across users
            $sumScore = 0.0;     // sum(score) across users
            $bestScore = 0.0;
            $resultCount = 0;
            $weight = $qualifyMax;

            // Aggregate per user: has_presence = 1 if any presence in (1,2,3)
            $sqlAll = 'SELECT s.user_id,
                      MAX(CASE WHEN s.presence IN (1, 2, 3) THEN 1 ELSE 0 END) AS has_presence
               FROM '.$tbl_sheet.' s
               INNER JOIN '.$tbl_calendar.' c ON c.iid = s.attendance_calendar_id
               WHERE c.attendance_id = '.$attendanceId.'
               GROUP BY s.user_id';

            $rs = Database::query($sqlAll);

            while ($row = Database::fetch_assoc($rs)) {
                $uid = (int) ($row['user_id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }

                $finalScore = ((int) ($row['has_presence'] ?? 0) === 1) ? $qualifyMax : 0.0;
                $students[$uid] = $finalScore;
            }

            // Compute stats (keep legacy behavior when qualifyMax is 0)
            foreach ($students as $uid => $finalScore) {
                if (0 != $qualifyMax) {
                    $resultCount++;
                    $sumRatio += $finalScore / $qualifyMax;
                    $sumScore += $finalScore;

                    if ($finalScore > $bestScore) {
                        $bestScore = $finalScore;
                    }
                }
            }

            if (0 == $resultCount) {
                return [null, null];
            }

            switch ($type) {
                case 'best':
                    return [$bestScore, $weight];

                case 'average':
                    return [$sumScore / $resultCount, $weight];

                case 'ranking':
                    return AbstractLink::getCurrentUserRanking($studentId, $students);

                default:
                    // Default expected format: [sum of ratios, number of users]
                    return [$sumRatio, $resultCount];
            }
        }

        $sql = 'SELECT user_id, score
        FROM '.$tbl_attendance_result.'
        WHERE attendance_id = '.$attendanceId;

        if (null !== $studentId) {
            $sql .= ' AND user_id = '.(int) $studentId;
        }

        $scores = Database::query($sql);

        // Single student
        if (null !== $studentId) {
            if ($row = Database::fetch_assoc($scores)) {
                $score = (float) ($row['score'] ?? 0);
                if ('default' === $type) {
                    return [$qualifyMax > 0 ? ($score / $qualifyMax) : 0.0, 1];
                }

                return [$score, $qualifyMax];
            }

            if ('default' === $type) {
                return [0.0, 1];
            }

            return [0.0, $qualifyMax];
        }

        // All students (average / best / ranking / default)
        $students = [];      // user_id => final score for that user
        $sumRatio = 0.0;     // sum(score/max) across users
        $sumScore = 0.0;     // sum(score) across users
        $bestScore = 0.0;
        $resultCount = 0;
        $weight = $qualifyMax;

        // Aggregate per user (legacy assumes one score per user)
        while ($row = Database::fetch_assoc($scores)) {
            $uid = (int) ($row['user_id'] ?? 0);
            $score = (float) ($row['score'] ?? 0);

            if ($uid <= 0) {
                continue;
            }

            if (!array_key_exists($uid, $students)) {
                $students[$uid] = $score;
            }
        }

        // Compute stats
        foreach ($students as $uid => $finalScore) {
            if (0 != $qualifyMax) {
                $resultCount++;
                $sumRatio += $finalScore / $qualifyMax;
                $sumScore += $finalScore;

                if ($finalScore > $bestScore) {
                    $bestScore = $finalScore;
                }
            }
        }

        if (0 == $resultCount) {
            return [null, null];
        }

        switch ($type) {
            case 'best':
                return [$bestScore, $weight];

            case 'average':
                return [$sumScore / $resultCount, $weight];

            case 'ranking':
                return AbstractLink::getCurrentUserRanking($studentId, $students);

            default:
                // Default expected format: [sum of ratios, number of users]
                return [$sumRatio, $resultCount];
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
        if (null === $this->attendance_data) {
            $sql = 'SELECT * FROM '.$this->get_attendance_table().' att
                    WHERE att.iid = '.(int) $this->get_ref_id();
            $query = Database::query($sql);
            $row = Database::fetch_assoc($query);
            $this->attendance_data = is_array($row) ? $row : [];
        }

        return $this->attendance_data;
    }
}

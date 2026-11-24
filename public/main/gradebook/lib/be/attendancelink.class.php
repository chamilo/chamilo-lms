<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceResult;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;

/**
 * Gradebook link to attendance item.
 *
 * @author Christian Fasanando (christian1827@gmail.com)
 */
class AttendanceLink extends AbstractLink
{
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
        $number = (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.iid)')
            ->from(CAttendanceResult::class, 'r')
            ->where('IDENTITY(r.attendance) = :attendanceId')
            ->setParameter('attendanceId', $this->get_ref_id())
            ->getQuery()
            ->getSingleScalarResult();

        return 0 != $number;
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

        $em = Container::getEntityManager();

        // Load attendance settings (qualify max + require unique mode)
        $attendance = $em->createQueryBuilder()
            ->select('a.attendanceQualifyMax AS attendance_qualify_max', 'a.requireUnique AS require_unique')
            ->from(CAttendance::class, 'a')
            ->where('a.iid = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
            ->getQuery()
            ->getOneOrNullResult();

        $qualifyMax = (float) ($attendance['attendance_qualify_max'] ?? 0);
        $requireUnique = !empty($attendance['require_unique']);

        // Avoid gradebook showing 0/0 in require-unique mode when qualify max was never set.
        if ($requireUnique && $qualifyMax <= 0.0) {
            // Using 100 matches the UI expectation ("100% when present at least once").
            $qualifyMax = 100.0;
        }

        if (!$requireUnique && $qualifyMax <= 0.0) {
            $totalDates = (int) $em->createQueryBuilder()
                ->select('COUNT(c.iid)')
                ->from(CAttendanceCalendar::class, 'c')
                ->where('IDENTITY(c.attendance) = :attendanceId')
                ->setParameter('attendanceId', $attendanceId)
                ->getQuery()
                ->getSingleScalarResult();

            if ($totalDates > 0) {
                $qualifyMax = (float) $totalDates;
            }
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

                $hasPresence = [] !== $em->createQueryBuilder()
                    ->select('s.iid')
                    ->from(CAttendanceSheet::class, 's')
                    ->innerJoin('s.attendanceCalendar', 'c')
                    ->where('IDENTITY(c.attendance) = :attendanceId')
                    ->andWhere('IDENTITY(s.user) = :studentId')
                    ->andWhere('s.presence IN (1, 2, 3)')
                    ->setParameter('attendanceId', $attendanceId)
                    ->setParameter('studentId', $studentId)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getArrayResult();
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
            $rs = $em->createQueryBuilder()
                ->select(
                    'IDENTITY(s.user) AS user_id',
                    'MAX(CASE WHEN s.presence IN (1, 2, 3) THEN 1 ELSE 0 END) AS has_presence'
                )
                ->from(CAttendanceSheet::class, 's')
                ->innerJoin('s.attendanceCalendar', 'c')
                ->where('IDENTITY(c.attendance) = :attendanceId')
                ->groupBy('s.user')
                ->setParameter('attendanceId', $attendanceId)
                ->getQuery()
                ->getArrayResult();

            foreach ($rs as $row) {
                $uid = (int) ($row['user_id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }

                $finalScore = ((int) ($row['has_presence'] ?? 0) === 1) ? $qualifyMax : 0.0;
                $students[$uid] = $finalScore;
            }

            // Compute stats (keep legacy behavior when qualifyMax is 0)
            foreach ($students as $uid => $finalScore) {
                if (0.0 != $qualifyMax) {
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

        $qb = $em->createQueryBuilder()
            ->select('IDENTITY(r.user) AS user_id', 'r.score AS score')
            ->from(CAttendanceResult::class, 'r')
            ->where('IDENTITY(r.attendance) = :attendanceId')
            ->setParameter('attendanceId', $attendanceId);

        if (null !== $studentId) {
            $qb->andWhere('IDENTITY(r.user) = :studentId')
                ->setParameter('studentId', (int) $studentId);
        }

        $scores = $qb->getQuery()->getArrayResult();

        // Single student
        if (null !== $studentId) {
            if ($row = ($scores[0] ?? false)) {
                $score = (float) ($row['score'] ?? 0);
                if ('default' === $type) {
                    return [$qualifyMax > 0.0 ? ($score / $qualifyMax) : 0.0, 1];
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
        foreach ($scores as $row) {
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
            if (0.0 != $qualifyMax) {
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
        $number = (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(a.iid)')
            ->from(CAttendance::class, 'a')
            ->where('a.iid = :attendanceId')
            ->setParameter('attendanceId', $this->get_ref_id())
            ->getQuery()
            ->getSingleScalarResult();

        return 0 != $number;
    }

    /**
     * @throws Exception
     */
    public function get_link(): string
    {
        // it was extracts the attendance id
        $sessionId = $this->get_session_id();
        $row = Container::getEntityManager()->createQueryBuilder()
            ->select('a.iid AS iid')
            ->from(CAttendance::class, 'a')
            ->where('a.iid = :attendanceId')
            ->setParameter('attendanceId', $this->get_ref_id())
            ->getQuery()
            ->getOneOrNullResult();
        $id = $row['iid'] ?? null;

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
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    private function get_attendance_data(): array
    {
        if (null === $this->attendance_data) {
            // Only the columns read through $this->attendance_data (the table has no "name" column).
            $row = Container::getEntityManager()->createQueryBuilder()
                ->select('att.iid AS iid', 'att.attendanceQualifyTitle AS attendance_qualify_title')
                ->from(CAttendance::class, 'att')
                ->where('att.iid = :attendanceId')
                ->setParameter('attendanceId', (int) $this->get_ref_id())
                ->getQuery()
                ->getOneOrNullResult();
            $this->attendance_data = is_array($row) ? $row : [];
        }

        return $this->attendance_data;
    }
}

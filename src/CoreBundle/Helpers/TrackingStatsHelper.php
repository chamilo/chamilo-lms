<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Tracking;

use const PATHINFO_FILENAME;
use const PHP_ROUND_HALF_UP;

/**
 * Helper for progress/grade/certificate aggregated statistics,
 * reusable from API Platform controllers.
 */
class TrackingStatsHelper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly CidReqHelper $cidReqHelper,
        private readonly CourseRepository $courseRepo,
        private readonly SessionRepository $sessionRepo,
        private readonly CLpRepository $lpRepo
    ) {}

    /**
     * Average learning path progress (0..100) for a user within a course/session.
     * Uses CLpRepository to fetch course LPs and their latest user progress.
     *
     * @return array{avg: float, count: int}
     */
    public function getUserAvgLpProgress(User $user, Course $course, ?Session $session): array
    {
        // Load all LPs for the course (optionally scoped by session); "published" filter kept by default.
        $qb = $this->lpRepo->findAllByCourse($course, $session);
        $lps = $qb->getQuery()->getResult();

        if (!$lps) {
            return ['avg' => 0.0, 'count' => 0];
        }

        // Get the latest progress per LP for this user.
        //    Repository is expected to return a map for all LP ids (missing progress -> 0).
        $progressMap = $this->lpRepo->lastProgressForUser($lps, $user, $session);
        $count = \count($progressMap);
        if (0 === $count) {
            return ['avg' => 0.0, 'count' => 0];
        }

        // Arithmetic mean across LPs (LPs without any view count as 0%).
        $sum = 0.0;
        foreach ($progressMap as $pct) {
            $sum += (float) $pct;
        }

        $avg = round($sum / $count, 2, PHP_ROUND_HALF_UP);

        return ['avg' => $avg, 'count' => $count];
    }
    public function getCourseVisits(Course $course, ?Session $session = null): int
    {
        $conn = $this->em->getConnection();
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = 'SELECT COUNT(course_access_id) FROM '.$table.' WHERE c_id = :cId';
        $params = ['cId' => $course->getId()];
        $types = ['cId' => ParameterType::INTEGER];

        if ($session !== null) {
            $sql .= ' AND session_id = :sessionId';
            $params['sessionId'] = $session->getId();
            $types['sessionId'] = ParameterType::INTEGER;
        }
        $count = $conn->fetchOne($sql, $params, $types);
        return (int) $count;
    }

    /**
     * Certificates of a user within a course/session.
     *
     * @return array<int, array{id:int,title:string,issuedAt:string,downloadUrl:?string}>
     */
    public function getUserCertificates(User $user, Course $course, ?Session $session): array
    {
        // Locate the Gradebook Category that ties this course/session.
        $category = $this->em->getRepository(GradebookCategory::class)->findOneBy([
            'course' => $course,
            'session' => $session, // will match NULL if $session is null
        ]);

        // If there is no category, there cannot be a course/session certificate.
        if (!$category) {
            return [];
        }

        // Read gradebook_certificate rows (DBAL keeps it simple even if there's no Doctrine entity).
        //    Expected columns: id, user_id, cat_id, created_at, path_certificate
        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT id, created_at, path_certificate
             FROM gradebook_certificate
             WHERE user_id = :uid AND cat_id = :cat
             ORDER BY created_at DESC',
            ['uid' => $user->getId(), 'cat' => $category->getId()]
        );

        // Build a public-ish URL if possible (fallback to null if you serve via a controller).
        $title = $category->getTitle() ?? 'Course certificate';

        $out = [];
        foreach ($rows as $r) {
            $issuedAt = !empty($r['created_at'])
                ? (new DateTime($r['created_at']))->format('c')
                : (new DateTime())->format('c');

            $downloadUrl = $this->buildCertificateUrlFromPath($r['path_certificate'] ?? null);

            $out[] = [
                'id' => (int) $r['id'],
                'title' => $title,
                'issuedAt' => $issuedAt,
                'downloadUrl' => $downloadUrl,
            ];
        }

        return $out;
    }

    /**
     * Build a public URL from a stored certificate path (legacy-compatible).
     * Return null if you prefer serving it through a Symfony controller.
     */
    private function buildCertificateUrlFromPath(?string $path): ?string
    {
        // Expected legacy format: "<hash>.html" placed under a public "certificates/" path.
        if (!$path) {
            return null;
        }
        $hash = pathinfo($path, PATHINFO_FILENAME);
        if (!$hash) {
            return null;
        }

        // If you have a Symfony route, replace the line below with $router->generate(...)
        return '/certificates/'.$hash.'.html';
    }

    /**
     * Global gradebook score for a user within a course/session.
     *
     * @return array{score: float, max: float, percentage: float}
     */
    public function getUserGradebookGlobal(User $user, Course $course, ?Session $session): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(r.score), 0) AS score_sum', 'COALESCE(SUM(e.max), 0) AS max_sum')
            ->from(GradebookResult::class, 'r')
            ->innerJoin('r.evaluation', 'e')
            ->innerJoin('e.category', 'c')
            ->where('c.course = :course')
            ->andWhere('e.visible = 1')
            ->andWhere('c.visible = 1')
            ->andWhere('r.user = :user')
            ->setParameter('course', $course, ParameterType::INTEGER)
            ->setParameter('user', $user, ParameterType::INTEGER)
        ;

        if ($session) {
            $qb->andWhere('c.session = :session')->setParameter('session', $session, ParameterType::INTEGER);
        } else {
            $qb->andWhere('c.session IS NULL');
        }

        $row = $qb->getQuery()->getSingleResult();
        $score = (float) $row['score_sum'];
        $max = (float) $row['max_sum'];

        if ($max <= 0.0) {
            return ['score' => 0.0, 'max' => 0.0, 'percentage' => 0.0];
        }

        $pct = ($score / $max) * 100.0;

        return [
            'score' => round($score, 2, PHP_ROUND_HALF_UP),
            'max' => round($max, 2, PHP_ROUND_HALF_UP),
            'percentage' => round($pct, 2, PHP_ROUND_HALF_UP),
        ];
    }

    /**
     * Average grade (0..100) across all participants for a course/session.
     *
     * @return array{avg: float, participants: int}
     */
    public function getCourseAverageScore(Course $course, ?Session $session): array
    {
        $participants = $this->getStudentParticipants($course, $session);
        $n = \count($participants);
        if (0 === $n) {
            return ['avg' => 0.0, 'participants' => 0];
        }

        $sumPct = 0.0;
        foreach ($participants as $user) {
            // Per-user: average score for tests/SCOs inside LPs of this course/session.
            $sumPct += $this->getUserAvgExerciseScore($user, $course, $session);
        }

        return ['avg' => round($sumPct / $n, 2, PHP_ROUND_HALF_UP), 'participants' => $n];
    }

    /**
     * User's average score (0..100) across LP tests/SCOs in a course/session.
     * Thin wrapper around legacy Tracking::get_avg_student_score.
     */
    private function getUserAvgExerciseScore(User $user, Course $course, ?Session $session): float
    {
        // Uses the legacy method (as seen in myStudents).
        $pct = Tracking::get_avg_student_score(
            $user->getId(),
            $course,
            [],       // all LPs
            $session  // session (or null)
        );

        return is_numeric($pct) ? (float) $pct : 0.0;
    }

    public function getCourseAverageProgress(Course $course, ?Session $session): array
    {
        // Delegates to the fast variant.
        return $this->getCourseAverageProgressFast($course, $session);
    }

    /**
     * Fast average progress (0..100) for a course/session.
     * Counts ALL LPs in the course (even if the user never opened them),
     * using the latest CLpView per (user, lp).
     *
     * @return array{avg: float, participants: int}
     */
    public function getCourseAverageProgressFast(Course $course, ?Session $session): array
    {
        $participants = $this->getStudentParticipants($course, $session);
        $n = \count($participants);
        if (0 === $n) {
            return ['avg' => 0.0, 'participants' => 0];
        }

        // Make LP query consistent with getUserAvgLpProgress (published filter = true)
        $lps = $this->lpRepo->findAllByCourse($course, $session)
            ->getQuery()
            ->getResult()
        ;

        if (!$lps) {
            return ['avg' => 0.0, 'participants' => $n];
        }

        $lpIds = array_map(static fn ($lp) => (int) $lp->getIid(), $lps);
        $lpCount = \count($lpIds);

        $qb = $this->em->createQueryBuilder();
        $qb->select('IDENTITY(v.user) AS uid', 'SUM(COALESCE(v.progress, 0)) AS sum_p')
            ->from(CLpView::class, 'v')
            ->where('IDENTITY(v.lp) IN (:lpIds)')
            ->andWhere($session ? 'v.session = :session' : 'v.session IS NULL')
            ->andWhere(
                'v.iid = (
                SELECT MAX(v2.iid) FROM '.CLpView::class.' v2
                WHERE v2.user = v.user AND v2.lp = v.lp '.
                ($session ? 'AND v2.session = :session' : 'AND v2.session IS NULL').'
            )'
            )
            ->groupBy('v.user')
            ->setParameter('lpIds', $lpIds)
        ;

        if ($session) {
            $qb->setParameter('session', $session, ParameterType::INTEGER);
        }

        $rows = $qb->getQuery()->getArrayResult();
        $sumByUser = [];
        foreach ($rows as $r) {
            $sumByUser[(int) $r['uid']] = (float) $r['sum_p'];
        }

        $totalAvg = 0.0;
        foreach ($participants as $user) {
            $userSum = $sumByUser[$user->getId()] ?? 0.0;
            $userAvg = $lpCount > 0 ? ($userSum / $lpCount) : 0.0;
            $totalAvg += $userAvg;
        }

        return ['avg' => round($totalAvg / $n, 2, PHP_ROUND_HALF_UP), 'participants' => $n];
    }

    /**
     * Returns student users for a course/session.
     *
     * @return User[]
     *
     * @throws Exception
     */
    private function getStudentParticipants(Course $course, ?Session $session): array
    {
        if ($session) {
            return $this->em->createQueryBuilder()
                ->select('DISTINCT u')
                ->from(User::class, 'u')
                ->innerJoin(SessionRelCourseRelUser::class, 'scru', 'WITH', 'scru.user = u')
                ->where('scru.course = :course')
                ->andWhere('scru.session = :session')
                ->andWhere('u.active = :active')
                ->setParameter('course', $course, ParameterType::INTEGER)
                ->setParameter('session', $session, ParameterType::INTEGER)
                ->setParameter('active', User::ACTIVE, ParameterType::INTEGER)
                ->getQuery()
                ->getResult()
            ;
        }

        $conn = $this->em->getConnection();

        $userIds = $conn->fetchFirstColumn(
            'SELECT DISTINCT user_id
         FROM course_rel_user
         WHERE c_id = :cid
         /* AND status = 0 */',
            ['cid' => (int) $course->getId()]
        );

        if (!$userIds) {
            $userIds = $conn->fetchFirstColumn(
                'SELECT DISTINCT user_id
             FROM session_rel_course_rel_user
             WHERE c_id = :cid AND (session_id = 0 OR session_id IS NULL)',
                ['cid' => (int) $course->getId()]
            );
        }

        if (!$userIds) {
            return [];
        }

        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id IN (:ids)')
            ->andWhere('u.active = :active')
            ->setParameter('ids', array_map('intval', $userIds))
            ->setParameter('active', User::ACTIVE)
            ->getQuery()
            ->getResult()
        ;
    }
}

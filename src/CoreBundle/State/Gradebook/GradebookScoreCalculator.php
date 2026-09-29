<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\GradebookCalculationMode;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceResult;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CForumThreadQualify;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\CourseBundle\Repository\CSurveyInvitationRepository;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

use const DATE_ATOM;

/**
 * Authoritative read-only Gradebook score calculator.
 *
 * The service mirrors the current legacy Gradebook score semantics while using
 * Doctrine entities/repositories. Vue only renders the returned values.
 */
final class GradebookScoreCalculator
{
    /**
     * @var array<string, array<string, mixed>|null>
     */
    private array $completionEvaluationCache = [];
    private readonly CourseCompletionRuleEvaluator $courseCompletionRuleEvaluator;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly GradebookLinkResourceResolver $linkResourceResolver,
        private readonly CStudentPublicationRepository $studentPublicationRepository,
        private readonly CSurveyInvitationRepository $surveyInvitationRepository,
        Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
        $this->courseCompletionRuleEvaluator = new CourseCompletionRuleEvaluator($connection);
    }

    /**
     * @return array{
     *     score: float|null,
     *     maxScore: float|null,
     *     percentage: float|null,
     *     attempts: int,
     *     date: string|null,
     *     weightedScore: float|null,
     *     weight: float,
     *     hasResult: bool
     * }
     */
    public function calculateCategory(
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
        bool $includeHidden = false,
    ): array {
        $this->assertCategoryContext($category, $course, $session);

        $completionResult = $this->getCourseCompletionCategoryResult($category, $user, $course, $session);
        if (null !== $completionResult) {
            return $completionResult;
        }

        $scoreSum = 0.0;
        $weightSum = 0.0;
        $hasResult = false;

        foreach ($category->getSubCategories() as $subCategory) {
            if (!$subCategory instanceof GradebookCategory || !$this->sameCategoryContext($subCategory, $course, $session)) {
                continue;
            }
            if (!$includeHidden && !$subCategory->getVisible()) {
                continue;
            }

            $weight = (float) $subCategory->getWeight();
            if (0.0 !== $weight) {
                $weightSum += $weight;
            }

            $result = $this->calculateCategory($subCategory, $user, $course, $session, $includeHidden);
            if (null === $result['score'] || null === $result['maxScore'] || 0.0 === $result['maxScore'] || 0.0 === $weight) {
                continue;
            }

            $scoreSum += ($result['score'] / $result['maxScore']) * $weight;
            $hasResult = $hasResult || $result['hasResult'];
        }

        foreach ($category->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof GradebookEvaluation || (int) $evaluation->getCourse()->getId() !== (int) $course->getId()) {
                continue;
            }
            if (!$includeHidden && 1 !== (int) $evaluation->getVisible()) {
                continue;
            }

            $weight = (float) $evaluation->getWeight();
            if (0.0 !== $weight) {
                $weightSum += $weight;
            }

            $result = $this->calculateEvaluation($evaluation, $user);
            if (null === $result['score'] || null === $result['maxScore'] || 0.0 === $result['maxScore'] || 0.0 === $weight) {
                continue;
            }

            $scoreSum += ($result['score'] / $result['maxScore']) * $weight;
            $hasResult = $hasResult || $result['hasResult'];
        }

        foreach ($category->getLinks() as $link) {
            if (!$link instanceof GradebookLink || (int) $link->getCourse()->getId() !== (int) $course->getId()) {
                continue;
            }
            if (!$includeHidden && 1 !== (int) $link->getVisible()) {
                continue;
            }

            $weight = $this->getLinkWeight($link);
            if (0.0 !== $weight) {
                $weightSum += $weight;
            }

            $result = $this->calculateLink($link, $user, $course, $session);
            if (null === $result['score'] || null === $result['maxScore'] || 0.0 === $result['maxScore'] || 0.0 === $weight) {
                continue;
            }

            $scoreSum += ($result['score'] / $result['maxScore']) * $weight;
            $hasResult = $hasResult || $result['hasResult'];
        }

        $denominator = GradebookCalculationMode::POINTS_SUM === $category->getCalculationMode()
            ? 100.0
            : $weightSum;

        return $this->buildResult(
            $scoreSum,
            $denominator,
            0,
            null,
            (float) $category->getWeight(),
            $hasResult,
        );
    }

    /**
     * Return the persisted course-completion component score used by the legacy
     * Gradebook table when the current category is the course root category.
     *
     * @return array{
     *     score: float|null,
     *     maxScore: float|null,
     *     percentage: float|null,
     *     attempts: int,
     *     date: string|null,
     *     weightedScore: float|null,
     *     weight: float,
     *     hasResult: bool
     * }|null
     */
    public function calculateConfiguredItem(
        GradebookEvaluation|GradebookLink $item,
        GradebookCategory $currentCategory,
        User $user,
        Course $course,
        ?Session $session,
    ): ?array {
        if (null !== $currentCategory->getParent() || !$this->sameCategoryContext($currentCategory, $course, $session)) {
            return null;
        }

        $componentType = null;
        $resourceId = 0;
        $weight = 0.0;

        if ($item instanceof GradebookEvaluation) {
            $componentType = 'evaluation';
            $resourceId = (int) $item->getId();
            $weight = (float) $item->getWeight();
        } else {
            $componentType = match ((int) $item->getType()) {
                GradebookLinkResourceResolver::LINK_FORUM_THREAD => 'forum',
                GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION => 'work',
                GradebookLinkResourceResolver::LINK_EXERCISE => 'exercise',
                default => null,
            };
            $resourceId = (int) $item->getRefId();
            $weight = $this->getLinkWeight($item);
        }

        if (null === $componentType || $resourceId <= 0) {
            return null;
        }

        $evaluation = $this->getCompletionEvaluation(
            $user,
            $course,
            $session,
            (float) ($currentCategory->getCertifMinScore() ?? 0),
        );
        if (null === $evaluation
            || true !== ($evaluation['supported'] ?? false)
            || true !== ($evaluation['complete'] ?? false)
            || !isset($evaluation['components'])
            || !\is_array($evaluation['components'])
        ) {
            return null;
        }

        foreach ($evaluation['components'] as $component) {
            if (!\is_array($component) || $componentType !== ($component['type'] ?? null)) {
                continue;
            }

            $mappedResourceId = (int) ($component['mapped_resource_id'] ?? 0);
            if ($mappedResourceId <= 0) {
                $mappedResourceId = (int) ($component['resource_id'] ?? 0);
            }
            if ($mappedResourceId !== $resourceId) {
                continue;
            }

            $attempts = max(0, (int) ($component['attempts'] ?? 0));
            if ($attempts <= 0) {
                $maxScore = 'forum' === $componentType
                    ? (float) ($component['weight'] ?? 0.0)
                    : (float) ($component['raw_max'] ?? 100.0);
                if ($maxScore <= 0.0) {
                    $maxScore = 100.0;
                }

                return $this->buildResult(null, $maxScore, 0, null, $weight, false);
            }

            if ('forum' === $componentType) {
                $maxScore = (float) ($component['weight'] ?? 0.0);
                if ($maxScore <= 0.0) {
                    return null;
                }

                return $this->buildResult(
                    (float) ($component['score'] ?? 0.0),
                    $maxScore,
                    $attempts,
                    null,
                    $weight,
                    true,
                );
            }

            $rawScore = $component['raw_score'] ?? null;
            $rawMax = (float) ($component['raw_max'] ?? 100.0);
            if (null === $rawScore) {
                return $this->buildResult(null, $rawMax, $attempts, null, $weight, false);
            }
            if ($rawMax <= 0.0) {
                $rawMax = 100.0;
            }

            return $this->buildResult(
                (float) $rawScore,
                $rawMax,
                $attempts,
                null,
                $weight,
                true,
            );
        }

        return null;
    }

    /**
     * @return array{
     *     score: float|null,
     *     maxScore: float|null,
     *     percentage: float|null,
     *     attempts: int,
     *     date: string|null,
     *     weightedScore: float|null,
     *     weight: float,
     *     hasResult: bool
     * }
     */
    public function calculateEvaluation(GradebookEvaluation $evaluation, User $user): array
    {
        if ($this->isSettingEnabled('gradebook.allow_gradebook_stats')) {
            $scoreList = $evaluation->getUserScoreList();
            $userId = (int) $user->getId();
            $hasResult = \array_key_exists($userId, $scoreList);
            $score = $hasResult && is_numeric($scoreList[$userId])
                ? (float) $scoreList[$userId]
                : 0.0;

            return $this->buildResult(
                $score,
                (float) $evaluation->getMax(),
                $hasResult ? 1 : 0,
                null,
                (float) $evaluation->getWeight(),
                $hasResult,
            );
        }

        $result = $this->entityManager->getRepository(GradebookResult::class)->findOneBy(
            [
                'evaluation' => $evaluation,
                'user' => $user,
            ],
            ['id' => 'DESC'],
        );

        $score = null;
        $date = null;
        if ($result instanceof GradebookResult && null !== $result->getScore()) {
            $score = (float) $result->getScore();
            $date = $this->formatDate($result->getCreatedAt());
        }

        return $this->buildResult(
            $score,
            (float) $evaluation->getMax(),
            null !== $score ? 1 : 0,
            $date,
            (float) $evaluation->getWeight(),
            null !== $score,
        );
    }

    /**
     * @return array{
     *     score: float|null,
     *     maxScore: float|null,
     *     percentage: float|null,
     *     attempts: int,
     *     date: string|null,
     *     weightedScore: float|null,
     *     weight: float,
     *     hasResult: bool
     * }
     */
    public function calculateLink(
        GradebookLink $link,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        if ((int) $link->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The Gradebook link belongs to another course.');
        }

        try {
            $resource = $this->linkResourceResolver->requireResource(
                (int) $link->getType(),
                (int) $link->getRefId(),
                $course,
                $session,
            );
        } catch (AccessDeniedHttpException|BadRequestHttpException|NotFoundHttpException) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        return match ((int) $link->getType()) {
            GradebookLinkResourceResolver::LINK_EXERCISE => $this->calculateExercise($link, $resource, $user, $course, $session),
            GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION => $this->calculateAssignment($link, $resource, $user, $course, $session),
            GradebookLinkResourceResolver::LINK_LEARNING_PATH => $this->calculateLearningPath($link, $resource, $user, $course, $session),
            GradebookLinkResourceResolver::LINK_FORUM_THREAD => $this->calculateForumThread($link, $resource, $user, $course),
            GradebookLinkResourceResolver::LINK_FORUM_PARTICIPATION => $this->calculateForumParticipation($link, $resource, $user),
            GradebookLinkResourceResolver::LINK_ATTENDANCE => $this->calculateAttendance($link, $resource, $user),
            GradebookLinkResourceResolver::LINK_SURVEY => $this->calculateSurvey($link, $resource, $user, $course, $session),
            default => $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateExercise(
        GradebookLink $link,
        object $resource,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        if (!$resource instanceof CQuiz) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        if ($this->isSettingEnabled('gradebook.allow_gradebook_stats')) {
            $scoreList = $link->getUserScoreList();
            $userId = (int) $user->getId();
            $hasResult = \array_key_exists($userId, $scoreList);
            $score = $hasResult && is_numeric($scoreList[$userId])
                ? (float) $scoreList[$userId]
                : 0.0;
            $scoreWeight = $link->getScoreWeight();
            $maxScore = is_numeric($scoreWeight) ? (float) $scoreWeight : null;

            return $this->buildResult(
                $score,
                $maxScore,
                $hasResult ? 1 : 0,
                null,
                $this->getLinkWeight($link),
                $hasResult,
            );
        }

        $configuredResult = $this->getConfiguredExerciseResult($resource, $user, $course, $session);
        if (null !== $configuredResult) {
            return $this->buildResult(
                $configuredResult['score'],
                $configuredResult['maxScore'],
                $configuredResult['attempts'],
                null,
                $this->getLinkWeight($link),
                null !== $configuredResult['score'],
            );
        }

        $quizId = (int) ($resource->getIid() ?? 0);
        $courseId = (int) $course->getId();
        $userId = (int) $user->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;

        $qb = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->where('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('IDENTITY(attempt.quiz) = :quizId')
            ->andWhere('attempt.status <> :incompleteStatus')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('userId', $userId, Types::INTEGER)
            ->setParameter('quizId', $quizId, Types::INTEGER)
            ->setParameter('incompleteStatus', 'incomplete', Types::STRING)
        ;

        if ($sessionId > 0) {
            $qb
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        } else {
            $qb->andWhere('attempt.session IS NULL');
        }

        if (!$this->exerciseExistsInLearningPath($resource, $course, $session)) {
            $qb
                ->andWhere('attempt.origLpId = 0')
                ->andWhere('attempt.origLpItemId = 0')
            ;
        }

        /** @var list<TrackEExercise> $attempts */
        $attempts = $qb
            ->orderBy('attempt.exeId', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if ([] === $attempts) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        $attempt = $attempts[0];

        return $this->buildResult(
            $attempt->getScore(),
            $attempt->getMaxScore(),
            \count($attempts),
            $this->formatDate($attempt->getExeDate()),
            $this->getLinkWeight($link),
            true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateAssignment(
        GradebookLink $link,
        object $resource,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        if (!$resource instanceof CStudentPublication) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        $qb = $this->studentPublicationRepository->getStudentAssignments($resource, $course, $session, null, $user);
        $qb
            ->andWhere('IDENTITY(resource.user) = :gradebookUserId')
            ->setParameter('gradebookUserId', (int) $user->getId(), Types::INTEGER)
        ;

        $order = $this->getSettingString('gradebook.student_publication_to_take_in_gradebook');
        if ('last' === $order) {
            $qb->orderBy('resource.sentDate', 'DESC');
        } else {
            $qb->orderBy('resource.iid', 'ASC');
        }

        $submission = $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if (!$submission instanceof CStudentPublication) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        return $this->buildResult(
            (float) $submission->getQualification(),
            (float) $resource->getQualification(),
            1,
            $this->formatDate($submission->getDateOfQualification()),
            $this->getLinkWeight($link),
            true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateLearningPath(
        GradebookLink $link,
        object $resource,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        if (!$resource instanceof CLp) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('view')
            ->from(CLpView::class, 'view')
            ->where('IDENTITY(view.course) = :courseId')
            ->andWhere('IDENTITY(view.lp) = :lpId')
            ->andWhere('IDENTITY(view.user) = :userId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('lpId', (int) ($resource->getIid() ?? 0), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('IDENTITY(view.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('view.session IS NULL');
        }

        $view = $qb
            ->orderBy('view.viewCount', 'DESC')
            ->addOrderBy('view.iid', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$view instanceof CLpView) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        return $this->buildResult(
            null !== $view->getProgress() ? (float) $view->getProgress() : 0.0,
            100.0,
            1,
            $this->formatDate($view->getCompletionDate()),
            $this->getLinkWeight($link),
            true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateForumThread(
        GradebookLink $link,
        object $resource,
        User $user,
        Course $course,
    ): array {
        if (!$resource instanceof CForumThread) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        $maxScore = (float) $resource->getThreadQualifyMax();
        $qb = $this->entityManager->createQueryBuilder()
            ->select('qualification')
            ->from(CForumThreadQualify::class, 'qualification')
            ->where('IDENTITY(qualification.thread) = :threadId')
            ->andWhere('IDENTITY(qualification.user) = :userId')
            ->andWhere('qualification.cId = :courseId')
            ->setParameter('threadId', (int) ($resource->getIid() ?? 0), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('qualification.qualifyTime', 'DESC')
        ;

        /** @var list<CForumThreadQualify> $qualifications */
        $qualifications = $qb->getQuery()->getResult();
        if (!$resource->isThreadPeerQualify()) {
            $qualification = $qualifications[0] ?? null;
            $score = $qualification instanceof CForumThreadQualify ? (float) $qualification->getQualify() : 0.0;

            return $this->buildResult(
                $score,
                $maxScore,
                $qualification instanceof CForumThreadQualify ? 1 : 0,
                $qualification instanceof CForumThreadQualify ? $this->formatDate($qualification->getQualifyTime()) : null,
                $this->getLinkWeight($link),
                $qualification instanceof CForumThreadQualify,
            );
        }

        if (\count($qualifications) <= 2) {
            return $this->buildResult(0.0, $maxScore, \count($qualifications), null, $this->getLinkWeight($link), false);
        }

        $score = 0.0;
        foreach ($qualifications as $qualification) {
            $score += (float) $qualification->getQualify();
        }

        return $this->buildResult(
            $score / \count($qualifications),
            $maxScore,
            \count($qualifications),
            null,
            $this->getLinkWeight($link),
            true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateForumParticipation(
        GradebookLink $link,
        object $resource,
        User $user,
    ): array {
        if (!$resource instanceof CForumThread) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        $postCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(post.iid)')
            ->from(CForumPost::class, 'post')
            ->where('IDENTITY(post.thread) = :threadId')
            ->andWhere('IDENTITY(post.user) = :userId')
            ->andWhere('post.visible = :visible')
            ->setParameter('threadId', (int) ($resource->getIid() ?? 0), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('visible', true, Types::BOOLEAN)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $pointsOne = (float) ($link->getPointsOne() ?? 0);
        $pointsMany = (float) ($link->getPointsMany() ?? 0);
        $effectiveMany = $pointsMany > 0.0 ? $pointsMany : $pointsOne;
        $maxScore = max($pointsOne, $effectiveMany);
        if ($maxScore <= 0.0) {
            $maxScore = 1.0;
        }

        $score = match (true) {
            $postCount <= 0 => 0.0,
            1 === $postCount => $pointsOne,
            default => $effectiveMany,
        };

        return $this->buildResult(
            $score,
            $maxScore,
            $postCount,
            null,
            $this->getLinkWeight($link),
            $postCount > 0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateAttendance(
        GradebookLink $link,
        object $resource,
        User $user,
    ): array {
        if (!$resource instanceof CAttendance) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        $attendanceId = (int) ($resource->getIid() ?? 0);
        $maxScore = (float) $resource->getAttendanceQualifyMax();

        if ($resource->isRequireUnique()) {
            if ($maxScore <= 0.0) {
                $maxScore = 100.0;
            }

            $presenceCount = (int) $this->entityManager->createQueryBuilder()
                ->select('COUNT(sheet.iid)')
                ->from(CAttendanceSheet::class, 'sheet')
                ->join('sheet.attendanceCalendar', 'calendar')
                ->where('IDENTITY(calendar.attendance) = :attendanceId')
                ->andWhere('IDENTITY(sheet.user) = :userId')
                ->andWhere('sheet.presence IN (:presentStates)')
                ->setParameter('attendanceId', $attendanceId, Types::INTEGER)
                ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
                ->setParameter('presentStates', [1, 2, 3], ArrayParameterType::INTEGER)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            return $this->buildResult(
                $presenceCount > 0 ? $maxScore : 0.0,
                $maxScore,
                $presenceCount,
                null,
                $this->getLinkWeight($link),
                $presenceCount > 0,
            );
        }

        if ($maxScore <= 0.0) {
            $maxScore = (float) $this->entityManager->createQueryBuilder()
                ->select('COUNT(calendar.iid)')
                ->from(CAttendanceCalendar::class, 'calendar')
                ->where('IDENTITY(calendar.attendance) = :attendanceId')
                ->setParameter('attendanceId', $attendanceId, Types::INTEGER)
                ->getQuery()
                ->getSingleScalarResult()
            ;
        }

        $result = $this->entityManager->createQueryBuilder()
            ->select('attendanceResult')
            ->from(CAttendanceResult::class, 'attendanceResult')
            ->where('IDENTITY(attendanceResult.attendance) = :attendanceId')
            ->andWhere('IDENTITY(attendanceResult.user) = :userId')
            ->setParameter('attendanceId', $attendanceId, Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $hasResult = $result instanceof CAttendanceResult;
        $score = $hasResult ? (float) $result->getScore() : 0.0;

        return $this->buildResult(
            $score,
            $maxScore,
            $hasResult ? 1 : 0,
            null,
            $this->getLinkWeight($link),
            $hasResult,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateSurvey(
        GradebookLink $link,
        object $resource,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        if (!$resource instanceof CSurvey) {
            return $this->buildResult(null, null, 0, null, $this->getLinkWeight($link), false);
        }

        $answered = $this->surveyInvitationRepository->hasUserAnswered($resource, $course, $user, $session);

        return $this->buildResult(
            $answered ? 1.0 : 0.0,
            1.0,
            $answered ? 1 : 0,
            null,
            $this->getLinkWeight($link),
            $answered,
        );
    }

    private function exerciseExistsInLearningPath(CQuiz $quiz, Course $course, ?Session $session): bool
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(item.iid)')
            ->from(CLpItem::class, 'item')
            ->join('item.lp', 'lp')
            ->join('lp.resourceNode', 'resourceNode')
            ->join('resourceNode.resourceLinks', 'resourceLink')
            ->where('item.itemType = :itemType')
            ->andWhere('item.path = :quizId')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.deletedAt IS NULL')
            ->setParameter('itemType', 'quiz', Types::STRING)
            ->setParameter('quizId', (string) ($quiz->getIid() ?? 0), Types::STRING)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $qb->andWhere('(IDENTITY(resourceLink.session) = :sessionId OR resourceLink.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return array{score: float|null, maxScore: float|null, attempts: int}|null
     */
    private function getConfiguredExerciseResult(
        CQuiz $quiz,
        User $user,
        Course $course,
        ?Session $session,
    ): ?array {
        $evaluation = $this->getCompletionEvaluation($user, $course, $session, 0.0);
        if (null === $evaluation || !isset($evaluation['components']) || !\is_array($evaluation['components'])) {
            return null;
        }

        $quizId = (int) ($quiz->getIid() ?? 0);
        foreach ($evaluation['components'] as $component) {
            if (!\is_array($component) || 'exercise' !== ($component['type'] ?? null)) {
                continue;
            }

            $resourceId = (int) ($component['resource_id'] ?? 0);
            $mappedResourceId = (int) ($component['mapped_resource_id'] ?? 0);
            if ($quizId !== $resourceId && $quizId !== $mappedResourceId) {
                continue;
            }

            return [
                'score' => isset($component['raw_score']) && is_numeric($component['raw_score'])
                    ? (float) $component['raw_score']
                    : null,
                'maxScore' => isset($component['raw_max']) && is_numeric($component['raw_max'])
                    ? (float) $component['raw_max']
                    : 100.0,
                'attempts' => max(0, (int) ($component['attempts'] ?? 0)),
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCourseCompletionCategoryResult(
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
    ): ?array {
        if (null !== $category->getParent()) {
            return null;
        }

        $evaluation = $this->getCompletionEvaluation(
            $user,
            $course,
            $session,
            (float) ($category->getCertifMinScore() ?? 0),
        );
        if (null === $evaluation
            || true !== ($evaluation['supported'] ?? false)
            || true !== ($evaluation['complete'] ?? false)
            || !isset($evaluation['score'])
            || !is_numeric($evaluation['score'])
        ) {
            return null;
        }

        return $this->buildResult(
            (float) $evaluation['score'],
            100.0,
            0,
            null,
            (float) $category->getWeight(),
            true,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCompletionEvaluation(
        User $user,
        Course $course,
        ?Session $session,
        float $minimumScore,
    ): ?array {
        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $cacheKey = (int) $user->getId().':'.$courseId.':'.$sessionId.':'.$minimumScore;
        if (!\array_key_exists($cacheKey, $this->completionEvaluationCache)) {
            try {
                $evaluation = $this->courseCompletionRuleEvaluator->evaluate(
                    (int) $user->getId(),
                    $courseId,
                    (string) $course->getCode(),
                    $minimumScore,
                    $sessionId,
                );
                $this->completionEvaluationCache[$cacheKey] = true === ($evaluation['supported'] ?? false)
                    ? $evaluation
                    : null;
            } catch (Throwable $exception) {
                $this->logger->warning(
                    'Could not evaluate the Gradebook course completion rule.',
                    [
                        'courseId' => $courseId,
                        'userId' => (int) $user->getId(),
                        'sessionId' => $sessionId,
                        'exception' => $exception,
                    ],
                );
                $this->completionEvaluationCache[$cacheKey] = null;
            }
        }

        return $this->completionEvaluationCache[$cacheKey];
    }

    private function getLinkWeight(GradebookLink $link): float
    {
        if (GradebookLinkResourceResolver::LINK_FORUM_PARTICIPATION !== (int) $link->getType()) {
            return (float) $link->getWeight();
        }

        $pointsOne = (float) ($link->getPointsOne() ?? 0);
        $pointsMany = (float) ($link->getPointsMany() ?? 0);
        $effectiveMany = $pointsMany > 0.0 ? $pointsMany : $pointsOne;

        return max($pointsOne, $effectiveMany);
    }

    private function assertCategoryContext(GradebookCategory $category, Course $course, ?Session $session): void
    {
        if (!$this->sameCategoryContext($category, $course, $session)) {
            throw new AccessDeniedHttpException('The Gradebook category belongs to another course or session context.');
        }
    }

    private function sameCategoryContext(GradebookCategory $category, Course $course, ?Session $session): bool
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            return false;
        }

        $categorySessionId = null !== $category->getSession() ? (int) $category->getSession()->getId() : 0;
        $sessionId = null !== $session ? (int) $session->getId() : 0;

        return $categorySessionId === $sessionId;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function getSettingString(string $name): string
    {
        $value = $this->settingsManager->getSetting($name, true);

        return strtolower(trim(\is_scalar($value) ? (string) $value : ''));
    }

    /**
     * @return array{
     *     score: float|null,
     *     maxScore: float|null,
     *     percentage: float|null,
     *     attempts: int,
     *     date: string|null,
     *     weightedScore: float|null,
     *     weight: float,
     *     hasResult: bool
     * }
     */
    private function buildResult(
        ?float $score,
        ?float $maxScore,
        int $attempts,
        ?string $date,
        float $weight,
        bool $hasResult,
    ): array {
        $percentage = null;
        $weightedScore = null;
        if (null !== $score && null !== $maxScore && 0.0 !== $maxScore) {
            $percentage = ($score / $maxScore) * 100.0;
            $weightedScore = ($score / $maxScore) * $weight;
        }

        return [
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'attempts' => max(0, $attempts),
            'date' => $date,
            'weightedScore' => $weightedScore,
            'weight' => $weight,
            'hasResult' => $hasResult,
        ];
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }
}

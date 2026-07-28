<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use const DATE_ATOM;

final readonly class UserCourseTestScoreProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function provide(Course $course, int $testId, string $userIdentifier): array
    {
        if ($testId <= 0) {
            throw new InvalidArgumentException('The test ID must be a positive integer.');
        }

        $userIdentifier = trim($userIdentifier);
        if ('' === $userIdentifier) {
            throw new InvalidArgumentException('The username or email is required.');
        }

        $quiz = $this->entityManager->getRepository(CQuiz::class)->find($testId);
        if (!$quiz instanceof CQuiz) {
            throw new InvalidArgumentException('The requested test was not found.');
        }

        if (null === $quiz->getResourceNode()?->getResourceLinkByContext($course, null, null)) {
            throw new InvalidArgumentException('The requested test does not belong to the selected base course.');
        }

        $user = str_contains($userIdentifier, '@')
            ? $this->userRepository->findByEmailCaseInsensitive($userIdentifier)
            : $this->userRepository->findByUsernameCaseInsensitive($userIdentifier);

        if (!$user instanceof User || null === $user->getId()) {
            throw new InvalidArgumentException('The requested user was not found.');
        }
        if (User::ACTIVE !== $user->getActive() || User::SOFT_DELETED === $user->getStatus()) {
            throw new InvalidArgumentException('The requested user is not active.');
        }

        $isStudent = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(courseUser.id)')
            ->from(CourseRelUser::class, 'courseUser')
            ->andWhere('courseUser.course = :courseId')
            ->andWhere('courseUser.user = :userId')
            ->andWhere('courseUser.status = :studentStatus')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('studentStatus', CourseRelUser::STUDENT, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;

        if (!$isStudent) {
            throw new InvalidArgumentException('The requested user is not a direct student of the selected base course.');
        }

        /** @var TrackEExercise[] $attempts */
        $attempts = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.quiz) = :testId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('attempt.session IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('testId', $testId, Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->orderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $completed = [];
        $incompleteAttemptCount = 0;
        foreach ($attempts as $attempt) {
            if ('incomplete' === $attempt->getStatus()) {
                ++$incompleteAttemptCount;

                continue;
            }

            $completed[] = self::normalizeAttempt($attempt);
        }

        $latest = $completed[0] ?? null;
        $best = null;
        foreach ($completed as $attempt) {
            if (null === $best || $attempt['percentage'] > $best['percentage']) {
                $best = $attempt;
            }
        }

        return [
            'scope' => 'base_course',
            'course' => [
                'course_id' => (int) $course->getId(),
                'title' => $course->getTitle(),
            ],
            'test' => [
                'quiz_id' => (int) $quiz->getIid(),
                'title' => $quiz->getTitle(),
            ],
            'user' => [
                'user_id' => (int) $user->getId(),
                'username' => $user->getUsername(),
                'full_name' => $user->getFullName(),
            ],
            'status' => [] !== $completed
                ? 'answered'
                : ($incompleteAttemptCount > 0 ? 'in_progress' : 'pending'),
            'completed_attempt_count' => \count($completed),
            'incomplete_attempt_count' => $incompleteAttemptCount,
            'latest_attempt' => $latest,
            'best_attempt' => $best,
            'attempts' => $completed,
        ];
    }

    /**
     * @return array{
     *     attempt_id: int,
     *     score: float,
     *     max_score: float,
     *     percentage: float,
     *     status: string,
     *     completed_at: string,
     *     duration_seconds: int
     * }
     */
    private static function normalizeAttempt(TrackEExercise $attempt): array
    {
        $maxScore = $attempt->getMaxScore();
        $percentage = $maxScore > 0.0
            ? round(($attempt->getScore() / $maxScore) * 100, 2)
            : 0.0;

        return [
            'attempt_id' => $attempt->getExeId(),
            'score' => round($attempt->getScore(), 2),
            'max_score' => round($maxScore, 2),
            'percentage' => $percentage,
            'status' => $attempt->getStatus(),
            'completed_at' => $attempt->getExeDate()->format(DATE_ATOM),
            'duration_seconds' => $attempt->getExeDuration(),
        ];
    }
}

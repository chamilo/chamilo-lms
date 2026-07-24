<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CourseTestResponseStatusProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{
     *     scope: 'base_course',
     *     course: array{course_id: int, title: string},
     *     test: array{quiz_id: int, title: string},
     *     students: array{
     *         total_students: int,
     *         answered_students: int,
     *         pending_students: int,
     *         in_progress_students: int,
     *         not_started_students: int,
     *         response_rate_percent: float
     *     },
     *     attempts: array{
     *         completed_attempts: int,
     *         incomplete_attempts: int,
     *         considered_attempts: int,
     *         ignored_non_student_attempts: int
     *     }
     * }
     */
    public function getBaseCourseStatus(Course $course, CQuiz $quiz): array
    {
        $courseId = (int) $course->getId();
        $quizId = (int) $quiz->getIid();
        $studentIds = $this->findActiveDirectStudentIds($courseId);
        $studentLookup = array_fill_keys($studentIds, true);
        $answeredStudents = [];
        $inProgressStudents = [];
        $completedAttempts = 0;
        $incompleteAttempts = 0;
        $ignoredNonStudentAttempts = 0;

        foreach ($this->findBaseCourseAttempts($courseId, $quizId) as $attempt) {
            $userId = (int) $attempt['userId'];

            if (!isset($studentLookup[$userId])) {
                ++$ignoredNonStudentAttempts;

                continue;
            }

            if ('incomplete' === (string) $attempt['status']) {
                ++$incompleteAttempts;

                if (!isset($answeredStudents[$userId])) {
                    $inProgressStudents[$userId] = true;
                }

                continue;
            }

            ++$completedAttempts;
            $answeredStudents[$userId] = true;
            unset($inProgressStudents[$userId]);
        }

        $totalStudents = \count($studentIds);
        $answeredStudentCount = \count($answeredStudents);
        $pendingStudentCount = max(0, $totalStudents - $answeredStudentCount);
        $inProgressStudentCount = \count($inProgressStudents);
        $notStartedStudentCount = max(0, $pendingStudentCount - $inProgressStudentCount);
        $responseRate = $totalStudents > 0
            ? round(($answeredStudentCount / $totalStudents) * 100, 2)
            : 0.0;

        return [
            'scope' => 'base_course',
            'course' => [
                'course_id' => $courseId,
                'title' => $course->getTitle(),
            ],
            'test' => [
                'quiz_id' => $quizId,
                'title' => $quiz->getTitle(),
            ],
            'students' => [
                'total_students' => $totalStudents,
                'answered_students' => $answeredStudentCount,
                'pending_students' => $pendingStudentCount,
                'in_progress_students' => $inProgressStudentCount,
                'not_started_students' => $notStartedStudentCount,
                'response_rate_percent' => $responseRate,
            ],
            'attempts' => [
                'completed_attempts' => $completedAttempts,
                'incomplete_attempts' => $incompleteAttempts,
                'considered_attempts' => $completedAttempts + $incompleteAttempts,
                'ignored_non_student_attempts' => $ignoredNonStudentAttempts,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function findActiveDirectStudentIds(int $courseId): array
    {
        /** @var list<array{userId: int|string}> $rows */
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select('DISTINCT student.id AS userId')
            ->from(CourseRelUser::class, 'subscription')
            ->innerJoin('subscription.user', 'student')
            ->andWhere('IDENTITY(subscription.course) = :courseId')
            ->andWhere('subscription.status = :studentStatus')
            ->andWhere('student.active = :active')
            ->andWhere('student.status != :softDeleted')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('studentStatus', CourseRelUser::STUDENT, Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->orderBy('student.id', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return array_values(array_map(
            static fn (array $row): int => (int) $row['userId'],
            $rows,
        ));
    }

    /**
     * @return list<array{attemptId: int|string, userId: int|string, status: string}>
     */
    private function findBaseCourseAttempts(int $courseId, int $quizId): array
    {
        /** @var list<array{attemptId: int|string, userId: int|string, status: string}> $rows */
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select(
                'attempt.exeId AS attemptId',
                'IDENTITY(attempt.user) AS userId',
                'attempt.status AS status',
            )
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.quiz) = :quizId')
            ->andWhere('attempt.session IS NULL')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('quizId', $quizId, Types::INTEGER)
            ->orderBy('attempt.exeId', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return $rows;
    }
}

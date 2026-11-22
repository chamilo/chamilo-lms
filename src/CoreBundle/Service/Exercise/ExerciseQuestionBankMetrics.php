<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;

/** Course/session-scoped usage and completed attempt metrics for the question bank. */
final readonly class ExerciseQuestionBankMetrics
{
    public const array SORT_FIELDS = ['title', 'category', 'difficulty', 'score', 'occurrences', 'correctAttempts', 'incorrectAttempts'];
    public const array METRIC_FIELDS = ['occurrences', 'correctAttempts', 'incorrectAttempts'];

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @param list<int> $ids
     */
    public function getMetrics(array $ids, Course $course, ?Session $session, ?string $sortMetric = null): array
    {
        $result = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach ($chunk as $id) {
                $result[$id] = ['occurrences' => 0, 'correctAttempts' => 0, 'incorrectAttempts' => 0];
            }
            if (null === $sortMetric || 'occurrences' === $sortMetric) {
                $usage = $this->entityManager->createQueryBuilder()
                    ->select('IDENTITY(relation.question) AS questionId', 'COUNT(DISTINCT quiz.iid) AS occurrences')
                    ->from(CQuizRelQuestion::class, 'relation')
                    ->innerJoin('relation.quiz', 'quiz')
                    ->innerJoin('quiz.resourceNode', 'node')
                    ->innerJoin('node.resourceLinks', 'link')
                    ->where('IDENTITY(relation.question) IN (:ids)')
                    ->andWhere('IDENTITY(link.course) = :courseId')
                    ->andWhere('link.deletedAt IS NULL AND link.endVisibilityAt IS NULL')
                    ->setParameter('ids', $chunk)
                    ->setParameter('courseId', $course->getId())
                    ->groupBy('relation.question')
                ;
                if (null === $session) {
                    $usage->andWhere('link.session IS NULL');
                } else {
                    $usage->andWhere('(link.session IS NULL OR IDENTITY(link.session) = :sessionId)')
                        ->setParameter('sessionId', $session->getId())
                    ;
                }
                foreach ($usage->getQuery()->getArrayResult() as $row) {
                    $result[(int) $row['questionId']]['occurrences'] = (int) $row['occurrences'];
                }
            }
            if ('occurrences' === $sortMetric) {
                continue;
            }

            // One observation per question and completed exercise attempt, not per answer row.
            $attempts = $this->entityManager->createQueryBuilder()
                ->select('attempt.questionId AS questionId', 'tracked.exeId AS attemptId', 'tracked.questionsToCheck AS pending', 'SUM(attempt.marks) AS marks', 'question.ponderation AS maxScore')
                ->from(TrackEAttempt::class, 'attempt')
                ->innerJoin('attempt.trackExercise', 'tracked')
                ->innerJoin(CQuizQuestion::class, 'question', 'ON', 'question.iid = attempt.questionId')
                ->where('attempt.questionId IN (:ids)')
                ->andWhere('IDENTITY(tracked.course) = :courseId')
                ->andWhere('tracked.status IN (:completed)')
                ->setParameter('ids', $chunk)
                ->setParameter('courseId', $course->getId())
                ->setParameter('completed', ['', 'completed'])
                ->groupBy('attempt.questionId, tracked.exeId, tracked.questionsToCheck, question.ponderation')
            ;
            if (null === $session) {
                $attempts->andWhere('tracked.session IS NULL');
            } else {
                $attempts->andWhere('IDENTITY(tracked.session) = :sessionId')
                    ->setParameter('sessionId', $session->getId())
                ;
            }
            foreach ($attempts->getQuery()->toIterable([], AbstractQuery::HYDRATE_ARRAY) as $row) {
                $id = (int) $row['questionId'];
                $metric = self::classifyAttempt($id, (float) $row['marks'], (float) $row['maxScore'], (string) $row['pending']);
                if (null !== $metric) {
                    ++$result[$id][$metric];
                }
            }
        }

        return $result;
    }

    public static function classifyAttempt(int $questionId, float $marks, float $maxScore, string $pending): ?string
    {
        if ($maxScore <= 0 || \in_array($questionId, array_map('intval', explode(',', $pending)), true)) {
            return null;
        }

        // Scores are stored to two decimal places. A partial answer is below full score.
        return round($marks, 2) >= round($maxScore, 2) ? 'correctAttempts' : 'incorrectAttempts';
    }

    /**
     * @param list<int> $ids
     */
    public static function sortIds(array $ids, array $metrics, string $field, string $direction): array
    {
        usort($ids, static function (int $left, int $right) use ($metrics, $field, $direction): int {
            $comparison = ($metrics[$left][$field] ?? 0) <=> ($metrics[$right][$field] ?? 0);

            return ('DESC' === $direction ? -$comparison : $comparison) ?: ($left <=> $right);
        });

        return $ids;
    }
}

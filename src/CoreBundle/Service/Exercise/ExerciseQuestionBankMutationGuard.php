<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** Stricter mutation scope than the read/reuse pool: never change another context's question. */
final readonly class ExerciseQuestionBankMutationGuard
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @param list<int> $ids
     */
    public function getFlags(array $ids, Course $course, ?Session $session): array
    {
        if ([] === $ids) {
            return [];
        }
        $flags = [];
        foreach ($ids as $id) {
            $flags[$id] = ['canClassify' => false, 'canAssignScores' => false];
        }
        $selectLinks = [
            'link.id AS linkId', 'IDENTITY(link.course) AS courseId', 'IDENTITY(link.session) AS sessionId',
            'IDENTITY(link.group) AS groupId', 'IDENTITY(link.userGroup) AS userGroupId', 'IDENTITY(link.user) AS userId',
            'link.endVisibilityAt AS ended', 'link.visibility AS visibility',
        ];
        $direct = $this->entityManager->createQueryBuilder()
            ->select('question.iid AS questionId', ...$selectLinks)
            ->from(CQuizQuestion::class, 'question')
            ->leftJoin('question.resourceNode', 'node')
            ->leftJoin('node.resourceLinks', 'link', 'WITH', 'link.deletedAt IS NULL')
            ->where('question.iid IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()->getArrayResult()
        ;
        $owned = [];
        $blocked = [];
        foreach ($direct as $row) {
            $id = (int) $row['questionId'];
            if (null === $row['linkId']) {
                continue;
            }
            if (!self::matchesContext($row, (int) $course->getId(), $session?->getId())) {
                $blocked[$id] = true;

                continue;
            }
            if (null === $row['ended'] && \in_array((int) $row['visibility'], [0, 2], true)) {
                $owned[$id] = true;
            }
        }
        $relations = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(relation.question) AS questionId', ...$selectLinks)
            ->from(CQuizRelQuestion::class, 'relation')
            ->innerJoin('relation.quiz', 'quiz')
            ->leftJoin('quiz.resourceNode', 'node')
            ->leftJoin('node.resourceLinks', 'link', 'WITH', 'link.deletedAt IS NULL')
            ->where('IDENTITY(relation.question) IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()->getArrayResult()
        ;
        $used = [];
        $relatedOwned = [];
        foreach ($relations as $row) {
            $id = (int) $row['questionId'];
            $used[$id] = true;
            if (!self::matchesContext($row, (int) $course->getId(), $session?->getId())) {
                $blocked[$id] = true;
            } elseif (null === $row['ended'] && \in_array((int) $row['visibility'], [0, 2], true)) {
                $relatedOwned[$id] = true;
            }
        }
        // Deliberately not scope this check: historical marks must not be affected anywhere.
        $attempts = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT attempt.questionId AS questionId')
            ->from(TrackEAttempt::class, 'attempt')
            ->where('attempt.questionId IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()->getArrayResult()
        ;
        $attempted = array_fill_keys(array_map('intval', array_column($attempts, 'questionId')), true);
        $singleAnswerRows = $this->entityManager->createQueryBuilder()
            ->select('question.iid AS questionId')
            ->from(CQuizQuestion::class, 'question')
            ->innerJoin('question.answers', 'answer')
            ->where('question.iid IN (:ids) AND question.type = 1')
            ->setParameter('ids', $ids)
            ->groupBy('question.iid')
            ->having('SUM(CASE WHEN answer.correct = 1 THEN 1 ELSE 0 END) = 1')
            ->andHaving('SUM(CASE WHEN answer.correct = 0 THEN 1 ELSE 0 END) = COUNT(answer.iid) - 1')
            ->andHaving('COUNT(answer.iid) > 1')
            ->getQuery()->getArrayResult()
        ;
        $validSingleAnswers = array_fill_keys(array_map('intval', array_column($singleAnswerRows, 'questionId')), true);
        foreach ($ids as $id) {
            $flags[$id]['canClassify'] = (isset($owned[$id]) || isset($relatedOwned[$id])) && !isset($blocked[$id]);
            $flags[$id]['canAssignScores'] = isset($owned[$id]) && $flags[$id]['canClassify'] && !isset($used[$id]) && !isset($attempted[$id]) && isset($validSingleAnswers[$id]);
        }

        return $flags;
    }

    public static function matchesContext(array $row, int $courseId, ?int $sessionId): bool
    {
        return null !== $row['linkId'] && (int) $row['courseId'] === $courseId
            && (null === $row['sessionId'] ? null : (int) $row['sessionId']) === $sessionId
            && null === $row['groupId'] && null === $row['userGroupId'] && null === $row['userId'];
    }

    public static function validateScores(?float $correct, ?float $incorrect): void
    {
        if (null === $correct || null === $incorrect || !is_finite($correct) || !is_finite($incorrect)
            || $correct < 0.01 || $correct > 9999.99 || $incorrect > 0 || $incorrect < -9999.99
            || abs($correct - round($correct, 2)) > 0.0000001 || abs($incorrect - round($incorrect, 2)) > 0.0000001) {
            throw new BadRequestHttpException('Enter a positive correct score and a zero or negative incorrect score, with at most two decimals.');
        }
    }

    /**
     * @param iterable<CQuizAnswer> $answers
     */
    public static function hasValidSingleAnswer(iterable $answers): bool
    {
        $correct = 0;
        $incorrect = 0;
        foreach ($answers as $answer) {
            if (1 === $answer->getCorrect()) {
                ++$correct;
            } elseif (0 === $answer->getCorrect()) {
                ++$incorrect;
            } else {
                return false;
            }
        }

        return 1 === $correct && $incorrect > 0;
    }
}

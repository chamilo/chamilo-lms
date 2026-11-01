<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\HotPotatoesAttempt;
use Chamilo\CoreBundle\Entity\HotPotatoesAttemptReview;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class HotPotatoesAttemptReviewService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HotPotatoesAttemptService $attempts,
    ) {}

    public function review(
        int $attemptId,
        int $documentId,
        int $reviewerId,
        int $scorePercent,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): HotPotatoesAttemptReview {
        $attempt = $this->entityManager->getRepository(HotPotatoesAttempt::class)->find($attemptId);
        if (!$attempt instanceof HotPotatoesAttempt
            || $attempt->getDocumentId() !== $documentId
            || !$this->attempts->matchesContext($attempt, $course, $session, $group)
        ) {
            throw new NotFoundHttpException('The attempt does not belong to this Hot Potatoes course context.');
        }

        if (null === $attempt->getCompletedAt()) {
            throw new ConflictHttpException('The attempt must be completed before a teacher can review it.');
        }

        // Never overwrite the previous review: an amended score is a new event.
        $review = new HotPotatoesAttemptReview($attemptId, $scorePercent, $reviewerId);
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    /**
     * Retrieve the latest teacher assessment in one query for up to 100 attempts.
     *
     * @param list<HotPotatoesAttempt> $attempts
     *
     * @return array<int, array{scorePercent: int, reviewedByUserId: int, reviewedAt: string, scoreSource: string}>
     */
    public function latestForAttempts(array $attempts): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (HotPotatoesAttempt $attempt): int => (int) $attempt->getId(),
            $attempts,
        )));
        if ([] === $ids) {
            return [];
        }

        $reviews = $this->entityManager->getRepository(HotPotatoesAttemptReview::class)
            ->createQueryBuilder('review')
            ->andWhere('review.attemptId IN (:attemptIds)')
            ->setParameter('attemptIds', $ids)
            ->orderBy('review.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $latest = [];
        foreach ($reviews as $review) {
            if (!$review instanceof HotPotatoesAttemptReview) {
                continue;
            }

            $attemptId = $review->getAttemptId();
            if (!isset($latest[$attemptId])) {
                $latest[$attemptId] = $review->toSummary();
            }
        }

        return $latest;
    }
}

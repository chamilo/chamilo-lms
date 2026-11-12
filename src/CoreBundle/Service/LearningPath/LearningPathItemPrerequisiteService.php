<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Rules for the prerequisite of a learning path item, shared by the learning path builder
 * (API) and the MCP tools so that both accept and refuse exactly the same prerequisites.
 */
final readonly class LearningPathItemPrerequisiteService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CLpItemRepository $lpItemRepository,
    ) {}

    /**
     * Sets, or clears when $prerequisiteId is 0, the prerequisite of $item. A negative id is an error,
     * never a silent clear. The entities are
     * persisted but not flushed: the caller owns the transaction.
     *
     * @return array{prerequisiteId: int, minScore: float, maxScore: float}
     *
     * @throws InvalidArgumentException when the item or the prerequisite cannot be used
     */
    public function set(CLp $lp, CLpItem $item, int $prerequisiteId, float $minScore = 0.0, float $maxScore = 100.0): array
    {
        if ('dir' === $item->getItemType() || 'root' === $item->getItemType()) {
            throw new InvalidArgumentException('Prerequisites cannot be assigned to this learning path item.');
        }

        if ($prerequisiteId < 0) {
            throw new InvalidArgumentException('The prerequisite item ID cannot be negative. Use 0 to remove the prerequisite.');
        }

        if (0 === $prerequisiteId) {
            $item
                ->setPrerequisite('')
                ->setPrerequisiteMinScore(0.0)
                ->setPrerequisiteMaxScore(100.0)
            ;
            $this->entityManager->persist($item);

            return ['prerequisiteId' => 0, 'minScore' => 0.0, 'maxScore' => 100.0];
        }

        $prerequisite = $this->lpItemRepository->find($prerequisiteId);
        if (!$prerequisite instanceof CLpItem || $prerequisite->getLp()->getIid() !== $lp->getIid()) {
            throw new InvalidArgumentException('The selected prerequisite does not belong to this learning path.');
        }
        if ('dir' === $prerequisite->getItemType() || 'root' === $prerequisite->getItemType()) {
            throw new InvalidArgumentException('The selected prerequisite is invalid.');
        }
        if ($prerequisite->getIid() === $item->getIid()
            || $prerequisite->getDisplayOrder() > $item->getDisplayOrder()
        ) {
            throw new InvalidArgumentException('The selected prerequisite is not available for this item.');
        }

        if (\in_array($prerequisite->getItemType(), ['quiz', 'hotpotatoes'], true)) {
            $availableMaxScore = $this->getCurrentItemMaxScore($prerequisite);
            $prerequisite->setMaxScore($availableMaxScore);
            $this->entityManager->persist($prerequisite);
            if ($availableMaxScore > 0.0) {
                $minScore = max(0.0, min($minScore, $availableMaxScore));
                $maxScore = max(0.0, min($maxScore, $availableMaxScore));
            } else {
                $minScore = max(0.0, $minScore);
                $maxScore = max(0.0, $maxScore);
            }
            if ($maxScore < $minScore) {
                throw new InvalidArgumentException('The maximum prerequisite score must be greater than or equal to the minimum score.');
            }
        } else {
            $minScore = 0.0;
            $maxScore = 100.0;
        }

        $item
            ->setPrerequisite((string) $prerequisiteId)
            ->setPrerequisiteMinScore($minScore)
            ->setPrerequisiteMaxScore($maxScore)
        ;
        $this->entityManager->persist($item);

        return ['prerequisiteId' => $prerequisiteId, 'minScore' => $minScore, 'maxScore' => $maxScore];
    }

    public function getCurrentItemMaxScore(CLpItem $item): float
    {
        if ('quiz' !== $item->getItemType()) {
            return (float) ($item->getMaxScore() ?? 0.0);
        }

        $path = trim((string) $item->getPath());
        $quizId = ctype_digit($path) ? (int) $path : 0;
        $quiz = $quizId > 0 ? $this->entityManager->getRepository(CQuiz::class)->find($quizId) : null;

        return $quiz instanceof CQuiz ? (float) $quiz->getMaxScore() : (float) ($item->getMaxScore() ?? 0.0);
    }
}

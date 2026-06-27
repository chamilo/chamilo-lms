<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\LearningPathEndedEvent;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class LearningPathRuntimeProgressManager
{
    private const COMPLETED_STATUSES = ['completed', 'passed', 'succeeded', 'browsed', 'failed'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CLpItemRepository $lpItemRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function initializeItemViews(CLp $lp, CLpView $view): void
    {
        /** @var array<int, CLpItem> $items */
        $items = $this->lpItemRepository->findBy(
            ['lp' => $lp],
            ['displayOrder' => 'ASC', 'iid' => 'ASC'],
        );

        $startedAt = time();
        foreach ($items as $item) {
            if ('root' === strtolower(trim($item->getItemType()))) {
                continue;
            }

            if ($this->findLatestItemView($view, $item) instanceof CLpItemView) {
                continue;
            }

            $itemView = (new CLpItemView())
                ->setItem($item)
                ->setView($view)
                ->setViewCount(1)
                ->setStartTime($startedAt)
                ->setTotalTime(0)
                ->setScore(0.0)
                ->setStatus('not attempted')
            ;

            $maxScore = $item->getMaxScore();
            if (null !== $maxScore && (float) $maxScore > 0.0) {
                $itemView->setMaxScore((string) $maxScore);
            }

            $this->entityManager->persist($itemView);
        }
    }

    public function findLatestItemView(CLpView $view, CLpItem|int $item): ?CLpItemView
    {
        $itemEntity = $item instanceof CLpItem ? $item : $this->lpItemRepository->find($item);
        if (!$itemEntity instanceof CLpItem) {
            return null;
        }

        /** @var CLpItemView|null $itemView */
        $itemView = $this->entityManager->getRepository(CLpItemView::class)->findOneBy(
            [
                'item' => $itemEntity,
                'view' => $view,
            ],
            [
                'viewCount' => 'DESC',
                'iid' => 'DESC',
            ],
        );

        return $itemView;
    }

    public function getNextItemAttempt(CLpView $view, CLpItem|int $item): int
    {
        $latest = $this->findLatestItemView($view, $item);

        return max(1, (int) ($latest?->getViewCount() ?? 0) + 1);
    }

    public function recordElapsedTime(
        CLpView $view,
        CLpItem|int|null $item = null,
        bool $flush = true,
    ): void {
        $itemId = $item instanceof CLpItem ? (int) $item->getIid() : (int) ($item ?? $view->getLastItem());
        if ($itemId <= 0) {
            return;
        }

        $itemView = $this->findLatestItemView($view, $itemId);
        if (!$itemView instanceof CLpItemView) {
            return;
        }

        $trackedItem = $itemView->getItem();
        if (CLp::SCORM_TYPE === $trackedItem->getLp()->getLpType()
            && 'sco' === strtolower(trim($trackedItem->getItemType()))
        ) {
            return;
        }

        $startedAt = (int) $itemView->getStartTime();
        if ($startedAt <= 0) {
            $itemView->setStartTime(time());
            if ($flush) {
                $this->entityManager->flush();
            }

            return;
        }

        $elapsed = time() - $startedAt;
        if ($elapsed <= 0) {
            return;
        }

        if ($elapsed > 3600) {
            $elapsed = 300;
        }

        $itemView
            ->setTotalTime(max(0, (int) $itemView->getTotalTime()) + $elapsed)
            ->setStartTime(time())
        ;

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function synchronize(CLp $lp, CLpView $view): int
    {
        $previousProgress = max(0, min(100, (int) ($view->getProgress() ?? 0)));
        $items = $this->getItems($lp);
        $latestViews = $this->indexLatestItemViews($view);

        $this->completeParentSections($items, $latestViews, $view);
        $latestViews = $this->indexLatestItemViews($view);

        $totalItems = 0;
        $completedItems = 0;
        foreach ($items as $item) {
            if (\in_array(strtolower(trim($item->getItemType())), ['root', 'dir'], true)) {
                continue;
            }

            ++$totalItems;
            $itemView = $latestViews[(int) $item->getIid()] ?? null;
            if ($itemView instanceof CLpItemView && $this->isCompletedStatus($itemView->getStatus())) {
                ++$completedItems;
            }
        }

        $progress = $totalItems > 0 ? (int) round(($completedItems * 100) / $totalItems) : 0;
        $progress = max(0, min(100, $progress));
        $view->setProgress($progress);
        $this->entityManager->flush();

        if ($previousProgress < 100 && 100 === $progress && null !== $view->getIid()) {
            $this->eventDispatcher->dispatch(
                new LearningPathEndedEvent(['lp_view_id' => (int) $view->getIid()]),
                Events::LP_ENDED,
            );
        }

        return $progress;
    }

    /** @return array<int, CLpItem> */
    private function getItems(CLp $lp): array
    {
        /** @var array<int, CLpItem> $items */
        $items = $this->lpItemRepository->findBy(
            ['lp' => $lp],
            ['displayOrder' => 'ASC', 'iid' => 'ASC'],
        );

        return $items;
    }

    /** @return array<int, CLpItemView> */
    private function indexLatestItemViews(CLpView $view): array
    {
        /** @var array<int, CLpItemView> $rows */
        $rows = $this->entityManager->getRepository(CLpItemView::class)->findBy(
            ['view' => $view],
            ['viewCount' => 'DESC', 'iid' => 'DESC'],
        );

        $latest = [];
        foreach ($rows as $row) {
            $itemId = (int) $row->getItem()->getIid();
            if (!isset($latest[$itemId])) {
                $latest[$itemId] = $row;
            }
        }

        return $latest;
    }

    /**
     * @param array<int, CLpItem>     $items
     * @param array<int, CLpItemView> $latestViews
     */
    private function completeParentSections(array $items, array $latestViews, CLpView $view): void
    {
        $itemsByParent = [];
        $sections = [];
        foreach ($items as $item) {
            $parentId = (int) ($item->getParent()?->getIid() ?? 0);
            $itemsByParent[$parentId][] = $item;

            if ('dir' === strtolower(trim($item->getItemType()))) {
                $sections[] = $item;
            }
        }

        usort(
            $sections,
            fn (CLpItem $first, CLpItem $second): int => $this->getItemDepth($second) <=> $this->getItemDepth($first),
        );

        foreach ($sections as $section) {
            $children = $itemsByParent[(int) $section->getIid()] ?? [];
            if ([] === $children) {
                continue;
            }

            $allCompleted = true;
            foreach ($children as $child) {
                $childView = $latestViews[(int) $child->getIid()] ?? null;
                if (!$childView instanceof CLpItemView || !$this->isCompletedStatus($childView->getStatus())) {
                    $allCompleted = false;
                    break;
                }
            }

            if (!$allCompleted) {
                continue;
            }

            $sectionView = $latestViews[(int) $section->getIid()] ?? null;
            if (!$sectionView instanceof CLpItemView) {
                $sectionView = (new CLpItemView())
                    ->setItem($section)
                    ->setView($view)
                    ->setViewCount(1)
                    ->setStartTime(time())
                    ->setTotalTime(0)
                    ->setScore(0.0)
                ;
                $this->entityManager->persist($sectionView);
                $latestViews[(int) $section->getIid()] = $sectionView;
            }

            $sectionView->setStatus('completed');
        }
    }

    private function getItemDepth(CLpItem $item): int
    {
        $depth = 0;
        $parent = $item->getParent();
        $visited = [];

        while ($parent instanceof CLpItem && 'root' !== strtolower(trim($parent->getItemType()))) {
            $parentId = (int) $parent->getIid();
            if ($parentId <= 0 || isset($visited[$parentId])) {
                break;
            }

            $visited[$parentId] = true;
            ++$depth;
            $parent = $parent->getParent();
        }

        return $depth;
    }

    private function isCompletedStatus(string $status): bool
    {
        return \in_array(strtolower(trim($status)), self::COMPLETED_STATUSES, true);
    }
}

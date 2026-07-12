<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final readonly class LearningPathCopyService
{
    private const TOOL_KEY = 'learnpaths';

    public function __construct(
        private CLpRepository $learningPathRepository,
        private CLpItemRepository $learningPathItemRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {}

    public function duplicate(CLp $learningPath, Course $course, ?Session $session): int
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $createdId = $this->duplicateInTransaction($learningPath, $course, $session);
            $connection->commit();

            return $createdId;
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            if ($this->entityManager->isOpen()) {
                $this->entityManager->clear();
            }

            throw $exception;
        }
    }

    private function duplicateInTransaction(CLp $learningPath, Course $course, ?Session $session): int
    {
        $learningPathId = (int) $learningPath->getIid();
        if ($learningPathId <= 0) {
            throw new RuntimeException('Learning path cannot be copied before it is persisted.');
        }

        $courseCode = trim($course->getCode());
        if ('' === $courseCode) {
            throw new RuntimeException('Source course code is missing.');
        }

        $sessionId = (int) ($session?->getId() ?? 0);
        $builder = new CourseBuilder('partial');
        $builder->set_tools_to_build([self::TOOL_KEY]);
        $builder->set_tools_specific_id_list([
            self::TOOL_KEY => [$learningPathId],
        ]);

        $snapshot = $builder->build($sessionId, $courseCode, false);
        $resourceKey = (string) ($builder->toolToName[self::TOOL_KEY] ?? '');
        if ('' === $resourceKey) {
            throw new RuntimeException('Learning path copy resource key is missing.');
        }

        $sourceSnapshot = $snapshot->resources[$resourceKey][$learningPathId] ?? null;
        if (!\is_object($sourceSnapshot)) {
            throw new RuntimeException('Learning path copy snapshot is missing.');
        }

        $items = \is_array($sourceSnapshot->items ?? null) ? $sourceSnapshot->items : [];
        foreach ($items as $index => $item) {
            if (!\is_array($item)) {
                continue;
            }

            unset($item['level'], $item['lvl']);
            $items[$index] = $item;
        }
        $sourceSnapshot->items = $items;

        $restorer = new CourseRestorer($snapshot);
        $restorer->tools_to_restore = [self::TOOL_KEY];
        $restorer->set_add_text_in_items(true);
        $restorer->set_tool_copy_settings([
            self::TOOL_KEY => ['reset_dates' => true],
        ]);
        $restorer->restore($courseCode, $sessionId, false, false);

        $createdId = (int) ($sourceSnapshot->destination_id ?? 0);
        if ($createdId <= 0 || $createdId === $learningPathId) {
            throw new RuntimeException('Learning path copy did not create a valid destination.');
        }

        $copy = $this->learningPathRepository->find($createdId);
        if (!$copy instanceof CLp) {
            throw new RuntimeException('Copied learning path could not be reloaded.');
        }

        $this->synchronizeLearningPath($learningPath, $copy);
        $this->synchronizeItems($learningPath, $copy);

        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        if (CLp::SCORM_TYPE === $learningPath->getLpType()) {
            $restorer->restore_scorm_documents();
            $this->entityManager->refresh($copy);

            if (!$copy->hasAsset()) {
                throw new RuntimeException('SCORM package files could not be copied.');
            }
        }

        return $createdId;
    }

    private function synchronizeLearningPath(CLp $source, CLp $copy): void
    {
        $now = new DateTime();

        $copy
            ->setLpType($source->getLpType())
            ->setTitle($source->getTitle().' '.$this->translator->trans('Copy'))
            ->setRef((string) ($source->getRef() ?? ''))
            ->setDescription((string) ($source->getDescription() ?? ''))
            ->setPath($source->getPath())
            ->setForceCommit($source->getForceCommit())
            ->setDefaultViewMod($source->getDefaultViewMod())
            ->setDefaultEncoding($source->getDefaultEncoding())
            ->setContentMaker($source->getContentMaker())
            ->setContentLocal($source->getContentLocal())
            ->setContentLicense($source->getContentLicense())
            ->setPreventReinit($source->getPreventReinit())
            ->setJsLib($source->getJsLib())
            ->setDebug($source->getDebug())
            ->setTheme($source->getTheme())
            ->setAuthor($source->getAuthor())
            ->setPrerequisite($source->getPrerequisite())
            ->setHideTocFrame($source->getHideTocFrame())
            ->setSeriousgameMode($source->getSeriousgameMode())
            ->setUseMaxScore($source->getUseMaxScore())
            ->setAutolaunch(0)
            ->setCategory($source->getCategory())
            ->setMaxAttempts($source->getMaxAttempts())
            ->setSubscribeUsers($source->getSubscribeUsers())
            ->setCreatedOn($now)
            ->setModifiedOn($now)
            ->setPublishedOn(null)
            ->setExpiredOn(null)
            ->setAccumulateScormTime($source->getAccumulateScormTime())
            ->setAccumulateWorkTime($source->getAccumulateWorkTime())
            ->setNextLpId($source->getNextLpId())
            ->setSubscribeUserByDate($source->getSubscribeUserByDate())
            ->setDisplayNotAllowedLp($source->getDisplayNotAllowedLp())
            ->setDuration($source->getDuration())
            ->setAutoForwardVideo($source->getAutoForwardVideo())
        ;
    }

    private function synchronizeItems(CLp $source, CLp $copy): void
    {
        $sourceItems = $this->getItemIndex($source);
        $copiedItems = $this->getItemIndex($copy);

        if (array_keys($sourceItems) !== array_keys($copiedItems)) {
            throw new RuntimeException('Learning path copy created an incomplete item hierarchy.');
        }

        $itemIdMap = [];
        foreach ($sourceItems as $path => $sourceItem) {
            $copiedItem = $copiedItems[$path] ?? null;
            if (!$copiedItem instanceof CLpItem) {
                throw new RuntimeException('Copied learning path item could not be matched.');
            }

            if ($sourceItem->getItemType() !== $copiedItem->getItemType()
                || $sourceItem->getTitle() !== $copiedItem->getTitle()
            ) {
                throw new RuntimeException('Copied learning path hierarchy does not match the source.');
            }

            $sourceItemId = (int) ($sourceItem->getIid() ?? 0);
            $copiedItemId = (int) ($copiedItem->getIid() ?? 0);
            if ($sourceItemId <= 0 || $copiedItemId <= 0) {
                throw new RuntimeException('Learning path item identifiers are invalid.');
            }

            $itemIdMap[$sourceItemId] = $copiedItemId;
            $this->synchronizeItemFields($sourceItem, $copiedItem);
        }

        foreach ($sourceItems as $path => $sourceItem) {
            $copiedItem = $copiedItems[$path];
            $prerequisite = trim((string) $sourceItem->getPrerequisite());
            if ('' === $prerequisite || !ctype_digit($prerequisite) || (int) $prerequisite <= 0) {
                $copiedItem->setPrerequisite($prerequisite);
                continue;
            }

            $mappedPrerequisite = $itemIdMap[(int) $prerequisite] ?? 0;
            if ($mappedPrerequisite <= 0) {
                throw new RuntimeException('Learning path item prerequisite could not be remapped.');
            }

            $copiedItem->setPrerequisite((string) $mappedPrerequisite);
        }
    }

    private function synchronizeItemFields(CLpItem $source, CLpItem $copy): void
    {
        $copy
            ->setTitle($source->getTitle())
            ->setItemType($source->getItemType())
            ->setRef($source->getRef())
            ->setDescription((string) ($source->getDescription() ?? ''))
            ->setPath((string) $source->getPath())
            ->setMinScore((float) $source->getMinScore())
            ->setMaxScore($source->getMaxScore())
            ->setDisplayOrder((int) $source->getDisplayOrder())
            ->setLaunchData((string) $source->getLaunchData())
            ->setDuration($source->getDuration())
            ->setExportAllowed($source->isExportAllowed())
        ;

        if (null !== $source->getMasteryScore()) {
            $copy->setMasteryScore((float) $source->getMasteryScore());
        }
        if (null !== $source->getParameters()) {
            $copy->setParameters((string) $source->getParameters());
        }
        if (null !== $source->getMaxTimeAllowed()) {
            $copy->setMaxTimeAllowed((string) $source->getMaxTimeAllowed());
        }
        if (null !== $source->getTerms()) {
            $copy->setTerms((string) $source->getTerms());
        }
        if (null !== $source->getSearchDid()) {
            $copy->setSearchDid((int) $source->getSearchDid());
        }
        if (null !== $source->getAudio()) {
            $copy->setAudio((string) $source->getAudio());
        }
        if (null !== $source->getPrerequisiteMinScore()) {
            $copy->setPrerequisiteMinScore((float) $source->getPrerequisiteMinScore());
        }
        if (null !== $source->getPrerequisiteMaxScore()) {
            $copy->setPrerequisiteMaxScore((float) $source->getPrerequisiteMaxScore());
        }
    }

    /**
     * @return array<string, CLpItem>
     */
    private function getItemIndex(CLp $learningPath): array
    {
        $items = $this->learningPathItemRepository->findBy(
            ['lp' => $learningPath],
            ['lvl' => 'ASC', 'displayOrder' => 'ASC', 'iid' => 'ASC'],
        );

        $root = null;
        $childrenByParent = [];
        $nonRootCount = 0;

        foreach ($items as $item) {
            if ('root' === $item->getItemType()) {
                $root = $item;
                continue;
            }

            $nonRootCount++;
            $parentId = (int) ($item->getParent()?->getIid() ?? 0);
            $childrenByParent[$parentId][] = $item;
        }

        if (!$root instanceof CLpItem || null === $root->getIid()) {
            throw new RuntimeException('Learning path root item is missing.');
        }

        foreach ($childrenByParent as &$children) {
            usort($children, static function (CLpItem $left, CLpItem $right): int {
                $orderComparison = ((int) $left->getDisplayOrder()) <=> ((int) $right->getDisplayOrder());
                if (0 !== $orderComparison) {
                    return $orderComparison;
                }

                return ((int) ($left->getIid() ?? 0)) <=> ((int) ($right->getIid() ?? 0));
            });
        }
        unset($children);

        $index = [];
        $this->indexItemChildren((int) $root->getIid(), '', $childrenByParent, $index);

        if ($nonRootCount !== \count($index)) {
            throw new RuntimeException('Learning path contains items outside its root hierarchy.');
        }

        return $index;
    }

    /**
     * @param array<int, CLpItem[]>    $childrenByParent
     * @param array<string, CLpItem>   $index
     */
    private function indexItemChildren(
        int $parentId,
        string $parentPath,
        array $childrenByParent,
        array &$index,
    ): void {
        foreach ($childrenByParent[$parentId] ?? [] as $position => $child) {
            $childId = (int) ($child->getIid() ?? 0);
            if ($childId <= 0) {
                throw new RuntimeException('Learning path item identifier is invalid.');
            }

            $path = $parentPath.'/'.($position + 1);
            $index[$path] = $child;
            $this->indexItemChildren($childId, $path, $childrenByParent, $index);
        }
    }

}

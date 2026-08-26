<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\LearningPath;

use Chamilo\CoreBundle\Service\LearningPath\ArticulateRiseSuspendDataDecoder;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProgressManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LearningPathRuntimeProgressManagerTest extends TestCase
{
    private const string RISE_V3_PROGRESS_23 = '{"v":3,"d":[123,34,112,114,111,103,114,101,115,115,34,58,256,112,266,50,51,44,34,108,263,115,111,110,265,267,274,276,278,49,266,256,99,266,49,125,291,273,99,112,118,266,34,116,263,116,34,125]}';

    private LearningPathRuntimeProgressManager $progressManager;

    protected function setUp(): void
    {
        $this->progressManager = new LearningPathRuntimeProgressManager(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CLpItemRepository::class),
            $this->createMock(EventDispatcherInterface::class),
            new ArticulateRiseSuspendDataDecoder(),
        );
    }

    public function testStandardScormProgressMeasureTakesPriorityOverRiseSuspendData(): void
    {
        $lp = $this->createLearningPath(ArticulateRiseSuspendDataDecoder::CONTENT_MAKER);
        $item = $this->createScoItem($lp, 1);
        $itemView = $this->createItemView($item, 'incomplete', self::RISE_V3_PROGRESS_23)
            ->setProgress(0.4)
        ;

        self::assertSame(40, $this->progressManager->calculateProgress($lp, [$item], [1 => $itemView]));
    }

    public function testRiseSuspendDataProvidesProgressWhenScormProgressMeasureIsMissing(): void
    {
        $lp = $this->createLearningPath(ArticulateRiseSuspendDataDecoder::CONTENT_MAKER);
        $item = $this->createScoItem($lp, 1);
        $itemView = $this->createItemView($item, 'incomplete', self::RISE_V3_PROGRESS_23);

        self::assertSame(23, $this->progressManager->calculateProgress($lp, [$item], [1 => $itemView]));
    }

    public function testCompletedRiseScoAlwaysReportsFullProgress(): void
    {
        $lp = $this->createLearningPath(ArticulateRiseSuspendDataDecoder::CONTENT_MAKER);
        $item = $this->createScoItem($lp, 1);
        $itemView = $this->createItemView($item, 'completed', self::RISE_V3_PROGRESS_23);

        self::assertSame(100, $this->progressManager->calculateProgress($lp, [$item], [1 => $itemView]));
    }

    public function testRiseFallbackIsNotUsedForMultiScoLearningPaths(): void
    {
        $lp = $this->createLearningPath(ArticulateRiseSuspendDataDecoder::CONTENT_MAKER);
        $firstItem = $this->createScoItem($lp, 1);
        $secondItem = $this->createScoItem($lp, 2);
        $firstView = $this->createItemView($firstItem, 'completed', self::RISE_V3_PROGRESS_23);
        $secondView = $this->createItemView($secondItem, 'incomplete', self::RISE_V3_PROGRESS_23);

        self::assertSame(
            50,
            $this->progressManager->calculateProgress(
                $lp,
                [$firstItem, $secondItem],
                [1 => $firstView, 2 => $secondView],
            ),
        );
    }

    public function testLegacyGenericScormRiseSuspendDataProvidesProgress(): void
    {
        $lp = $this->createLearningPath('Scorm');
        $item = $this->createScoItem($lp, 1);
        $itemView = $this->createItemView($item, 'incomplete', self::RISE_V3_PROGRESS_23);

        self::assertSame(23, $this->progressManager->calculateProgress($lp, [$item], [1 => $itemView]));
    }

    public function testUnknownSuspendDataIsIgnoredForGenericScormPackages(): void
    {
        $lp = $this->createLearningPath('Scorm');
        $item = $this->createScoItem($lp, 1);
        $itemView = $this->createItemView(
            $item,
            'incomplete',
            '{"progress":{"p":23},"vendor":"unknown"}',
        );

        self::assertSame(0, $this->progressManager->calculateProgress($lp, [$item], [1 => $itemView]));
    }

    private function createLearningPath(string $contentMaker): CLp
    {
        return (new CLp())
            ->setLpType(CLp::SCORM_TYPE)
            ->setContentMaker($contentMaker)
        ;
    }

    private function createScoItem(CLp $lp, int $id): CLpItem
    {
        $item = (new CLpItem())
            ->setLp($lp)
            ->setItemType('sco')
        ;
        $this->setEntityId($item, $id);

        return $item;
    }

    private function createItemView(CLpItem $item, string $status, string $suspendData): CLpItemView
    {
        return (new CLpItemView())
            ->setItem($item)
            ->setStatus($status)
            ->setSuspendData($suspendData)
        ;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = (new ReflectionClass($entity))->getProperty('iid');
        $property->setValue($entity, $id);
    }
}

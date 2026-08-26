<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class ScormRuntimeManagerTest extends TestCase
{
    public function testScorm12TerminateKeepsIncompleteStatusWhenStatusWasNotChanged(): void
    {
        $itemView = (new CLpItemView())->setStatus('incomplete');

        $this->applyScorm12Values(
            $itemView,
            [
                'cmi.core.lesson_status' => 'incomplete',
                'cmi.core.exit' => 'suspend',
                'cmi.suspend_data' => '{"v":3,"d":[]}',
            ],
            ['cmi.core.exit', 'cmi.suspend_data'],
            true,
            'terminate',
        );

        self::assertSame('incomplete', $itemView->getStatus());
        self::assertSame('suspend', $itemView->getCoreExit());
    }

    public function testScorm12ExplicitCompletedStatusStillCompletes(): void
    {
        $itemView = (new CLpItemView())->setStatus('incomplete');

        $this->applyScorm12Values(
            $itemView,
            [
                'cmi.core.lesson_status' => 'completed',
                'cmi.core.exit' => '',
            ],
            ['cmi.core.lesson_status'],
            true,
            'terminate',
        );

        self::assertSame('completed', $itemView->getStatus());
    }

    public function testScorm12TerminateWithoutAnyStatusStillUsesLegacyCompletionFallback(): void
    {
        $itemView = new CLpItemView();

        $this->applyScorm12Values(
            $itemView,
            [],
            [],
            true,
            'terminate',
        );

        self::assertSame('completed', $itemView->getStatus());
    }

    /**
     * @param array<string, string> $values
     * @param array<int, string>    $changedKeys
     */
    private function applyScorm12Values(
        CLpItemView $itemView,
        array $values,
        array $changedKeys,
        bool $terminated,
        string $reason,
    ): void {
        $manager = (new ReflectionClass(ScormRuntimeManager::class))->newInstanceWithoutConstructor();
        $user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ScormRuntimeManager::class, 'applyCommonValues');

        $method->invoke(
            $manager,
            new CLp(),
            new CLpItem(),
            $itemView,
            $user,
            ScormRuntimeManager::VERSION_12,
            $values,
            $changedKeys,
            $terminated,
            $reason,
        );
    }
}

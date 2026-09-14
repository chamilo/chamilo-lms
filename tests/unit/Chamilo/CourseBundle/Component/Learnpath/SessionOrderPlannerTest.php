<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\Learnpath;

use Chamilo\CourseBundle\Component\Learnpath\SessionOrderPlanner;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 6).'/src/Chamilo/CourseBundle/Component/Learnpath/SessionOrderPlanner.php';

final class SessionOrderPlannerTest extends TestCase
{
    public function testBuildPlanPermutesExistingSparseSlots(): void
    {
        $rows = [
            ['id' => '11', 'display_order' => '3'],
            ['id' => '22', 'display_order' => '7'],
            ['id' => '33', 'display_order' => '12'],
        ];

        self::assertSame(
            [33 => 3, 11 => 7, 22 => 12],
            SessionOrderPlanner::buildPlan($rows, ['33', '11', '22'])
        );
    }

    /**
     * @dataProvider invalidOrders
     */
    public function testBuildPlanRejectsIncompleteOrAmbiguousOrders(array $rows, array $order): void
    {
        self::assertNull(SessionOrderPlanner::buildPlan($rows, $order));
    }

    public static function invalidOrders(): iterable
    {
        $rows = [
            ['id' => 11, 'display_order' => 3],
            ['id' => 22, 'display_order' => 7],
        ];

        yield 'empty' => [[], []];
        yield 'missing id' => [$rows, [11]];
        yield 'unknown id' => [$rows, [11, 44]];
        yield 'duplicate submitted id' => [$rows, [11, 11]];
        yield 'invalid submitted id' => [$rows, [11, '22invalid']];
        yield 'duplicate stored id' => [[
            ['id' => 11, 'display_order' => 3],
            ['id' => 11, 'display_order' => 7],
        ], [11, 22]];
        yield 'duplicate stored position' => [[
            ['id' => 11, 'display_order' => 3],
            ['id' => 22, 'display_order' => 3],
        ], [11, 22]];
        yield 'invalid stored position' => [[
            ['id' => 11, 'display_order' => -1],
            ['id' => 22, 'display_order' => 7],
        ], [11, 22]];
    }
}

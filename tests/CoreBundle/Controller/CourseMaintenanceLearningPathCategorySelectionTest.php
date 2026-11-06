<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\CourseMaintenanceController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class CourseMaintenanceLearningPathCategorySelectionTest extends TestCase
{
    public function testSelectedLearningPathsRetainTheirSourceCategories(): void
    {
        $first = (object) ['source_id' => 15, 'title' => 'First'];
        $second = (object) ['source_id' => 25, 'title' => 'Second'];
        $third = (object) ['source_id' => 35, 'title' => 'Unselected'];
        $explicit = (object) ['source_id' => 45, 'title' => 'Explicitly selected'];

        $course = (object) [
            'resources' => [
                RESOURCE_LEARNPATH => [
                    7 => (object) ['category_id' => 15],
                    8 => (object) ['obj' => (object) ['category_id' => 25]],
                    9 => (object) ['category_id' => 0],
                    10 => (object) ['categoryId' => 15],
                ],
                RESOURCE_LEARNPATH_CATEGORY => [45 => $explicit],
            ],
        ];

        $this->includeCategories($course, [
            RESOURCE_LEARNPATH_CATEGORY => [15 => $first, 25 => $second, 35 => $third, 45 => $explicit],
        ]);

        self::assertSame($explicit, self::categoriesOf($course)[45]);
        self::assertSame($first, self::categoriesOf($course)[15]);
        self::assertSame($second, self::categoriesOf($course)[25]);
        self::assertArrayNotHasKey(35, self::categoriesOf($course));
    }

    public function testSourceIdsAreResolvedWhenCategoryBucketKeysDiffer(): void
    {
        $category = (object) ['source_id' => 123, 'title' => 'Original'];
        $course = (object) [
            'resources' => [
                RESOURCE_LEARNPATH => [12 => (object) ['category_id' => 123]],
            ],
        ];

        $this->includeCategories($course, [RESOURCE_LEARNPATH_CATEGORY => [1 => $category]]);

        self::assertSame($category, self::categoriesOf($course)[1]);
    }

    public function testNoUnrequestedCategoriesAreCopiedWithoutLearningPaths(): void
    {
        $course = (object) ['resources' => [RESOURCE_LEARNPATH => []]];

        $this->includeCategories($course, [
            RESOURCE_LEARNPATH_CATEGORY => [7 => (object) ['source_id' => 7, 'title' => 'Unused']],
        ]);

        self::assertArrayNotHasKey(RESOURCE_LEARNPATH_CATEGORY, $course->resources);
    }

    private function includeCategories(object $course, array $sourceResources): void
    {
        $method = new ReflectionMethod(CourseMaintenanceController::class, 'includeSelectedLearningPathCategories');
        $method->invoke(null, $course, $sourceResources);
    }

    /**
     * Reads the category bucket after includeCategories() mutated it, so Psalm does not keep the literal shape.
     *
     * @return array<int, object>
     */
    private static function categoriesOf(object $course): array
    {
        return $course->resources[RESOURCE_LEARNPATH_CATEGORY];
    }
}

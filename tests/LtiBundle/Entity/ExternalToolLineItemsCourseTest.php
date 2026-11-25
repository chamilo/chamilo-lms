<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\LtiBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\LtiBundle\Entity\LineItem;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A tool can be linked from multiple courses, but its AGS line items must never expose
 * an evaluation that belongs to a different course. Apply pagination after this boundary.
 */
final class ExternalToolLineItemsCourseTest extends TestCase
{
    public function testCourseScopeExcludesItemsFromAnotherCourse(): void
    {
        $tool = $this->tool(70);
        $first = $this->item($tool, 10, 'quiz-a');
        $second = $this->item($tool, 20, 'quiz-b');

        self::assertSame([$first], array_values($tool->getLineItems(0, '', '', 0, 0, 10)->toArray()));
        self::assertSame([$second], array_values($tool->getLineItems(0, '', '', 0, 0, 20)->toArray()));
        self::assertCount(2, $tool->getLineItems());
    }

    public function testPaginationRunsAfterCourseScopeAndHonorsResourceId(): void
    {
        $tool = $this->tool(70);
        $first = $this->item($tool, 10, 'quiz');
        $this->item($tool, 20, 'quiz');
        $second = $this->item($tool, 10, 'quiz');
        $this->item($tool, 10, 'other');

        self::assertSame([$first], array_values($tool->getLineItems(70, 'quiz', '', 1, 0, 10)->toArray()));
        self::assertSame([$second], array_values($tool->getLineItems(70, 'quiz', '', 1, 1, 10)->toArray()));
        self::assertCount(0, $tool->getLineItems(999, 'quiz', '', 0, 0, 10));
    }

    private function tool(int $id): ExternalTool
    {
        $tool = new ExternalTool();
        (new ReflectionProperty(ExternalTool::class, 'id'))->setValue($tool, $id);

        return $tool;
    }

    private function item(ExternalTool $tool, int $courseId, string $resourceId): LineItem
    {
        $course = new Course();
        (new ReflectionProperty(Course::class, 'id'))->setValue($course, $courseId);

        $evaluation = new GradebookEvaluation();
        $evaluation->setCourse($course);

        $item = new LineItem();
        $item->setEvaluation($evaluation)->setResourceId($resourceId);
        $tool->addLineItem($item);

        return $item;
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Mcp\GenerateStudentSuccessFeedbackTool;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class GenerateStudentSuccessFeedbackToolTest extends TestCase
{
    public function testRejectsInvalidLearnerIdBeforeAnyAiCall(): void
    {
        $tool = $this->createToolWithoutDependencies();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('The learner user ID must be a positive integer.');

        $tool->generateStudentSuccessFeedback(1, 0);
    }

    public function testRejectsNegativeSessionIdBeforeAnyAiCall(): void
    {
        $tool = $this->createToolWithoutDependencies();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('The session ID cannot be negative.');

        $tool->generateStudentSuccessFeedback(1, 1, -1);
    }

    public function testRejectsOversizedTeacherPromptBeforeAnyAiCall(): void
    {
        $tool = $this->createToolWithoutDependencies();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Additional instructions must be 6000 characters or fewer.');

        $tool->generateStudentSuccessFeedback(1, 1, 0, str_repeat('x', 6001));
    }

    private function createToolWithoutDependencies(): GenerateStudentSuccessFeedbackTool
    {
        $reflection = new ReflectionClass(GenerateStudentSuccessFeedbackTool::class);

        /** @var GenerateStudentSuccessFeedbackTool $tool */
        return $reflection->newInstanceWithoutConstructor();
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Service\Assignment\AssignmentHtmlSubmission;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class AssignmentHtmlSubmissionTest extends TestCase
{
    public function testCreatesSafeHtmlDocumentAndPreservesFormatting(): void
    {
        $result = (new AssignmentHtmlSubmission())->prepare(
            'My answer',
            '<h2>Heading</h2><p><strong>Important</strong> <em>note</em></p><ul><li>First</li></ul>',
        );

        self::assertSame('My answer.html', $result['fileName']);
        self::assertStringContainsString('<strong>Important</strong>', $result['description']);
        self::assertStringContainsString('<!DOCTYPE html>', $result['document']);
        self::assertStringContainsString('<meta charset="UTF-8">', $result['document']);
        self::assertStringContainsString($result['description'], $result['document']);
    }

    public function testRemovesScriptsAndEventHandlersBeforePersisting(): void
    {
        $result = (new AssignmentHtmlSubmission())->prepare(
            'Example',
            '<p onclick="alert(1)">Safe</p><script>alert(2)</script><a href="javascript:alert(3)">Link</a>',
        );

        self::assertStringContainsString('Safe', $result['description']);
        self::assertStringNotContainsString('<script', $result['document']);
        self::assertStringNotContainsString('onclick', $result['document']);
        self::assertStringNotContainsString('javascript:', $result['document']);
    }

    public function testRejectsEmptySubmission(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);
        (new AssignmentHtmlSubmission())->prepare('Answer', '   ');
    }

    public function testRejectsOversizedSubmission(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);
        (new AssignmentHtmlSubmission())->prepare('Answer', str_repeat('A', 60001));
    }

    public function testNormalizesUnsafeFileName(): void
    {
        $result = (new AssignmentHtmlSubmission())->prepare('Test/../work', '<p>Answer</p>');

        self::assertSame('Test_work.html', $result['fileName']);
    }
}

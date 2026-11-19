<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\CourseDescription;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use const PHP_BINARY;

final class CourseDescriptionHtmlReadTest extends TestCase
{
    public function testReadContentUsesTheCurrentTeacherHtmlPolicy(): void
    {
        $this->assertReadSmokePasses('policy');
    }

    /**
     * The list is rendered as HTML for every course member, so the stored HTML must be
     * filtered with the reader's profile even when teachers may store JavaScript.
     */
    public function testListFiltersStoredHtmlWithTheReaderProfile(): void
    {
        $this->assertReadSmokePasses('reader');
    }

    public function testReadContentPreservesHarmlessMarkup(): void
    {
        $this->assertReadSmokePasses('markup');
    }

    private function assertReadSmokePasses(string $scenario): void
    {
        // Legacy API functions and Symfony's test bootstrap cannot be redefined
        // inside PHPUnit. Run the real HTMLPurifier check in an isolated process.
        $process = new Process(
            [PHP_BINARY, __DIR__.'/Fixtures/TeacherHtmlReadSmoke.php', $scenario],
            \dirname(__DIR__, 4),
        );
        $process->run();

        self::assertSame(
            0,
            $process->getExitCode(),
            \sprintf(
                "Teacher HTML read smoke failed (%s):\n%s%s",
                $scenario,
                $process->getErrorOutput(),
                $process->getOutput(),
            ),
        );
        self::assertStringContainsString('PASS:', $process->getOutput());
    }
}

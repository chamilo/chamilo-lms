<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\State\Wiki\WikiPageDiffService;
use PHPUnit\Framework\TestCase;

final class WikiPageDiffServiceTest extends TestCase
{
    private WikiPageDiffService $diffService;

    protected function setUp(): void
    {
        $this->diffService = new WikiPageDiffService();
    }

    public function testComparesLinesUsingLegacyChangeTypes(): void
    {
        self::assertSame(
            [
                ['type' => 'equal', 'content' => '<p>First</p>'],
                ['type' => 'deleted', 'content' => '<p>Second</p>'],
                ['type' => 'added', 'content' => '<p>Changed</p>'],
            ],
            $this->diffService->compareLines(
                "<p>First</p>\n<p>Second</p>",
                "<p>First</p>\n<p>Changed</p>",
            ),
        );
    }

    public function testDetectsMovedLines(): void
    {
        self::assertSame(
            [
                ['type' => 'moved', 'content' => 'Second'],
                ['type' => 'moved', 'content' => 'First'],
            ],
            $this->diffService->compareLines("First\nSecond", "Second\nFirst"),
        );
    }

    public function testComparesWordsWithoutReturningHtmlFromTheSource(): void
    {
        self::assertSame(
            [
                ['type' => 'equal', 'text' => 'The '],
                ['type' => 'deleted', 'text' => 'old'],
                ['type' => 'added', 'text' => 'new'],
                ['type' => 'equal', 'text' => ' page'],
            ],
            $this->diffService->compareWords('<p>The old page</p>', '<p>The new page</p>'),
        );
    }
}

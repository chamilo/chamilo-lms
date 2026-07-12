<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\State\Wiki\WikiPageRenderer;
use PHPUnit\Framework\TestCase;

final class WikiPageRendererTest extends TestCase
{
    private WikiPageRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new WikiPageRenderer();
    }

    public function testNormalizesWikiReflinks(): void
    {
        self::assertSame('index', $this->renderer->normalizeReflink(null));
        self::assertSame('index', $this->renderer->normalizeReflink('Index'));
        self::assertSame('sample_page', $this->renderer->normalizeReflink(' Sample Page '));
    }

    public function testExtractsUniqueInternalReflinks(): void
    {
        self::assertSame(
            ['first_page', 'second_page'],
            $this->renderer->extractInternalReflinks('[[First Page]] [[Second Page|Second]] [[First Page]]'),
        );
    }

    public function testRendersInternalLinksWithCurrentContext(): void
    {
        $content = $this->renderer->renderInternalLinks(
            'Read [[Existing Page]] and [[Missing Page|another page]].',
            ['existing_page'],
            42,
            [
                'cid' => 7,
                'sid' => 8,
                'gid' => 9,
            ],
        );

        self::assertStringContainsString('data-wiki-reflink="existing_page"', $content);
        self::assertStringContainsString('data-wiki-reflink="missing_page"', $content);
        self::assertStringContainsString(
            '/resources/wiki/42/?cid=7&amp;title=existing_page&amp;sid=8&amp;gid=9',
            $content,
        );
        self::assertStringContainsString('text-red-500', $content);
    }

    public function testCountsWordsFromHtmlContent(): void
    {
        self::assertSame(4, $this->renderer->wordCount('<p>One two <strong>three four</strong></p>'));
    }
}

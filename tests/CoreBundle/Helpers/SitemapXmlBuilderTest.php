<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\SitemapXmlBuilder;
use PHPUnit\Framework\TestCase;

class SitemapXmlBuilderTest extends TestCase
{
    public function testUrlSetEscapesAbsoluteUrls(): void
    {
        $xml = (new SitemapXmlBuilder())->urlSet([
            'https://example.org/',
            'https://example.org/course/15/about?type=a&view=b',
        ]);

        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        self::assertStringContainsString('<loc>https://example.org/course/15/about?type=a&amp;view=b</loc>', $xml);
        self::assertSame(2, substr_count($xml, '<url>'));
    }

    public function testSitemapIndexUsesTheCorrectElements(): void
    {
        $xml = (new SitemapXmlBuilder())->index(['https://example.org/sitemap-pages.xml']);

        self::assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        self::assertStringContainsString('<sitemap><loc>https://example.org/sitemap-pages.xml</loc></sitemap>', $xml);
        self::assertStringNotContainsString('<url>', $xml);
    }
}

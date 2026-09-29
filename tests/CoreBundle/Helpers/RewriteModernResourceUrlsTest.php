<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use PHPUnit\Framework\TestCase;

final class RewriteModernResourceUrlsTest extends TestCase
{
    public function testRewritesUuidBasedDocumentFileUrls(): void
    {
        $oldUuid = 'ba82a4fc-4d1f-4055-92de-1ffb7bc7b2c3';
        $newUrl = '/r/document/files/11111111-2222-3333-4444-555555555555/view';
        $html = '<p><img src="/r/document/files/'.$oldUuid.'/view" alt="x"></p>'
            .'<a href="https://example.test/r/document/files/'.$oldUuid.'/download">dl</a>'
            .'<div style="background:url(/r/document/files/'.$oldUuid.'/view)"></div>'
            .'<img src="/r/document/files/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee/view">';

        $result = ChamiloHelper::rewriteModernResourceUrlsWithMap($html, [
            $oldUuid => $newUrl,
        ]);

        self::assertSame(3, (int) $result['replaced']);
        self::assertSame(1, (int) $result['misses']);
        self::assertStringContainsString($newUrl, $result['html']);
        self::assertStringContainsString('/r/document/files/11111111-2222-3333-4444-555555555555/download', $result['html']);
        self::assertStringContainsString('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee', $result['html']);
        self::assertStringNotContainsString($oldUuid, $result['html']);
    }

    public function testLegacyRewriteAlsoAppliesUuidMap(): void
    {
        $oldUuid = 'ba82a4fc-4d1f-4055-92de-1ffb7bc7b2c3';
        $newUrl = '/r/document/files/99999999-aaaa-bbbb-cccc-ddddeeeeffff/view';
        $html = '<img src="/r/document/files/'.$oldUuid.'/view">';

        $result = ChamiloHelper::rewriteLegacyCourseUrlsWithMap(
            $html,
            'COURSECODE',
            [],
            [],
            [$oldUuid => $newUrl]
        );

        self::assertSame(1, (int) $result['replaced']);
        self::assertStringContainsString($newUrl, $result['html']);
    }
}

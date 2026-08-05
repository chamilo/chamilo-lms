<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\WikiVersionCollectionNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

final class WikiVersionCollectionNormalizerTest extends TestCase
{
    public function testItGroupsAndOrdersWikiVersions(): void
    {
        $first = (object) [
            'page_id' => 10,
            'iid' => 101,
            'reflink' => 'home',
            'title' => 'Home',
            'content' => '<p>Version 1</p>',
            'version' => 1,
            'user_id' => 7,
            'dtime' => '2026-01-01 10:00:00',
        ];
        $second = (object) [
            'pageId' => 10,
            'id' => 102,
            'reflink' => 'home',
            'title' => 'Home',
            'content' => '<p>Version 2</p>',
            'version' => 2,
            'userId' => 8,
            'dtime' => '2026-01-02 10:00:00',
        ];
        $other = (object) [
            'page_id' => 20,
            'iid' => 201,
            'reflink' => 'other',
            'title' => 'Other',
            'content' => '<p>Other</p>',
            'version' => 1,
            'user_id' => 9,
        ];

        $bag = [
            102 => (object) ['obj' => $second],
            201 => (object) ['obj' => $other],
            101 => (object) ['obj' => $first],
        ];

        $pages = (new WikiVersionCollectionNormalizer())->normalize($bag);

        self::assertCount(2, $pages);
        self::assertSame(10, $pages[0]['id']);
        self::assertSame([1, 2], array_column($pages[0]['versions'], 'version'));
        self::assertSame([101, 102], array_column($pages[0]['versions'], 'id'));
        self::assertSame(20, $pages[1]['id']);
    }

    public function testItIgnoresInvalidEntries(): void
    {
        $wrapper = new stdClass();
        $wrapper->obj = (object) ['page_id' => 0];

        $pages = (new WikiVersionCollectionNormalizer())->normalize([
            'invalid',
            $wrapper,
        ]);

        self::assertSame([], $pages);
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PHPUnit\Framework\TestCase;

/**
 * Direct tests of msort() (array.lib.php): jqGrid callers pass the sort order the user clicked, so
 * 'asc' must give the lowest first and 'desc' the highest first. It used to be the other way round.
 */
final class ArrayLibMsortTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!function_exists('msort')) {
            require_once __DIR__.'/../../../public/main/inc/lib/array.lib.php';
        }
    }

    private function rows(): array
    {
        return [
            ['id' => 'a', 'n' => 10, 's' => 'banana'],
            ['id' => 'b', 'n' => 9, 's' => 'apple'],
            ['id' => 'c', 'n' => 100, 's' => 'cherry'],
            ['id' => 'd', 'n' => 9, 's' => 'apple'],
        ];
    }

    public function testNumericAscendingIsLowestFirstAndStable(): void
    {
        $sorted = msort($this->rows(), 'n', 'asc');

        // 'b' and 'd' tie: they keep their input order.
        $this->assertSame(['b', 'd', 'a', 'c'], array_column($sorted, 'id'));
    }

    public function testNumericDescendingIsHighestFirstAndStable(): void
    {
        $sorted = msort($this->rows(), 'n', 'desc');

        $this->assertSame(['c', 'a', 'b', 'd'], array_column($sorted, 'id'));
    }

    public function testStringKeysAndHtmlStripping(): void
    {
        $rows = [
            ['s' => '<b>banana</b>'],
            ['s' => 'cherry'],
            ['s' => '<a href="#">apple</a>'],
        ];

        $this->assertSame(['apple', 'banana', 'cherry'], array_map('strip_tags', array_column(msort($rows, 's', 'asc'), 's')));
        $this->assertSame(['cherry', 'banana', 'apple'], array_map('strip_tags', array_column(msort($rows, 's', 'desc'), 's')));
    }

    public function testDefaultOrderIsAscendingAndOrderIsCaseInsensitive(): void
    {
        $this->assertSame(['b', 'd', 'a', 'c'], array_column(msort($this->rows(), 'n'), 'id'));
        $this->assertSame(['c', 'a', 'b', 'd'], array_column(msort($this->rows(), 'n', 'DESC'), 'id'));
    }

    public function testEmptyArrayAndReindexing(): void
    {
        $this->assertSame([], msort([], 'n', 'asc'));
        $sorted = msort(['x' => ['n' => 2], 'y' => ['n' => 1]], 'n', 'asc');
        $this->assertSame([['n' => 1], ['n' => 2]], $sorted);
    }
}

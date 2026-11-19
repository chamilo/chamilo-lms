<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use PHPUnit\Framework\TestCase;

final class CourseDepartmentUrlTest extends TestCase
{
    /**
     * A bare scheme was stored for courses saved with an empty department URL and then made the
     * course settings form fail validation, so it must be treated as an empty value.
     *
     * @dataProvider departmentUrlProvider
     */
    public function testNormalizeDepartmentUrl(?string $input, string $expected): void
    {
        self::assertSame($expected, Course::normalizeDepartmentUrl($input));
    }

    /**
     * @return iterable<string, array{0: ?string, 1: string}>
     */
    public static function departmentUrlProvider(): iterable
    {
        yield 'null' => [null, ''];

        yield 'empty' => ['', ''];

        yield 'blank' => ['   ', ''];

        yield 'http scheme only' => ['http://', ''];

        yield 'https scheme only' => ['https://', ''];

        yield 'scheme only, other case and spaces' => [' HTTPS:// ', ''];

        yield 'real http url' => ['http://example.org/dept', 'http://example.org/dept'];

        yield 'real https url' => ['https://example.org', 'https://example.org'];

        yield 'url without scheme is kept as is' => ['example.org', 'example.org'];
    }
}

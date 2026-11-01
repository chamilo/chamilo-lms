<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesArchiveUpdateValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class HotPotatoesArchiveUpdateValidatorTest extends TestCase
{
    public function testTitleIsTrimmedAndPublicationFlagIsPreserved(): void
    {
        $result = (new HotPotatoesArchiveUpdateValidator())->validate([
            'title' => '  My Hot Potatoes activity  ',
            'published' => false,
        ]);

        self::assertSame(['title' => 'My Hot Potatoes activity', 'published' => false], $result);
    }

    public function testPublicationFlagCanBeUpdatedWithoutTitle(): void
    {
        self::assertSame(['published' => true], (new HotPotatoesArchiveUpdateValidator())->validate(['published' => true]));
    }

    /**
     * @dataProvider invalidPayloads
     */
    public function testInvalidPayloadIsRejected(array $payload): void
    {
        $this->expectException(BadRequestHttpException::class);
        (new HotPotatoesArchiveUpdateValidator())->validate($payload);
    }

    public static function invalidPayloads(): iterable
    {
        yield 'empty' => [[]];

        yield 'unexpected key' => [['resourceNodeId' => 1]];

        yield 'empty title' => [['title' => '  ']];

        yield 'markup title' => [['title' => '<script>alert(1)</script>']];

        yield 'control character' => [['title' => "Bad\nTitle"]];

        yield 'overlong title' => [['title' => str_repeat('a', 256)]];

        yield 'nontext title' => [['title' => 123]];

        yield 'invalid flag' => [['published' => 'true']];

        yield 'null flag' => [['published' => null]];
    }
}

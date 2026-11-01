<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesAttemptReviewValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class HotPotatoesAttemptReviewValidatorTest extends TestCase
{
    public function testAcceptsTeacherScoreIncludingBounds(): void
    {
        $validator = new HotPotatoesAttemptReviewValidator();
        self::assertSame(0, $validator->validate(['scorePercent' => 0]));
        self::assertSame(70, $validator->validate(['scorePercent' => 70]));
        self::assertSame(100, $validator->validate(['scorePercent' => 100]));
    }

    /**
     * @dataProvider invalidScores
     */
    public function testRejectsUntrustedOrMalformedScores(array $payload): void
    {
        $this->expectException(BadRequestHttpException::class);
        (new HotPotatoesAttemptReviewValidator())->validate($payload);
    }

    public static function invalidScores(): iterable
    {
        yield 'negative' => [['scorePercent' => -1]];

        yield 'overflow' => [['scorePercent' => 101]];

        yield 'string' => [['scorePercent' => '100']];

        yield 'float' => [['scorePercent' => 98.5]];

        yield 'boolean' => [['scorePercent' => true]];

        yield 'empty' => [[]];

        yield 'unknown field' => [['scorePercent' => 50, 'userId' => 1]];
    }
}

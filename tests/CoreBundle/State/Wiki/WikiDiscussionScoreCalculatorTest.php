<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\State\Wiki\WikiDiscussionScoreCalculator;
use PHPUnit\Framework\TestCase;

final class WikiDiscussionScoreCalculatorTest extends TestCase
{
    public function testNormalizeAcceptsOnlyLegacyRatingRange(): void
    {
        $calculator = new WikiDiscussionScoreCalculator();

        self::assertSame(0, $calculator->normalize('0'));
        self::assertSame(10, $calculator->normalize(10));
        self::assertNull($calculator->normalize('-'));
        self::assertNull($calculator->normalize('11'));
        self::assertNull($calculator->normalize('invalid'));
    }

    public function testAverageIgnoresUnscoredAndInvalidValues(): void
    {
        $calculator = new WikiDiscussionScoreCalculator();

        self::assertSame(7.5, $calculator->average(['5', '10', '-', null, 'invalid']));
        self::assertSame(0.0, $calculator->average(['-', null]));
    }
}

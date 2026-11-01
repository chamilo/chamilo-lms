<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\HotPotatoesAttemptReview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HotPotatoesAttemptReviewTest extends TestCase
{
    public function testScoreIsExplicitlyAttributedToTeacher(): void
    {
        $review = new HotPotatoesAttemptReview(42, 85, 7);
        self::assertSame(42, $review->getAttemptId());
        self::assertSame(85, $review->getScorePercent());
        self::assertSame(7, $review->getReviewedByUserId());
        self::assertSame('teacher_review', $review->toSummary()['scoreSource']);
    }

    public function testRejectsOutOfRangeScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HotPotatoesAttemptReview(42, 120, 7);
    }
}

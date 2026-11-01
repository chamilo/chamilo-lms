<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\HotPotatoesAttempt;
use PHPUnit\Framework\TestCase;

final class HotPotatoesAttemptTest extends TestCase
{
    public function testAttemptsAreUngradedAndScoped(): void
    {
        $attempt = new HotPotatoesAttempt(91, 'jquiz.htm', 5, 13, 2, 7);
        self::assertSame(91, $attempt->getDocumentId());
        self::assertSame('jquiz.htm', $attempt->getEntrypoint());
        self::assertSame(5, $attempt->getUserId());
        self::assertSame(13, $attempt->getCourseId());
        self::assertSame(2, $attempt->getSessionId());
        self::assertSame(7, $attempt->getGroupId());
        self::assertSame('started', $attempt->toSummary(false)['status']);
        self::assertFalse($attempt->toSummary(false)['graded']);
        self::assertArrayNotHasKey('userId', $attempt->toSummary(false));
        $attempt->complete();
        $finishedAt = $attempt->getCompletedAt();
        self::assertNotNull($finishedAt);
        $attempt->complete();
        self::assertSame($finishedAt, $attempt->getCompletedAt());
        self::assertSame('completed', $attempt->toSummary(true)['status']);
        self::assertSame(5, $attempt->toSummary(true)['userId']);
    }
}

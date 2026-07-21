<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Traits;

use Carbon\Carbon;
use Chamilo\CoreBundle\Traits\TimestampableAgoTrait;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test guarding the human-readable "ago" output produced by
 * TimestampableAgoTrait across the nesbot/carbon 2 -> 3 upgrade. The API exposes
 * these strings verbatim, so the format must stay stable.
 */
class TimestampableAgoTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testAgoOutputFormatIsStable(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 10, 12, 0, 0, 'UTC'));

        $object = new class {
            use TimestampableAgoTrait;

            public function getCreatedAt(): DateTimeInterface
            {
                return new DateTimeImmutable('2026-01-10 10:00:00', new DateTimeZone('UTC'));
            }

            public function getUpdatedAt(): DateTimeInterface
            {
                return new DateTimeImmutable('2026-01-09 12:00:00', new DateTimeZone('UTC'));
            }
        };

        self::assertSame('2 hours ago', $object->getCreatedAtAgo());
        self::assertSame('1 day ago', $object->getUpdatedAtAgo());
    }
}

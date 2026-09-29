<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\SessionRelCourse;
use PHPUnit\Framework\TestCase;

final class SessionRelCourseTrackingTest extends TestCase
{
    public function testTransientStudentTrackingMetricsCanBeHydrated(): void
    {
        $relation = new SessionRelCourse();
        $relation->setTrackingProgress(63.4);
        $relation->setScore(74.25);
        $relation->setBestScore(88.5);
        $relation->setTimeSpentSeconds(3723);
        $relation->setCertificateAvailable(true);
        $relation->setCompleted(false);

        self::assertSame(63.4, $relation->getTrackingProgress());
        self::assertSame(74.25, $relation->getScore());
        self::assertSame(88.5, $relation->getBestScore());
        self::assertSame(3723, $relation->getTimeSpentSeconds());
        self::assertTrue($relation->getCertificateAvailable());
        self::assertFalse($relation->getCompleted());
    }
}

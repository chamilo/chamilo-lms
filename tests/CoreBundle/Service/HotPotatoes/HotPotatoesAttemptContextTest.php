<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\HotPotatoesAttempt;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesAttemptService;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class HotPotatoesAttemptContextTest extends TestCase
{
    public function testAttemptIsLimitedToItsCourseSessionAndGroup(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new HotPotatoesAttemptService($em);
        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(13);
        $session = $this->createMock(Session::class);
        $session->method('getId')->willReturn(4);
        $group = $this->createMock(CGroup::class);
        $group->method('getIid')->willReturn(6);
        $attempt = new HotPotatoesAttempt(91, 'jquiz.htm', 5, 13, 4, 6);

        self::assertTrue($service->matchesContext($attempt, $course, $session, $group));
        self::assertFalse($service->matchesContext($attempt, $course, null, $group));
        self::assertFalse($service->matchesContext($attempt, $course, $session, null));
    }
}

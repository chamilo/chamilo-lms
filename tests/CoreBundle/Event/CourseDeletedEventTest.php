<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Event;

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\CourseDeletedEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Bbb, BuyCourses and EmbedRegistry clean up what they attached to a course from a
 * COURSE_DELETED listener: recordings on the remote BBB server, purchase rows kept as
 * history, and embeds whose foreign key would block the deletion. All of that needs
 * the event to arrive while the course row is still readable.
 */
class CourseDeletedEventTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testDeleteCourseDispatchesEventWhileCourseStillExists(): void
    {
        $course = $this->createCourse('course to delete');
        $courseId = $course->getId();

        // deleteCourse() dips into legacy code (GroupManager, Database) that a real
        // request wires up in LegacyListener.
        $request = new Request();
        $request->setSession(new HttpSession(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());

        $repo = self::getContainer()->get(CourseRepository::class);
        $seen = [];

        self::getContainer()->get('event_dispatcher')->addListener(
            Events::COURSE_DELETED,
            function (CourseDeletedEvent $event) use (&$seen, $repo): void {
                $seen[] = [
                    'id' => $event->getCourseId(),
                    'type' => $event->getType(),
                    'readable' => null !== $repo->find($event->getCourseId()),
                ];
            }
        );

        $this->assertTrue(self::getContainer()->get(CourseHelper::class)->deleteCourse($course));

        $this->assertCount(1, $seen, 'Deleting a course must notify COURSE_DELETED listeners exactly once.');
        $this->assertSame($courseId, $seen[0]['id']);
        $this->assertSame(AbstractEvent::TYPE_PRE, $seen[0]['type']);
        $this->assertTrue(
            $seen[0]['readable'],
            'The course must still be readable when the event fires, or listeners cannot find the rows to clean up.'
        );
        $this->assertNull($repo->find($courseId), 'The course must be gone once the deletion completes.');
    }
}

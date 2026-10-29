<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use learnpath;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use const PHP_URL_QUERY;

/**
 * A learning path item of type calendar_event (migrated from 1.11) must open the event in the Vue
 * agenda: the legacy agenda.php?agenda_id= link only landed on the agenda, without the event.
 */
final class LearnpathCalendarEventLinkTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Legacy code reads its services, session and request through this static bridge.
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    public function testTheItemOpensTheEventInTheVueAgenda(): void
    {
        $course = $this->createCourse('LP calendar event link');
        $event = $this->createEvent($course->getId(), new DateTime('2040-06-30 11:00:00'));
        [$lpId, $itemId] = $this->createItem($course->getId(), (string) $event->getIid());

        $link = learnpath::rl_get_resource_link_for_learnpath($course->getId(), $lpId, $itemId, 0);

        $this->assertStringContainsString('/resources/ccalendarevent?', $link);
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $this->assertSame((string) $course->getId(), $query['cid']);
        $this->assertSame((string) $event->getIid(), $query['event']);
        $this->assertSame('timeGridDay', $query['view']);
        $this->assertSame(api_get_local_time('2040-06-30 11:00:00', null, null, false, false, false, 'Y-m-d'), $query['date']);
    }

    public function testAnItemWhoseEventWasDeletedHasNoLink(): void
    {
        $course = $this->createCourse('LP calendar event link deleted');
        [$lpId, $itemId] = $this->createItem($course->getId(), '999999999');

        $this->assertSame('', learnpath::rl_get_resource_link_for_learnpath($course->getId(), $lpId, $itemId, 0));
    }

    private function createEvent(int $courseId, DateTime $start): CCalendarEvent
    {
        $em = $this->getEntityManager();
        $course = $this->getCourse($courseId);

        $event = (new CCalendarEvent())
            ->setTitle('LP calendar event')
            ->setContent('')
            ->setStartDate($start)
            ->setEndDate((clone $start)->modify('+1 hour'))
            ->setParent($course)
            ->setCreator($this->getAdmin())
            ->addCourseLink($course)
        ;
        $em->persist($event);
        $em->flush();

        return $event;
    }

    /**
     * @return array{int, int} the learning path id and the item id
     */
    private function createItem(int $courseId, string $eventId): array
    {
        $em = $this->getEntityManager();
        $course = $this->getCourse($courseId);

        $lp = (new CLp())
            ->setTitle('LP with a calendar event')
            ->setLpType(CLp::LP_TYPE)
            ->setParent($course)
            ->setCreator($this->getAdmin())
            ->addCourseLink($course)
        ;
        $em->persist($lp);
        $em->flush();

        $item = (new CLpItem())
            ->setLp($lp)
            ->setItemType(TOOL_CALENDAR_EVENT)
            ->setTitle('Calendar event item')
            ->setPath($eventId)
            ->setRef($eventId)
            ->setDisplayOrder(1)
        ;
        $em->persist($item);
        $em->flush();

        return [(int) $lp->getIid(), (int) $item->getIid()];
    }
}

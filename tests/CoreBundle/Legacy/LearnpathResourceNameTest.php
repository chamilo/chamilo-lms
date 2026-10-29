<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
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

/**
 * The learning path reports show the name of the resource when an item has no title. The function
 * queried columns that no longer exist (c_lp_item.c_id), so every report with such an item failed.
 */
final class LearnpathResourceNameTest extends KernelTestCase
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

    public function testAnItemWithoutTitleTakesTheTitleOfItsResource(): void
    {
        $course = $this->createCourse('LP resource name');
        $event = $this->createEvent($course);
        // The resource is found by the path; the ref is a different number on migrated items.
        [$lpId, $itemId] = $this->createItem($course, TOOL_CALENDAR_EVENT, '', (string) $event->getIid());

        $this->assertSame('LP resource event', learnpath::rl_get_resource_name($course->getCode(), $lpId, $itemId));
    }

    public function testADocumentItemKeepsItsOwnTitle(): void
    {
        $course = $this->createCourse('LP resource name document');
        [$lpId, $itemId] = $this->createItem($course, TOOL_DOCUMENT, 'Chapter 1', '999999999');

        $this->assertSame('Chapter 1', learnpath::rl_get_resource_name($course->getCode(), $lpId, $itemId));
    }

    public function testAnItemWhoseResourceWasDeletedHasNoName(): void
    {
        $course = $this->createCourse('LP resource name deleted');
        [$lpId, $itemId] = $this->createItem($course, TOOL_CALENDAR_EVENT, '', '999999999');

        $this->assertSame('', learnpath::rl_get_resource_name($course->getCode(), $lpId, $itemId));
    }

    private function createEvent(Course $course): CCalendarEvent
    {
        $em = $this->getEntityManager();
        $start = new DateTime('2037-06-30 11:00:00');

        $event = (new CCalendarEvent())
            ->setTitle('LP resource event')
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
    private function createItem(Course $course, string $type, string $title, string $path): array
    {
        $em = $this->getEntityManager();

        $lp = (new CLp())
            ->setTitle('LP with an untitled item')
            ->setLpType(CLp::LP_TYPE)
            ->setParent($course)
            ->setCreator($this->getAdmin())
            ->addCourseLink($course)
        ;
        $em->persist($lp);
        $em->flush();

        $item = (new CLpItem())
            ->setLp($lp)
            ->setItemType($type)
            ->setTitle($title)
            ->setPath($path)
            ->setRef('1')
            ->setDisplayOrder(1)
        ;
        $em->persist($item);
        $em->flush();

        return [(int) $lp->getIid(), (int) $item->getIid()];
    }
}

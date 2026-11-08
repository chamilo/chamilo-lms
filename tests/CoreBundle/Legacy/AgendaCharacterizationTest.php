<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Agenda;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization of the legacy Agenda class (public/main/inc/lib/agenda.lib.php).
 *
 * The calendar tool runs on Vue + API Platform (CCalendarEvent resources); what survives of the class is the
 * write/read path used by course creation, the work tool deadline, the cron CSV import and the Bbb plugin:
 * constructor, addEvent(), editEvent(), get_event(), parseSendToArray(). Everything built on raw SQL against the
 * pre-2.0 tables (personal/global agenda, resize/move, attachments, repeats) is dead and broken, see the decisions
 * file; a few cheap pins document that. Fixtures are raw prepared inserts or addEvent() itself; tags "ag_".
 * The platform timezone is fixed to Asia/Tokyo (UTC+9, no DST) so local-to-UTC conversions are exact.
 */
final class AgendaCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../../public/main/inc/lib/agenda.lib.php';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/calendar/agenda.php');
        $request->setSession($session);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        $this->setContext(1, 1);
    }

    // ---------------------------------------------------------------- helpers

    private function setContext(int $userId, ?int $courseId, int $sessionId = 0): void
    {
        \ChamiloSession::write('_user', ['id' => $userId, 'user_id' => $userId]);
        if (null !== $courseId) {
            $code = 1 === $courseId ? 'AIACT' : 'USINGCHAMILO';
            \ChamiloSession::write('_real_cid', $courseId);
            \ChamiloSession::write('_cid', $code);
            \ChamiloSession::write('_course', api_get_course_info($code));
        } else {
            \ChamiloSession::erase('_real_cid');
            \ChamiloSession::erase('_cid');
            \ChamiloSession::erase('_course');
        }
        \ChamiloSession::write('sid', $sessionId);
        \ChamiloSession::write('gid', 0);
        \ChamiloSession::write('system_timezone', 'Asia/Tokyo');
        \ChamiloSession::erase('login_as');
        Container::getEntityManager()->clear();
        // ResourceListener takes the creator of a new resource from the security token, as in a real request.
        $user = $this->getUser(1 === $userId ? 'admin' : 'anon');
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }

    private function agenda(string $type = 'course', ?int $courseId = 1, bool $canEdit = true): Agenda
    {
        $agenda = new Agenda($type);
        // The constructor already took the course of the context; null means "no course at all".
        $agenda->set_course(null === $courseId ? ['real_id' => 0] : api_get_course_info(1 === $courseId ? 'AIACT' : 'USINGCHAMILO'));
        $agenda->setIsAllowedToEdit($canEdit);

        return $agenda;
    }

    /**
     * Inserts an event of a course the way the repository would: node + event + course link.
     */
    private function event(int $courseId = 1, string $title = '', ?int $parentEventId = null): int
    {
        $n = ++$this->seq;
        $typeId = (int) $this->conn->fetchOne("SELECT id FROM resource_type WHERE title = 'events'");
        $now = '2020-01-01 00:00:00';
        $this->conn->executeStatement(
            'INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, 1, ?, ?, 1, ?, ?, ?, 0, ?)',
            [$typeId, 'ag_node_'.$n, 'ag-node-'.$n.'-'.uniqid(), 'ag-node-'.$n.'-'.uniqid().'/', $now, $now, UuidV4::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_calendar_event', [
            'resource_node_id' => $nodeId,
            'parent_event_id' => $parentEventId,
            'title' => '' !== $title ? $title : 'ag_event_'.$n,
            'content' => 'ag_content_'.$n,
            'start_date' => '2030-06-14 00:00:00',
            'end_date' => '2030-06-14 01:00:00',
            'all_day' => 0,
            'comment' => 'ag_comment',
            'color' => '#112233',
            'collective' => 0,
            'subscription_visibility' => 0,
            'max_attendees' => 0,
        ]);
        $id = (int) $this->conn->lastInsertId();
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'visibility' => 2,
            'display_order' => 0,
            'resource_type_group' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    private function row(int $iid): array
    {
        return $this->conn->fetchAssociative('SELECT * FROM c_calendar_event WHERE iid = ?', [$iid]);
    }

    /** @return list<array<string, mixed>> */
    private function links(int $iid): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT l.* FROM resource_link l INNER JOIN c_calendar_event e ON e.resource_node_id = l.resource_node_id
             WHERE e.iid = ? AND l.deleted_at IS NULL ORDER BY l.id',
            [$iid]
        );
    }

    // ---------------------------------------------------------------- constructor

    public function testTypeIsKeptOnlyWhenKnownAndDefaultsToPersonal(): void
    {
        $this->assertSame('course', (new Agenda('course'))->getType());
        $this->assertSame('admin', (new Agenda('admin'))->getType());
        $this->assertSame('personal', (new Agenda('personal'))->getType());
        // PINNED QUIRK: an unknown type is ignored silently and the class default (personal) stays.
        $this->assertSame('personal', (new Agenda('nonsense'))->getType());
        $this->assertSame('course', (new Agenda(' course '))->getType(), 'the type is trimmed');
    }

    public function testSenderDefaultsToTheCurrentUserAndIsCastToInt(): void
    {
        $this->assertSame(1, (new Agenda('course'))->getSenderId());
        $this->assertSame(2, (new Agenda('course', 2))->getSenderId());
        $agenda = new Agenda('course');
        $agenda->setSenderId('7abc');
        $this->assertSame(7, $agenda->getSenderId());
    }

    public function testCourseAgendaTakesTheSessionOfTheContextUnlessGiven(): void
    {
        $this->setContext(1, 1, 5);
        $this->assertSame(5, (new Agenda('course'))->getSessionId());
        $this->assertSame(9, (new Agenda('course', 0, 0, 9))->getSessionId());
        $this->setContext(1, 1, 0);
        $this->assertSame(0, (new Agenda('course'))->getSessionId());
    }

    public function testEditRightsDependOnTypeAndUser(): void
    {
        // The platform admin may edit in every type.
        $this->assertTrue((new Agenda('course'))->getIsAllowedToEdit());
        $this->assertTrue((new Agenda('admin'))->getIsAllowedToEdit());
        $this->assertTrue((new Agenda('personal'))->getIsAllowedToEdit());

    }

    public function testAnonymousCannotEditAnyTypeOfAgenda(): void
    {
        // Anonymous (id 2) can edit no course/admin agenda. (The personal type is skipped: it only tests
        // api_is_anonymous(), which is false for this fixture account in a kernel test.)
        $this->setContext(2, 1);
        $this->assertFalse((new Agenda('course'))->getIsAllowedToEdit());
        $this->assertFalse((new Agenda('admin'))->getIsAllowedToEdit());
    }

    public function testEventColorsDefaultAndTypeAttributesAreSet(): void
    {
        $agenda = new Agenda('course');

        $this->assertSame([], $agenda->events);
        $this->assertSame('red', $agenda->event_platform_color);
        $this->assertSame('#458B00', $agenda->event_course_color);
        $this->assertSame('#FF8C00', $agenda->eventStudentPublicationColor);
    }

    // ---------------------------------------------------------------- parseSendToArray

    public function testParseSendToArrayEveryone(): void
    {
        $this->assertSame(
            ['everyone' => true, 'groups' => [], 'users' => []],
            $this->agenda()->parseSendToArray(['everyone'])
        );
        // The CourseHelper passes ['everyone' => 'everyone']: the value is what counts.
        $this->assertTrue($this->agenda()->parseSendToArray(['everyone' => 'everyone'])['everyone']);
    }

    public function testParseSendToArrayGroupsAndUsers(): void
    {
        $this->assertSame(
            ['everyone' => false, 'groups' => ['4', '9'], 'users' => ['3']],
            $this->agenda()->parseSendToArray(['GROUP:4', 'USER:3', 'GROUP:9'])
        );
        // Unknown prefixes are dropped silently.
        $this->assertSame(
            ['everyone' => false, 'groups' => [], 'users' => []],
            $this->agenda()->parseSendToArray(['SESSION:4'])
        );
    }

    public function testParseSendToArrayEmptyInputHasNoGroupsOrUsersKeys(): void
    {
        // PINNED QUIRK: an empty/non-array input returns only the "everyone" key.
        $this->assertSame(['everyone' => false], $this->agenda()->parseSendToArray([]));
        $this->assertSame(['everyone' => false], $this->agenda()->parseSendToArray('everyone'));
    }

    // ---------------------------------------------------------------- addEvent

    public function testAddCourseEventStoresTheEventWithUtcDatesAndAFullCourseLink(): void
    {
        $id = $this->agenda()->addEvent(
            '2030-06-14 09:00:00',
            '2030-06-14 10:30:00',
            'false',
            'ag_added',
            '<p>ag_body</p>',
            ['everyone'],
            false,
            0,
            [],
            [],
            'ag_note',
            '#abcdef'
        );

        $this->assertGreaterThan(0, $id);
        $row = $this->row($id);
        $this->assertSame('ag_added', $row['title']);
        $this->assertSame('<p>ag_body</p>', $row['content']);
        $this->assertSame('2030-06-14 00:00:00', $row['start_date'], 'local time (Tokyo) is stored as UTC');
        $this->assertSame('2030-06-14 01:30:00', $row['end_date']);
        $this->assertSame(0, (int) $row['all_day']);
        $this->assertSame('ag_note', $row['comment']);
        $this->assertSame('#abcdef', $row['color']);
        $this->assertNull($row['parent_event_id']);
        $links = $this->links($id);
        $this->assertCount(1, $links);
        $this->assertSame(1, (int) $links[0]['c_id']);
        $this->assertNull($links[0]['session_id']);
        $this->assertNull($links[0]['group_id']);
        $this->assertNull($links[0]['user_id']);
        $this->assertSame(2, (int) $links[0]['visibility'], 'published');
    }

    public function testAddEventAllDayIsOnlyTheExactStringTrue(): void
    {
        $agenda = $this->agenda();
        $a = $agenda->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'true', 'ag_allday', '', ['everyone']);
        $b = $agenda->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', true, 'ag_allday_bool', '', ['everyone']);
        $c = $agenda->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 1, 'ag_allday_int', '', ['everyone']);

        $this->assertSame(1, (int) $this->row($a)['all_day']);
        // PINNED QUIRK: the boolean true and the integer 1 (as the callers pass) do not make an all-day event.
        $this->assertSame(0, (int) $this->row($b)['all_day']);
        $this->assertSame(0, (int) $this->row($c)['all_day']);
    }

    public function testAddEventWithoutRecipientsCreatesNoLinkAtAll(): void
    {
        // PINNED QUIRK: with an empty $usersToSend no resource link is created, so the event is attached to
        // the course node only and is listed to nobody. add_course.lib.inc.php (the "Course creation" event)
        // and the work tool when the group id is 0 (['GROUP:0']) end up here.
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_nobody', '');
        $this->assertGreaterThan(0, $id);
        $this->assertSame([], $this->links($id));

        $viaGroupZero = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_g0', '', ['GROUP:0']);
        $this->assertSame([], $this->links($viaGroupZero));
    }

    public function testAddEventToUsersCreatesOneUserLinkEach(): void
    {
        $id = $this->agenda()->addEvent(
            '2030-06-14 09:00:00',
            '2030-06-14 10:00:00',
            'false',
            'ag_users',
            '',
            ['USER:2', 'USER:3']
        );

        $links = $this->links($id);
        $this->assertCount(2, $links);
        $this->assertEqualsCanonicalizing([2, 3], array_map(static fn (array $l): int => (int) $l['user_id'], $links));
        foreach ($links as $link) {
            $this->assertSame(1, (int) $link['c_id']);
        }
    }

    public function testAddEventInASessionLinksTheSession(): void
    {
        $sessionId = $this->session();
        $this->setContext(1, 1, $sessionId);

        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_sess', '', ['everyone']);

        $links = $this->links($id);
        $this->assertCount(1, $links);
        $this->assertSame($sessionId, (int) $links[0]['session_id']);
    }

    public function testAddEventWithoutEndDateEndsNow(): void
    {
        // PINNED QUIRK: a null end (the Bbb plugin passes null) becomes "now", not the start date, so the event
        // starts in the future and ends before it began.
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', null, 'false', 'ag_noend', '', ['everyone']);

        $end = strtotime($this->row($id)['end_date'].' UTC');
        $this->assertLessThanOrEqual(10, abs(time() - $end));
        $this->assertSame('2030-06-14 00:00:00', $this->row($id)['start_date']);
    }

    public function testAddEventWithParentSetsParentEventId(): void
    {
        $parent = $this->event();

        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_child', '', ['everyone'], false, $parent);

        $this->assertSame($parent, (int) $this->row($id)['parent_event_id']);
    }

    public function testAddEventOfAdminAndPersonalTypeCreatesNothing(): void
    {
        // PINNED QUIRK: addEvent() has no branch for 'admin' nor 'personal' (the code was commented out when the
        // tables went away), so system_announcements "add to calendar" silently stores nothing and returns null.
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_calendar_event');

        $this->assertNull($this->agenda('admin', null)->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', false, 'ag_admin', ''));
        $this->assertNull($this->agenda('personal', null)->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', false, 'ag_pers', ''));

        $this->assertSame($before, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_calendar_event'));
    }

    public function testStoreAgendaEventAsAnnouncementDoesNothingWithoutRecipients(): void
    {
        $id = $this->event();
        $event = Container::getCalendarEventRepository()->find($id);

        $this->assertFalse($this->agenda()->storeAgendaEventAsAnnouncement($event, []));
    }

    // ---------------------------------------------------------------- get_event

    public function testGetEventReturnsTheDescribedFields(): void
    {
        $id = $this->event();

        $event = $this->agenda()->get_event($id);

        $this->assertSame($id, $event['iid']);
        $this->assertSame('ag_event_1', $event['title']);
        $this->assertSame('ag_content_1', $event['content']);
        $this->assertSame('2030-06-14 00:00:00', $event['start_date'], 'returned as stored (UTC), not localised');
        $this->assertSame('2030-06-14 01:00:00', $event['end_date']);
        $this->assertFalse($event['all_day']);
        $this->assertSame('ag_comment', $event['description'], 'description is the comment column');
        $this->assertSame(['everyone' => true, 'users' => [], 'groups' => []], $event['send_to']);
        $this->assertCount(0, $event['repeat_info']);
        $this->assertCount(0, $event['attachment']);
        // PINNED QUIRK: "parent_event_id" is never put in the result, so "parent_info" can never be set either.
        $this->assertArrayNotHasKey('parent_event_id', $event);
        $this->assertArrayNotHasKey('parent_info', $event);
    }

    public function testGetEventSendToListsTheUsersLinked(): void
    {
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_to', '', ['USER:3']);
        Container::getEntityManager()->clear();

        $event = $this->agenda()->get_event($id);

        $this->assertSame(['everyone' => false, 'users' => [3], 'groups' => []], $event['send_to']);
    }

    public function testGetEventUnknownIdAndMissingCourseGiveNull(): void
    {
        $id = $this->event();

        $this->assertNull($this->agenda()->get_event(999999999));
        $this->assertNull($this->agenda('course', null)->get_event($id), 'without a course nothing is returned');
        $this->assertNull($this->agenda('personal', null)->get_event($id), 'the personal branch is commented out');
    }

    public function testGetEventOfAnotherCourseIsNotReturned(): void
    {
        // Access control: the event id may come from a request, an event of course 2 must not be readable
        // through the agenda of course 1.
        $foreign = $this->event(2, 'ag_foreign');

        $this->assertNull($this->agenda('course', 1)->get_event($foreign));
        $this->assertSame('ag_foreign', $this->agenda('course', 2)->get_event($foreign)['title']);
    }

    public function testGetEventOfAnEventWithoutLinkIsReturnedThroughItsParentCourse(): void
    {
        // An event created without recipients has no resource link (see testAddEventWithoutRecipients...); it
        // still belongs to the course through its parent node, so the work tool can find its own event again.
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_nolink', '');
        Container::getEntityManager()->clear();

        $this->assertSame('ag_nolink', $this->agenda('course', 1)->get_event($id)['title']);
        $this->assertNull($this->agenda('course', 2)->get_event($id));
    }

    // ---------------------------------------------------------------- editEvent

    public function testEditEventUpdatesTheFieldsAndConvertsToUtc(): void
    {
        $id = $this->event();

        $result = $this->agenda()->editEvent(
            $id,
            '2031-01-02 08:00:00',
            '2031-01-02 09:15:00',
            'true',
            'ag_edited',
            'ag_new_content',
            [],
            [],
            [],
            'ag_new_comment',
            '#445566'
        );

        $this->assertTrue($result);
        $row = $this->row($id);
        $this->assertSame('ag_edited', $row['title']);
        $this->assertSame('ag_new_content', $row['content']);
        $this->assertSame('2031-01-01 23:00:00', $row['start_date']);
        $this->assertSame('2031-01-02 00:15:00', $row['end_date']);
        $this->assertSame(1, (int) $row['all_day']);
        $this->assertSame('ag_new_comment', $row['comment']);
        $this->assertSame('#445566', $row['color']);
    }

    public function testEditEventKeepsContentAndColorWhenAskedTo(): void
    {
        $id = $this->event();

        $this->agenda()->editEvent($id, '2031-01-02 08:00:00', '2031-01-02 09:00:00', 'false', 'ag_t', 'ag_ignored', [], [], [], '', '', false, false);

        $row = $this->row($id);
        $this->assertSame('ag_content_1', $row['content'], 'updateContent=false keeps the content');
        $this->assertSame('#112233', $row['color'], 'an empty color keeps the stored one');
        $this->assertSame('', $row['comment'], 'the comment is always overwritten, even with an empty value');
    }

    public function testEditEventRefusedWhenTheAgendaIsNotEditable(): void
    {
        $id = $this->event();

        $result = $this->agenda('course', 1, false)->editEvent($id, '2031-01-02 08:00:00', '2031-01-02 09:00:00', 'false', 'ag_hacked', 'x');

        $this->assertFalse($result);
        $this->assertSame('ag_event_1', $this->row($id)['title']);
    }

    public function testEditEventUnknownIdOrNoCourseReturnsFalse(): void
    {
        $id = $this->event();

        $this->assertFalse($this->agenda()->editEvent(999999999, '2031-01-02 08:00:00', '2031-01-02 09:00:00', 'false', 't', 'c'));
        $this->assertFalse($this->agenda('course', null)->editEvent($id, '2031-01-02 08:00:00', '2031-01-02 09:00:00', 'false', 't', 'c'));
    }

    public function testEditEventOfAnotherCourseIsRefused(): void
    {
        // Access control: a teacher of course 1 must not change an event of course 2 by passing its id.
        $foreign = $this->event(2, 'ag_foreign');

        $result = $this->agenda('course', 1)->editEvent($foreign, '2031-01-02 08:00:00', '2031-01-02 09:00:00', 'false', 'ag_overwritten', 'c');

        $this->assertFalse($result);
        $this->assertSame('ag_foreign', $this->row($foreign)['title']);
        $this->assertTrue($this->agenda('course', 2)->editEvent($foreign, '2031-01-02 08:00:00', '2031-01-02 09:00:00', 'false', 'ag_by_owner', 'c'));
        $this->assertSame('ag_by_owner', $this->row($foreign)['title']);
    }

    public function testEditEventReplacesTheUserRecipients(): void
    {
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_r', '', ['USER:2']);
        Container::getEntityManager()->clear();

        $this->agenda()->editEvent($id, '2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_r', '', ['USER:3']);

        $this->assertSame([3], $this->linkedUsers($id), 'user 2 is unlinked, user 3 linked');
        $this->assertCount(1, $this->links($id));
    }

    public function testEditEventKeepsTheRecipientsThatStayAndSwapsTheOthers(): void
    {
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_k', '', ['USER:1', 'USER:2']);
        Container::getEntityManager()->clear();

        $this->agenda()->editEvent($id, '2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_k', '', ['USER:2', 'USER:3']);

        $this->assertEqualsCanonicalizing([2, 3], $this->linkedUsers($id));
        $this->assertCount(2, $this->links($id));
    }

    public function testEditEventFromEveryoneToUsersDropsTheCourseWideLink(): void
    {
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_e2u', '', ['everyone']);
        Container::getEntityManager()->clear();

        $this->agenda()->editEvent($id, '2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_e2u', '', ['USER:3']);

        $this->assertSame([3], $this->linkedUsers($id));
        $this->assertCount(1, $this->links($id), 'the course-wide link is gone, so the event is no longer for everyone');
    }

    public function testEditEventFromUsersToEveryoneUnlinksThemAndLinksTheCourse(): void
    {
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_all', '', ['USER:2']);
        Container::getEntityManager()->clear();

        $this->agenda()->editEvent($id, '2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_all', '', ['everyone']);

        $this->assertSame([], $this->linkedUsers($id));
        $links = $this->links($id);
        $this->assertCount(1, $links, 'one course-wide link, so the event is visible to everyone again');
        $this->assertSame(1, (int) $links[0]['c_id']);
        $this->assertNull($links[0]['user_id']);
    }

    public function testEditEventToEveryoneTwiceKeepsASingleCourseLink(): void
    {
        $id = $this->agenda()->addEvent('2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_ee', '', ['everyone']);
        Container::getEntityManager()->clear();

        $this->agenda()->editEvent($id, '2030-06-14 09:00:00', '2030-06-14 10:00:00', 'false', 'ag_ee', '', ['everyone']);

        $this->assertCount(1, $this->links($id));
    }

    /** @return list<int> */
    private function linkedUsers(int $iid): array
    {
        return array_values(array_filter(array_map(
            static fn (array $l): int => (int) $l['user_id'],
            $this->links($iid)
        )));
    }

    // ---------------------------------------------------------------- dead and broken, pinned cheaply

    public function testDeleteEventRemovesTheEventOnlyWhenEditable(): void
    {
        $id = $this->event();

        $this->agenda('course', 1, false)->deleteEvent($id);
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_calendar_event WHERE iid = ?', [$id]));

        // PINNED QUIRK: deleteEvent() prints "1" on success (legacy AJAX protocol).
        ob_start();
        $this->agenda()->deleteEvent($id);
        $this->assertSame('1', ob_get_clean());
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_calendar_event WHERE iid = ?', [$id]));
    }

    public function testDeleteEventOfAnotherCourseIsRefused(): void
    {
        // Access control: deleteEvent() reads the course from the context but the event only by id.
        $foreign = $this->event(2, 'ag_foreign');

        ob_start();
        $this->agenda('course', 1)->deleteEvent($foreign);
        ob_end_clean();

        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_calendar_event WHERE iid = ?', [$foreign]));
    }

    public function testDeleteWholeSeriesFailsBecauseOfTheOldRepeatQuery(): void
    {
        // PINNED QUIRK (schema drift): getAllRepeatEvents() filters c_calendar_event on c_id, a column that no
        // longer exists, so deleting a series throws before anything is removed.
        $id = $this->event();

        $this->expectException(NotAllowedException::class);

        $this->agenda()->deleteEvent($id, true);
    }

    public function testResizeAndMoveEventFailBecauseOfTheOldTableLayout(): void
    {
        $id = $this->event();

        foreach ([
            fn () => $this->agenda()->resizeEvent($id, 30),
            fn () => $this->agenda()->move_event($id, 30, 0),
            fn () => $this->agenda()->getAllRepeatEvents($id),
        ] as $call) {
            try {
                $call();
                $this->fail('expected the query on c_calendar_event.c_id/id to fail');
            } catch (NotAllowedException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame('2030-06-14 01:00:00', $this->row($id)['end_date'], 'nothing was changed');
    }

    public function testPersonalAndPlatformEventsFailBecauseTheirTablesAreGone(): void
    {
        // The personal_agenda and sys_calendar tables do not exist any more.
        foreach ([
            fn () => $this->agenda('personal', null)->getPersonalEvents(0, 0),
            fn () => $this->agenda('admin', null)->getPlatformEvents(0, 0),
        ] as $call) {
            try {
                $call();
                $this->fail('expected a missing table error');
            } catch (NotAllowedException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function session(): int
    {
        $this->conn->insert('session', [
            'title' => 'ag_session_'.(++$this->seq).'_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }
}

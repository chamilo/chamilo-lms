<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A course event can be sent to the whole course, to one user or to one group (legacy
 * "users_to_send"). The agenda must show an event sent to a user only to that user, and an event
 * sent to a group only to its members, as Agenda::getCourseEvents() did; the teacher sees them
 * all. Each hidden case has a twin proving the event exists and is visible to its recipient.
 */
final class CCalendarEventRecipientApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCourseAgendaShowsEachEventOnlyToItsRecipients(): void
    {
        $ctx = $this->bootstrapScenario('course');
        $query = '?cid='.$ctx['course']->getId();

        $this->assertSame(
            ['CrEveryone', 'CrToGroup', 'CrToUser'],
            $this->visibleTitles($ctx['member'], $query, 'Cr')
        );
        $this->assertSame(['CrEveryone'], $this->visibleTitles($ctx['other'], $query, 'Cr'));
        $this->assertSame(
            ['CrEveryone', 'CrToGroup', 'CrToUser'],
            $this->visibleTitles($ctx['teacher'], $query, 'Cr')
        );
    }

    public function testPersonalAgendaShowsEachEventOnlyToItsRecipients(): void
    {
        $ctx = $this->bootstrapScenario('personal');

        $this->assertSame(
            ['CrEveryone', 'CrToGroup', 'CrToUser'],
            $this->visibleTitles($ctx['member'], '', 'Cr')
        );
        $this->assertSame(['CrEveryone'], $this->visibleTitles($ctx['other'], '', 'Cr'));
    }

    public function testGroupAgendaShowsTheEventSentToTheGroup(): void
    {
        $ctx = $this->bootstrapScenario('group');
        $query = '?cid='.$ctx['course']->getId().'&gid='.$ctx['group']->getIid();

        $this->assertSame(['CrToGroup'], $this->visibleTitles($ctx['member'], $query, 'Cr'));
    }

    public function testCannotSendAnEventToAUserOutsideTheCourse(): void
    {
        $ctx = $this->bootstrapScenario('outsider');
        $outsider = $this->createUser('cer_outsider_outsider');

        $this->createEvent($ctx, 'CrToOutsider', ['uid' => (int) $outsider->getId()]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame([], $this->eventLinks('CrToOutsider'));
    }

    public function testCannotSendAnEventToAGroupOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('foreign_group');
        $foreignGroup = $this->createCourseGroup('CER foreign group', $this->createCourse('CER foreign course'));

        $this->createEvent($ctx, 'CrToForeignGroup', ['gid' => (int) $foreignGroup->getIid()]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame([], $this->eventLinks('CrToForeignGroup'));
    }

    public function testEventSentToAGroupKeepsItsGroup(): void
    {
        $ctx = $this->bootstrapScenario('keep_group');

        // Without this, the event would be published to the whole course.
        $this->assertSame(
            [['user' => 0, 'group' => (int) $ctx['group']->getIid()]],
            $this->eventLinks('CrToGroup')
        );
        $this->assertSame(
            [['user' => (int) $ctx['member']->getId(), 'group' => 0]],
            $this->eventLinks('CrToUser')
        );
    }

    public function testEditingReplacesTheRecipients(): void
    {
        $ctx = $this->bootstrapScenario('edit');
        $groupId = (int) $ctx['group']->getIid();

        $this->updateRecipients($ctx, 'CrToUser', [['gid' => $groupId]]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['user' => 0, 'group' => $groupId]], $this->eventLinks('CrToUser'));

        // No recipient means the whole course again.
        $this->updateRecipients($ctx, 'CrToUser', []);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['user' => 0, 'group' => 0]], $this->eventLinks('CrToUser'));
        $this->assertContains('CrToUser', $this->visibleTitles($ctx['other'], '?cid='.$ctx['course']->getId(), 'Cr'));
    }

    public function testEditingCannotSendAnEventToAUserOutsideTheCourse(): void
    {
        $ctx = $this->bootstrapScenario('edit_outsider');
        $outsider = $this->createUser('cer_outsider_edit_outsider');

        $this->updateRecipients($ctx, 'CrToGroup', [['uid' => (int) $outsider->getId()]]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame(
            [['user' => 0, 'group' => (int) $ctx['group']->getIid()]],
            $this->eventLinks('CrToGroup')
        );
    }

    public function testOnlyTeachersGetTheRecipientList(): void
    {
        $ctx = $this->bootstrapScenario('list');
        $url = '/api/calendar/course-recipients?cid='.$ctx['course']->getId();

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))
            ->request('GET', $url, ['headers' => ['Accept' => 'application/json']])
        ;
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertContains((int) $ctx['member']->getId(), array_column($data['users'], 'id'));
        $this->assertSame([(int) $ctx['group']->getIid()], array_column($data['groups'], 'id'));

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['member']))
            ->request('GET', $url, ['headers' => ['Accept' => 'application/json']])
        ;
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * A course with a teacher, a group member and another student, and three events created by
     * the teacher: for everyone, for the group and for the member.
     *
     * @return array{course: Course, group: CGroup, teacher: User, member: User, other: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $course = $this->createCourse('CER '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('cer_teacher_'.$suffix);
        $member = $this->createUser('cer_member_'.$suffix);
        $other = $this->createUser('cer_other_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($member);
        $course->addUserAsStudent($other);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $group = $this->createCourseGroup('CER group '.$suffix, $course);

        $em = $this->getEntityManager();
        $em->persist(
            (new CGroupRelUser())
                ->setStatus(1)
                ->setUser($member)
                ->setGroup($group)
                ->setRole('member')
                ->setCId((int) $course->getId())
        );
        $em->flush();

        $ctx = [
            'course' => $course,
            'group' => $group,
            'teacher' => $teacher,
            'member' => $member,
            'other' => $other,
        ];

        $this->createEvent($ctx, 'CrEveryone');
        $this->assertResponseStatusCodeSame(201);
        $this->createEvent($ctx, 'CrToGroup', ['gid' => (int) $group->getIid()]);
        $this->assertResponseStatusCodeSame(201);
        $this->createEvent($ctx, 'CrToUser', ['uid' => (int) $member->getId()]);
        $this->assertResponseStatusCodeSame(201);

        return $ctx;
    }

    private function createCourseGroup(string $title, Course $course): CGroup
    {
        $group = (new CGroup())
            ->setTitle($title)
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->setMaxStudent(100)
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($group);
        $em->flush();

        return $group;
    }

    /**
     * @param array{course: Course, teacher: User} $ctx
     * @param array<string, int>                   $recipient the uid or gid of the link
     */
    private function createEvent(array $ctx, string $title, array $recipient = []): void
    {
        $course = $ctx['course'];
        $teacher = $ctx['teacher'];

        $link = ['visibility' => ResourceLink::VISIBILITY_PUBLISHED];
        if ([] !== $recipient) {
            $link = ['cid' => (int) $course->getId()] + $recipient + $link;
        }

        $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/c_calendar_events?cid='.$course->getId(),
            [
                'json' => [
                    'title' => $title,
                    'content' => '<p>'.$title.'</p>',
                    'startDate' => '2040-06-30 11:00:00',
                    'endDate' => '2040-06-30 15:00:00',
                    'allDay' => false,
                    'collective' => false,
                    'parentResourceNode' => (int) $teacher->getResourceNode()?->getId(),
                    'resourceLinkList' => [$link],
                ],
            ],
        );
    }

    /**
     * @param array{course: Course, teacher: User} $ctx
     * @param list<array<string, int>>             $recipients
     */
    private function updateRecipients(array $ctx, string $title, array $recipients): void
    {
        $em = $this->getEntityManager();
        $em->clear();
        $event = $em->getRepository(CCalendarEvent::class)->findOneBy(['title' => $title]);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/c_calendar_events/'.$event?->getIid().'?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => $title,
                    'content' => '<p>'.$title.'</p>',
                    'startDate' => '2040-06-30 11:00:00',
                    'endDate' => '2040-06-30 15:00:00',
                    'collective' => false,
                    'courseRecipients' => $recipients,
                ],
            ],
        );
    }

    /**
     * Titles starting with the prefix that the user's agenda lists, sorted.
     *
     * @return list<string>
     */
    private function visibleTitles(User $user, string $query, string $prefix): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/c_calendar_events'.$query,
            ['headers' => ['Accept' => 'application/json']],
        );
        $this->assertResponseIsSuccessful();

        $titles = array_filter(
            array_column($response->toArray(), 'title'),
            static fn (string $title): bool => str_starts_with($title, $prefix)
        );
        sort($titles);

        return array_values($titles);
    }

    /**
     * The user and group of each link of the events with this title.
     *
     * @return list<array{user: int, group: int}>
     */
    private function eventLinks(string $title): array
    {
        $em = $this->getEntityManager();
        $em->clear();

        $links = [];
        foreach ($em->getRepository(CCalendarEvent::class)->findBy(['title' => $title]) as $event) {
            foreach ($event->getResourceNode()?->getResourceLinks() ?? [] as $link) {
                $links[] = [
                    'user' => (int) $link->getUser()?->getId(),
                    'group' => (int) $link->getGroup()?->getIid(),
                ];
            }
        }

        return $links;
    }
}

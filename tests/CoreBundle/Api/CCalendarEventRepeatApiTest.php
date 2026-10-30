<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventRepeat;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use DateTimeZone;

/**
 * A course event can be repeated until a given day, as in the legacy agenda form: each repetition
 * is a copy of the event, sent to the same recipients, and the series can be deleted at once.
 */
final class CCalendarEventRepeatApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAWeeklyEventIsCopiedEachWeekUntilTheLastDay(): void
    {
        $ctx = $this->bootstrapScenario('weekly');

        $this->createEvent($ctx, 'CrpWeekly', ['repeatType' => 'weekly', 'repeatEndDate' => '2037-06-25']);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame(
            ['2037-06-04 11:00', '2037-06-11 11:00', '2037-06-18 11:00', '2037-06-25 11:00'],
            $this->startDates('CrpWeekly')
        );
        // The learners see every event of the series.
        $events = $this->visibleEvents($ctx['student'], $ctx['course'], 'CrpWeekly');
        $this->assertCount(4, $events);
        // Every event names the same series, which the "Delete all items" button needs.
        $this->assertCount(1, array_unique(array_column($events, 'seriesId')));
        $this->assertNotNull($events[0]['seriesId'] ?? null);
    }

    public function testASeriesCanEndAfterJanuary2038(): void
    {
        $ctx = $this->bootstrapScenario('2038');

        // The end of the series is stored as a timestamp, which a 32-bit column cannot hold here.
        $this->createEvent($ctx, 'CrpAfter2038', ['repeatType' => 'yearly', 'repeatEndDate' => '2039-06-10']);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame(
            ['2037-06-04 11:00', '2038-06-04 11:00', '2039-06-04 11:00'],
            $this->startDates('CrpAfter2038')
        );

        // The series keeps its last day, after January 2038.
        $calEnd = $this->getEntityManager()->getRepository(CCalendarEventRepeat::class)->findOneBy([
            'event' => $this->getEntityManager()->getRepository(CCalendarEvent::class)->findOneBy(['title' => 'CrpAfter2038', 'parentEvent' => null]),
        ])?->getCalEnd();
        $this->assertNotNull($calEnd);
        $this->assertGreaterThan(new DateTime('2039-06-10 00:00:00', new DateTimeZone('UTC')), $calEnd);
        $this->assertLessThan(new DateTime('2039-06-11 12:00:00', new DateTimeZone('UTC')), $calEnd);
    }

    public function testTheRepeatedEventsAreSentToTheSameRecipients(): void
    {
        $ctx = $this->bootstrapScenario('recipients');

        $this->createEvent(
            $ctx,
            'CrpToStudent',
            ['repeatType' => 'daily', 'repeatEndDate' => '2037-06-05'],
            ['cid' => (int) $ctx['course']->getId(), 'uid' => (int) $ctx['student']->getId()]
        );
        $this->assertResponseStatusCodeSame(201);

        $this->assertCount(2, $this->visibleEvents($ctx['student'], $ctx['course'], 'CrpToStudent'));
        $this->assertCount(0, $this->visibleEvents($ctx['other'], $ctx['course'], 'CrpToStudent'));
    }

    public function testAnInvalidRepeatCreatesNoEvent(): void
    {
        $ctx = $this->bootstrapScenario('invalid');

        $this->createEvent($ctx, 'CrpBadType', ['repeatType' => 'hourly', 'repeatEndDate' => '2037-06-25']);
        $this->assertResponseStatusCodeSame(400);

        $this->createEvent($ctx, 'CrpEndsBefore', ['repeatType' => 'weekly', 'repeatEndDate' => '2037-06-01']);
        $this->assertResponseStatusCodeSame(400);

        $this->assertSame([], $this->startDates('CrpBadType'));
        $this->assertSame([], $this->startDates('CrpEndsBefore'));
    }

    public function testDeletingWithTheSeriesOptionDeletesEveryEventOfTheSeries(): void
    {
        $ctx = $this->bootstrapScenario('delete_series');

        $this->createEvent($ctx, 'CrpDeleteAll', ['repeatType' => 'weekly', 'repeatEndDate' => '2037-06-25']);
        $this->assertResponseStatusCodeSame(201);

        $this->deleteEvent($ctx, 'CrpDeleteAll', '2037-06-18 11:00', true);
        $this->assertResponseStatusCodeSame(204);

        $this->assertSame([], $this->startDates('CrpDeleteAll'));
    }

    public function testDeletingTheFirstEventKeepsTheOtherEvents(): void
    {
        $ctx = $this->bootstrapScenario('delete_first');

        $this->createEvent($ctx, 'CrpDeleteFirst', ['repeatType' => 'weekly', 'repeatEndDate' => '2037-06-18']);
        $this->assertResponseStatusCodeSame(201);

        // The other events point to the first one, which must not block its deletion.
        $this->deleteEvent($ctx, 'CrpDeleteFirst', '2037-06-04 11:00', false);
        $this->assertResponseStatusCodeSame(204);

        $this->assertSame(['2037-06-11 11:00', '2037-06-18 11:00'], $this->startDates('CrpDeleteFirst'));
    }

    /**
     * @return array{course: Course, teacher: User, student: User, other: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $course = $this->createCourse('CRP '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('crp_teacher_'.$suffix);
        $student = $this->createUser('crp_student_'.$suffix);
        $other = $this->createUser('crp_other_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $course->addUserAsStudent($other);
        self::getContainer()->get(CourseRepository::class)->update($course);

        return ['course' => $course, 'teacher' => $teacher, 'student' => $student, 'other' => $other];
    }

    /**
     * @param array{course: Course, teacher: User, ...} $ctx
     * @param array<string, string>                     $repeat
     * @param array<string, int>                        $link
     */
    private function createEvent(array $ctx, string $title, array $repeat, array $link = []): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/c_calendar_events?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => $title,
                    'content' => '<p>'.$title.'</p>',
                    'startDate' => '2037-06-04T11:00:00+00:00',
                    'endDate' => '2037-06-04T12:00:00+00:00',
                    'allDay' => false,
                    'collective' => false,
                    'parentResourceNode' => (int) $ctx['teacher']->getResourceNode()?->getId(),
                    'resourceLinkList' => [$link + ['visibility' => ResourceLink::VISIBILITY_PUBLISHED]],
                ] + $repeat,
            ],
        );
    }

    /**
     * @param array{course: Course, teacher: User, ...} $ctx
     */
    private function deleteEvent(array $ctx, string $title, string $startDate, bool $wholeSeries): void
    {
        $em = $this->getEntityManager();
        $em->clear();

        $eventId = null;
        foreach ($em->getRepository(CCalendarEvent::class)->findBy(['title' => $title]) as $event) {
            if ($startDate === $event->getStartDate()?->format('Y-m-d H:i')) {
                $eventId = $event->getIid();
            }
        }

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'DELETE',
            '/api/c_calendar_events/'.$eventId.'?cid='.$ctx['course']->getId().($wholeSeries ? '&deleteSeries=1' : ''),
        );
    }

    /**
     * The start dates (UTC) of the events with this title, sorted.
     *
     * @return list<string>
     */
    private function startDates(string $title): array
    {
        $em = $this->getEntityManager();
        $em->clear();

        $dates = array_map(
            static fn (CCalendarEvent $event): string => (string) $event->getStartDate()?->format('Y-m-d H:i'),
            $em->getRepository(CCalendarEvent::class)->findBy(['title' => $title])
        );
        sort($dates);

        return $dates;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function visibleEvents(User $user, Course $course, string $title): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/c_calendar_events?cid='.$course->getId(),
            ['headers' => ['Accept' => 'application/json']],
        );
        $this->assertResponseIsSuccessful();

        return array_values(array_filter(
            $response->toArray(),
            static fn (array $event): bool => $title === $event['title']
        ));
    }
}

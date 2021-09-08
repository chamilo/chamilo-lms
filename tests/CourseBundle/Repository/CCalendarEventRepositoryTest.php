<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Datetime;

class CCalendarEventRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetEvents(): void
    {
        $token = $this->getUserToken([]);
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events');
        $this->assertResponseIsSuccessful();

        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@id' => '/api/c_calendar_events',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);

        $this->assertCount(0, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(CCalendarEvent::class);
    }

    public function testCreatePersonalEvent(): void
    {
        self::bootKernel();
        $user = $this->createUser('test');
        $resourceNodeId = $user->getResourceNode()->getId();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CCalendarEventRepository::class);

        // Current server local time (check your php.ini).
        $start = new Datetime('2040-06-30 11:00');
        $end = new Datetime('2040-06-30 15:00');

        // 1. Add event.
        $event = (new CCalendarEvent())
            ->setTitle('hello')
            ->setContent('content hello')
            ->setStartDate($start)
            ->setEndDate($end)
            ->setCreator($user)
            ->setParent($user)
            ->setParentResourceNode($resourceNodeId)
        ;
        $this->assertHasNoEntityViolations($event);

        $repo->create($event);

        $token = $this->getUserToken(
            [
                'username' => 'test',
                'password' => 'test',
            ],
            true
        );

        /*$this->createClientWithCredentials($token)->request(
            'GET',
            '/r/agenda/events/'.$event->getResourceNode()->getId().'/info'
        );
        $this->assertResponseIsSuccessful();*/
    }

    public function testCreatePersonalEventApi(): void
    {
        $user = $this->createUser('test');
        $token = $this->getUserToken(
            [
                'username' => 'test',
                'password' => 'test',
            ]
        );
        $resourceNodeId = $user->getResourceNode()->getId();

        // Current server local time (check your php.ini).
        $start = new Datetime('2040-06-30 11:00');
        $end = new Datetime('2040-06-30 15:00');

        // 1. Add event.
        $responseEvent = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_calendar_events',
            [
                'json' => [
                    'allDay' => true,
                    'collective' => false,
                    'title' => 'hello',
                    'content' => '<p>test event</p>',
                    'startDate' => $start->format('Y-m-d H:i:s'),
                    'endDate' => $end->format('Y-m-d H:i:s'),
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@type' => 'CCalendarEvent',
            'title' => 'hello',
            'startDate' => $this->convertToUTCAndFormat($start),
            'endDate' => $this->convertToUTCAndFormat($end),
            'content' => '<p>test event</p>',
            'parentResourceNode' => $resourceNodeId,
        ]);

        // 2. Check that event exists.
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@id' => '/api/c_calendar_events',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);
        $this->assertCount(1, $response->toArray()['hydra:member']);

        // 3. Get events filter by date, search for a very old date. Result: no events.
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events', [
            'query' => [
                'startDate[after]' => '2009-02-14T18:00:00+02:00',
                'endDate[before]' => '2009-02-14T19:00:00+02:00',
            ],
        ]);
        $this->assertCount(0, $response->toArray()['hydra:member']);

        // 4. Get events for valid date.
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events', [
            'query' => [
                'startDate[after]' => '2040-06-01T09:00:00+02:00',
                'endDate[before]' => '2040-06-30T23:00:00+02:00',
            ],
        ]);
        $this->assertCount(1, $response->toArray()['hydra:member']);
    }

    public function testCreatePersonalEventAsAnotherUser(): void
    {
        self::bootKernel();

        // 1. Create user 'test'.
        $user = $this->createUser('test');
        $resourceNodeId = $user->getResourceNode()->getId();

        // Current server local time (check your php.ini).
        $start = new Datetime('2040-06-30 11:00');
        $end = new Datetime('2040-06-30 15:00');

        // 2. Create user "another"
        $this->createUser('another');
        $anotherToken = $this->getUserToken(
            [
                'username' => 'another',
                'password' => 'another',
            ],
            true
        );

        // 3. Add event to user 'test' but logged in as another user.
        $this->createClientWithCredentials($anotherToken)->request(
            'POST',
            '/api/c_calendar_events',
            [
                'json' => [
                    'allDay' => true,
                    'collective' => false,
                    'title' => 'hello',
                    'content' => '<p>test event</p>',
                    'startDate' => $start->format('Y-m-d H:i:s'),
                    'endDate' => $end->format('Y-m-d H:i:s'),
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(500);

        // Another user should have 0 events.
        $this->createClientWithCredentials($anotherToken)->request('GET', '/api/c_calendar_events');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@id' => '/api/c_calendar_events',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);
    }

    public function testAccessPersonalEvent(): void
    {
        self::bootKernel();

        // 1. Create user 'test'.
        $user = $this->createUser('test');
        $token = $this->getUserToken(
            [
                'username' => 'test',
                'password' => 'test',
            ]
        );
        $resourceNodeId = $user->getResourceNode()->getId();

        // Current server local time (check your php.ini).
        $start = new Datetime('2040-06-30 11:00');
        $end = new Datetime('2040-06-30 15:00');

        // 2. Add event to user test
        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_calendar_events',
            [
                'json' => [
                    'allDay' => true,
                    'collective' => false,
                    'title' => 'hello',
                    'content' => '<p>test event</p>',
                    'startDate' => $start->format('Y-m-d H:i:s'),
                    'endDate' => $end->format('Y-m-d H:i:s'),
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        $eventIri = $response->toArray()['@id'];
        $eventId = $response->toArray()['iid'];

        $this->assertNotEmpty($eventIri);

        // 3. Create user "another"
        $another = $this->createUser('another');
        $anotherToken = $this->getUserToken(
            [
                'username' => 'another',
                'password' => 'another',
            ],
            true
        );

        // 4. View as another user.
        $this->createClientWithCredentials($anotherToken)->request('GET', $eventIri);
        $this->assertResponseStatusCodeSame(403);

        // 5. Edit
        $this->createClientWithCredentials($anotherToken)->request('PUT', $eventIri, ['json' => ['title' => 'hehe']]);
        $this->assertResponseStatusCodeSame(403);

        // 6. Delete
        $this->createClientWithCredentials($anotherToken)->request('DELETE', $eventIri);
        $this->assertResponseStatusCodeSame(403);

        // Now change to collective.
        $calendarRepo = self::getContainer()->get(CCalendarEventRepository::class);
        /** @var CCalendarEvent $event */
        $event = $calendarRepo->find($eventId);
        $event->setCollective(true);
        $calendarRepo->update($event);

        // View.
        $this->createClientWithCredentials($anotherToken)->request('GET', $eventIri);
        $this->assertResponseStatusCodeSame(403);

        // Edit.
        $this->createClientWithCredentials($anotherToken)->request(
            'PUT',
            $eventIri,
            ['json' => ['title' => 'hehe', 'content' => 'modified']]
        );
        $this->assertResponseStatusCodeSame(403);

        // Delete
        $this->createClientWithCredentials($anotherToken)->request('DELETE', $eventIri);
        $this->assertResponseStatusCodeSame(403);

        // Share event with "another" user.
        $userRepo = self::getContainer()->get(UserRepository::class);
        $calendarRepo = self::getContainer()->get(CCalendarEventRepository::class);

        /** @var CCalendarEvent $event */
        $event = $calendarRepo->find($eventId);
        $another = $userRepo->find($another->getId());

        // Add "another" as to the user.
        $event
            ->addUserLink($another)
        ;
        $calendarRepo->update($event);

        $this->createClientWithCredentials($anotherToken)->request('GET', $eventIri);
        $this->assertResponseStatusCodeSame(200);

        // Edit.
        $this->createClientWithCredentials($anotherToken)->request(
            'PUT',
            $eventIri,
            ['json' => ['title' => 'hehe', 'content' => 'modified']]
        );
        $this->assertResponseStatusCodeSame(200);

        // Delete
        $this->createClientWithCredentials($anotherToken)->request('DELETE', $eventIri);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCourseEvent(): void
    {
        $course = $this->createCourse('Test');

        $resourceLinkList = [
            [
                'cid' => $course->getId(),
                'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            ],
        ];

        $user = $this->createUser('test');
        $token = $this->getUserToken(
            [
                'username' => 'test',
                'password' => 'test',
            ]
        );
        $resourceNodeId = $course->getResourceNode()->getId();

        // Current server local time (check your php.ini).
        $start = new Datetime('2040-06-30 11:00');
        $end = new Datetime('2040-06-30 15:00');

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_calendar_events',
            [
                'json' => [
                    'allDay' => true,
                    'collective' => false,
                    'title' => 'hello',
                    'content' => '<p>test event</p>',
                    'startDate' => $start->format('Y-m-d H:i:s'),
                    'endDate' => $end->format('Y-m-d H:i:s'),
                    'parentResourceNodeId' => $resourceNodeId,
                    'resourceLinkListFromEntity' => $resourceLinkList,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@type' => 'CCalendarEvent',
            'title' => 'hello',
            'startDate' => $this->convertToUTCAndFormat($start),
            'endDate' => $this->convertToUTCAndFormat($end),
            'content' => '<p>test event</p>',
            'parentResourceNode' => $resourceNodeId,
        ]);

        // Get ALL events.
        $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@id' => '/api/c_calendar_events',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * @covers \Chamilo\CourseBundle\Repository\CCalendarEventRepository
 */
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
        $user = $this->createUser('test');
        $token = $this->getUserToken(
            [
                'username' => 'test',
                'password' => 'test',
            ]
        );
        $resourceNodeId = $user->getResourceNode()->getId();

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_calendar_events',
            [
                'json' => [
                    'allDay' => true,
                    'collective' => false,
                    'title' => 'hello',
                    'content' => '<p>test event</p>',
                    'startDate' => '2040-06-30 11:00',
                    'endDate' => '2040-06-30 15:00',
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
            'startDate' => '2040-06-30T11:00:00+00:00',
            'endDate' => '2040-06-30T15:00:00+00:00',
            'content' => '<p>test event</p>',
            'parentResourceNode' => $resourceNodeId,
        ]);

        // Get ALL events.
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@id' => '/api/c_calendar_events',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);

        $this->assertCount(1, $response->toArray()['hydra:member']);

        // Get events filter by date search for old date.
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events', [
            'query' => [
                'startDate[after]' => '2009-02-14T18:00:00+02:00',
                'endDate[before]' => '2009-02-14T19:00:00+02:00',
            ],
        ]);
        $this->assertCount(0, $response->toArray()['hydra:member']);

        // Search for valid date.
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_calendar_events', [
            'query' => [
                'startDate[after]' => '2040-06-01T18:00:00+02:00',
                'endDate[before]' => '2040-06-30T23:00:00+02:00',
            ],
        ]);
        $this->assertCount(1, $response->toArray()['hydra:member']);

        // Create another user:
        $this->createUser('another');
        $anotherToken = $this->getUserToken(
            [
                'username' => 'another',
                'password' => 'another',
            ],
            true
        );

        $this->createClientWithCredentials($anotherToken)->request('GET', '/api/c_calendar_events');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@id' => '/api/c_calendar_events',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);
    }

    public function testCreateCourseEvent(): void
    {
        $course = $this->createCourse('Test');

        $resourceLinkList = [[
            'cid' => $course->getId(),
            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
        ]];

        $user = $this->createUser('test');
        $token = $this->getUserToken(
            [
                'username' => 'test',
                'password' => 'test',
            ]
        );
        $resourceNodeId = $course->getResourceNode()->getId();

        // Current server local time (check your php.ini).
        $start = new \Datetime('2040-06-30 11:00');
        $end = new \Datetime('2040-06-30 15:00');

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

        // In UTC.
        $start = $start->setTimezone(new \DateTimeZone('UTC'))->format('c');
        $end = $end->setTimezone(new \DateTimeZone('UTC'))->format('c');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CCalendarEvent',
            '@type' => 'CCalendarEvent',
            'title' => 'hello',
            'startDate' => $start,
            'endDate' => $end,
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

<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\Events;

use DigitalOcean\Tests\TestCase;
use DigitalOcean\Events\Events;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class EventsTest extends TestCase
{
    protected $eventId;

    protected function setUp()
    {
        $this->eventId = 123;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMEssage Impossible to process this query: https://api.digitalocean.com/events/123/?client_id=foo&api_key=bar
     */
    public function testProcessQuery()
    {
        $events = new Events($this->getMockCredentials(), $this->getMockAdapterReturns(null));
        $events->show($this->eventId);
    }

    public function testShowUrl()
    {
        $events = new Events($this->getMockCredentials(), $this->getMockAdapter($this->never()));

        $method = new \ReflectionMethod(
            $events, 'buildQuery'
        );
        $method->setAccessible(true);

        $this->assertEquals(
            'https://api.digitalocean.com/events/123/?client_id=foo&api_key=bar',
            $method->invoke($events, $this->eventId)
        );
    }

    public function testShow()
    {
        $response = <<<JSON
{
  "status": "OK",
  "event": {
    "id": 123,
    "action_status": "done",
    "droplet_id": 100824,
    "event_type_id": 1,
    "percentage": "100"
  }
}
JSON
        ;

        $events = new Events($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $events = $events->show($this->eventId);

        $this->assertTrue(is_object($events));
        $this->assertEquals('OK', $events->status);

        $this->assertTrue(is_object($events->event));
        $this->assertSame($this->eventId, $events->event->id);
        $this->assertSame('done', $events->event->action_status);
        $this->assertSame(100824, $events->event->droplet_id);
        $this->assertSame(1, $events->event->event_type_id);
        $this->assertSame('100', $events->event->percentage);
    }
}

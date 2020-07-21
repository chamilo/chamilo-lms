<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

class CreateGlobalEventTest extends V2TestCase
{
    public function action()
    {
        return Rest::CREATE_GLOBAL_EVENT;
    }

    /**
     * Creates a global event.
     */
    public function testCreateGlobalEvent()
    {
        $eventTitle = 'Summer holidays';
        $eventText = 'Rest and have fun';
        $timezone = new DateTimeZone('utc');
        $eventStartDate = new DateTime('tomorrow', $timezone);
        $eventEndDate = clone $eventStartDate;
        $eventEndDate->add(new DateInterval('PT2H'));

        $eventId = $this->integer(
            [
                'eventTitle' => $eventTitle,
                'eventText' => $eventText,
                'eventStartDate' => $eventStartDate->format('c'),
                'eventEndDate' => $eventEndDate->format('c'),
            ]
        );

        $event = Database::getManager()->getRepository('ChamiloCoreBundle:SysCalendar')->find($eventId);
        self::assertNotNull($event, sprintf('Could not get event %d', $eventId));
        self::assertEquals($eventTitle, $event->getTitle());
        self::assertEquals($eventText, $event->getContent());
        self::assertEquals($eventStartDate, $event->getStartDate());
        self::assertEquals($eventEndDate, $event->getEndDate());
    }
}

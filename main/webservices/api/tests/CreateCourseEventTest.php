<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

class CreateCourseEventTest extends V2TestCase
{

    /**
     * @var \Chamilo\CoreBundle\Entity\Course
     */
    private static $course;

    /**
     * @var \Chamilo\CoreBundle\Entity\Session
     */
    private static $session;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$course = \Chamilo\CoreBundle\Entity\Course::getRepository()->findOneBy(['code' => 'TESTCOURSE']);
        if (is_null(self::$course)) {
            self::$course = (new \Chamilo\CoreBundle\Entity\Course())
                ->setCode('TESTCOURSE')
                ->setTitle('Test Course');
            Database::getManager()->persist(self::$course);
            Database::getManager()->flush();
        }

        self::$session = \Chamilo\CoreBundle\Entity\Session::getRepository()->findOneBy(['name' => 'Test Session']);
        if (is_null(self::$session)) {
            self::$session = (new \Chamilo\CoreBundle\Entity\Session())
                ->setName('Test Session');
            Database::getManager()->persist(self::$course);
            Database::getManager()->flush();
        }
    }

    public function action()
    {
        return Rest::CREATE_COURSE_EVENT;
    }

    /**
     * Creates an event for the predefined course.
     */
    public function testCreateCourseEvent()
    {
        $eventTitle = 'A course in session meeting';
        $eventText = 'Do not be late';
        $timezone = new DateTimeZone('utc');
        $eventStartDate = new DateTime('tomorrow', $timezone);
        $eventEndDate = clone $eventStartDate;
        $eventEndDate->add(new DateInterval('PT2H'));

        $eventId = $this->integer(
            [
                'course_code' => self::$course->getCode(),
                'session_id' => 0,
                'eventTitle' => $eventTitle,
                'eventText' => $eventText,
                'eventStartDate' => $eventStartDate->format('c'),
                'eventEndDate' => $eventEndDate->format('c'),
            ]
        );

        $event = Database::getManager()->getRepository('ChamiloCourseBundle:CCalendarEvent')->find($eventId);

        self::assertNotNull($event, sprintf('Could not get event %d', $eventId));
        self::assertEquals(self::$course->getId(), $event->getCId());
        self::assertEmpty($event->getSessionId());
        self::assertEquals($eventTitle, $event->getTitle());
        self::assertEquals($eventText, $event->getContent());
        self::assertEquals($eventStartDate, $event->getStartDate());
        self::assertEquals($eventEndDate, $event->getEnddate());
    }

    /**
     * Creates an event for the predefined course in the predefined session.
     *
     * @throws Exception
     */
    public function testCreateCourseInSessionEvent()
    {
        $eventTitle = 'A course meeting';
        $eventText = 'Do not be late';
        $timezone = new DateTimeZone('utc');
        $eventStartDate = new DateTime('tomorrow', $timezone);
        $eventEndDate = clone $eventStartDate;
        $eventEndDate->add(new DateInterval('PT2H'));

        $eventId = $this->integer(
            [
                'course_code' => self::$course->getCode(),
                'session_id' => self::$session->getId(),
                'eventTitle' => $eventTitle,
                'eventText' => $eventText,
                'eventStartDate' => $eventStartDate->format('c'),
                'eventEndDate' => $eventEndDate->format('c'),
            ]
        );

        $event = Database::getManager()->getRepository('ChamiloCourseBundle:CCalendarEvent')->find($eventId);

        self::assertNotNull($event, sprintf('Could not get event %d', $eventId));
        self::assertEquals(self::$course->getId(), $event->getCId());
        self::assertEquals(self::$session->getId(), $event->getSessionId());
        self::assertEquals($eventTitle, $event->getTitle());
        self::assertEquals($eventText, $event->getContent());
        self::assertEquals($eventStartDate, $event->getStartDate());
        self::assertEquals($eventEndDate, $event->getEnddate());
    }
}

<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

class CreateUserEventTest extends V2TestCase
{
    /**
     * @var \Chamilo\UserBundle\Entity\User|null
     */
    private static $user;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $username = 'sebastien';
        self::$user = \Chamilo\UserBundle\Entity\User::getRepository()->findOneBy(['username' => $username]);
        if (is_null(self::$user)) {
            self::$user = (new \Chamilo\UserBundle\Entity\User())
                ->setUsername($username)
                ->setEmail('test@test.com')
                ;
            Database::getManager()->persist(self::$user);
            Database::getManager()->flush();
        }
    }

    public function action()
    {
        return Rest::CREATE_USER_EVENT;
    }

    /**
     * Creates an event for the predefined user.
     */
    public function testCreateUserEvent()
    {
        $eventTitle = 'An appointment';
        $eventText = 'Do not be late';
        $timezone = new DateTimeZone('utc');
        $eventStartDate = new DateTime('tomorrow', $timezone);
        $eventEndDate = clone $eventStartDate;
        $eventEndDate->add(new DateInterval('PT2H'));

        $eventId = $this->integer(
            [
                'loginname' => self::$user->getUsername(),
                'eventTitle' => $eventTitle,
                'eventText' => $eventText,
                'eventStartDate' => $eventStartDate->format('c'),
                'eventEndDate' => $eventEndDate->format('c'),
            ]
        );

        $event = Database::getManager()->getRepository('ChamiloCoreBundle:PersonalAgenda')->find($eventId);
        self::assertNotNull($event, sprintf('Could not get event %d', $eventId));
        self::assertEquals(self::$user->getId(), $event->getUser());
        self::assertEquals($eventTitle, $event->getTitle());
        self::assertEquals($eventText, $event->getText());
        self::assertEquals($eventStartDate, $event->getDate());
        self::assertEquals($eventEndDate, $event->getEnddate());
    }
}

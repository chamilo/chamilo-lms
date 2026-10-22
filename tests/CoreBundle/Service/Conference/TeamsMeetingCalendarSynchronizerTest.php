<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingCalendarSynchronizer;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TeamsMeetingCalendarSynchronizerTest extends TestCase
{
    public function testUpdatesLinkedAgendaEventWithProtectedJoinLink(): void
    {
        $event = (new CCalendarEvent())
            ->setTitle('Old title')
            ->setContent('Old content')
            ->setStartDate(new DateTime('2026-10-08 10:00:00+00:00'))
            ->setEndDate(new DateTime('2026-10-08 11:00:00+00:00'))
        ;
        $meeting = self::meeting(42, 99, 'Updated Teams class');

        $service = $this->service($event);
        $service->updateFromMeeting($meeting);

        self::assertSame('Updated Teams class', $event->getTitle());
        self::assertSame('2026-10-08 15:00:00', $event->getStartDate()?->format('Y-m-d H:i:s'));
        self::assertSame('2026-10-08 16:30:00', $event->getEndDate()?->format('Y-m-d H:i:s'));
        self::assertStringContainsString('/conference/teams-auth/join/42', (string) $event->getContent());
        self::assertStringNotContainsString('teams.microsoft.com', (string) $event->getContent());
    }

    public function testCancellingMeetingKeepsHistoricalAgendaEventWithoutJoinLink(): void
    {
        $event = (new CCalendarEvent())
            ->setTitle('Teams class')
            ->setContent('Old content')
        ;
        $meeting = self::meeting(42, 99, 'Teams class');

        $service = $this->service($event);
        $service->markCancelled($meeting);

        self::assertSame('Cancelled: Teams class', $event->getTitle());
        self::assertStringContainsString('cancelled in Chamilo', (string) $event->getContent());
        self::assertStringNotContainsString('/conference/teams-auth/join/42', (string) $event->getContent());
    }

    private function service(CCalendarEvent $event): TeamsMeetingCalendarSynchronizer
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('find')
            ->with(CCalendarEvent::class, 99)
            ->willReturn($event)
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->with(
                'teams_conference_auth_join',
                ['id' => 42],
                UrlGeneratorInterface::ABSOLUTE_PATH,
            )
            ->willReturn('/conference/teams-auth/join/42')
        ;

        return new TeamsMeetingCalendarSynchronizer($entityManager, $urlGenerator);
    }

    private static function meeting(int $id, int $calendarId, string $title): ConferenceMeeting
    {
        $meeting = (new ConferenceMeeting())
            ->setTitle($title)
            ->setCalendarId($calendarId)
            ->setStartAt(new DateTime('2026-10-08 15:00:00+00:00'))
            ->setEndAt(new DateTime('2026-10-08 16:30:00+00:00'))
        ;

        $idProperty = new ReflectionProperty(ConferenceMeeting::class, 'id');
        $idProperty->setValue($meeting, $id);

        return $meeting;
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Schema\Schema;

class Version20240323181500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate sys_calendar to c_calendar_event';
    }

    public function up(Schema $schema): void
    {
        $sysCalendars = $this->connection->fetchAllAssociative('SELECT * FROM sys_calendar');

        $utc = new DateTimeZone('UTC');
        $admin = $this->getAdmin();
        foreach ($sysCalendars as $sysCalendar) {
            $calendarEvent = $this->createCCalendarEvent(
                $sysCalendar['title'] ?: '-',
                $sysCalendar['content'],
                $sysCalendar['start_date'] ? new DateTime($sysCalendar['start_date'], $utc) : null,
                $sysCalendar['end_date'] ? new DateTime($sysCalendar['end_date'], $utc) : null,
                (bool) $sysCalendar['all_day'],
                $sysCalendar['color'] ?? '',
                $admin
            );

            $this->entityManager->persist($calendarEvent);
            $this->addGlobalResourceLinkToNode($calendarEvent->getResourceNode());
        }

        $this->entityManager->flush();
    }

    private function createCCalendarEvent(
        string $title,
        string $content,
        ?DateTime $startDate,
        ?DateTime $endDate,
        bool $allDay,
        string $color,
        User $creator
    ): CCalendarEvent {
        $calendarEvent = new CCalendarEvent();
        $calendarEvent
            ->setTitle($title)
            ->setContent($content)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setAllDay($allDay)
            ->setColor($color)
            ->setCreator($creator)
            ->setResourceName($title)
            ->setParentResourceNode($creator->getResourceNode()->getId())
        ;

        return $calendarEvent;
    }

    private function addGlobalResourceLinkToNode($resourceNode): void
    {
        $globalLink = new ResourceLink();
        $globalLink->setCourse(null)
            ->setSession(null)
            ->setGroup(null)
            ->setUser(null)
        ;

        $alreadyHasGlobalLink = false;
        foreach ($resourceNode->getResourceLinks() as $existingLink) {
            if (null === $existingLink->getCourse() && null === $existingLink->getSession()
                && null === $existingLink->getGroup() && null === $existingLink->getUser()) {
                $alreadyHasGlobalLink = true;

                break;
            }
        }

        if (!$alreadyHasGlobalLink) {
            $resourceNode->addResourceLink($globalLink);
            $this->entityManager->persist($globalLink);
        }
    }

    public function down(Schema $schema): void
    {
        // Down migration is not defined, as data migration cannot be easily reverted
    }
}

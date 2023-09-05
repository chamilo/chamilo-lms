<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\PersonalAgenda;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Exception\ORMException;

class Version20230904173400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate personal_agenda to c_calendar_event';
    }

    /**
     * @inheritDoc
     *
     * @throws ORMException
     * @throws \Exception
     */
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE personal_agenda SET parent_event_id = NULL WHERE parent_event_id = 0 OR parent_event_id = ''");
        $this->addSql("UPDATE personal_agenda SET parent_event_id = NULL WHERE parent_event_id NOT IN (SELECT id FROM personal_agenda)");
        $this->addSql("DELETE FROM personal_agenda WHERE user NOT IN (SELECT id FROM user)");

        /** @var array<int, CCalendarEvent> $map */
        $map = [];

        $em = $this->getEntityManager();
        $userRepo = $em->getRepository(User::class);

        $sql = "SELECT * FROM personal_agenda ORDER BY id";
        $result = $em->getConnection()->executeQuery($sql);
        $personalAgendas = $result->fetchAllAssociative();

        $utc = new DateTimeZone('UTC');

        /** @var array $personalAgenda */
        foreach ($personalAgendas as $personalAgenda) {
            $oldParentId = (int) $personalAgenda['parent_event_id'];
            $user = $userRepo->find($personalAgenda['user']);
            $title = $personalAgenda['title'] ?: '-';
            $startDate = $personalAgenda['date'] ? new DateTime($personalAgenda['date'], $utc) : null;
            $endDate = $personalAgenda['enddate'] ? new DateTime($personalAgenda['enddate'], $utc) : null;
            $allDay = (bool) $personalAgenda['all_day'];

            $calendarEvent = new CCalendarEvent();
            $calendarEvent
                ->setTitle($title)
                ->setContent($personalAgenda['text'])
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setAllDay($allDay)
                ->setColor($personalAgenda['color'])
                ->setCreator($user)
                ->setResourceName($title);

            if ($oldParentId && isset($map[$oldParentId])) {
                $newParent = $map[$oldParentId];

                $calendarEvent
                    ->setParentEvent($newParent)
                    ->setParentResourceNode($newParent->getResourceNode()->getId());
            } else {
                $calendarEvent->setParentResourceNode($user->getResourceNode()->getId());
            }

            $map[$personalAgenda['id']] = $calendarEvent;

            $em->persist($calendarEvent);
        }

        $em->flush();
    }
}
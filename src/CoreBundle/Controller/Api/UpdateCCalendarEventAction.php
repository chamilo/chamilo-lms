<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\AgendaHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpdateCCalendarEventAction extends BaseResourceFileAction
{
    public function __invoke(
        CCalendarEvent $calendarEvent,
        Request $request,
        CCalendarEventRepository $repo,
        EntityManager $em,
        SettingsManager $settingsManager,
        AgendaHelper $agendaHelper,
    ): CCalendarEvent {
        // The new links need the right to add an event where they point.
        $this->handleUpdateRequest(
            $calendarEvent,
            $repo,
            $request,
            $em,
            static function (array $links) use ($agendaHelper): void {
                if (!$agendaHelper->canAddLinks($links)) {
                    throw new AccessDeniedHttpException();
                }
            },
            // The agenda form sends the creator's user id as parent; events are scoped by their links.
            false,
        );

        $result = json_decode($request->getContent(), true);

        $calendarEvent
            ->setContent($result['content'] ?? '')
            ->setComment($result['comment'] ?? '')
            ->setColor($result['color'] ?? '')
            ->setStartDate(new DateTime($result['startDate'] ?? ''))
            ->setEndDate(new DateTime($result['endDate'] ?? ''))
            // ->setAllDay($result['allDay'] ?? false)
            ->setCollective($result['collective'] ?? false)
        ;

        if (isset($result['room'])) {
            if (!empty($result['room'])) {
                $roomIri = $result['room'];
                $roomId = (int) preg_replace('/.*\/(\d+)$/', '$1', $roomIri);
                $room = $em->find(Room::class, $roomId);
                $calendarEvent->setRoom($room);
            } else {
                $calendarEvent->setRoom(null);
            }
        }

        $calendarEvent->getReminders()->clear();

        if (isset($result['reminders'])) {
            foreach ($result['reminders'] as $reminderInfo) {
                $reminder = new AgendaReminder();
                $reminder->count = $reminderInfo['count'];
                $reminder->period = $reminderInfo['period'];
                $reminder->decodeDateInterval();

                $calendarEvent->addReminder($reminder);
            }
        }

        return $calendarEvent;
    }
}

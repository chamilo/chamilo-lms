<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class UpdateCCalendarEventAction extends BaseResourceFileAction
{
    public function __invoke(
        CCalendarEvent $calendarEvent,
        Request $request,
        CCalendarEventRepository $repo,
        EntityManager $em
    ): CCalendarEvent {
        $this->handleUpdateRequest($calendarEvent, $repo, $request, $em);

        $result = json_decode($request->getContent(), true);

        $calendarEvent
            ->setContent($result['content'] ?? '')
            ->setComment($result['comment'] ?? '')
            ->setColor($result['color'] ?? '')
            ->setStartDate(new DateTime($result['startDate'] ?? ''))
            ->setEndDate(new DateTime($result['endDate'] ?? ''))
            //->setAllDay($result['allDay'] ?? false)
            ->setCollective($result['collective'] ?? false)
        ;

        return $calendarEvent;
    }
}

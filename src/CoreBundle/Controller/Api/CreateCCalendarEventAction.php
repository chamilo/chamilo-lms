<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class CreateCCalendarEventAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, CCalendarEventRepository $repo, EntityManager $em): CCalendarEvent
    {
        $event = new CCalendarEvent();
        $result = $this->handleCreateRequest($event, $repo, $request);

        $event
            ->setContent($result['content'] ?? '')
            ->setComment($result['comment'] ?? '')
            ->setColor($result['color'] ?? '')
            ->setStartDate(new DateTime($result['startDate'] ?? ''))
            ->setEndDate(new DateTime($result['endDate'] ?? ''))
            //->setAllDay($result['allDay'] ?? false)
        ;

        return $event;
    }
}

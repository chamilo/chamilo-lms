<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class CreateCCalendarEventAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, CCalendarEventRepository $repo, Security $security): CCalendarEvent
    {
        $event = new CCalendarEvent();
        $result = $this->handleCreateRequest($event, $repo, $request);

        /** @var User $currentUser */
        $currentUser = $security->getUser();

        $event
            ->setContent($result['content'] ?? '')
            ->setComment($result['comment'] ?? '')
            ->setColor($result['color'] ?? '')
            ->setStartDate(new DateTime($result['startDate'] ?? ''))
            ->setEndDate(new DateTime($result['endDate'] ?? ''))
            //->setAllDay($result['allDay'] ?? false)
            ->setCollective($result['collective'] ?? false)
            ->setCreator($currentUser)
        ;

        // Detect event type based in the resource link array.

        $type = 'personal';
        if (!empty($event->getResourceLinkArray())) {
            foreach ($event->getResourceLinkArray() as $link) {
                if (isset($link['cid'])) {
                    $type = 'course';

                    break;
                }
            }
        }

        if ('personal' === $type) {
            if ($currentUser->getResourceNode()->getId() !== $event->getParentResourceNode()) {
                throw new Exception('Not allowed');
            }
        }
        // @todo check course access? Should be handle by CourseVoter?

        return $event;
    }
}

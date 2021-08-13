<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

final class CCalendarEventRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CCalendarEvent::class);
    }

    public function createFromAnnouncement(
        CAnnouncement $announcement,
        DateTime $startDate,
        DateTime $endDate,
        array $users,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): CCalendarEvent {
        $event = (new CCalendarEvent())
            ->setTitle($announcement->getTitle())
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setContent($announcement->getContent())
            ->setParent($course)
        ;

        $em = $this->getEntityManager();

        if (empty($users) || (isset($users[0]) && 'everyone' === $users[0])) {
            $event->addCourseLink($course, $session, $group);
        } else {
            $sendTo = AbstractResource::separateUsersGroups($users);

            if (\is_array($sendTo['groups']) && !empty($sendTo['groups'])) {
                $sendTo['groups'] = array_map(
                    fn ($groupId) => $em->find(CGroup::class, $groupId),
                    $sendTo['groups']
                );
                $sendTo['groups'] = array_filter($sendTo['groups']);

                $event->addResourceToGroupList($sendTo['groups'], $course, $session);
            }

            // Storing the selected users.
            if (\is_array($sendTo['users'])) {
                $sendTo['users'] = array_map(
                    fn ($userId) => $em->find(User::class, $userId),
                    $sendTo['users']
                );
                $sendTo['users'] = array_filter($sendTo['users']);

                $event->addResourceToUserList($sendTo['users'], $course, $session, $group);
            }
        }

        $em->persist($event);
        $em->flush();

        return $event;
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceCalendarRelGroup;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\Persistence\ManagerRegistry;

class CAttendanceCalendarRelGroupRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CAttendanceCalendarRelGroup::class);
    }

    /**
     * Add a group relation to a calendar entry.
     */
    public function addGroupToCalendar(int $calendarId, int $groupId): void
    {
        $em = $this->getEntityManager();
        $existingRelation = $this->findOneBy([
            'attendanceCalendar' => $calendarId,
            'group' => $groupId,
        ]);

        if (!$existingRelation) {
            $relation = new CAttendanceCalendarRelGroup();
            $relation->setAttendanceCalendar($em->getReference(CAttendanceCalendar::class, $calendarId));
            $relation->setGroup($em->getReference(CGroup::class, $groupId));

            $em->persist($relation);
            $em->flush();
        }
    }
}

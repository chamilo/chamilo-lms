<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Doctrine\Persistence\ManagerRegistry;

final class CAttendanceSheetRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CAttendanceSheet::class);
    }

    public function getUserScore(int $userId, int $attendanceId, ?int $groupId = null): int
    {
        $qb = $this->createQueryBuilder('sheet')
            ->select('SUM(sheet.presence) as score')
            ->join('sheet.attendanceCalendar', 'calendar')
            ->where('calendar.attendance = :attendanceId')
            ->andWhere('sheet.user = :userId')
            ->setParameter('attendanceId', $attendanceId)
            ->setParameter('userId', $userId);

        if ($groupId) {
            $qb->join('calendar.groups', 'groups')
                ->andWhere('groups.group = :groupId')
                ->setParameter('groupId', $groupId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

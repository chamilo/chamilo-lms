<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceResultComment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class CAttendanceCalendarRepository extends ResourceRepository
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        parent::__construct($registry, CAttendanceCalendar::class);
        $this->em = $em;
    }

    /**
     * Retrieves all calendar events for a specific attendance.
     *
     * @return CAttendanceCalendar[]
     */
    public function findByAttendanceId(int $attendanceId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
            ->orderBy('c.dateTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Deletes all calendar events associated with a specific attendance.
     *
     * @return int The number of deleted records
     */
    public function deleteAllByAttendance(int $attendanceId): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Finds a specific calendar event by its ID and the associated attendance ID.
     */
    public function findByIdAndAttendance(int $calendarId, int $attendanceId): ?CAttendanceCalendar
    {
        return $this->createQueryBuilder('c')
            ->where('c.id = :calendarId')
            ->andWhere('c.attendance = :attendanceId')
            ->setParameters([
                'calendarId' => $calendarId,
                'attendanceId' => $attendanceId,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Retrieves calendar events filtered by a date range.
     *
     * @return CAttendanceCalendar[]
     */
    public function findByDateRange(
        int $attendanceId,
        ?DateTime $startDate,
        ?DateTime $endDate
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->where('c.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
        ;

        if ($startDate) {
            $qb->andWhere('c.dateTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if ($endDate) {
            $qb->andWhere('c.dateTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        return $qb->orderBy('c.dateTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Checks if a calendar event is blocked.
     */
    public function isBlocked(int $calendarId): bool
    {
        return (bool) $this->createQueryBuilder('c')
            ->select('c.blocked')
            ->where('c.id = :calendarId')
            ->setParameter('calendarId', $calendarId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findAttendanceWithData(int $attendanceId): array
    {
        $calendars = $this->createQueryBuilder('calendar')
            ->andWhere('calendar.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
            ->orderBy('calendar.dateTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $attendanceDates = array_map(function (CAttendanceCalendar $calendar) {
            return [
                'id' => $calendar->getIid(),
                'label' => $calendar->getDateTime()->format('M d, Y - h:i A'),
                'done' => true === $calendar->getDoneAttendance(),
            ];
        }, $calendars);

        $attendanceData = [];
        $commentData = [];
        $signatureData = [];
        foreach ($calendars as $calendar) {
            foreach ($calendar->getSheets() as $sheet) {
                $userId = $sheet->getUser()->getId();
                $calendarId = $calendar->getIid();
                $key = "$userId-$calendarId";

                $attendanceData[$key] = (int) $sheet->getPresence();

                $commentEntity = $this->em->getRepository(CAttendanceResultComment::class)->findOneBy([
                    'attendanceSheetId' => $sheet->getIid(),
                    'userId' => $userId,
                ]);

                $commentData[$key] = $commentEntity?->getComment();
                $signatureData[$key] = $sheet->getSignature();
            }
        }

        return [
            'attendanceDates' => $attendanceDates,
            'attendanceData' => $attendanceData,
            'commentData' => $commentData,
            'signatureData' => $signatureData,
        ];
    }

    public function countByAttendanceAndGroup(int $attendanceId, ?int $groupId = null): int
    {
        $qb = $this->createQueryBuilder('calendar')
            ->select('COUNT(calendar.iid)')
            ->where('calendar.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
        ;

        if ($groupId) {
            $qb->join('calendar.groups', 'groups')
                ->andWhere('groups.group = :groupId')
                ->setParameter('groupId', $groupId)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countDoneAttendanceByAttendanceAndGroup(int $attendanceId, ?int $groupId = null): int
    {
        $qb = $this->createQueryBuilder('calendar')
            ->select('COUNT(calendar.iid)')
            ->where('calendar.attendance = :attendanceId')
            ->andWhere('calendar.doneAttendance = true')
            ->setParameter('attendanceId', $attendanceId)
        ;

        if ($groupId) {
            $qb->join('calendar.groups', 'groups')
                ->andWhere('groups.group = :groupId')
                ->setParameter('groupId', $groupId)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

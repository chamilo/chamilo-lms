<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Doctrine\Persistence\ManagerRegistry;

final class CAttendanceCalendarRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CAttendanceCalendar::class);
    }

    /**
     * Retrieves all calendar events for a specific attendance.
     *
     * @param int $attendanceId
     * @return CAttendanceCalendar[]
     */
    public function findByAttendanceId(int $attendanceId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
            ->orderBy('c.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Deletes all calendar events associated with a specific attendance.
     *
     * @param int $attendanceId
     * @return int The number of deleted records
     */
    public function deleteAllByAttendance(int $attendanceId): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
            ->getQuery()
            ->execute();
    }

    /**
     * Finds a specific calendar event by its ID and the associated attendance ID.
     *
     * @param int $calendarId
     * @param int $attendanceId
     * @return CAttendanceCalendar|null
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
            ->getOneOrNullResult();
    }

    /**
     * Retrieves calendar events filtered by a date range.
     *
     * @param int $attendanceId
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return CAttendanceCalendar[]
     */
    public function findByDateRange(
        int $attendanceId,
        ?\DateTime $startDate,
        ?\DateTime $endDate
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->where('c.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId);

        if ($startDate) {
            $qb->andWhere('c.dateTime >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('c.dateTime <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('c.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Checks if a calendar event is blocked.
     *
     * @param int $calendarId
     * @return bool
     */
    public function isBlocked(int $calendarId): bool
    {
        return (bool) $this->createQueryBuilder('c')
            ->select('c.blocked')
            ->where('c.id = :calendarId')
            ->setParameter('calendarId', $calendarId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAttendanceWithData(int $attendanceId): array
    {
        $calendars = $this->createQueryBuilder('calendar')
            ->andWhere('calendar.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId)
            ->orderBy('calendar.dateTime', 'ASC')
            ->getQuery()
            ->getResult();

        $attendanceDates = array_map(function (CAttendanceCalendar $calendar) {
            return [
                'id' => $calendar->getIid(),
                'label' => $calendar->getDateTime()->format('M d, Y - h:i A'),
            ];
        }, $calendars);

        $attendanceData = [];
        foreach ($calendars as $calendar) {
            /* @var CAttendanceSheet $sheet */
            foreach ($calendar->getSheets() as $sheet) {
                $key = $sheet->getUser()->getId() . '-' . $calendar->getIid();
                $attendanceData[$key] = (int) $sheet->getPresence(); // Status: 1 (Present), 0 (Absent), null (No Status)
            }
        }

        return [
            'attendanceDates' => $attendanceDates,
            'attendanceData' => $attendanceData,
        ];
    }

    public function countByAttendanceAndGroup(int $attendanceId, ?int $groupId = null): int
    {
        $qb = $this->createQueryBuilder('calendar')
            ->select('COUNT(calendar.iid)')
            ->where('calendar.attendance = :attendanceId')
            ->setParameter('attendanceId', $attendanceId);

        if ($groupId) {
            $qb->join('calendar.groups', 'groups')
                ->andWhere('groups.group = :groupId')
                ->setParameter('groupId', $groupId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countDoneAttendanceByAttendanceAndGroup(int $attendanceId, ?int $groupId = null): int
    {
        $qb = $this->createQueryBuilder('calendar')
            ->select('COUNT(calendar.iid)')
            ->where('calendar.attendance = :attendanceId')
            ->andWhere('calendar.doneAttendance = true')
            ->setParameter('attendanceId', $attendanceId);

        if ($groupId) {
            $qb->join('calendar.groups', 'groups')
                ->andWhere('groups.group = :groupId')
                ->setParameter('groupId', $groupId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

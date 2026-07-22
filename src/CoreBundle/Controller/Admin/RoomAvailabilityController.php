<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoomAvailabilityController extends BaseController
{
    #[Route('/admin/rooms/availability', name: 'admin_rooms_availability', methods: ['GET'])]
    public function availability(
        Request $request,
        EntityManagerInterface $em,
        AccessUrlHelper $accessUrlHelper
    ): JsonResponse {
        $start = new DateTime($request->query->get('start', 'now'));
        $end = new DateTime($request->query->get('end', '+1 hour'));
        $accessUrlId = $accessUrlHelper->getCurrent()?->getId();

        if (null === $accessUrlId) {
            return $this->json(['available' => [], 'occupied' => []]);
        }

        $rooms = $em->createQueryBuilder()
            ->select('r.id, r.title, r.floorNumber, r.capacity, b.id AS branchId, b.title AS branchTitle')
            ->from(Room::class, 'r')
            ->innerJoin('r.branch', 'b')
            ->where('IDENTITY(b.url) = :accessUrlId')
            ->setParameter('accessUrlId', $accessUrlId)
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $available = [];
        $occupied = [];

        foreach ($rooms as $room) {
            $calendars = $em->createQueryBuilder()
                ->select('DISTINCT cal.iid, cal.dateTime, cal.duration, a.title AS attendanceTitle, c.title AS courseTitle')
                ->from(CAttendanceCalendar::class, 'cal')
                ->innerJoin('cal.attendance', 'a')
                ->innerJoin('a.resourceNode', 'rn')
                ->innerJoin('rn.resourceLinks', 'rl')
                ->innerJoin('rl.course', 'c')
                ->leftJoin(
                    SessionRelCourse::class,
                    'src',
                    'WITH',
                    'src.session = rl.session AND src.course = rl.course'
                )
                ->where('cal.dateTime < :end')
                ->andWhere(
                    '(IDENTITY(cal.room) = :roomId)'
                    .' OR (cal.room IS NULL AND IDENTITY(a.room) = :roomId)'
                    .' OR (cal.room IS NULL AND a.room IS NULL AND IDENTITY(src.room) = :roomId)'
                    .' OR (cal.room IS NULL AND a.room IS NULL AND src.room IS NULL AND IDENTITY(c.room) = :roomId)'
                )
                ->setParameter('roomId', $room['id'])
                ->setParameter('end', $end, Types::DATETIME_MUTABLE)
                ->getQuery()
                ->getArrayResult()
            ;

            $conflicts = [];
            foreach ($calendars as $calendar) {
                $duration = $calendar['duration'] ?? 60;
                $calendarStart = $calendar['dateTime'];
                $calendarEnd = (clone $calendarStart)->modify("+{$duration} minutes");

                if ($calendarEnd <= $start) {
                    continue;
                }

                $conflicts[] = [
                    'courseTitle' => $calendar['courseTitle'],
                    'attendanceTitle' => $calendar['attendanceTitle'],
                    'start' => $calendarStart->format('c'),
                    'end' => $calendarEnd->format('c'),
                ];
            }

            $roomData = [
                'id' => $room['id'],
                'title' => $room['title'],
                'floorNumber' => $room['floorNumber'],
                'capacity' => $room['capacity'],
                'branch' => [
                    'id' => $room['branchId'],
                    'title' => $room['branchTitle'],
                ],
            ];

            if ([] === $conflicts) {
                $available[] = $roomData;
            } else {
                $roomData['conflicts'] = $conflicts;
                $occupied[] = $roomData;
            }
        }

        return $this->json([
            'available' => $available,
            'occupied' => $occupied,
        ]);
    }
}

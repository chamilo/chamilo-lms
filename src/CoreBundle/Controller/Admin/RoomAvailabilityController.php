<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoomAvailabilityController extends BaseController
{
    #[Route('/admin/rooms/availability', name: 'admin_rooms_availability', methods: ['GET'])]
    public function availability(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $start = new DateTime($request->query->get('start', 'now'));
        $end = new DateTime($request->query->get('end', '+1 hour'));

        // Get all rooms with their branches
        $rooms = $em->createQueryBuilder()
            ->select('r.id, r.title, b.id AS branchId, b.title AS branchTitle')
            ->from('Chamilo\CoreBundle\Entity\Room', 'r')
            ->leftJoin('r.branch', 'b')
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $available = [];
        $occupied = [];

        foreach ($rooms as $room) {
            // Find courses assigned to this room
            $courses = $em->createQueryBuilder()
                ->select('c.id, c.title')
                ->from('Chamilo\CoreBundle\Entity\Course', 'c')
                ->where('c.room = :room')
                ->setParameter('room', $room['id'])
                ->getQuery()
                ->getArrayResult()
            ;

            $conflicts = [];

            foreach ($courses as $course) {
                // Find attendance calendars that overlap with the requested range
                $calendars = $em->createQueryBuilder()
                    ->select('cal.iid, cal.dateTime, cal.duration, a.title AS attendanceTitle')
                    ->from('Chamilo\CourseBundle\Entity\CAttendanceCalendar', 'cal')
                    ->innerJoin('cal.attendance', 'a')
                    ->innerJoin('a.resourceNode', 'rn')
                    ->innerJoin('rn.resourceLinks', 'rl')
                    ->where('rl.course = :courseId')
                    ->andWhere('cal.dateTime < :end')
                    ->setParameter('courseId', $course['id'])
                    ->setParameter('end', $end)
                    ->getQuery()
                    ->getArrayResult()
                ;

                foreach ($calendars as $cal) {
                    $duration = $cal['duration'] ?? 60;
                    $calStart = $cal['dateTime'];
                    $calEnd = (clone $calStart)->modify("+{$duration} minutes");

                    // Check overlap: calStart < requestedEnd AND calEnd > requestedStart
                    if ($calEnd > $start) {
                        $conflicts[] = [
                            'courseTitle' => $course['title'],
                            'start' => $calStart->format('c'),
                            'end' => $calEnd->format('c'),
                        ];
                    }
                }
            }

            $roomData = [
                'id' => $room['id'],
                'title' => $room['title'],
                'branch' => [
                    'id' => $room['branchId'],
                    'title' => $room['branchTitle'],
                ],
            ];

            if (empty($conflicts)) {
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

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoomOccupationController extends BaseController
{
    #[Route('/admin/rooms/{id}/info', name: 'admin_room_info', methods: ['GET'])]
    public function info(Room $room): JsonResponse
    {
        $branch = $room->getBranch();

        return $this->json([
            'title' => $room->getTitle(),
            'branchTitle' => $branch?->getTitle(),
        ]);
    }

    #[Route('/admin/rooms/{id}/occupation', name: 'admin_room_occupation', methods: ['GET'])]
    public function occupation(Room $room, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $start = new DateTime($request->query->get('start', 'now'));
        $end = new DateTime($request->query->get('end', '+7 days'));

        // Find all courses assigned to this room
        $courses = $em->createQueryBuilder()
            ->select('c.id, c.title')
            ->from(Course::class, 'c')
            ->where('c.room = :room')
            ->setParameter('room', $room->getId())
            ->getQuery()
            ->getArrayResult()
        ;

        $events = [];
        $colors = ['#3788d8', '#e67c73', '#33b679', '#f6bf26', '#8e24aa', '#039be5'];
        $colorIndex = 0;

        foreach ($courses as $course) {
            $color = $colors[$colorIndex % \count($colors)];
            $colorIndex++;

            // Find CAttendance linked to this course via resource links
            $attendances = $em->createQueryBuilder()
                ->select('a.iid, a.title')
                ->from(CAttendance::class, 'a')
                ->innerJoin('a.resourceNode', 'rn')
                ->innerJoin('rn.resourceLinks', 'rl')
                ->where('rl.course = :courseId')
                ->setParameter('courseId', $course['id'])
                ->getQuery()
                ->getArrayResult()
            ;

            foreach ($attendances as $attendance) {
                // Get calendar entries in the date range
                $calendars = $em->createQueryBuilder()
                    ->select('cal.iid, cal.dateTime, cal.duration')
                    ->from(CAttendanceCalendar::class, 'cal')
                    ->where('cal.attendance = :attendanceId')
                    ->andWhere('cal.dateTime >= :start')
                    ->andWhere('cal.dateTime <= :end')
                    ->setParameter('attendanceId', $attendance['iid'])
                    ->setParameter('start', $start->format('Y-m-d H:i:s'))
                    ->setParameter('end', $end->format('Y-m-d H:i:s'))
                    ->getQuery()
                    ->getArrayResult()
                ;

                foreach ($calendars as $cal) {
                    $duration = $cal['duration'] ?? 60;
                    $startDt = $cal['dateTime'];
                    $endDt = (clone $startDt)->modify("+{$duration} minutes");

                    $events[] = [
                        'id' => 'cal-'.$cal['iid'],
                        'title' => $course['title'].' - '.$attendance['title'],
                        'start' => $startDt->format('c'),
                        'end' => $endDt->format('c'),
                        'color' => $color,
                    ];
                }
            }
        }

        return $this->json($events);
    }
}

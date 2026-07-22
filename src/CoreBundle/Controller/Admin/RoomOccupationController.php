<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoomOccupationController extends BaseController
{
    #[Route('/admin/rooms/{id}/info', name: 'admin_room_info', methods: ['GET'])]
    public function info(Room $room, RoomAccessUrlHelper $roomAccessUrlHelper): JsonResponse
    {
        if (!$roomAccessUrlHelper->isRoomAllowed($room)) {
            throw new NotFoundHttpException();
        }
        $branch = $room->getBranch();

        return $this->json([
            'title' => $room->getTitle(),
            'branchTitle' => $branch?->getTitle(),
            'floorNumber' => $room->getFloorNumber(),
            'capacity' => $room->getCapacity(),
        ]);
    }

    #[Route('/admin/rooms/{id}/occupation', name: 'admin_room_occupation', methods: ['GET'])]
    public function occupation(
        Room $room,
        Request $request,
        EntityManagerInterface $em,
        RoomAccessUrlHelper $roomAccessUrlHelper
    ): JsonResponse {
        if (!$roomAccessUrlHelper->isRoomAllowed($room)) {
            throw new NotFoundHttpException();
        }

        $start = new DateTime($request->query->get('start', 'now'));
        $end = new DateTime($request->query->get('end', '+7 days'));

        $calendars = $em->createQueryBuilder()
            ->select('DISTINCT cal.iid, cal.dateTime, cal.duration, a.title AS attendanceTitle, c.id AS courseId, c.title AS courseTitle')
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
            ->where('cal.dateTime >= :start')
            ->andWhere('cal.dateTime <= :end')
            ->andWhere(
                '(cal.room = :room)'
                .' OR (cal.room IS NULL AND a.room = :room)'
                .' OR (cal.room IS NULL AND a.room IS NULL AND src.room = :room)'
                .' OR (cal.room IS NULL AND a.room IS NULL AND src.room IS NULL AND c.room = :room)'
            )
            ->setParameter('room', (int) $room->getId())
            ->setParameter('start', $start, Types::DATETIME_MUTABLE)
            ->setParameter('end', $end, Types::DATETIME_MUTABLE)
            ->orderBy('cal.dateTime', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $events = [];
        $colors = ['#3788d8', '#e67c73', '#33b679', '#f6bf26', '#8e24aa', '#039be5'];
        $courseColors = [];

        foreach ($calendars as $calendar) {
            $courseId = (int) $calendar['courseId'];
            if (!isset($courseColors[$courseId])) {
                $courseColors[$courseId] = $colors[\count($courseColors) % \count($colors)];
            }

            $duration = $calendar['duration'] ?? 60;
            $startDt = $calendar['dateTime'];
            $endDt = (clone $startDt)->modify("+{$duration} minutes");

            $events[] = [
                'id' => 'cal-'.$calendar['iid'],
                'title' => $calendar['courseTitle'].' - '.$calendar['attendanceTitle'],
                'start' => $startDt->format('c'),
                'end' => $endDt->format('c'),
                'color' => $courseColors[$courseId],
            ];
        }

        return $this->json($events);
    }
}

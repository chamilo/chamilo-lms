<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\FloorPlanSvgValidator;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Access to a floor plan is granted through the actual course or attendance,
 * never by knowing a branch or room ID.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class RoomFloorPlanViewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RoomAccessUrlHelper $roomAccessUrlHelper,
        private readonly ResourceFileHelper $resourceFiles,
        private readonly ResourceNodeRepository $nodes,
        private readonly FloorPlanSvgValidator $svgValidator,
    ) {}

    #[Route('/api/room-floor-plans/courses/{id}', name: 'room_floor_plan_course_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function courseData(int $id): JsonResponse
    {
        return $this->data($this->courseRoom($id));
    }

    #[Route('/api/room-floor-plans/courses/{id}/image', name: 'room_floor_plan_course_image', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function courseImage(int $id): Response
    {
        return $this->image($this->courseRoom($id));
    }

    #[Route('/api/room-floor-plans/attendance-calendars/{id}', name: 'room_floor_plan_attendance_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function attendanceData(int $id): JsonResponse
    {
        return $this->data($this->calendarRoom($id));
    }

    #[Route('/api/room-floor-plans/attendance-calendars/{id}/image', name: 'room_floor_plan_attendance_image', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function attendanceImage(int $id): Response
    {
        return $this->image($this->calendarRoom($id));
    }

    private function courseRoom(int $id): ?Room
    {
        $course = $this->em->find(Course::class, $id);
        if (!$course instanceof Course) {
            throw $this->createNotFoundException('Course not found.');
        }
        if (!$this->isGranted('VIEW', $course)) {
            throw new AccessDeniedHttpException();
        }

        return $this->checkedRoom($course->getRoom());
    }

    private function calendarRoom(int $id): ?Room
    {
        $calendar = $this->em->find(CAttendanceCalendar::class, $id);
        if (!$calendar instanceof CAttendanceCalendar) {
            throw $this->createNotFoundException('Attendance date not found.');
        }
        if (!$this->isGranted('VIEW', $calendar->getAttendance()->getResourceNode())) {
            throw new AccessDeniedHttpException();
        }

        return $this->checkedRoom($calendar->getEffectiveRoom());
    }

    private function checkedRoom(?Room $room): ?Room
    {
        $this->roomAccessUrlHelper->assertRoomAllowed($room);

        return $room;
    }

    private function data(?Room $room): JsonResponse
    {
        $branch = $room?->getBranch();
        $available = null !== $branch?->getFloorPlan();

        return $this->json([
            'available' => $available,
            'roomTitle' => $room?->getTitle(),
            'branchTitle' => $branch?->getTitle(),
            'roomMap' => $available ? $room?->getRoomMap() : null,
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    private function image(?Room $room): Response
    {
        $branch = $room?->getBranch();
        $node = $branch?->getFloorPlan();
        if (null === $node) {
            throw $this->createNotFoundException('Floor plan not found.');
        }
        $file = $this->resourceFiles->resolveResourceFileByAccessUrl($node);
        if (null === $file
            || !\in_array($file->getMimeType(), ['image/png', 'image/jpeg', 'image/svg+xml'], true)
            || null === $file->getSize() || $file->getSize() > 10485760
            || (null !== $file->getAccessUrl() && $file->getAccessUrl()->getId() !== $branch->getUrl()?->getId())
        ) {
            throw $this->createNotFoundException('Floor plan file unavailable.');
        }
        if ('image/svg+xml' === $file->getMimeType()) {
            $check = $this->nodes->getResourceNodeFileStream($node, $file);
            if (!\is_resource($check)) {
                throw $this->createNotFoundException('Floor plan file unavailable.');
            }

            try {
                $contents = stream_get_contents($check, 10485761);
            } finally {
                fclose($check);
            }
            if (false === $contents || !$this->svgValidator->isSafe($contents)) {
                throw $this->createNotFoundException('Floor plan file unavailable.');
            }
        }
        $stream = $this->nodes->getResourceNodeFileStream($node, $file);
        if (!\is_resource($stream)) {
            throw $this->createNotFoundException('Floor plan file unavailable.');
        }
        $response = new StreamedResponse(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        });
        $response->headers->set('Content-Type', (string) $file->getMimeType());
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Disposition', 'inline');
        if ('image/svg+xml' === $file->getMimeType()) {
            $response->headers->set('Content-Security-Policy', "sandbox; default-src 'none'; style-src 'none'");
        }

        return $response;
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * What was recorded for one date of an attendance: who was present, absent or late, for integrations.
 *
 * Read-only. The existing /attendance/{id}/date/{calendarId}/sheet route does the same for the web interface but is
 * outside the /api firewall, so an API key cannot reach it. Needs ROLE_ADMIN, since it exposes every learner's
 * presence. A learner without a recorded presence is simply absent from the list.
 */
#[IsGranted('ROLE_ADMIN')]
final class AttendanceCalendarSheetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/api/c_attendance_calendars/{id}/sheet', name: 'api_attendance_calendar_sheet', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $calendar = $this->em->getRepository(CAttendanceCalendar::class)->find($id);
        if (!$calendar instanceof CAttendanceCalendar) {
            return $this->json(['error' => 'Calendar not found'], 404);
        }

        $sheets = [];
        foreach ($this->em->getRepository(CAttendanceSheet::class)->findBy(['attendanceCalendar' => $calendar]) as $sheet) {
            if (null === $sheet->getPresence() || null === $sheet->getUser()) {
                continue;
            }
            $sheets[] = ['userId' => (int) $sheet->getUser()->getId(), 'presence' => $sheet->getPresence()];
        }

        return $this->json([
            'calendarId' => $id,
            'done' => true === (bool) $calendar->getDoneAttendance(),
            'sheets' => $sheets,
        ]);
    }
}

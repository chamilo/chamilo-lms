<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceResult;
use Chamilo\CourseBundle\Entity\CAttendanceResultComment;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\CourseBundle\Entity\CAttendanceSheetLog;
use Chamilo\CourseBundle\Repository\CAttendanceCalendarRepository;
use Chamilo\CourseBundle\Repository\CAttendanceSheetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/attendance')]
class AttendanceController extends AbstractController
{

    public function __construct(
        private readonly CAttendanceCalendarRepository $attendanceCalendarRepository,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator
    ) {}

    #[Route('/full-data', name: 'chamilo_core_attendance_get_full_data', methods: ['GET'])]
    public function getFullAttendanceData(Request $request): JsonResponse
    {
        $attendanceId = (int) $request->query->get('attendanceId', 0);

        if (!$attendanceId) {
            return $this->json(['error' => 'Attendance ID is required'], 400);
        }

        $data = $this->attendanceCalendarRepository->findAttendanceWithData($attendanceId);

        return $this->json($data, 200);
    }

    #[Route('/users/context', name: 'chamilo_core_get_users_with_faults', methods: ['GET'])]
    public function getUsersWithFaults(
        Request $request,
        UserRepository $userRepository,
        CAttendanceCalendarRepository $calendarRepository,
        CAttendanceSheetRepository $sheetRepository
    ): JsonResponse {
        $courseId = (int) $request->query->get('courseId', 0);
        $sessionId = $request->query->get('sessionId') ? (int) $request->query->get('sessionId') : null;
        $groupId = $request->query->get('groupId') ? (int) $request->query->get('groupId') : null;

        if (!$courseId) {
            return $this->json(['error' => 'Course ID is required'], 400);
        }

        try {
            $users = $userRepository->findUsersByContext($courseId, $sessionId, $groupId);

            $totalCalendars = $calendarRepository->countByAttendanceAndGroup($courseId, $groupId);

            $formattedUsers = array_map(function ($user) use ($sheetRepository, $calendarRepository, $userRepository, $courseId, $groupId, $totalCalendars) {
                $userScore = $sheetRepository->getUserScore($user->getId(), $courseId, $groupId);

                $faults = max(0, $totalCalendars - $userScore);
                $faultsPercent = $totalCalendars > 0 ? round(($faults * 100) / $totalCalendars, 0) : 0;

                return [
                    'id' => $user->getId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'photo' => $userRepository->getUserPicture($user->getId()),
                    'notAttended' => "$faults/$totalCalendars ({$faultsPercent}%)",
                ];
            }, $users);

            return $this->json($formattedUsers, 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/sheet/save', name: 'chamilo_core_attendance_sheet_save', methods: ['POST'])]
    public function saveAttendanceSheet(
        Request $request,
        UserRepository $userRepository,
        CAttendanceSheetRepository $sheetRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['attendanceData']) || empty($data['courseId'])) {
            return $this->json(['error' => 'Missing required parameters'], 400);
        }

        $attendanceData = $data['attendanceData'];
        $courseId = (int) $data['courseId'];
        $sessionId = isset($data['sessionId']) ? (int) $data['sessionId'] : null;
        $groupId = isset($data['groupId']) ? (int) $data['groupId'] : null;

        $usersInCourse = $userRepository->findUsersByContext($courseId, $sessionId, $groupId);
        $userIdsInCourse = array_map(fn(User $user) => $user->getId(), $usersInCourse);

        $affectedRows = 0;

        try {
            foreach ($attendanceData as $entry) {
                $userId = (int) $entry['userId'];
                $calendarId = (int) $entry['calendarId'];
                $presence = array_key_exists('presence', $entry) ? $entry['presence'] : null;
                $signature = $entry['signature'] ?? null;
                $comment = $entry['comment'] ?? null;

                $calendar = $this->attendanceCalendarRepository->find($calendarId);
                if (!$calendar) {
                    return $this->json(['error' => "Attendance calendar with ID $calendarId not found"], 404);
                }

                $user = $this->em->getRepository(User::class)->find($userId);
                if (!$user) {
                    continue;
                }

                $sheet = $sheetRepository->findOneBy([
                    'user' => $user,
                    'attendanceCalendar' => $calendar,
                ]);

                if ($sheet && $presence === null) {
                    $this->em->remove($sheet);
                    continue;
                }

                if (!$sheet && $presence === null) {
                    continue;
                }

                if (!$sheet) {
                    $sheet = new CAttendanceSheet();
                }

                $sheet->setUser($user)
                    ->setAttendanceCalendar($calendar)
                    ->setPresence($presence)
                    ->setSignature($signature);

                $this->em->persist($sheet);

                $this->em->flush();

                if ($comment !== null) {
                    $existingComment = $this->em->getRepository(CAttendanceResultComment::class)->findOneBy([
                        'attendanceSheetId' => $sheet->getIid(),
                        'userId' => $user->getId(),
                    ]);

                    if (!$existingComment) {
                        $existingComment = new CAttendanceResultComment();
                        $existingComment->setAttendanceSheetId($sheet->getIid());
                        $existingComment->setUserId($user->getId());
                        $existingComment->setAuthorUserId($this->getUser()->getId());
                    }

                    $existingComment->setComment($comment);
                    $existingComment->setUpdatedAt(new \DateTime());

                    $this->em->persist($existingComment);
                }
            }

            $calendarIds = array_unique(array_column($attendanceData, 'calendarId'));
            foreach ($calendarIds as $calendarId) {
                $calendar = $this->attendanceCalendarRepository->find($calendarId);
                if ($calendar && !$calendar->getDoneAttendance()) {
                    $calendar->setDoneAttendance(true);
                    $this->em->persist($calendar);
                }
            }

            $calendars = $this->attendanceCalendarRepository->findBy(['iid' => $calendarIds]);
            $attendance = $calendars[0]->getAttendance();
            $this->updateAttendanceResults($attendance);

            $lasteditType = $calendars[0]->getDoneAttendance()
                ? 'UPDATED_ATTENDANCE_LOG_TYPE'
                : 'DONE_ATTENDANCE_LOG_TYPE';

            foreach ($calendars as $calendar) {
                $this->saveAttendanceLog($attendance, $lasteditType, $calendar);
            }

            $this->em->flush();

            return $this->json([
                'message' => $this->translator->trans('Attendance data and comments saved successfully'),
                'affectedRows' => $affectedRows,
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    private function updateAttendanceResults(CAttendance $attendance): void
    {
        $sheets = $attendance->getCalendars()->map(fn ($calendar) => $calendar->getSheets())->toArray();
        $results = [];

        foreach ($sheets as $calendarSheets) {
            foreach ($calendarSheets as $sheet) {
                $userId = $sheet->getUser()->getId();
                $results[$userId] = ($results[$userId] ?? 0) + $sheet->getPresence();
            }
        }

        foreach ($results as $userId => $score) {
            $user = $this->em->getRepository(User::class)->find($userId);
            if (!$user) {
                continue;
            }

            $result = $this->em->getRepository(CAttendanceResult::class)->findOneBy([
                'user' => $user,
                'attendance' => $attendance,
            ]);

            if (!$result) {
                $result = new CAttendanceResult();
                $result->setUser($user);
                $result->setAttendance($attendance);
            }

            $result->setScore((int) $score);
            $this->em->persist($result);
        }
    }

    private function saveAttendanceLog(CAttendance $attendance, string $lasteditType, CAttendanceCalendar $calendar): void
    {
        $log = new CAttendanceSheetLog();
        $log->setAttendance($attendance)
            ->setLasteditDate(new \DateTime())
            ->setLasteditType($lasteditType)
            ->setCalendarDateValue($calendar->getDateTime())
            ->setUser($this->getUser());

        $this->em->persist($log);
    }
}

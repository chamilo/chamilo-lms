<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
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
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Exception;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
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

        if (isset($data['attendanceDates']) && \is_array($data['attendanceDates'])) {
            foreach ($data['attendanceDates'] as &$date) {
                if (!isset($date['id'])) {
                    continue;
                }

                $calendar = $this->attendanceCalendarRepository->find((int) $date['id']);
                if (!$calendar instanceof CAttendanceCalendar) {
                    continue;
                }

                $duration = $calendar->getDuration();
                if (null !== $duration) {
                    $date['duration'] = $duration;
                }
            }
            unset($date);
        }

        return $this->json($data, 200);
    }

    #[Route('/{id}/users/context', name: 'chamilo_core_get_users_with_faults', methods: ['GET'])]
    public function getUsersWithFaults(
        int $id,
        Request $request,
        UserRepository $userRepository,
        CAttendanceCalendarRepository $calendarRepository,
        CAttendanceSheetRepository $sheetRepository
    ): JsonResponse {
        $courseId = (int) $request->query->get('courseId', 0);
        $sessionId = $request->query->get('sessionId') ? (int) $request->query->get('sessionId') : null;
        $groupId = $request->query->get('groupId') ? (int) $request->query->get('groupId') : null;

        $attendance = $this->em->getRepository(CAttendance::class)->find($id);
        if (!$attendance) {
            return $this->json(['error' => 'Attendance not found'], 404);
        }

        $calendars = $attendance->getCalendars();
        $totalCalendars = \count($calendars);

        $users = $userRepository->findUsersByContext($courseId, $sessionId, $groupId);

        $formattedUsers = array_map(function ($user) use ($userRepository, $sheetRepository, $calendars, $totalCalendars) {
            $absences = 0;

            foreach ($calendars as $calendar) {
                $sheet = $sheetRepository->findOneBy([
                    'user' => $user,
                    'attendanceCalendar' => $calendar,
                ]);

                if (!$sheet || null === $sheet->getPresence()) {
                    continue;
                }

                // Only count full absences, same logic as in PDF/XLS export
                if (CAttendanceSheet::ABSENT === $sheet->getPresence()) {
                    $absences++;
                }
            }

            $percentage = $totalCalendars > 0 ? round(($absences * 100) / $totalCalendars) : 0;

            return [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'photo' => $userRepository->getUserPicture($user->getId()),
                'notAttended' => $absences.'/'.$totalCalendars." ({$percentage}%)",
            ];
        }, $users);

        return $this->json($formattedUsers, 200);
    }

    #[Route('/list_with_done_count', name: 'attendance_list_with_done_count', methods: ['GET'])]
    public function listWithDoneCount(Request $request): JsonResponse
    {
        $courseId = (int) $request->query->get('cid', 0);
        $sessionId = $request->query->get('sid') ? (int) $request->query->get('sid') : null;
        $groupId = $request->query->get('gid') ? (int) $request->query->get('gid') : null;
        $parentNode = (int) $request->query->get('resourceNode.parent', 0);

        $attendances = $this->em->getRepository(CAttendance::class)->findBy([
            'active' => 1,
        ]);

        $result = [];
        foreach ($attendances as $attendance) {
            $doneCount = $this->attendanceCalendarRepository->countDoneAttendanceByAttendanceAndGroup($attendance->getIid(), $groupId);

            $result[] = [
                'id' => $attendance->getIid(),
                'title' => $attendance->getTitle(),
                'description' => $attendance->getDescription(),
                'attendanceWeight' => $attendance->getAttendanceWeight(),
                'attendanceQualifyTitle' => $attendance->getAttendanceQualifyTitle(),
                'requireUnique' => $attendance->isRequireUnique(),
                'resourceLinkListFromEntity' => $attendance->getResourceLinkListFromEntity(),
                'doneCalendars' => $doneCount,
            ];
        }

        return $this->json($result);
    }

    #[Route('/{id}/export/pdf', name: 'attendance_export_pdf', methods: ['GET'])]
    public function exportToPdf(int $id, Request $request): Response
    {
        $courseId = (int) $request->query->get('cid');
        $sessionId = ((int) $request->query->get('sid')) ?: null;
        $groupId = ((int) $request->query->get('gid')) ?: null;

        $attendance = $this->em->getRepository(CAttendance::class)->find($id);
        if (!$attendance) {
            throw $this->createNotFoundException('Attendance not found');
        }

        $calendars = $attendance->getCalendars();
        $totalCalendars = \count($calendars);

        $students = $this->em->getRepository(User::class)->findUsersByContext($courseId, $sessionId, $groupId);
        $sheetRepo = $this->em->getRepository(CAttendanceSheet::class);

        $course = $this->em->getRepository(Course::class)->find($courseId);
        $teacher = null;

        if ($sessionId) {
            $session = $this->em->getRepository(Session::class)->find($sessionId);
            $rel = $session?->getCourseCoachesSubscriptions()
                ->filter(fn ($rel) => $rel->getCourse()?->getId() === $courseId)
                ->first()
            ;

            $teacher = $rel instanceof SessionRelCourseRelUser
                ? $rel->getUser()?->getFullName()
                : null;
        } else {
            $rel = $course?->getTeachersSubscriptions()?->first();

            $teacher = $rel instanceof CourseRelUser
                ? $rel->getUser()?->getFullName()
                : null;
        }

        // Header
        $dataTable = [];
        $header = ['#', 'Last Name', 'First Name', 'Not Attended'];
        foreach ($calendars as $calendar) {
            $header[] = $calendar->getDateTime()->format('d/m H:i');
        }
        $dataTable[] = $header;

        // Rows
        $count = 1;
        $stateLabels = CAttendanceSheet::getPresenceLabels();

        foreach ($students as $student) {
            $row = [
                $count++,
                $student->getLastname(),
                $student->getFirstname(),
                '',
            ];

            $absences = 0;
            foreach ($calendars as $calendar) {
                $sheetEntity = $sheetRepo->findOneBy([
                    'user' => $student,
                    'attendanceCalendar' => $calendar,
                ]);

                if (!$sheetEntity || null === $sheetEntity->getPresence()) {
                    $row[] = '';

                    continue;
                }

                $presence = $sheetEntity->getPresence();
                $row[] = $stateLabels[$presence] ?? 'NP';

                if (CAttendanceSheet::ABSENT === $presence) {
                    $absences++;
                }
            }

            $percentage = $totalCalendars > 0 ? round(($absences * 100) / $totalCalendars) : 0;
            $row[3] = "$absences/$totalCalendars ($percentage%)";
            $dataTable[] = $row;
        }

        // Render HTML
        $html = '
            <style>
                body { font-family: sans-serif; font-size: 12px; }
                h2 { text-align: center; margin-bottom: 5px; }
                table.meta { margin: 0 auto 10px auto; width: 80%; }
                table.meta td { padding: 2px 5px; }
                table.attendance { border-collapse: collapse; width: 100%; }
                .attendance th, .attendance td { border: 1px solid #000; padding: 4px; text-align: center; }
                .np { color: red; font-weight: bold; }
            </style>

            <h2>'.htmlspecialchars($attendance->getTitle()).'</h2>

            <table class="meta">
                <tr><td><strong>Trainer:</strong></td><td>'.htmlspecialchars($teacher ?? '-').'</td></tr>
                <tr><td><strong>Course:</strong></td><td>'.htmlspecialchars($course?->getTitleAndCode() ?? '-').'</td></tr>
                <tr><td><strong>Date:</strong></td><td>'.date('F d, Y \a\t h:i A').'</td></tr>
            </table>

            <table class="attendance">
            <tr>';
        foreach ($dataTable[0] as $cell) {
            $html .= '<th>'.htmlspecialchars((string) $cell).'</th>';
        }
        $html .= '</tr>';

        foreach (\array_slice($dataTable, 1) as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $class = 'NP' === $cell ? ' class="np"' : '';
                $html .= "<td$class>".htmlspecialchars((string) $cell).'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        try {
            $mpdf = new Mpdf([
                'orientation' => 'L',
                'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
            ]);
            $mpdf->WriteHTML($html);

            return new Response(
                $mpdf->Output('', Destination::INLINE),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="attendance-'.$id.'.pdf"',
                ]
            );
        } catch (MpdfException $e) {
            throw new RuntimeException('Failed to generate PDF: '.$e->getMessage(), 500, $e);
        }
    }

    #[Route('/{id}/export/xls', name: 'attendance_export_xls', methods: ['GET'])]
    public function exportToXls(int $id, Request $request): Response
    {
        $courseId = (int) $request->query->get('cid');
        $sessionId = $request->query->get('sid') ? (int) $request->query->get('sid') : null;
        $groupId = $request->query->get('gid') ? (int) $request->query->get('gid') : null;

        $attendance = $this->em->getRepository(CAttendance::class)->find($id);
        if (!$attendance) {
            throw $this->createNotFoundException('Attendance not found');
        }

        $calendars = $attendance->getCalendars();
        $totalCalendars = \count($calendars);
        $students = $this->em->getRepository(User::class)->findUsersByContext($courseId, $sessionId, $groupId);
        $sheetRepo = $this->em->getRepository(CAttendanceSheet::class);

        $stateLabels = CAttendanceSheet::getPresenceLabels();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance');

        // Header
        $headers = ['#', 'Last Name', 'First Name', 'Not Attended'];
        foreach ($calendars as $calendar) {
            $headers[] = $calendar->getDateTime()->format('d/m H:i');
        }
        $sheet->fromArray($headers, null, 'A1');

        // Rows
        $rowNumber = 2;
        $count = 1;
        foreach ($students as $student) {
            $row = [$count++, $student->getLastname(), $student->getFirstname()];
            $absences = 0;

            foreach ($calendars as $calendar) {
                $sheetEntity = $sheetRepo->findOneBy([
                    'user' => $student,
                    'attendanceCalendar' => $calendar,
                ]);

                if (!$sheetEntity || null === $sheetEntity->getPresence()) {
                    $row[] = '';

                    continue;
                }

                $presence = $sheetEntity->getPresence();
                $row[] = $stateLabels[$presence] ?? 'NP';

                if (CAttendanceSheet::ABSENT === $presence) {
                    $absences++;
                }
            }

            $percentage = $totalCalendars > 0 ? round(($absences * 100) / $totalCalendars) : 0;
            array_splice($row, 3, 0, "$absences/$totalCalendars ($percentage%)");

            $sheet->fromArray($row, null, 'A'.$rowNumber++);
        }

        // Output
        $writer = new Xls($spreadsheet);
        $response = new StreamedResponse(fn () => $writer->save('php://output'));
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            "attendance-$id.xls"
        );

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/{id}/qrcode', name: 'attendance_qrcode', methods: ['GET'])]
    public function generateQrCode(int $id, Request $request): Response
    {
        $attendance = $this->em->getRepository(CAttendance::class)->find($id);
        if (!$attendance) {
            throw $this->createNotFoundException('Attendance not found');
        }

        $resourceNodeId = $attendance->getResourceNode()?->getParent()?->getId();
        if (!$resourceNodeId) {
            throw new RuntimeException('Missing resourceNode for course');
        }

        $sid = $request->query->get('sid');
        $gid = $request->query->get('gid');

        $query = 'readonly=1';
        if ($sid) {
            $query .= "&sid=$sid";
        }
        if ($gid) {
            $query .= "&gid=$gid";
        }

        $url = "/resources/attendance/$resourceNodeId/$id/sheet-list?$query";
        $fullUrl = $request->getSchemeAndHttpHost().$url;

        $result = Builder::create()
            ->data($fullUrl)
            ->size(300)
            ->margin(10)
            ->build()
        ;

        return new Response($result->getString(), 200, ['Content-Type' => $result->getMimeType()]);
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
        $userIdsInCourse = array_map(fn (User $user) => $user->getId(), $usersInCourse);

        $affectedRows = 0;

        try {
            foreach ($attendanceData as $entry) {
                $userId = (int) $entry['userId'];
                $calendarId = (int) $entry['calendarId'];
                $presence = \array_key_exists('presence', $entry) ? $entry['presence'] : null;
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

                if ($sheet && null === $presence) {
                    $this->em->remove($sheet);

                    continue;
                }

                if (!$sheet && null === $presence) {
                    continue;
                }

                if (!$sheet) {
                    $sheet = new CAttendanceSheet();
                }

                $sheet->setUser($user)
                    ->setAttendanceCalendar($calendar)
                    ->setPresence($presence)
                    ->setSignature($signature)
                ;

                $this->em->persist($sheet);

                $this->em->flush();

                if (null !== $comment) {
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
                    $existingComment->setUpdatedAt(new DateTime());

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
        } catch (Exception $e) {
            return $this->json(['error' => 'An error occurred: '.$e->getMessage()], 500);
        }
    }

    #[Route('/{id}/student-dates', name: 'attendance_student_dates', methods: ['GET'])]
    public function getStudentDates(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $attendance = $this->em->getRepository(CAttendance::class)->find($id);
        if (!$attendance) {
            return $this->json(['error' => 'Attendance not found'], 404);
        }

        $dates = $attendance->getCalendars()->map(function (CAttendanceCalendar $calendar) use ($user) {
            $sheet = $calendar->getSheets()->filter(
                fn ($s) => $s->getUser()->getId() === $user->getId()
            )->first() ?: null;

            return [
                'id' => $calendar->getIid(),
                'label' => $calendar->getDateTime()->format('M d, Y - h:i A'),
                'done' => $calendar->getDoneAttendance(),
                'presence' => $sheet ? $sheet->getPresence() : null,
                'sheetId' => $sheet?->getIid(),
                'signature' => $sheet?->getSignature(),
                'duration' => $calendar->getDuration(),
            ];
        })->toArray();

        $attendanceData = [];
        $commentData = [];
        $signatureData = [];

        foreach ($dates as $item) {
            $key = $user->getId().'-'.$item['id'];
            $attendanceData[$key] = $item['presence'];
            $signatureData[$key] = $item['signature'];

            if (!empty($item['sheetId'])) {
                $comment = $this->em->getRepository(CAttendanceResultComment::class)->findOneBy([
                    'attendanceSheetId' => $item['sheetId'],
                    'userId' => $user->getId(),
                ]);
                $commentData[$key] = $comment?->getComment();
            }
        }

        return $this->json([
            'attendanceDates' => array_map(
                static fn ($d) => [
                    'id' => $d['id'],
                    'label' => $d['label'],
                    'done' => $d['done'],
                    'duration' => $d['duration'],
                ],
                $dates
            ),
            'attendanceData' => $attendanceData,
            'commentData' => $commentData,
            'signatureData' => $signatureData,
        ]);
    }

    #[Route('/{attendanceId}/date/{calendarId}/sheet', name: 'attendance_date_sheet', methods: ['GET'])]
    public function getDateSheet(
        int $attendanceId,
        int $calendarId,
        Request $request,
        UserRepository $userRepository,
        CAttendanceCalendarRepository $calendarRepo,
        CAttendanceSheetRepository $sheetRepo
    ): JsonResponse {
        $cid = (int) $request->query->get('cid', 0);
        $sid = $request->query->get('sid') ? (int) $request->query->get('sid') : null;
        $gid = $request->query->get('gid') ? (int) $request->query->get('gid') : null;

        $calendar = $calendarRepo->find($calendarId);
        if (!$calendar || $calendar->getAttendance()?->getIid() !== $attendanceId) {
            return $this->json(['error' => 'Calendar not found'], 404);
        }

        $users = $userRepository->findUsersByContext($cid, $sid, $gid);
        $presence = [];
        $comments = [];
        $signatures = [];

        foreach ($users as $u) {
            $sheet = $sheetRepo->findOneBy(['user' => $u, 'attendanceCalendar' => $calendar]);
            $k = $u->getId().'-'.$calendarId;
            if ($sheet) {
                $presence[$k] = $sheet->getPresence();
                $signatures[$k] = $sheet->getSignature();
            }
        }

        $formatted = array_map(static fn ($u) => [
            'id' => $u->getId(),
            'firstName' => $u->getFirstname(),
            'lastName' => $u->getLastname(),
            'photo' => $userRepository->getUserPicture($u->getId()),
        ], $users);

        return $this->json([
            'dateLabel' => $calendar->getDateTime()->format('M d, Y - h:i A'),
            'isLocked' => true === (bool) $calendar->getDoneAttendance(),
            'users' => $formatted,
            'presence' => $presence,
            'comments' => $comments,
            'signatures' => $signatures,
        ]);
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
            ->setLasteditDate(new DateTime())
            ->setLasteditType($lasteditType)
            ->setCalendarDateValue($calendar->getDateTime())
            ->setUser($this->getUser())
        ;

        $this->em->persist($log);
    }
}

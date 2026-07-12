<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematicAdvance;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\CourseBundle\Repository\CThematicAdvanceRepository;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProcessorInterface<CourseProgressThematicAdvance, CourseProgressThematicAdvance>
 */
final readonly class CourseProgressThematicAdvanceProcessor implements ProcessorInterface
{
    use CourseProgressAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private CThematicAdvanceRepository $thematicAdvanceRepository,
        private CAttendanceRepository $attendanceRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CourseProgressThematicAdvance {
        if (!$data instanceof CourseProgressThematicAdvance) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertCanManage($request, $course, $session);
        $this->validateCsrfToken($data->csrfToken);

        $thematicId = isset($uriVariables['thematicId'])
            ? (int) $uriVariables['thematicId']
            : $request->query->getInt('thematicId');
        if ($data->thematicId > 0 && $data->thematicId !== $thematicId) {
            throw new BadRequestHttpException('The thematic id does not match the current route.');
        }

        $thematic = $this->getEditableThematic($thematicId, $course, $session);
        $advance = null;

        if ('put_course_progress_thematic_advance' === $operation->getName()) {
            $advanceId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
            $advance = $this->getEditableAdvance($advanceId, $thematic);
        }

        if ('post_course_progress_thematic_advance' !== $operation->getName()
            && 'put_course_progress_thematic_advance' !== $operation->getName()
        ) {
            throw new BadRequestHttpException('The requested operation is not supported.');
        }

        if ($data->duration < 1 || $data->duration > 100000) {
            throw new BadRequestHttpException('The duration must be a positive number of hours.');
        }

        [$attendance, $startDate] = $this->resolveStartDate(
            $data,
            $course,
            $session,
            null !== $advance?->getIid() ? (int) $advance->getIid() : null,
        );

        $isNew = !$advance instanceof CThematicAdvance;
        if ($isNew) {
            $advance = new CThematicAdvance();
            $advance
                ->setThematic($thematic)
                ->setDoneAdvance(false)
            ;
            $thematic->getAdvances()->add($advance);
            $this->entityManager->persist($advance);
        }

        $advance
            ->setAttendance($attendance)
            ->setStartDate($startDate)
            ->setDuration($data->duration)
            ->setContent($this->sanitizeContent(trim($data->content)))
        ;

        $this->entityManager->flush();
        $this->normalizeDoneAdvances($course, $session);

        return $this->buildResponse($advance);
    }

    private function normalizeDoneAdvances(Course $course, ?Session $session): void
    {
        $orderedAdvances = [];

        foreach ($this->thematicRepository->findOrderedAdvancesForCourse($course, $session) as $advance) {
            if (!$this->thematicBelongsToExactContext($advance->getThematic(), $course, $session)) {
                continue;
            }

            $orderedAdvances[] = $advance;
        }

        $lastDoneAdvanceId = null;

        foreach ($orderedAdvances as $orderedAdvance) {
            if (true === $orderedAdvance->getDoneAdvance() && null !== $orderedAdvance->getIid()) {
                $lastDoneAdvanceId = (int) $orderedAdvance->getIid();
            }
        }

        if (null === $lastDoneAdvanceId) {
            return;
        }

        $isDone = true;

        foreach ($orderedAdvances as $orderedAdvance) {
            $orderedAdvance->setDoneAdvance($isDone);
            $this->entityManager->persist($orderedAdvance);

            if ((int) $orderedAdvance->getIid() === $lastDoneAdvanceId) {
                $isDone = false;
            }
        }

        $this->entityManager->flush();
    }

    private function assertCanManage(Request $request, Course $course, ?Session $session): void
    {
        if (!$this->isCourseProgressStudentView($request, (int) $course->getId())
            && $this->canManageCourseProgress(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
            )
        ) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage thematic advances in this context.');
    }

    private function getEditableThematic(int $thematicId, Course $course, ?Session $session): CThematic
    {
        if ($thematicId <= 0) {
            throw new BadRequestHttpException('A valid thematic id is required.');
        }

        $thematic = $this->thematicRepository->find($thematicId);
        if (!$thematic instanceof CThematic) {
            throw new NotFoundHttpException('The requested thematic was not found.');
        }

        if (!$this->thematicBelongsToExactContext($thematic, $course, $session)) {
            throw new AccessDeniedHttpException('The requested thematic does not belong to the current course context.');
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit thematic advances.');
        }

        return $thematic;
    }

    private function getEditableAdvance(int $advanceId, CThematic $thematic): CThematicAdvance
    {
        if ($advanceId <= 0) {
            throw new BadRequestHttpException('A valid thematic advance id is required.');
        }

        $advance = $this->thematicAdvanceRepository->find($advanceId);
        if (!$advance instanceof CThematicAdvance) {
            throw new NotFoundHttpException('The requested thematic advance was not found.');
        }

        if ($advance->getThematic()->getIid() !== $thematic->getIid()) {
            throw new AccessDeniedHttpException('The requested thematic advance does not belong to this thematic.');
        }

        return $advance;
    }

    /**
     * @return array{0: ?CAttendance, 1: DateTime}
     */
    private function resolveStartDate(
        CourseProgressThematicAdvance $data,
        Course $course,
        ?Session $session,
        ?int $currentAdvanceId,
    ): array {
        if ('custom' === $data->dateSource) {
            return [null, $this->parseIsoDateTime($data->startDate)];
        }

        if ('attendance' !== $data->dateSource) {
            throw new BadRequestHttpException('The start date option is invalid.');
        }

        $attendanceId = (int) ($data->attendanceId ?? 0);
        $calendarId = (int) ($data->attendanceCalendarId ?? 0);
        if ($attendanceId <= 0 || $calendarId <= 0) {
            throw new BadRequestHttpException('An attendance and one of its dates are required.');
        }

        $attendance = $this->getAllowedAttendance($attendanceId, $course, $session);
        $calendar = $this->entityManager->getRepository(CAttendanceCalendar::class)->find($calendarId);

        if (!$calendar instanceof CAttendanceCalendar
            || $calendar->getAttendance()->getIid() !== $attendance->getIid()
        ) {
            throw new AccessDeniedHttpException('The selected attendance date is invalid.');
        }

        if (!$this->isAttendanceDateAvailable($calendar, $course, $session, $currentAdvanceId)) {
            throw new ConflictHttpException('The selected attendance date is already used by another thematic advance.');
        }

        return [$attendance, DateTime::createFromInterface($calendar->getDateTime())];
    }

    private function getAllowedAttendance(
        int $attendanceId,
        Course $course,
        ?Session $session,
    ): CAttendance {
        foreach ($this->attendanceRepository->getAttendanceListForCourse($course, $session) as $attendance) {
            if ($attendance instanceof CAttendance && $attendance->getIid() === $attendanceId) {
                return $attendance;
            }
        }

        throw new AccessDeniedHttpException('The selected attendance does not belong to the current course context.');
    }

    private function isAttendanceDateAvailable(
        CAttendanceCalendar $calendar,
        Course $course,
        ?Session $session,
        ?int $currentAdvanceId,
    ): bool {
        $selectedTimestamp = $calendar->getDateTime()->getTimestamp();

        foreach ($this->thematicRepository->getThematicListForCourse($course, $session) as $thematic) {
            if (!$thematic instanceof CThematic) {
                continue;
            }

            foreach ($thematic->getAdvances() as $advance) {
                if (!$advance instanceof CThematicAdvance
                    || null === $advance->getAttendance()
                    || $advance->getIid() === $currentAdvanceId
                ) {
                    continue;
                }

                if ($advance->getStartDate()->getTimestamp() === $selectedTimestamp) {
                    return false;
                }
            }
        }

        return true;
    }

    private function parseIsoDateTime(?string $value): DateTime
    {
        if (null === $value || '' === trim($value)) {
            throw new BadRequestHttpException('The custom start date is required.');
        }

        $raw = trim($value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $raw)) {
            throw new BadRequestHttpException('The custom start date must use an ISO date-time format.');
        }

        try {
            return DateTime::createFromImmutable(new DateTimeImmutable($raw));
        } catch (Exception) {
            throw new BadRequestHttpException('The custom start date is invalid.');
        }
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(
            new CsrfToken(CourseProgressThematicAdvanceProvider::CSRF_TOKEN_ID, $token),
        )) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function sanitizeContent(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function findMatchingAttendanceCalendarId(
        CAttendance $attendance,
        DateTimeInterface $startDate,
    ): ?int {
        foreach ($attendance->getCalendars() as $calendar) {
            if (!$calendar instanceof CAttendanceCalendar || null === $calendar->getIid()) {
                continue;
            }

            if ($calendar->getDateTime()->getTimestamp() === $startDate->getTimestamp()) {
                return (int) $calendar->getIid();
            }
        }

        return null;
    }

    private function buildResponse(CThematicAdvance $advance): CourseProgressThematicAdvance
    {
        $result = new CourseProgressThematicAdvance();
        $result->iid = $advance->getIid();
        $result->thematicId = (int) $advance->getThematic()->getIid();
        $result->thematicTitle = $this->sanitizeContent($advance->getThematic()->getTitle());
        $result->dateSource = null !== $advance->getAttendance() ? 'attendance' : 'custom';
        $result->startDate = $advance->getStartDate()->format(DateTimeInterface::ATOM);
        $attendance = $advance->getAttendance();
        $result->attendanceId = null !== $attendance?->getIid() ? (int) $attendance->getIid() : null;
        $result->attendanceCalendarId = null !== $attendance
            ? $this->findMatchingAttendanceCalendarId($attendance, $advance->getStartDate())
            : null;
        $result->duration = (int) $advance->getDuration();
        $result->content = (string) $advance->getContent();
        $result->csrfToken = (string) $this->csrfTokenManager->getToken(
            CourseProgressThematicAdvanceProvider::CSRF_TOKEN_ID,
        );
        $result->canEdit = true;
        $result->isNew = false;

        return $result;
    }
}

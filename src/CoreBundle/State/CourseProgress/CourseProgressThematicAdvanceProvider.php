<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematicAdvance;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\CourseBundle\Repository\CThematicAdvanceRepository;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use IntlDateFormatter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProviderInterface<CourseProgressThematicAdvance>
 */
final readonly class CourseProgressThematicAdvanceProvider implements ProviderInterface
{
    use CourseProgressAccessHelperTrait;

    public const CSRF_TOKEN_ID = 'course_progress_thematic_advance';

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseProgressThematicAdvance
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertCanManage($request, $course, $session);

        $thematicId = isset($uriVariables['thematicId'])
            ? (int) $uriVariables['thematicId']
            : $request->query->getInt('thematicId');
        $thematic = $this->getEditableThematic($thematicId, $course, $session);
        $advanceId = isset($uriVariables['iid'])
            ? (int) $uriVariables['iid']
            : $request->query->getInt('id');
        $advance = null;

        if ($advanceId > 0) {
            $advance = $this->getEditableAdvance($advanceId, $thematic);
        }

        return $this->buildResponse($request, $course, $session, $thematic, $advance);
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
        $advance = $this->thematicAdvanceRepository->find($advanceId);
        if (!$advance instanceof CThematicAdvance) {
            throw new NotFoundHttpException('The requested thematic advance was not found.');
        }

        if ($advance->getThematic()->getIid() !== $thematic->getIid()) {
            throw new AccessDeniedHttpException('The requested thematic advance does not belong to this thematic.');
        }

        return $advance;
    }

    private function buildResponse(
        Request $request,
        Course $course,
        ?Session $session,
        CThematic $thematic,
        ?CThematicAdvance $advance,
    ): CourseProgressThematicAdvance {
        $timezone = $this->getUserTimezone();
        $dateFormatter = $this->createDateFormatter($request, $timezone);
        $attendances = $this->buildAttendanceOptions(
            $course,
            $session,
            null !== $advance?->getIid() ? (int) $advance->getIid() : null,
            $dateFormatter,
            $timezone,
        );

        $item = new CourseProgressThematicAdvance();
        $item->thematicId = (int) $thematic->getIid();
        $item->thematicTitle = $this->sanitizeHtml($thematic->getTitle());
        $item->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $item->attendances = $attendances;
        $item->canEdit = true;
        $item->isNew = !$advance instanceof CThematicAdvance;
        $item->startDate = (new DateTimeImmutable('now', $timezone))->format(DateTimeInterface::ATOM);

        if (!$advance instanceof CThematicAdvance) {
            $this->applyDefaultAttendanceSelection($item, $attendances);

            return $item;
        }

        $item->iid = $advance->getIid();
        $item->content = $this->sanitizeHtml((string) $advance->getContent());
        $item->duration = (int) $advance->getDuration();
        $item->startDate = $this->formatIsoDate($advance->getStartDate(), $timezone);
        $attendance = $advance->getAttendance();

        if (!$attendance instanceof CAttendance || null === $attendance->getIid()) {
            return $item;
        }

        $calendarId = $this->findMatchingCalendarId($attendance, $advance->getStartDate());
        if (null === $calendarId || !$this->attendanceOptionExists($attendances, (int) $attendance->getIid())) {
            return $item;
        }

        $item->dateSource = 'attendance';
        $item->attendanceId = (int) $attendance->getIid();
        $item->attendanceCalendarId = $calendarId;

        return $item;
    }

    /**
     * @param array<int, array<string, mixed>> $attendances
     */
    private function applyDefaultAttendanceSelection(
        CourseProgressThematicAdvance $item,
        array $attendances,
    ): void {
        foreach ($attendances as $attendance) {
            $dates = $attendance['dates'] ?? [];
            if (!\is_array($dates) || [] === $dates) {
                continue;
            }

            $firstDate = reset($dates);
            if (!\is_array($firstDate)) {
                continue;
            }

            $item->dateSource = 'attendance';
            $item->attendanceId = (int) ($attendance['value'] ?? 0);
            $item->attendanceCalendarId = (int) ($firstDate['value'] ?? 0);

            return;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAttendanceOptions(
        Course $course,
        ?Session $session,
        ?int $currentAdvanceId,
        IntlDateFormatter $dateFormatter,
        DateTimeZone $timezone,
    ): array {
        $usedTimestamps = $this->getUsedAttendanceTimestamps($course, $session, $currentAdvanceId);
        $options = [];

        foreach ($this->attendanceRepository->getAttendanceListForCourse($course, $session) as $attendance) {
            if (!$attendance instanceof CAttendance || null === $attendance->getIid()) {
                continue;
            }

            $calendars = $attendance->getCalendars()->toArray();
            usort(
                $calendars,
                static fn (CAttendanceCalendar $left, CAttendanceCalendar $right): int => $left->getDateTime() <=> $right->getDateTime(),
            );
            $dates = [];

            foreach ($calendars as $calendar) {
                if (!$calendar instanceof CAttendanceCalendar || null === $calendar->getIid()) {
                    continue;
                }

                $dateTime = $calendar->getDateTime();
                if (isset($usedTimestamps[$dateTime->getTimestamp()])) {
                    continue;
                }

                $dates[] = [
                    'value' => (int) $calendar->getIid(),
                    'label' => $this->formatDate($dateTime, $dateFormatter),
                    'startDate' => $this->formatIsoDate($dateTime, $timezone),
                ];
            }

            $options[] = [
                'value' => (int) $attendance->getIid(),
                'label' => trim(strip_tags($attendance->getTitle())),
                'dates' => $dates,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, true>
     */
    private function getUsedAttendanceTimestamps(
        Course $course,
        ?Session $session,
        ?int $currentAdvanceId,
    ): array {
        $used = [];

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

                $used[$advance->getStartDate()->getTimestamp()] = true;
            }
        }

        return $used;
    }

    private function findMatchingCalendarId(CAttendance $attendance, DateTimeInterface $startDate): ?int
    {
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

    /**
     * @param array<int, array<string, mixed>> $attendances
     */
    private function attendanceOptionExists(array $attendances, int $attendanceId): bool
    {
        foreach ($attendances as $attendance) {
            if ((int) ($attendance['value'] ?? 0) === $attendanceId) {
                return true;
            }
        }

        return false;
    }

    private function createDateFormatter(Request $request, DateTimeZone $timezone): IntlDateFormatter
    {
        return new IntlDateFormatter(
            $request->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            $timezone->getName(),
        );
    }

    private function getUserTimezone(): DateTimeZone
    {
        $timezoneId = date_default_timezone_get();
        $user = $this->security->getUser();

        if ($user instanceof User && method_exists($user, 'getTimezone') && $user->getTimezone()) {
            $timezoneId = (string) $user->getTimezone();
        }

        try {
            return new DateTimeZone($timezoneId);
        } catch (Exception) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }

    private function formatDate(DateTimeInterface $date, IntlDateFormatter $dateFormatter): string
    {
        $formattedDate = $dateFormatter->format($date);

        return false === $formattedDate ? $date->format('Y-m-d H:i') : $formattedDate;
    }

    private function formatIsoDate(DateTimeInterface $date, DateTimeZone $timezone): string
    {
        return DateTimeImmutable::createFromInterface($date)
            ->setTimezone($timezone)
            ->format(DateTimeInterface::ATOM)
        ;
    }

    private function sanitizeHtml(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

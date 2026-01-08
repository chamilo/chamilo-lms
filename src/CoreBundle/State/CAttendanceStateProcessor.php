<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceCalendarRelGroup;
use Chamilo\CourseBundle\Repository\CAttendanceCalendarRepository;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<void, void>
 */
final class CAttendanceStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly CAttendanceCalendarRepository $calendarRepo,
        private readonly RequestStack $requestStack
    ) {}

    /**
     * Main process function for handling attendance and calendar operations.
     *
     * @param mixed $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof CAttendance);

        $operationName = $operation->getName();

        match ($operationName) {
            'toggle_visibility' => $this->handleToggleVisibility($data),
            'soft_delete' => $this->handleSoftDelete($data),
            'calendar_add' => $this->handleAddCalendar($data),
            default => throw new BadRequestHttpException('Operation not supported.'),
        };

        $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function handleToggleVisibility(CAttendance $attendance): void
    {
        $attendanceRepo = $this->entityManager->getRepository(CAttendance::class);

        $course = $attendance->getFirstResourceLink()->getCourse();
        $attendanceRepo->toggleVisibilityPublishedDraft($attendance, $course);

        $this->entityManager->persist($attendance);
        $this->entityManager->flush();
    }

    private function handleSoftDelete(CAttendance $attendance): void
    {
        $attendance->setActive(2);
        $this->entityManager->persist($attendance);
    }

    private function handleAddCalendar(CAttendance $attendance): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('[Attendance] Request is missing.');
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            throw new BadRequestHttpException('[Attendance] Request data is required to create a calendar.');
        }

        // Required
        $startIso = $data['startDate'] ?? null;
        $startDateImmutable = $this->parseLocalIsoDateTime($startIso, 'startDate');
        $startDate = DateTime::createFromImmutable($startDateImmutable);

        // Optional
        $repeatDate = (bool) ($data['repeatDate'] ?? false);
        $repeatType = $data['repeatType'] ?? null;
        $repeatDays = isset($data['repeatDays']) ? (int) $data['repeatDays'] : null;

        $endDate = null;
        if ($repeatDate) {
            $endIso = $data['repeatEndDate'] ?? null;
            if (null === $repeatType || '' === (string) $repeatType) {
                throw new BadRequestHttpException('[Attendance] Repeat settings are incomplete: repeatType is required.');
            }
            if (null === $endIso || '' === (string) $endIso) {
                throw new BadRequestHttpException('[Attendance] Repeat settings are incomplete: repeatEndDate is required.');
            }

            $endDateImmutable = $this->parseLocalIsoDateTime($endIso, 'repeatEndDate');
            if ($endDateImmutable < $startDateImmutable) {
                throw new BadRequestHttpException('[Attendance] repeatEndDate must be greater than or equal to startDate.');
            }
            $endDate = DateTime::createFromImmutable($endDateImmutable);
        }

        $groupId = isset($data['group']) ? (int) $data['group'] : 0;
        $duration = isset($data['duration']) ? (int) $data['duration'] : null;

        // Save the first calendar
        $this->saveCalendar($attendance, $startDate, $groupId, $duration);

        // Save repetitions (never create dates beyond endDate)
        if ($repeatDate && $repeatType && $endDate) {
            $interval = $this->getRepeatInterval((string) $repeatType, $repeatDays);

            $current = $startDateImmutable;

            while (true) {
                $next = $current->add($interval);

                // stop if the next occurrence is outside the allowed range
                if ($next > $endDateImmutable) {
                    break;
                }

                $this->saveCalendar($attendance, DateTime::createFromImmutable($next), $groupId, $duration);
                $current = $next;
            }
        }
    }

    /**
     * Accept only local ISO datetime strings (no ambiguity).
     * Examples accepted:
     * - 2025-12-01T18:55
     * - 2025-12-01T18:55:00
     * - 2025-12-01T18:55:00.000Z (timezone part will be ignored safely).
     */
    private function parseLocalIsoDateTime(mixed $value, string $fieldName): DateTimeImmutable
    {
        if (!\is_string($value) || '' === trim($value)) {
            throw new BadRequestHttpException(\sprintf('[Attendance] "%s" is required and must be a string.', $fieldName));
        }

        $raw = trim($value);

        // Extract "YYYY-MM-DDTHH:mm" and optional ":ss" from the beginning, ignore trailing timezone/offset/millis
        if (!preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2})(?::(\d{2}))?/', $raw, $m)) {
            throw new BadRequestHttpException(\sprintf('[Attendance] Invalid "%s". Expected local ISO "YYYY-MM-DDTHH:mm(:ss)". Got "%s".', $fieldName, $raw));
        }

        $base = $m[1];
        $seconds = isset($m[2]) ? $m[2] : '00';
        $normalized = $base.':'.$seconds;

        $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $normalized);
        if (false === $dt) {
            throw new BadRequestHttpException(\sprintf('[Attendance] Invalid "%s". Failed to parse "%s".', $fieldName, $normalized));
        }

        return $dt;
    }

    private function saveCalendar(CAttendance $attendance, DateTime $date, ?int $groupId, ?int $duration = null): void
    {
        $existingCalendar = $this->calendarRepo->findOneBy([
            'attendance' => $attendance->getIid(),
            'dateTime' => $date,
        ]);

        if ($existingCalendar) {
            return;
        }

        $calendar = new CAttendanceCalendar();
        $calendar->setAttendance($attendance);
        $calendar->setDateTime($date);
        $calendar->setDoneAttendance(false);
        $calendar->setBlocked(false);
        $calendar->setDuration($duration);

        $this->entityManager->persist($calendar);
        $this->entityManager->flush();

        if (!empty($groupId)) {
            $this->addAttendanceCalendarToGroup($calendar, $groupId);
        }
    }

    private function addAttendanceCalendarToGroup(CAttendanceCalendar $calendar, int $groupId): void
    {
        $repository = $this->entityManager->getRepository(CAttendanceCalendarRelGroup::class);
        $repository->addGroupToCalendar($calendar->getIid(), $groupId);
    }

    private function getRepeatInterval(string $repeatType, ?int $repeatDays = null): DateInterval
    {
        if ('every-x-days' === $repeatType) {
            $days = (int) ($repeatDays ?? 0);
            if ($days < 1) {
                throw new BadRequestHttpException('[Attendance] repeatDays must be >= 1 for repeatType "every-x-days".');
            }

            return new DateInterval("P{$days}D");
        }

        return match ($repeatType) {
            'daily' => new DateInterval('P1D'),
            'weekly' => new DateInterval('P7D'),
            'bi-weekly' => new DateInterval('P14D'),
            'monthly-by-date' => new DateInterval('P1M'),
            default => throw new BadRequestHttpException(\sprintf('[Attendance] Invalid repeat type "%s".', $repeatType)),
        };
    }
}

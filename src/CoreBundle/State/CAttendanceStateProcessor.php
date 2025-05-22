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
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            throw new BadRequestHttpException('Request data is required to create a calendar.');
        }

        $startDate = new DateTime($data['startDate']);
        $repeatDate = $data['repeatDate'] ?? false;
        $repeatType = $data['repeatType'] ?? null;
        $repeatDays = $data['repeatDays'] ?? null;
        $endDate = $repeatDate ? new DateTime($data['repeatEndDate']) : null;
        $groupId = $data['group'] ?? 0;
        $duration = $data['duration'] ?? null;

        $this->saveCalendar($attendance, $startDate, $groupId, $duration);

        if ($repeatDate && $repeatType && $endDate) {
            $interval = $this->getRepeatInterval($repeatType, $repeatDays);
            $currentDate = clone $startDate;

            while ($currentDate < $endDate) {
                $currentDate->add($interval);
                $this->saveCalendar($attendance, $currentDate, $groupId, $duration);
            }
        }
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
        return match ($repeatType) {
            'daily' => new DateInterval('P1D'),
            'weekly' => new DateInterval('P7D'),
            'bi-weekly' => new DateInterval('P14D'),
            'every-x-days' => new DateInterval("P{$repeatDays}D"),
            'monthly-by-date' => new DateInterval('P1M'),
            default => throw new BadRequestHttpException('Invalid repeat type.'),
        };
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeetingCreateInput;
use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeetingUpdateInput;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

use const DATE_ATOM;
use const FILTER_VALIDATE_EMAIL;
use const PHP_SESSION_ACTIVE;

final readonly class TeamsMeetingApplicationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MicrosoftTeamsGraphClientInterface $graphClient,
        private TeamsMeetingAccessHelper $accessHelper,
        private TeamsMeetingCalendarSynchronizer $calendarSynchronizer,
        private LoggerInterface $logger,
    ) {}

    public function create(TeamsMeetingCreateInput $input): ConferenceMeeting
    {
        $scope = $this->accessHelper->normalizeScope($input->scope);
        $this->accessHelper->assertCanCreateScope($scope);

        if ($input->addToCalendar && TeamsMeetingAccessHelper::SCOPE_COURSE !== $scope) {
            throw new InvalidArgumentException('Only course Teams meetings can be added to the Chamilo agenda.');
        }

        if (!$this->graphClient->isConfigured()) {
            throw new ServiceUnavailableHttpException(null, 'Microsoft Teams integration is not configured.');
        }

        $actor = $this->accessHelper->getAuthenticatedUser();
        $accessUrl = $this->accessHelper->getCurrentAccessUrl();
        $title = $this->normalizeTitle($input->title);
        [$startAt, $endAt] = $this->normalizeCreateSchedule($input->startAt, $input->endAt);
        $organizer = $this->normalizeOrganizer($actor);

        $meeting = new ConferenceMeeting();
        $meeting
            ->setServiceProvider('teams')
            ->setTitle($title)
            ->setAccessUrl($accessUrl)
            ->setAccountEmail($organizer)
            ->setStartAt($startAt)
            ->setEndAt($endAt)
            ->setStatus(1)
            ->setVisibility(1)
            ->setRecord(false)
        ;

        if (TeamsMeetingAccessHelper::SCOPE_COURSE === $scope) {
            $context = $this->accessHelper->getCourseContext();
            $meeting
                ->setCourse($context['course'])
                ->setSession($context['session'])
                ->setGroup($context['group'])
                ->setUser($actor)
            ;
        } elseif (TeamsMeetingAccessHelper::SCOPE_PERSONAL === $scope) {
            $meeting->setUser($actor);
        } else {
            $meeting->setUser(null);
        }

        $this->releaseSessionLock();

        $remote = $this->graphClient->createOnlineMeeting(
            $organizer,
            $title,
            $startAt,
            $endAt,
        );

        $meeting
            ->setRemoteId($remote['meetingId'])
            ->setJoinUrl($remote['joinUrl'])
        ;

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->entityManager->persist($meeting);
            $this->entityManager->flush();

            if ($input->addToCalendar) {
                $this->calendarSynchronizer->createForMeeting($meeting, $actor);
                $this->entityManager->flush();
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            try {
                $this->graphClient->deleteOnlineMeeting($organizer, $remote['meetingId']);
            } catch (Throwable) {
                // Best-effort compensation only. Preserve the original persistence exception.
            }

            throw $exception;
        }

        return $meeting;
    }

    public function update(ConferenceMeeting $meeting, TeamsMeetingUpdateInput $input): ConferenceMeeting
    {
        $this->accessHelper->assertCanManageMeeting($meeting);

        if (!$this->graphClient->isConfigured()) {
            throw new ServiceUnavailableHttpException(null, 'Microsoft Teams integration is not configured.');
        }

        $this->assertMeetingCanChange($meeting);

        $title = null === $input->title ? $meeting->getTitle() : $this->normalizeTitle($input->title);
        [$startAt, $endAt] = $this->normalizeUpdateSchedule($meeting, $input->startAt, $input->endAt);
        [$organizer, $meetingId] = $this->getRemoteIdentity($meeting);

        $this->releaseSessionLock();

        $this->graphClient->updateOnlineMeeting(
            $organizer,
            $meetingId,
            $title,
            $startAt,
            $endAt,
        );

        $meeting
            ->setTitle($title)
            ->setStartAt($startAt)
            ->setEndAt($endAt)
        ;

        try {
            $this->calendarSynchronizer->updateFromMeeting($meeting);
        } catch (Throwable $exception) {
            $this->logger->warning('Could not synchronize a Teams meeting with the Chamilo agenda after update.', [
                'meetingId' => $meeting->getId(),
                'exception' => $exception,
            ]);
        }

        $this->entityManager->flush();

        return $meeting;
    }

    public function cancel(ConferenceMeeting $meeting): ConferenceMeeting
    {
        $this->accessHelper->assertCanManageMeeting($meeting);

        if (!$meeting->isOpen()) {
            return $meeting;
        }

        if (!$this->graphClient->isConfigured()) {
            throw new ServiceUnavailableHttpException(null, 'Microsoft Teams integration is not configured.');
        }

        $this->assertMeetingCanChange($meeting);
        [$organizer, $meetingId] = $this->getRemoteIdentity($meeting);

        $this->releaseSessionLock();
        $this->graphClient->deleteOnlineMeeting($organizer, $meetingId);

        $meeting
            ->setStatus(0)
            ->setClosedAt(new DateTime('now', new DateTimeZone('UTC')))
        ;

        try {
            $this->calendarSynchronizer->markCancelled($meeting);
        } catch (Throwable $exception) {
            $this->logger->warning('Could not synchronize a cancelled Teams meeting with the Chamilo agenda.', [
                'meetingId' => $meeting->getId(),
                'exception' => $exception,
            ]);
        }

        $this->entityManager->flush();

        return $meeting;
    }

    private function normalizeTitle(string $title): string
    {
        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException('A Teams meeting title is required.');
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException('The Teams meeting title is too long.');
        }

        return $title;
    }

    /**
     * @return array{0: DateTime, 1: DateTime}
     */
    private function normalizeCreateSchedule(?string $start, ?string $end): array
    {
        if (null === $start && null === $end) {
            $startAt = new DateTime('now', new DateTimeZone('UTC'));
            $endAt = (clone $startAt)->modify('+1 hour');

            return [$startAt, $endAt];
        }

        if (null === $start || null === $end) {
            throw new InvalidArgumentException('Both start and end dates are required for a scheduled Teams meeting.');
        }

        return $this->parseSchedule($start, $end);
    }

    /**
     * @return array{0: DateTime, 1: DateTime}
     */
    private function normalizeUpdateSchedule(
        ConferenceMeeting $meeting,
        ?string $start,
        ?string $end,
    ): array {
        $currentStart = $meeting->getStartAt();
        $currentEnd = $meeting->getEndAt();

        if (null === $start && null === $end) {
            if (null === $currentStart || null === $currentEnd) {
                throw new InvalidArgumentException('The Teams meeting schedule is incomplete.');
            }

            return [
                DateTime::createFromInterface($currentStart),
                DateTime::createFromInterface($currentEnd),
            ];
        }

        $startValue = $start ?? $currentStart?->format(DATE_ATOM);
        $endValue = $end ?? $currentEnd?->format(DATE_ATOM);
        if (null === $startValue || null === $endValue) {
            throw new InvalidArgumentException('Both start and end dates are required for a Teams meeting.');
        }

        return $this->parseSchedule($startValue, $endValue);
    }

    /**
     * @return array{0: DateTime, 1: DateTime}
     */
    private function parseSchedule(string $start, string $end): array
    {
        try {
            $startAt = new DateTime($start);
            $endAt = new DateTime($end);
            $utc = new DateTimeZone('UTC');
            $startAt->setTimezone($utc);
            $endAt->setTimezone($utc);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('The Teams meeting dates are invalid.', 0, $exception);
        }

        if ($endAt <= $startAt) {
            throw new InvalidArgumentException('The Teams meeting end date must be after its start date.');
        }

        if ($endAt <= new DateTime('now', new DateTimeZone('UTC'))) {
            throw new InvalidArgumentException('The Teams meeting end date must be in the future.');
        }

        return [$startAt, $endAt];
    }

    private function normalizeOrganizer(User $user): string
    {
        $email = trim($user->getEmail());
        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The current user needs a valid e-mail address to organize a Teams meeting.');
        }

        return $email;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getRemoteIdentity(ConferenceMeeting $meeting): array
    {
        $organizer = trim((string) $meeting->getAccountEmail());
        $meetingId = trim((string) $meeting->getRemoteId());

        if ('' === $organizer || '' === $meetingId) {
            throw new ConflictHttpException('The Teams meeting is missing its Microsoft remote identity.');
        }

        return [$organizer, $meetingId];
    }

    private function assertMeetingCanChange(ConferenceMeeting $meeting): void
    {
        if (!$meeting->isOpen()) {
            throw new ConflictHttpException('The Teams meeting has already been cancelled.');
        }

        $endAt = $meeting->getEndAt();
        if (null !== $endAt && $endAt < new DateTime('now', new DateTimeZone('UTC'))) {
            throw new ConflictHttpException('Past Teams meetings cannot be edited.');
        }
    }

    private function releaseSessionLock(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }
    }
}

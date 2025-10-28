<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const PHP_EOL;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Send notification messages to users that have reminders from events in their agenda.',
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly CourseRepository $courseRepository,
        private readonly TranslatorInterface $translator,
        private readonly MessageHelper $messageHelper
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode')
            ->setHelp('This command sends notifications to users who have pending reminders for calendar events.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $debug = $input->getOption('debug');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $initialSentRemindersCount = 0;
        $sentRemindersCount = 0;

        if ($debug) {
            error_log('Debug mode activated');
            $io->note('Debug mode activated');
        }

        $remindersRepo = $this->entityManager->getRepository(AgendaReminder::class);
        $reminders = $remindersRepo->findBy(['sent' => false]);

        if ($debug) {
            error_log('Total reminders fetched: '.\count($reminders));
        }

        $senderId = $this->settingsManager->getSetting('agenda.agenda_reminders_sender_id');
        $senderId = (int) $senderId ?: $this->getFirstAdminId();

        foreach ($reminders as $reminder) {
            /** @var CCalendarEvent $event */
            $event = $reminder->getEvent();

            if (null === $event) {
                if ($debug) {
                    error_log('No event found for reminder ID: '.$reminder->getId());
                }

                continue;
            }

            $eventType = $event->determineType();
            $notificationDate = clone $event->getStartDate();
            $notificationDate->sub($reminder->getDateInterval());
            if ($notificationDate > $now) {
                continue;
            }

            // NOTE: keep this call (without user) if you rely on it elsewhere; it does not change behavior.
            // We now format per-user inside sendReminderMessage().
            $eventDetails = $this->generateEventDetails($event, null);

            $initialSentRemindersCount = $sentRemindersCount;

            if ('personal' === $eventType) {
                $creator = $event->getResourceNode()->getCreator();
                if ($creator) {
                    $this->sendReminderMessage($creator, $event, $senderId, $debug, $io, $sentRemindersCount);
                }

                $resourceLinks = $event->getResourceNode()->getResourceLinks();
                if (!$resourceLinks->isEmpty()) {
                    foreach ($resourceLinks as $link) {
                        if ($user = $link->getUser()) {
                            $this->sendReminderMessage($user, $event, $senderId, $debug, $io, $sentRemindersCount);
                        }
                    }
                }
            } else {
                $resourceLink = $event->getResourceNode()->getResourceLinks()->first();
                if (!$resourceLink) {
                    if ($debug) {
                        error_log("No ResourceLink found for event ID: {$event->getIid()}");
                    }

                    continue;
                }

                switch ($eventType) {
                    case 'global':
                        foreach ($event->getResourceNode()->getResourceLinks() as $link) {
                            if ($user = $link->getUser()) {
                                $this->sendReminderMessage($user, $event, $senderId, $debug, $io, $sentRemindersCount);
                            }
                        }

                        break;

                    case 'course':
                        if ($course = $resourceLink->getCourse()) {
                            $users = $this->courseRepository->getSubscribedUsers($course)->getQuery()->getResult();
                            foreach ($users as $user) {
                                $this->sendReminderMessage($user, $event, $senderId, $debug, $io, $sentRemindersCount);
                            }
                        }

                        break;

                    case 'session':
                        if ($session = $resourceLink->getSession()) {
                            $course = $resourceLink->getCourse();
                            if (!$course) {
                                if ($debug) {
                                    error_log("No course found for resource link in session ID: {$session->getId()}");
                                }

                                break;
                            }

                            $usersToNotify = [];
                            $studentSubscriptions = $session->getSessionRelCourseRelUsersByStatus($course, Session::STUDENT);
                            foreach ($studentSubscriptions as $studentSubscription) {
                                $usersToNotify[$studentSubscription->getUser()->getId()] = $studentSubscription->getUser();
                            }

                            $coachSubscriptions = $session->getSessionRelCourseRelUsersByStatus($course, Session::COURSE_COACH);
                            foreach ($coachSubscriptions as $coachSubscription) {
                                $usersToNotify[$coachSubscription->getUser()->getId()] = $coachSubscription->getUser();
                            }

                            $generalCoaches = $session->getGeneralCoaches();
                            foreach ($generalCoaches as $generalCoach) {
                                $usersToNotify[$generalCoach->getId()] = $generalCoach;
                            }

                            foreach ($usersToNotify as $user) {
                                $this->sendReminderMessage($user, $event, $senderId, $debug, $io, $sentRemindersCount);
                            }
                        }

                        break;
                }
            }

            if ($sentRemindersCount > $initialSentRemindersCount) {
                $reminder->setSent(true);
                $this->entityManager->persist($reminder);
            }
        }

        $this->entityManager->flush();

        if ($sentRemindersCount > 0) {
            $io->success(\sprintf('%d event reminders have been sent successfully.', $sentRemindersCount));
        } else {
            $io->warning('No event reminders were sent.');
        }

        return Command::SUCCESS;
    }

    /**
     * Resolve the DateTimeZone to use for a given user (CLI-safe, no legacy api_* calls).
     * Priority: user's timezone -> platform timezone setting -> UTC.
     */
    private function resolveTimezoneForUser(?User $user): DateTimeZone
    {
        // User explicit timezone (if present and valid)
        $tzId = $user?->getTimezone();
        if (\is_string($tzId) && '' !== $tzId) {
            try {
                return new DateTimeZone($tzId);
            } catch (Throwable) {
                // keep going
            }
        }

        // Platform timezone setting (equivalent to api_get_setting('platform.timezone', false, 'timezones'))
        $platformTz = (string) ($this->settingsManager->getSetting('platform.timezone', false, 'timezones') ?? '');
        if ('' !== $platformTz) {
            try {
                return new DateTimeZone($platformTz);
            } catch (Throwable) {
                // keep going
            }
        }

        return new DateTimeZone('UTC');
    }

    /**
     * Format a UTC DateTime into the user's local timezone, CLI-safe.
     */
    private function formatForUser(DateTime $utc, ?User $user): string
    {
        $dt = (clone $utc);
        $dt->setTimezone($this->resolveTimezoneForUser($user));

        // Keep the existing format as used previously.
        return $dt->format('Y-m-d H:i:s');
    }

    private function sendReminderMessage(User $user, CCalendarEvent $event, int $senderId, bool $debug, SymfonyStyle $io, int &$sentRemindersCount): void
    {
        $locale = $user->getLocale() ?: 'en';
        $this->translator->setLocale($locale);

        $messageSubject = \sprintf(
            $this->translator->trans('Reminder for event : %s'),
            $event->getTitle()
        );

        // IMPORTANT: build details with user's timezone applied
        $messageContent = implode(PHP_EOL, $this->generateEventDetails($event, $user));

        $this->messageHelper->sendMessage(
            $user->getId(),
            $messageSubject,
            $messageContent,
            [],
            [],
            0,
            0,
            0,
            $senderId,
            0,
            false,
            true
        );

        if ($debug) {
            error_log("Message sent to user ID: {$user->getId()} for event: {$event->getTitle()}");
            error_log("Message Subject: {$messageSubject}");
            error_log("Message Content: {$messageContent}");
        }

        $sentRemindersCount++;
    }

    private function getFirstAdminId(): int
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy([]);

        return $admin && ($admin->isAdmin() || $admin->isSuperAdmin())
            ? $admin->getId()
            : 1;
    }

    /**
     * Build event details text. If $user is provided, dates are converted to user's local timezone.
     * Otherwise, keep UTC (backward compatible path).
     */
    private function generateEventDetails(CCalendarEvent $event, ?User $user = null): array
    {
        $details = [];
        $details[] = \sprintf('<p><strong>%s</strong></p>', $event->getTitle());

        if ($event->isAllDay()) {
            $details[] = \sprintf('<p class="small">%s</p>', $this->translator->trans('All day'));
        } else {
            $fromStr = $user
                ? $this->formatForUser($event->getStartDate(), $user)
                : $event->getStartDate()->format('Y-m-d H:i:s');

            $details[] = \sprintf(
                '<p class="small">%s</p>',
                $this->translator->trans('From %s', ['%s' => $fromStr])
            );

            if ($event->getEndDate()) {
                $untilStr = $user
                    ? $this->formatForUser($event->getEndDate(), $user)
                    : $event->getEndDate()->format('Y-m-d H:i:s');

                $details[] = \sprintf(
                    '<p class="small">%s</p>',
                    $this->translator->trans('Until %s', ['%s' => $untilStr])
                );
            }
        }

        if ($event->getContent()) {
            $cleanContent = strip_tags($event->getContent());
            $details[] = \sprintf('<p>%s</p>', $cleanContent);
        }

        return $details;
    }
}

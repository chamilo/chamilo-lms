<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Doctrine\ORM\EntityManagerInterface;
use MessageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTime;
use DateTimeZone;

class SendEventRemindersCommand extends Command
{
    protected static $defaultName = 'app:send-event-reminders';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send notification messages to users that have reminders from events in their agenda.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode')
            ->setHelp('This command sends notifications to users who have pending reminders for calendar events.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $debug = $input->getOption('debug');
        $now = new DateTime('now', new DateTimeZone('UTC'));

        if ($debug) {
            error_log('Debug mode activated');
            $io->note('Debug mode activated');
        }

        $remindersRepo = $this->entityManager->getRepository(AgendaReminder::class);
        $reminders = $remindersRepo->findBy(['sent' => false]);

        $senderId = $this->settingsManager->getSetting('agenda.agenda_reminders_sender_id');
        $senderId = (int) $senderId ?: $this->getFirstAdminId();

        $batchCounter = 0;
        $batchSize = 100;

        foreach ($reminders as $reminder) {
            $event = $reminder->getEvent();

            if (!$event) {
                if ($debug) {
                    error_log('No event found for reminder ID: ' . $reminder->getId());
                    $io->note('No event found for reminder ID: ' . $reminder->getId());
                }
                continue;
            }

            $eventId = $event->getIid();
            $eventEntity = $this->entityManager->getRepository(CCalendarEvent::class)->find($eventId);

            if (!$eventEntity) {
                if ($debug) {
                    error_log('No event entity found for event ID: ' . $eventId);
                    $io->note('No event entity found for event ID: ' . $eventId);
                }
                continue;
            }

            $notificationDate = clone $event->getStartDate();
            $notificationDate->sub($reminder->getDateInterval());
            if ($notificationDate > $now) {
                continue;
            }

            $messageSubject = sprintf('Reminder for event: %s', $event->getTitle());
            $messageContent = $this->generateEventDetails($event);
            $invitees = $this->getInviteesForEvent($event);

            foreach ($invitees as $userId) {
                MessageManager::send_message_simple(
                    $userId,
                    $messageSubject,
                    $messageContent,
                    $senderId
                );

                if ($debug) {
                    error_log("Message sent to user ID: $userId for event: " . $event->getTitle());
                    $io->note("Message sent to user ID: $userId for event: " . $event->getTitle());
                }
            }

            $reminder->setSent(true);
            $batchCounter++;

            if (($batchCounter % $batchSize) === 0) {
                $this->entityManager->flush();

                if ($debug) {
                    error_log('Batch of reminders flushed');
                    $io->note('Batch of reminders flushed');
                }
            }
        }

        $this->entityManager->flush();
        if ($debug) {
            error_log('Final batch of reminders flushed');
            $io->note('Final batch of reminders flushed');
        }

        $io->success('Event reminders have been sent successfully.');

        return Command::SUCCESS;
    }

    private function getFirstAdminId(): int
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy([]);
        if ($admin && ($admin->hasRole('ROLE_ADMIN') || $admin->hasRole('ROLE_SUPER_ADMIN'))) {
            return $admin->getId();
        }
        return 1;
    }

    private function generateEventDetails(CCalendarEvent $event): string
    {
        $details = [];
        $details[] = sprintf('<p><strong>%s</strong></p>', $event->getTitle());

        if ($event->isAllDay()) {
            $details[] = '<p class="small">All Day</p>';
        } else {
            $details[] = sprintf('<p class="small">From %s</p>', $event->getStartDate()->format('Y-m-d H:i:s'));
            if ($event->getEndDate()) {
                $details[] = sprintf('<p class="small">Until %s</p>', $event->getEndDate()->format('Y-m-d H:i:s'));
            }
        }

        if ($event->getContent()) {
            $details[] = $event->getContent();
        }

        return implode(PHP_EOL, $details);
    }

    private function getInviteesForEvent(CCalendarEvent $event): array
    {
        $inviteeList = [];

        foreach ($event->getResourceNode()->getResourceLinks() as $resourceLink) {
            if ($user = $resourceLink->getUser()) {
                $inviteeList[] = $user->getId();
            }
        }

        return $inviteeList;
    }
}

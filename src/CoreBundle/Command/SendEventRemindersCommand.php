<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\ServiceHelper\MessageHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

use const PHP_EOL;

class SendEventRemindersCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:send-event-reminders';

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
            ->setDescription('Send notification messages to users that have reminders from events in their agenda.')
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

            $eventDetails = $this->generateEventDetails($event);
            $messageSubject = \sprintf('Reminder for event: %s', $event->getTitle());
            $messageContent = implode(PHP_EOL, $eventDetails);

            $initialSentRemindersCount = $sentRemindersCount;

            if ('personal' === $eventType) {
                $creator = $event->getResourceNode()->getCreator();
                if ($creator) {
                    $this->messageHelper->sendMessageSimple($creator->getId(), $messageSubject, $messageContent, $senderId);
                    if ($debug) {
                        error_log("Message sent to creator ID: {$creator->getId()} for personal event: ".$event->getTitle());
                    }
                    $sentRemindersCount++;
                }

                $resourceLinks = $event->getResourceNode()->getResourceLinks();
                if (!$resourceLinks->isEmpty()) {
                    foreach ($resourceLinks as $link) {
                        if ($user = $link->getUser()) {
                            $this->messageHelper->sendMessageSimple($user->getId(), $messageSubject, $messageContent, $senderId);
                            if ($debug) {
                                error_log("Message sent to user ID: {$user->getId()} for personal event: ".$event->getTitle());
                            }
                            $sentRemindersCount++;
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
                                $this->messageHelper->sendMessageSimple($user->getId(), $messageSubject, $messageContent, $senderId);
                                if ($debug) {
                                    error_log("Message sent to user ID: {$user->getId()} for global event: ".$event->getTitle());
                                }
                                $sentRemindersCount++;
                            }
                        }

                        break;

                    case 'course':
                        if ($course = $resourceLink->getCourse()) {
                            $users = $this->courseRepository->getSubscribedUsers($course)->getQuery()->getResult();
                            foreach ($users as $user) {
                                $this->messageHelper->sendMessageSimple($user->getId(), $messageSubject, $messageContent, $senderId);
                                if ($debug) {
                                    error_log("Message sent to user ID: {$user->getId()} for course event: ".$event->getTitle());
                                }
                                $sentRemindersCount++;
                            }
                        }

                        break;

                    case 'session':
                        if ($session = $resourceLink->getSession()) {
                            foreach ($session->getUsers() as $sessionRelUser) {
                                $user = $sessionRelUser->getUser();
                                $this->messageHelper->sendMessageSimple($user->getId(), $messageSubject, $messageContent, $senderId);
                                if ($debug) {
                                    error_log("Message sent to user ID: {$user->getId()} for session event: ".$event->getTitle());
                                }
                                $sentRemindersCount++;
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

    private function getFirstAdminId(): int
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy([]);

        return $admin && ($admin->hasRole('ROLE_ADMIN') || $admin->hasRole('ROLE_SUPER_ADMIN'))
            ? $admin->getId()
            : 1;
    }

    private function generateEventDetails(CCalendarEvent $event): array
    {
        $details = [];
        $details[] = \sprintf('<p><strong>%s</strong></p>', $event->getTitle());

        if ($event->isAllDay()) {
            $details[] = \sprintf('<p class="small">%s</p>', $this->translator->trans('All Day'));
        } else {
            $details[] = \sprintf(
                '<p class="small">%s</p>',
                \sprintf($this->translator->trans('From %s'), $event->getStartDate()->format('Y-m-d H:i:s'))
            );

            if ($event->getEndDate()) {
                $details[] = \sprintf(
                    '<p class="small">%s</p>',
                    \sprintf($this->translator->trans('Until %s'), $event->getEndDate()->format('Y-m-d H:i:s'))
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

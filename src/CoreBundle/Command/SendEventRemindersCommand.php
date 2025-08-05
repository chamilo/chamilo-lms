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

            $eventDetails = $this->generateEventDetails($event);

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

    private function sendReminderMessage(User $user, CCalendarEvent $event, int $senderId, bool $debug, SymfonyStyle $io, int &$sentRemindersCount): void
    {
        $locale = $user->getLocale() ?: 'en';
        $this->translator->setLocale($locale);

        $messageSubject = $this->translator->trans('Reminder for event : %s', ['%s' => $event->getTitle()]);
        $messageContent = implode(PHP_EOL, $this->generateEventDetails($event));

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

    private function generateEventDetails(CCalendarEvent $event): array
    {
        $details = [];
        $details[] = \sprintf('<p><strong>%s</strong></p>', $event->getTitle());

        if ($event->isAllDay()) {
            $details[] = \sprintf('<p class="small">%s</p>', $this->translator->trans('All day'));
        } else {
            $details[] = \sprintf(
                '<p class="small">%s</p>',
                $this->translator->trans('From %s', ['%s' => $event->getStartDate()->format('Y-m-d H:i:s')])
            );

            if ($event->getEndDate()) {
                $details[] = \sprintf(
                    '<p class="small">%s</p>',
                    $this->translator->trans('Until %s', ['%s' => $event->getEndDate()->format('Y-m-d H:i:s')])
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

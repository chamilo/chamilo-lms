<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRelCourseRelUserRepository;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CoreBundle\ServiceHelper\MessageHelper;
use DateTime;
use DateTimeZone;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LpProgressReminderCommand extends Command
{
    protected static $defaultName = 'app:lp-progress-reminder';

    private const NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION = 3;

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseRelUserRepository $courseRelUserRepository,
        private readonly SessionRelCourseRelUserRepository $sessionRelCourseRelUserRepository,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepository,
        private readonly TrackEDefaultRepository $trackEDefaultRepository,
        private readonly UserRepository $userRepository,
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly MessageHelper $messageHelper
    ) {
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('Send LP progress reminders to users based on "number_of_days_for_completion".')
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'If set, will output detailed debug information'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debugMode = $input->getOption('debug');
        $output->writeln('Starting the LP progress reminder process...');

        // Retrieve LPs with completion days
        $lpItems = $this->extraFieldValuesRepository->getLpIdWithDaysForCompletion();
        if ($debugMode && !empty($lpItems)) {
            $output->writeln('LP Items retrieved: ' . print_r($lpItems, true));
        }

        if (empty($lpItems)) {
            $output->writeln('No learning paths with days for completion found.');
            return Command::SUCCESS;
        }

        $lpMap = [];
        foreach ($lpItems as $lpItem) {
            $lpMap[$lpItem['lp_id']] = $lpItem['ndays'];
        }
        $lpIds = array_keys($lpMap);

        // Retrieve all courses from the CourseRepository
        $courses = $this->courseRepository->findAll();
        if ($debugMode && !empty($courses)) {
            $output->writeln('Courses retrieved: ' . count($courses));
        }

        foreach ($courses as $course) {
            $courseId = $course->getId();

            // Retrieve users for the course (without session)
            $courseUsers = $this->courseRelUserRepository->getCourseUsers($courseId, $lpIds);
            // Retrieve users for the course session
            $sessionCourseUsers = $this->sessionRelCourseRelUserRepository->getSessionCourseUsers($courseId, $lpIds);

            if ($debugMode && (!empty($courseUsers) || !empty($sessionCourseUsers))) {
                $output->writeln('Processing course ID: ' . $courseId);
                if (!empty($courseUsers)) {
                    $output->writeln('Course users retrieved: ' . count($courseUsers));
                    //$output->writeln('Course retrieved: ' . print_r($courseUsers, true));
                }
                if (!empty($sessionCourseUsers)) {
                    $output->writeln('Session users retrieved: ' . count($sessionCourseUsers));
                    //$output->writeln('Session retrieved: ' . print_r($sessionCourseUsers, true));
                }
            }

            // Process users from the main course (sessionId = 0 or null)
            $this->processCourseUsers($courseUsers, $lpMap, $courseId, $debugMode);

            // Process users from the course session (sessionId > 0)
            $this->processCourseUsers($sessionCourseUsers, $lpMap, $courseId, $debugMode, true);
        }

        $output->writeln('LP progress reminder process finished.');
        return Command::SUCCESS;
    }

    /**
     * Processes users from a course or session to check if a reminder needs to be sent.
     */
    private function processCourseUsers(array $users, array $lpItems, int $courseId, bool $debugMode = false, bool $checkSession = false): void
    {
        foreach ($users as $user) {
            $userId = $user['userId'];
            $courseTitle = $user['courseTitle'];
            $lpId = $user['lpId'];
            $progress = (int) $user['progress'];

            if (!isset($lpItems[$lpId])) {
                continue;
            }

            $sessionId = $checkSession && isset($user['sessionId']) && $user['sessionId'] > 0 ? $user['sessionId'] : 0;

            $registrationDate = $this->trackEDefaultRepository->getUserCourseRegistrationAt($courseId, $userId, $sessionId);
            $nbDaysForLpCompletion = (int) $lpItems[$lpId];

            if ($registrationDate) {
                if ($debugMode) {
                    $sessionInfo = $sessionId > 0 ? "in session ID $sessionId" : "without a session";
                    echo "Registration date: {$registrationDate->format('Y-m-d H:i:s')}, Days for completion: $nbDaysForLpCompletion, LP ID: $lpId, $sessionInfo\n";
                }
                if ($this->isTimeToRemindUser($registrationDate, $nbDaysForLpCompletion)) {
                    $nbRemind = $this->getNbReminder($registrationDate, $nbDaysForLpCompletion);
                    if ($debugMode) {
                        echo "Sending reminder to user $userId for course $courseTitle (LP ID: $lpId) $sessionInfo\n";
                        $this->logReminderSent($userId, $courseTitle, $nbRemind, $debugMode, $lpId, $sessionId);
                    }
                    $this->sendLpReminder($userId, $courseTitle, $progress, $registrationDate, $nbRemind);
                }
            }
        }
    }

    /**
     * Logs the reminder details if debug mode is enabled.
     */
    private function logReminderSent(int $userId, string $courseTitle, int $nbRemind, bool $debugMode, int $lpId, int $sessionId = 0): void
    {
        if ($debugMode) {
            $sessionInfo = $sessionId > 0 ? sprintf("in session ID %d", $sessionId) : "without a session";
            echo sprintf(
                "Reminder number %d sent to user ID %d for the course %s (LP ID: %d) %s.\n",
                $nbRemind,
                $userId,
                $courseTitle,
                $lpId,
                $sessionInfo
            );
        }
    }

    /**
     * Calculates the number of reminders to be sent based on registration date and days for completion.
     */
    private function getNbReminder(DateTime $registrationDate, int $nbDaysForLpCompletion): int
    {
        $date1 = clone $registrationDate;
        $date1->modify("+$nbDaysForLpCompletion day");

        $date2 = new DateTime('now', new DateTimeZone('UTC'));

        $interval = $date1->diff($date2);
        $diffDays = (int) $interval->format('%a');

        return (int) ceil($diffDays / self::NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION) + 1;
    }

    /**
     * Checks if it is time to remind the user based on their registration date and LP completion days.
     */
    private function isTimeToRemindUser(DateTime $registrationDate, int $nbDaysForLpCompletion): bool
    {
        $date1 = clone $registrationDate;
        $date1->modify("+$nbDaysForLpCompletion day");
        $startDate = $date1->format('Y-m-d');

        $date2 = new DateTime('now', new DateTimeZone('UTC'));
        $now = $date2->format('Y-m-d');

        if ($startDate < $now) {
            $interval = $date1->diff($date2);
            $diffDays = (int) $interval->format('%a');
            return (0 === $diffDays % self::NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION);
        }

        return $startDate === $now;
    }

    /**
     * Sends a reminder email to the user regarding their LP progress.
     */
    private function sendLpReminder(int $toUserId, string $courseName, int $lpProgress, DateTime $registrationDate, int $nbRemind): bool
    {
        $user = $this->userRepository->find($toUserId);
        if (!$user) {
            throw new \Exception("User not found");
        }

        $hello = $this->translator->trans("Hello %s");
        $youAreRegCourse = $this->translator->trans("You are registered in the training %s since the %s");
        $thisMessageIsAbout = $this->translator->trans("You are receiving this message because you have completed a learning path with a %s% progress of your training.<br/>Your progress must be 100 to consider that your training was carried out.<br/>If you have the slightest problem, you should contact with your trainer.");
        $stepsToRemind = $this->translator->trans("As a reminder, to access the training platform:<br/>1. Connect to the platform at the address: %s <br/>2. Then enter: <br/>Your username: %s <br/>Your password: This was emailed to you.<br/>if you forgot it and can't find it, you can retrieve it by going to %s <br/><br/>Thank you for doing what is necessary.");
        $lpRemindFooter = $this->translator->trans("The training center<p>%s</p>Trainers:<br/>%s");

        $hello = sprintf($hello, $user->getFullName());
        $youAreRegCourse = sprintf($youAreRegCourse, $courseName, $registrationDate->format('Y-m-d'));
        $thisMessageIsAbout = sprintf($thisMessageIsAbout, $lpProgress);
        $stepsToRemind = sprintf($stepsToRemind, '', $user->getUsername(), '');
        $lpRemindFooter = sprintf($lpRemindFooter, '', 'm');

        $messageContent = $this->twig->render('@ChamiloCore/Mailer/Legacy/lp_progress_reminder_body.html.twig', [
            'HelloX' => $hello,
            'YouAreRegCourseXFromDateX' => $youAreRegCourse,
            'ThisMessageIsAboutX' => $thisMessageIsAbout,
            'StepsToRemindX' => $stepsToRemind,
            'LpRemindFooterX' => $lpRemindFooter,
        ]);

        try {
            $this->messageHelper->sendMessageSimple(
                $toUserId,
                sprintf("Reminder number %d for the course %s", $nbRemind, $courseName),
                $messageContent,
                0,
                true
            );

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Error sending reminder: ' . $e->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
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
        private CourseRepository $courseRepository,
        private CourseRelUserRepository $courseRelUserRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private TrackEDefaultRepository $trackEDefaultRepository,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private TranslatorInterface $translator
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
        if ($debugMode) {
            $output->writeln('LP Items retrieved: ' . print_r($lpItems, true));
        }

        if (empty($lpItems)) {
            $output->writeln('No learning paths with days for completion found.');
            return Command::SUCCESS;
        }

        // Retrieve all courses from the CourseRepository
        $courses = $this->courseRepository->findAll();
        if ($debugMode) {
            $output->writeln('Courses retrieved: ' . count($courses));
        }

        foreach ($courses as $course) {
            $courseId = $course->getId();

            if ($debugMode) {
                $output->writeln('Processing course ID: ' . $courseId);
            }

            // Retrieve users for the course (without session)
            $courseUsers = $this->courseRelUserRepository->getCourseUsers($courseId, array_keys($lpItems));
            // Retrieve users for the course session
            $sessionCourseUsers = $this->courseRelUserRepository->getCourseUsers($courseId, array_keys($lpItems), true);

            if ($debugMode) {
                $output->writeln('Course users retrieved: ' . count($courseUsers));
                $output->writeln('Session users retrieved: ' . count($sessionCourseUsers));
            }

            // Process users from the main course (sessionId = 0 or null)
            $this->processCourseUsers($courseUsers, $lpItems, $courseId);

            // Process users from the course session (sessionId > 0)
            $this->processCourseUsers($sessionCourseUsers, $lpItems, $courseId, true);
        }

        $output->writeln('LP progress reminder process finished.');
        return Command::SUCCESS;
    }

    /**
     * Processes users from a course or session to check if a reminder needs to be sent.
     */
    private function processCourseUsers(array $users, array $lpItems, int $courseId, bool $checkSession = false): void
    {
        foreach ($users as $user) {
            $userId = $user['userId'];
            $courseTitle = $user['courseTitle'];
            $lpId = $user['lpId'];
            $progress = (int) $user['progress'];
            $nbDaysForLpCompletion = (int) $lpItems[$lpId]['ndays'];

            if ($checkSession && isset($user['session_id']) && $user['session_id'] > 0) {
                $sessionId = $user['session_id'];
            } else {
                $sessionId = 0;
            }

            $registrationDate = $this->trackEDefaultRepository->getUserCourseRegistrationAt($courseId, $userId, $sessionId);

            if ($registrationDate && $this->isTimeToRemindUser($registrationDate, $nbDaysForLpCompletion)) {
                $nbRemind = $this->getNbReminder($registrationDate, $nbDaysForLpCompletion);
                $this->sendLpReminder($userId, $courseTitle, $progress, $registrationDate, $nbRemind);
            }
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

        $hello = $this->translator->trans('HelloX');
        $youAreRegCourse = $this->translator->trans('YouAreRegCourseXFromDateX');
        $thisMessageIsAbout = $this->translator->trans('ThisMessageIsAboutX');
        $stepsToRemind = $this->translator->trans('StepsToRemindX');
        $lpRemindFooter = $this->translator->trans('LpRemindFooterX');

        $hello = sprintf($hello, $user->getFullName());
        $youAreRegCourse = sprintf($youAreRegCourse, $courseName, $registrationDate->format('Y-m-d'));
        $thisMessageIsAbout = sprintf($thisMessageIsAbout, $lpProgress);
        $stepsToRemind = sprintf($stepsToRemind, '', $user->getUsername(), '');
        $lpRemindFooter = sprintf($lpRemindFooter, '', 'm');

        $body = $this->twig->render('@ChamiloCore/Mailer/Legacy/lp_progress_reminder_body.html.twig', [
            'HelloX' => $hello,
            'YouAreRegCourseXFromDateX' => $youAreRegCourse,
            'ThisMessageIsAboutX' => $thisMessageIsAbout,
            'StepsToRemindX' => $stepsToRemind,
            'LpRemindFooterX' => $lpRemindFooter,
        ]);

        $email = (new Email())
            ->from('noreply@yourdomain.com')
            ->to($user->getEmail())
            ->subject(sprintf("Reminder number %d for the course %s", $nbRemind, $courseName))
            ->html($body);

        try {
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Error to send email: ' . $e->getMessage());
        }
    }
}

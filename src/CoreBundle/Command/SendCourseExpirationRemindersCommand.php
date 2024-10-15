<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Chamilo\CoreBundle\Settings\SettingsManager;

class SendCourseExpirationRemindersCommand extends Command
{
    protected static $defaultName = 'app:send-course-expiration-reminders';

    public function __construct(
        private readonly Connection $connection,
        private readonly MailerInterface $mailer,
        private readonly SettingsManager $settingsManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send course expiration reminders to users.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode')
            ->setHelp('This command sends email reminders to users before their course access expires.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $debug = $input->getOption('debug');

        if ($debug) {
            $io->note('Debug mode activated');
        }

        $isActive = 'true' === $this->settingsManager->getSetting('crons.cron_remind_course_expiration_activate');
        if (!$isActive) {
            $io->warning('Course expiration reminder cron is not active.');
            return Command::SUCCESS;
        }

        $frequency = (int) $this->settingsManager->getSetting('crons.cron_remind_course_expiration_frequency');
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $expirationDate = (clone $today)->add(new DateInterval("P{$frequency}D"))->format('Y-m-d');

        $sessions = $this->getSessionsExpiringBetween($today->format('Y-m-d'), $expirationDate);

        if (empty($sessions)) {
            $io->success("No users to be reminded.");
            return Command::SUCCESS;
        }

        foreach ($sessions as $session) {
            $this->sendReminder($session, $io, $debug);
        }

        $io->success('Course expiration reminders sent successfully.');
        return Command::SUCCESS;
    }

    private function getSessionsExpiringBetween(string $today, string $expirationDate): array
    {
        $sql = "
        SELECT DISTINCT category.session_id, certificate.user_id, session.access_end_date, session.title as name
        FROM gradebook_category AS category
        LEFT JOIN gradebook_certificate AS certificate ON category.id = certificate.cat_id
        INNER JOIN session AS session ON category.session_id = session.id
        WHERE session.access_end_date BETWEEN :today AND :expirationDate
        AND category.session_id IS NOT NULL AND certificate.user_id IS NOT NULL
    ";

        return $this->connection->fetchAllAssociative($sql, [
            'today' => $today,
            'expirationDate' => $expirationDate
        ]);
    }


    private function sendReminder(array $session, SymfonyStyle $io, bool $debug): void
    {
        $userInfo = $this->getUserInfo((int) $session['user_id']);
        $userInfo['complete_name'] = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
        $remainingDays = $this->calculateRemainingDays($session['access_end_date']);

        $administrator = [
            'completeName' => $this->settingsManager->getSetting('admin.administrator_name'),
            'email' => $this->settingsManager->getSetting('admin.administrator_email'),
        ];

        $institution = $this->settingsManager->getSetting('platform.institution');
        $rootWeb = $this->settingsManager->getSetting('platform.institution_url');

        $email = (new TemplatedEmail())
            ->from($administrator['email'])
            ->to($userInfo['email'])
            ->subject('Course Expiration Reminder')
            ->htmlTemplate('@ChamiloCore/Mailer/Legacy/cron_remind_course_expiration_body.html.twig')
            ->context([
                'complete_user_name' => $userInfo['complete_name'],
                'session_name' => $session['name'],
                'session_access_end_date' => $session['access_end_date'],
                'remaining_days' => $remainingDays,
                'institution' => $institution,
                'root_web' => $rootWeb,
            ]);

        try {
            $this->mailer->send($email);

            if ($debug) {
                $io->note("Reminder sent to {$userInfo['complete_name']} ({$userInfo['email']}) for session: {$session['name']}");
            }
        } catch (TransportExceptionInterface $e) {
            $io->error("Failed to send reminder: {$e->getMessage()}");
        }
    }

    private function getUserInfo(int $userId): array
    {
        $sql = "SELECT * FROM user WHERE id = :userId";
        return $this->connection->fetchAssociative($sql, ['userId' => $userId]);
    }

    private function calculateRemainingDays(string $accessEndDate): string
    {
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $endDate = new DateTime($accessEndDate);
        return $today->diff($endDate)->format('%d');
    }
}

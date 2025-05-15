<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use UserManager;

#[AsCommand(
    name: 'app:send-course-expiration-emails',
    description: 'Send an email to users when their course is finished.',
)]
class SendCourseExpirationEmailsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode')
            ->setHelp('This command sends an email to users whose course session is expiring today.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $debug = $input->getOption('debug');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $endDate = $now->format('Y-m-d');

        if ($debug) {
            error_log('Debug mode activated');
            $io->note('Debug mode activated');
        }

        $isActive = 'true' === $this->settingsManager->getSetting('crons.cron_remind_course_expiration_activate');

        if (!$isActive) {
            if ($debug) {
                error_log('Cron job for course expiration emails is not active.');
                $io->note('Cron job for course expiration emails is not active.');
            }

            return Command::SUCCESS;
        }

        $sessionRepo = $this->entityManager->getRepository(Session::class);
        $sessions = $sessionRepo->createQueryBuilder('s')
            ->where('s.accessEndDate LIKE :date')
            ->setParameter('date', "$endDate%")
            ->getQuery()
            ->getResult()
        ;

        if (empty($sessions)) {
            $io->success("No sessions finishing today $endDate");

            return Command::SUCCESS;
        }

        $administrator = [
            'complete_name' => $this->getAdministratorName(),
            'email' => $this->settingsManager->getSetting('admin.administrator_email'),
        ];

        foreach ($sessions as $session) {
            $sessionUsers = $session->getUsers();

            if (empty($sessionUsers)) {
                $io->warning('No users to send mail for session: '.$session->getTitle());

                continue;
            }

            foreach ($sessionUsers as $sessionUser) {
                $user = $sessionUser->getUser();
                $this->sendEmailToUser($user, $session, $administrator, $io, $debug);
            }
        }

        $io->success('Emails sent successfully for sessions expiring today.');

        return Command::SUCCESS;
    }

    private function getAdministratorName(): string
    {
        return api_get_person_name(
            $this->settingsManager->getSetting('admin.administrator_name'),
            $this->settingsManager->getSetting('admin.administrator_surname'),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
    }

    private function sendEmailToUser(User $user, Session $session, array $administrator, SymfonyStyle $io, bool $debug): void
    {
        $siteName = $this->settingsManager->getSetting('platform.site_name');

        $subject = $this->twig->render('@ChamiloCore/Mailer/Legacy/cron_course_finished_subject.html.twig', [
            'session_name' => $session->getTitle(),
        ]);

        $body = $this->twig->render('@ChamiloCore/Mailer/Legacy/cron_course_finished_body.html.twig', [
            'complete_user_name' => UserManager::formatUserFullName($user),
            'session_name' => $session->getTitle(),
            'site_name' => $siteName,
        ]);

        $email = (new Email())
            ->from($administrator['email'])
            ->to($user->getEmail())
            ->subject($subject)
            ->html($body)
        ;

        $this->mailer->send($email);

        if ($debug) {
            error_log('Email sent to: '.UserManager::formatUserFullName($user)." ({$user->getEmail()})");
            $io->note('Email sent to: '.UserManager::formatUserFullName($user)." ({$user->getEmail()})");
            $io->note("Session: {$session->getTitle()}");
            $io->note("End date: {$session->getAccessEndDate()->format('Y-m-d h:i')}");
        }
    }
}

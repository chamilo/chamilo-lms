<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\FileIntegrityChecker;
use Chamilo\CoreBundle\Helpers\MailHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

use const FILTER_VALIDATE_EMAIL;

#[AsCommand(
    name: 'app:file-integrity:scan',
    description: 'Scan the installed file tree for unexpected changes and alert admins when it drifts from the baseline.',
)]
class FileIntegrityScanCommand extends Command
{
    public function __construct(
        private readonly FileIntegrityChecker $checker,
        private readonly SettingsManager $settingsManager,
        private readonly UserRepository $userRepository,
        private readonly MailHelper $mailHelper,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable verbose debug output')
            ->setHelp(
                'Intended to run from cron, e.g.:'."\n\n"
                .'    0 3 * * *  cd /var/www/chamilo && php bin/console app:file-integrity:scan'."\n\n"
                .'Recipients come from the "security.file_integrity_check_notify_admins" setting, or '
                .'every global administrator when that list is empty. During a maintenance window opened '
                .'with app:file-integrity:snooze, the current tree is silently adopted as the new baseline '
                .'instead of raising an alert.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // MailHelper::send() goes through the legacy Notification class, which reads
        // settings via the Container::getSettingsManager() static bridge. That bridge is
        // normally populated by a request listener, which never runs for console commands.
        Container::setContainer($this->getApplication()->getKernel()->getContainer());

        $io = new SymfonyStyle($input, $output);
        $debug = true === $input->getOption('debug');

        if ($this->checker->isRunInProgress()) {
            $io->note('A file integrity scan is already running; skipping this run.');

            return Command::SUCCESS;
        }

        try {
            if ($this->checker->isSnoozed()) {
                $count = $this->checker->generateBaseline();
                $io->success(\sprintf(
                    'Maintenance window active: baseline adopted (%d files), no alert sent.',
                    $count
                ));

                return Command::SUCCESS;
            }

            $report = $this->checker->scan();
        } catch (RuntimeException $e) {
            $io->note($e->getMessage());

            return Command::SUCCESS;
        }

        if ($report['establishedBaseline']) {
            $io->success(\sprintf(
                'No baseline existed yet: one was just established with %d files.',
                $report['scannedCount']
            ));

            return Command::SUCCESS;
        }

        if ($report['scanIncomplete']) {
            $io->warning('Scan stopped early: a subdirectory could not be read (permissions?). Results may be partial.');
        }

        if (!$this->checker->hasDrift($report) && !$report['gitConfigChanged']) {
            if ($debug) {
                $io->success('No file integrity drift detected.');
            }

            return Command::SUCCESS;
        }

        $sentTo = $this->notifyAdmins($report);

        $io->warning(\sprintf(
            'File integrity drift detected: %d added, %d modified, %d deleted, %d permission change(s).%s Notified %d admin(s).',
            $report['addedCount'],
            $report['modifiedCount'],
            $report['deletedCount'],
            $report['permissionsChangedCount'],
            $report['gitConfigChanged'] ? ' Git remote configuration changed!' : '',
            $sentTo
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return int the number of admins notified
     */
    private function notifyAdmins(array $report): int
    {
        $subject = $this->twig->render('@ChamiloCore/Mailer/Legacy/file_integrity_alert_subject.html.twig', [
            'platform' => (string) $this->settingsManager->getSetting('platform.site_name'),
        ]);

        $body = $this->twig->render('@ChamiloCore/Mailer/Legacy/file_integrity_alert_body.html.twig', [
            'added_count' => $report['addedCount'],
            'modified_count' => $report['modifiedCount'],
            'deleted_count' => $report['deletedCount'],
            'permissions_changed_count' => $report['permissionsChangedCount'],
            'git_config_changed' => $report['gitConfigChanged'],
            'scan_incomplete' => $report['scanIncomplete'],
            'truncated' => $report['truncated'],
            'added' => array_keys($report['added']),
            'modified' => array_keys($report['modified']),
            'deleted' => array_keys($report['deleted']),
            'permissions_changed' => array_keys($report['permissionsChanged']),
            'cef_log_url' => $this->urlGenerator->generate(
                'admin_security_file_integrity_cef_log',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);

        $from = $this->mailHelper->getPlatformFromAddress();
        $sent = 0;

        foreach ($this->resolveRecipients() as $email => $name) {
            if ($this->mailHelper->send($name, $email, $subject, $body, $from->getName(), $from->getAddress())) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return array<string, string> email => display name
     */
    private function resolveRecipients(): array
    {
        $configured = trim(
            (string) $this->settingsManager->getSetting('security.file_integrity_check_notify_admins', true)
        );

        if ('' !== $configured) {
            $recipients = [];

            foreach (preg_split('/[,\s;]+/', $configured) ?: [] as $email) {
                $email = trim($email);

                if ('' !== $email && false !== filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[$email] = $email;
                }
            }

            if ([] !== $recipients) {
                return $recipients;
            }
        }

        $recipients = [];

        foreach ($this->userRepository->findByRole('ROLE_GLOBAL_ADMIN', '') as $admin) {
            $email = (string) $admin->getEmail();

            if ('' !== $email && false !== filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[$email] = trim($admin->getFirstname().' '.$admin->getLastname());
            }
        }

        return $recipients;
    }
}

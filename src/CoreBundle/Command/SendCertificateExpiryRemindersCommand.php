<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateExpiryNotifier;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
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

#[AsCommand(
    name: 'app:send-certificate-expiry-reminders',
    description: 'Scan for expired and about-to-expire Gradebook certificates and send reminder emails.',
)]
class SendCertificateExpiryRemindersCommand extends Command
{
    private const DEFAULT_BATCH_SIZE = 200;

    /**
     * @var array<string, list<int>>
     */
    private array $subscribedUserIdsByContext = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GradebookCertificateRepository $certificateRepository,
        private readonly GradebookCertificateGenerator $certificateGenerator,
        private readonly GradebookCertificateExpiryNotifier $expiryNotifier,
        private readonly SettingsManager $settingsManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days-ahead',
                null,
                InputOption::VALUE_REQUIRED,
                'How many days ahead to scan for certificates about to expire',
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Actually send the reminders. Without this flag the command only reports what it would do.',
            )
            ->addOption(
                'resend',
                null,
                InputOption::VALUE_NONE,
                'Resend to learners who already received a reminder for the certificate\'s current expiry date',
            )
            ->addOption(
                'access-url-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Restrict the scan to courses belonging to this access URL (portal). Without it, every access '
                .'URL on this installation is scanned and each learner is mailed using their own course\'s '
                .'institution context — pass this on a multi-URL platform if reminders should only go out for '
                .'one portal\'s courses.',
            )
            ->addOption(
                'include-unsubscribed-users',
                null,
                InputOption::VALUE_NONE,
                'Also remind learners who are no longer subscribed to the certificate\'s course/session '
                .'(by default they are skipped)',
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of certificates to process before clearing the entity manager',
                self::DEFAULT_BATCH_SIZE,
            )
            ->setHelp(
                'Scans gradebook_certificate for rows whose expiry_date has passed or falls within '
                .'--days-ahead, and sends each learner an "about to expire" or "expired" reminder email plus an '
                .'in-app message. Defaults to a dry run: pass --force to actually send. Already-notified '
                .'learners are skipped unless --resend is given, where "already notified" is scoped to the '
                .'certificate\'s CURRENT expiry date — editing a certificate\'s expiry date makes it eligible '
                .'for a fresh reminder automatically.',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('true' !== $this->settingsManager->getSetting('crons.cron_certificate_expiry_reminder_activate')) {
            $io->warning('Certificate expiry reminder cron is not active.');

            return Command::SUCCESS;
        }

        $daysAheadOption = $input->getOption('days-ahead');
        $daysAhead = null !== $daysAheadOption
            ? (int) $daysAheadOption
            : (int) ($this->settingsManager->getSetting('crons.cron_certificate_expiry_reminder_days') ?: 30);
        $daysAhead = max(0, $daysAhead);

        $force = (bool) $input->getOption('force');
        $resend = (bool) $input->getOption('resend');
        $includeUnsubscribed = (bool) $input->getOption('include-unsubscribed-users');
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $accessUrlIdOption = $input->getOption('access-url-id');
        $accessUrlId = null !== $accessUrlIdOption ? (int) $accessUrlIdOption : null;

        if (!$force) {
            $io->note('Dry run: no email will be sent. Re-run with --force to send.');
        }

        $today = new DateTime('today', new DateTimeZone('UTC'));
        $horizon = (clone $today)->modify("+{$daysAhead} days");

        $query = $this->certificateRepository->createExpiringCertificatesQuery($horizon, $accessUrlId);

        $scanned = 0;
        $sent = 0;
        $skippedAlreadyNotified = 0;
        $skippedUnsubscribed = 0;
        $skippedOther = 0;

        foreach ($query->toIterable() as $certificate) {
            /** @var GradebookCertificate $certificate */
            ++$scanned;

            $category = $certificate->getCategory();
            $course = $category?->getCourse();
            if (!$category instanceof GradebookCategory || null === $course) {
                ++$skippedOther;

                continue;
            }

            if (!$includeUnsubscribed && !$this->isSubscribed($certificate->getUser(), $course, $category->getSession())) {
                ++$skippedUnsubscribed;

                continue;
            }

            if (!$force) {
                $summary = $this->certificateGenerator->normalizeCertificate($certificate, false);
                $io->text(\sprintf(
                    '[DRY-RUN] Would remind %s (certificate #%d, expires %s, status %s)',
                    $certificate->getUser()->getUsername(),
                    (int) $certificate->getId(),
                    (string) ($summary['expiryDate'] ?? ''),
                    (string) ($summary['expiryStatus'] ?? ''),
                ));

                if (0 === $scanned % $batchSize) {
                    $this->entityManager->clear();
                }

                continue;
            }

            $summary = $this->certificateGenerator->normalizeCertificate($certificate, false);
            $viewUrl = (string) ($summary['viewUrl'] ?? '');
            $certificateUrl = '' !== $viewUrl ? rtrim((string) api_get_path(WEB_PATH), '/').$viewUrl : '';

            $result = $this->expiryNotifier->notify($certificate, $certificateUrl, $resend, null);
            if ($result['sent']) {
                ++$sent;
            } elseif ('already_notified' === $result['reason']) {
                ++$skippedAlreadyNotified;
            } else {
                ++$skippedOther;
            }

            if (0 === $scanned % $batchSize) {
                $this->entityManager->clear();
                gc_collect_cycles();
            }
        }

        $this->entityManager->clear();

        $io->table(
            ['Scanned', 'Sent', 'Skipped (already notified)', 'Skipped (unsubscribed)', 'Skipped (other)'],
            [[$scanned, $sent, $skippedAlreadyNotified, $skippedUnsubscribed, $skippedOther]],
        );

        $io->success($force
            ? "Certificate expiry reminders sent: {$sent}."
            : 'Dry run complete. Re-run with --force to actually send.');

        return Command::SUCCESS;
    }

    private function isSubscribed(User $user, Course $course, ?Session $session): bool
    {
        $contextKey = $course->getId().':'.($session?->getId() ?? 0);
        if (!isset($this->subscribedUserIdsByContext[$contextKey])) {
            $this->subscribedUserIdsByContext[$contextKey] = $this->fetchSubscribedUserIds($course, $session);
        }

        return \in_array((int) $user->getId(), $this->subscribedUserIdsByContext[$contextKey], true);
    }

    /**
     * @return list<int>
     */
    private function fetchSubscribedUserIds(Course $course, ?Session $session): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        if ($session instanceof Session) {
            $qb->select('IDENTITY(rel.user) AS userId')
                ->from(SessionRelCourseRelUser::class, 'rel')
                ->andWhere('rel.course = :course')
                ->andWhere('rel.session = :session')
                ->andWhere('rel.status = :status')
                ->setParameter('course', $course)
                ->setParameter('session', $session)
                ->setParameter('status', Session::STUDENT)
            ;
        } else {
            $qb->select('IDENTITY(rel.user) AS userId')
                ->from(CourseRelUser::class, 'rel')
                ->andWhere('rel.course = :course')
                ->andWhere('rel.status = :status')
                ->setParameter('course', $course)
                ->setParameter('status', CourseRelUser::STUDENT)
            ;
        }

        return array_map('intval', array_column($qb->getQuery()->getScalarResult(), 'userId'));
    }
}

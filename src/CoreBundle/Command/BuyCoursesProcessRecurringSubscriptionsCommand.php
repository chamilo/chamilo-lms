<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Component\LegacyCliBootstrapper;
use Chamilo\CoreBundle\Service\BuyCoursesCourseDeletionService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Throwable;

use const FILTER_VALIDATE_INT;
use const PHP_BINARY;

#[AsCommand(
    name: 'chamilo:buycourses:process-recurring-subscriptions',
    description: 'Processes expired and renewed BuyCourses recurring service subscriptions.'
)]
final class BuyCoursesProcessRecurringSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly BuyCoursesCourseDeletionService $deletionService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Shows what would be processed without changing data.'
            )
            ->addOption(
                'max-deletions',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of courses to delete per run (1–25).',
                '25'
            )
            ->addOption(
                'delete-expired',
                null,
                InputOption::VALUE_NONE,
                'Explicitly enables permanent deletion of eligible, hidden subscription courses.'
            )
            ->addOption(
                'course-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Process only this already-hidden course, without updating unrelated subscriptions.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = filter_var($input->getOption('max-deletions'), FILTER_VALIDATE_INT);
        if (false === $limit || $limit < 1 || $limit > 25) {
            $output->writeln('<error>--max-deletions must be an integer between 1 and 25.</error>');

            return Command::INVALID;
        }

        $courseId = null;
        if (null !== $input->getOption('course-id')) {
            $courseId = filter_var($input->getOption('course-id'), FILTER_VALIDATE_INT);
            if (false === $courseId || $courseId < 1) {
                $output->writeln('<error>--course-id must be a positive integer.</error>');

                return Command::INVALID;
            }

            if (!(bool) $input->getOption('dry-run') && !(bool) $input->getOption('delete-expired')) {
                $output->writeln('<error>--course-id requires --dry-run or --delete-expired.</error>');

                return Command::INVALID;
            }
        }

        // Use a separate DB connection so the lock survives legacy bootstrap and subprocess execution.
        $lockName = 'bc_lifecycle_'.substr(hash('sha256', $this->kernel->getProjectDir()), 0, 40);
        $lockConnection = null;
        $locked = false;

        try {
            $lockConnection = DriverManager::getConnection($this->entityManager->getConnection()->getParams());
            $locked = '1' === (string) $lockConnection->fetchOne('SELECT GET_LOCK(?, 0)', [$lockName]);

            if (!$locked) {
                $output->writeln('<error>Another BuyCourses lifecycle run is already active.</error>');

                return Command::FAILURE;
            }

            return $this->runLifecycle($input, $output, $limit, $courseId);
        } catch (Throwable $exception) {
            $output->writeln('<error>Unable to run BuyCourses lifecycle with a database lock.</error>');

            return Command::FAILURE;
        } finally {
            if (null !== $lockConnection) {
                if ($locked) {
                    try {
                        $lockConnection->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]);
                    } catch (Throwable $exception) {
                        $output->writeln('<comment>Could not explicitly release the lifecycle lock; closing its connection.</comment>');
                    }
                }

                $lockConnection->close();
            }
        }
    }

    private function runLifecycle(InputInterface $input, OutputInterface $output, int $limit, ?int $courseId): int
    {
        if (null !== $courseId) {
            // Manual deletion must not process unrelated expired service sales first.
            return $this->runDeletion($input, $output, $limit, $courseId);
        }

        $projectDir = $this->kernel->getProjectDir();
        $scriptPath = $projectDir.'/public/plugin/BuyCourses/src/process_recurring_subscriptions.php';

        if (!is_file($scriptPath)) {
            $output->writeln('<error>BuyCourses recurring subscriptions script was not found.</error>');
            $output->writeln('<comment>Expected path: '.$scriptPath.'</comment>');

            return Command::FAILURE;
        }

        $command = [PHP_BINARY, $scriptPath];

        if ((bool) $input->getOption('dry-run')) {
            $command[] = '--dry-run';
        }

        $process = new Process($command, $projectDir, ['APP_ENV' => $this->kernel->getEnvironment()]);
        $process->setTimeout(null);

        $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Recurring subscription processing failed; deletion was skipped.</error>');

            return Command::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $allowDeletion = (bool) $input->getOption('delete-expired');

        if (!$dryRun && !$allowDeletion) {
            $output->writeln('<comment>Course deletion is disabled. Use --dry-run to preview or --delete-expired to opt in.</comment>');

            return Command::SUCCESS;
        }

        return $this->runDeletion($input, $output, $limit);
    }

    private function runDeletion(InputInterface $input, OutputInterface $output, int $limit, ?int $courseId = null): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $allowDeletion = (bool) $input->getOption('delete-expired');

        try {
            // CourseHelper and plugin deletion subscribers also rely on legacy services.
            // Use the existing CLI bootstrapper to bind them to this command's EntityManager.
            LegacyCliBootstrapper::bootstrap($this->kernel, $this->entityManager);
            $result = $this->deletionService->process($dryRun, $allowDeletion, $limit, $courseId);

            foreach ($result['messages'] as $message) {
                $output->writeln($message);
            }

            if (null !== $courseId && 0 === $result['eligible']) {
                $output->writeln('<error>The selected course is not eligible for deletion.</error>');

                return Command::FAILURE;
            }

            $output->writeln(\sprintf(
                'BuyCourses course cleanup: eligible=%d deleted=%d errors=%d dry_run=%s',
                $result['eligible'],
                $result['deleted'],
                $result['errors'],
                $dryRun ? 'true' : 'false'
            ));

            if (null !== $courseId && $allowDeletion && !$dryRun && 1 !== $result['deleted']) {
                $output->writeln('<error>The selected course was not deleted; review its current eligibility.</error>');

                return Command::FAILURE;
            }

            return 0 === $result['errors'] ? Command::SUCCESS : Command::FAILURE;
        } catch (Throwable $exception) {
            $output->writeln('<error>BuyCourses course cleanup failed: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}

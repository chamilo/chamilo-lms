<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

use const PHP_BINARY;

#[AsCommand(
    name: 'chamilo:buycourses:process-recurring-subscriptions',
    description: 'Processes expired and renewed BuyCourses recurring service subscriptions.'
)]
final class BuyCoursesProcessRecurringSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly KernelInterface $kernel,
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

        $process = new Process($command, $projectDir);
        $process->setTimeout(null);

        $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });

        return $process->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}

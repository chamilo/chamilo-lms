<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Helpers\BuyCoursesPurchaseReminderHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use const FILTER_VALIDATE_INT;

#[AsCommand(
    name: 'buycourses:send-purchase-reminders',
    description: 'Send BuyCourses reminders for pending, unconfirmed purchase attempts.',
)]
class BuyCoursesSendPurchaseRemindersCommand extends Command
{
    public function __construct(
        private readonly BuyCoursesPurchaseReminderHelper $reminderHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of reminders to send in this run (1–'.BuyCoursesPurchaseReminderHelper::MAX_BATCH_SIZE.').',
                (string) BuyCoursesPurchaseReminderHelper::DEFAULT_BATCH_SIZE
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'List what would be sent without writing coupons, mail or sent-log rows.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);
        if (
            false === $limit
            || $limit < 1
            || $limit > BuyCoursesPurchaseReminderHelper::MAX_BATCH_SIZE
        ) {
            $output->writeln(
                '<error>--limit must be an integer between 1 and '.BuyCoursesPurchaseReminderHelper::MAX_BATCH_SIZE.'.</error>'
            );

            return Command::INVALID;
        }

        try {
            $result = $this->reminderHelper->processReminders($limit, (bool) $input->getOption('dry-run'));
        } catch (Throwable $exception) {
            $output->writeln(
                \sprintf('<error>BuyCourses purchase reminders failed: %s</error>', $exception->getMessage())
            );

            return Command::FAILURE;
        }

        $output->writeln(
            \sprintf(
                '<info>BuyCourses purchase reminders %s. Sent: %d. Skipped: %d.</info>',
                $result['dry_run'] ? 'dry-run completed' : 'completed',
                $result['sent'],
                $result['skipped']
            )
        );

        return Command::SUCCESS;
    }
}

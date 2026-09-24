<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Message\MessageInboundMailboxService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use const FILTER_VALIDATE_INT;

#[AsCommand(
    name: 'chamilo:mail:fetch-inbound',
    description: 'Collect unread replies from the configured inbound IMAP mailbox.'
)]
final class FetchInboundMailCommand extends Command
{
    public function __construct(
        private readonly MessageInboundMailboxService $mailboxService,
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
                'Maximum number of unread mailbox messages to process in one run.',
                '20'
            )
            ->addOption(
                'mark-failed-seen',
                null,
                InputOption::VALUE_NONE,
                'Keep failed messages marked as seen instead of restoring their unread flag.'
            )
            ->setHelp(
                <<<'HELP'
Collects unread e-mails from mail.inbound_mail_dsn and forwards each RFC822 message to the existing Chamilo inbound-mail processor.

Example cron entry (every 5 minutes):
  */5 * * * * cd /path/to/chamilo && php bin/console chamilo:mail:fetch-inbound --no-interaction

By default, a message that fails processing is restored to unread so it can be retried. Use --mark-failed-seen only when the mailbox should not retry rejected messages.
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);
        if (false === $limit) {
            $io->error('The --limit option must be an integer.');

            return Command::INVALID;
        }

        try {
            $result = $this->mailboxService->fetch(
                $limit,
                (bool) $input->getOption('mark-failed-seen')
            );
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if ($result['skipped']) {
            $io->note('Inbound mail is disabled. Nothing to collect.');

            return Command::SUCCESS;
        }

        if ($result['message_ids']) {
            $io->writeln('Chamilo message IDs: '.implode(', ', $result['message_ids']));
        }

        if ($result['errors']) {
            foreach ($result['errors'] as $index => $error) {
                $io->warning(\sprintf('Mailbox message %d: %s', $index, $error));
            }
        }

        if ($result['failed'] > 0) {
            $io->error(
                \sprintf(
                    'Inbound mailbox run completed with %d processed and %d failed message(s).',
                    $result['processed'],
                    $result['failed']
                )
            );

            return Command::FAILURE;
        }

        $io->success(\sprintf('Processed %d inbound mailbox message(s).', $result['processed']));

        return Command::SUCCESS;
    }
}

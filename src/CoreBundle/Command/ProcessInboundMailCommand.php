<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Message\MessageInboundMailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use const STDIN;

#[AsCommand(
    name: 'chamilo:mail:process-inbound',
    description: 'Process an inbound RFC822 e-mail as a reply to a Chamilo message.'
)]
final class ProcessInboundMailCommand extends Command
{
    public function __construct(
        private readonly MessageInboundMailService $inboundMailService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'Read the raw RFC822 e-mail from this file instead of STDIN.'
            )
            ->addOption(
                'recipient',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional SMTP envelope recipient. Useful when the To header was rewritten.'
            )
            ->setHelp(
                <<<'HELP'
Reads one raw RFC822 e-mail from STDIN (or --file) and creates a reply in Chamilo Messages.

Example:
  cat reply.eml | php bin/console chamilo:mail:process-inbound

An MTA can pipe a routed replies+TOKEN@example.org address to this command.
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getOption('file');

        if (\is_string($file) && '' !== trim($file)) {
            if (!is_file($file) || !is_readable($file)) {
                $io->error('Inbound e-mail file is not readable.');

                return Command::INVALID;
            }

            $rawEmail = file_get_contents($file);
        } else {
            $rawEmail = stream_get_contents(STDIN);
        }

        if (false === $rawEmail || '' === trim($rawEmail)) {
            $io->error('No inbound e-mail content was provided.');

            return Command::INVALID;
        }

        $recipient = $input->getOption('recipient');
        $recipient = \is_string($recipient) && '' !== trim($recipient) ? trim($recipient) : null;

        try {
            $message = $this->inboundMailService->processRawEmail($rawEmail, $recipient);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf('Inbound e-mail processed as Chamilo message #%d.', (int) $message->getId()));

        return Command::SUCCESS;
    }
}

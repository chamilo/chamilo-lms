<?php

namespace ChamiloLMS\Command\Transaction;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ChamiloLMS\Transaction\TransactionLogController;

class ReceiveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tx:receive')
            ->setDescription('Runs local branch associated receive plugin processing envelope reception.')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'The maximum number of envelopes to receive in this operation.', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tc = new TransactionLogController();
        $limit = (int) $input->getOption('limit');
        if ($limit <= 0) {
            $limit = 1;
        }
        $envelopes = $tc->receiveEnvelopeData($limit);
        $output->writeln(sprintf('Correctly received (%d) envelopes now added to the queue.', count($envelopes)));
    }
}

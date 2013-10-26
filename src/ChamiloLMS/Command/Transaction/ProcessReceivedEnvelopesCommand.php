<?php

namespace ChamiloLMS\Command\Transaction;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ChamiloLMS\Transaction\TransactionLogController;

class ProcessReceivedEnvelopesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tx:process-received-envelopes')
            ->setDescription('Process already received envelopes to extract its transactions to the transactions table.')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'The maximum number of received envelopes to process in this operation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tc = new TransactionLogController();
        $limit = (int) $input->getOption('limit');
        if ($limit <= 0) {
            $limit = 10;
        }
        $imported_transaction_ids_by_envelope = $tc->importPendingEnvelopes($limit);
        $total_transactions = 0;
        foreach ($imported_transaction_ids_by_envelope as $envelope_id => $imported_transaction_ids) {
            $total_transactions += count($imported_transaction_ids);
        }
        $output->writeln(sprintf('Imported correctly (%d) transactions from (%d) envelopes to the transactions table.', $total_transactions, count($imported_transaction_ids_by_envelope)));
    }
}

<?php

namespace ChamiloLMS\Command\Transaction;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ChamiloLMS\Transaction\TransactionLogController;

class ImportToSystemCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tx:import-to-system')
            ->setDescription('Imports transactions on the transaction table to the local system.')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'The maximum number of transactions to import into the system in this operation.', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tc = new TransactionLogController();
        $limit = (int) $input->getOption('limit');
        if ($limit <= 0) {
            $limit = 10;
        }
        $imported_ids = $tc->importPendingToSystem($limit);
        $output->writeln(sprintf('Imported correctly (%d) transactions to the system.', count($imported_ids['success'])));
        if (!empty($imported_ids['fail'])) {
            $output->writeln(sprintf('The transactions identified by the following ids failed to be imported: (%s)', implode(', ', $imported_ids['fail'])));
            return 1;
        }
    }
}

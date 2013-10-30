<?php

namespace ChamiloLMS\Command\Transaction;

use Database;
use ChamiloLMS\Transaction\TransactionLog;
use ChamiloLMS\Transaction\TransactionLogController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;

/**
 * Shows statistics related with transactions.
 */
class StatsCommand extends Command
{
    /**
     * The transaction controller.
     *
     * @var TransactionLogController
     */
    protected $controller = null;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->controller = new TransactionLogController();
    }

    protected function configure()
    {
        $this
            ->setName('tx:stats')
            ->setDescription('Shows some statistics about transactions related information.')
            ->addOption('transactions', null, InputOption::VALUE_OPTIONAL, 'Include transactions statistics.', true)
            ->addOption('blobs', null, InputOption::VALUE_OPTIONAL, 'Include received blobs statistics.', true);
    }

    /**
     * Shows transaction statistics per status.
     */
    protected function showTransactionStats(OutputInterface $output)
    {
        $table_helper = new TableHelper();
        $results = $this->controller->getTransactionCounterPerStatus();
        $status_names = TransactionLog::getStatusNames();
        $table_helper->setHeaders(array('Transaction status', '# transactions'));
        foreach ($results as $status_id => $counter) {
            $table_helper->addRow(array($status_names[$status_id], $counter));
        }
        $table_helper->render($output);
    }

    /**
     * Shows transaction statistics per status.
     */
    protected function showReceivedEnvelopeStats(OutputInterface $output)
    {
        $table_helper = new TableHelper();
        $results = $this->controller->getReceivedEnvelopesCounterPerStatus();
        $status_names = TransactionLog::getStatusNames();
        $table_helper->setHeaders(array('Receive envelope status', '# envelopes'));
        foreach ($results as $status_id => $counter) {
            $table_helper->addRow(array($status_names[$status_id], $counter));
        }
        $table_helper->render($output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $with_transactions = (bool) $input->getOption('transactions');
        $with_blobs = (bool) $input->getOption('blobs');
        if ($with_transactions) {
            $this->showTransactionStats($output);
        }
        if ($with_blobs) {
            $this->showReceivedEnvelopeStats($output);
        }
    }
}

<?php

namespace ChamiloLMS\Command\Transaction;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ChamiloLMS\Transaction\Envelope;
use ChamiloLMS\Transaction\TransactionLogController;
use ChamiloLMS\Transaction\TransactionLog;

/**
 * Selects a subset of transactions, wraps and send them.
 *
 * It will use local branch settings.
 *
 * @fixme Let choose the transactions subset.
 */
class SendCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tx:send')
            ->setDescription('Selects a subset of transactions, wraps and send them using local branch configuration.');
            //->addOption('temp-folder', null, InputOption::VALUE_OPTIONAL, 'The temp folder.', '/tmp');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @todo Is there a clean way/no-global to access app from here?
        global $app;
        $local_branch = $app['orm.em']->getRepository('Entity\BranchSync')->getLocalBranch();
        $tc = new TransactionLogController();
        // @fixme Do not hardcode conditions.
        $conditions = array(
            'c_id' => 1,
            'session_id' => 0,
            'branch_id' => $local_branch->getId(),
            'status_id' => TransactionLog::STATUS_LOCAL,
        );
        $transactions = $tc->load($conditions);
        if (empty($transactions)) {
            $output->writeln('No transactions to be exported');
            return;
        }

        // Export.
        $export_result = $tc->exportTransactions($transactions);
        if (!empty($export_result['fail'])) {
            $fail_data = print_r($export_result['fail'], 1);
            $output->writeln('Failed exporting some transactions:');
            $output->writeln("$fail_data");
            return;
        }

        // Create an evelope and wrap it.
        $wrapper = TransactionLogController::createPlugin('wrapper', $local_branch->getPluginEnvelope(), $local_branch->getPluginData('wrapper'));
        $envelope_data = array('transactions' => $transactions, 'origin_branch_id' => $local_branch->getId());
        $envelope = new Envelope($wrapper, $envelope_data);
        try {
            $envelope->wrap();
        }
        catch (Exception $e) {
            $output->writeln(sprintf('Failed wrapping the envelope: %s.', $e->getMessage()));
            return;
        }

        // Finally send it.
        $success = $tc->sendEnvelope($envelope);
        if ($success !== TRUE) {
            $output->writeln('There was a problem while sending the envelope.');
            return;
        }
        $output->writeln('Envelope sent!');
    }
}

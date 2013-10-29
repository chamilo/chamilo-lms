<?php

namespace ChamiloLMS\Command\Transaction;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ChamiloLMS\Transaction\Envelope;
use ChamiloLMS\Transaction\TransactionLogController;
use ChamiloLMS\Transaction\TransactionLog;

/**
 * Selects a subset of transactions, wraps and send them.
 *
 * It will use local branch settings.
 */
class SendCommand extends Command
{
    protected $local_branch = null;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        // @todo Is there a clean way/no-global to access app from here?
        global $app;
        $this->local_branch = $app['orm.em']->getRepository('Entity\BranchSync')->getLocalBranch();
    }

    /**
     * @todo Let accept multiple values on options. Notice Database::select()
     * does not allow multiple values.
     */
    protected function configure()
    {
        $this
            ->setName('tx:send')
            ->setDescription('Selects a subset of transactions, wraps and send them using local branch configuration.')
            ->addOption('course', null, InputOption::VALUE_OPTIONAL, 'A course ID to select transactions.', '')
            ->addOption('session', null, InputOption::VALUE_OPTIONAL, 'A session ID to select transactions.', '');
    }

    /**
     * Convert input options into TransactionLogController::load() conditions.
     *
     * @todo Validate arguments?
     */
    protected function getSelectConditions(InputInterface $input)
    {
        $conditions = array(
            'branch_id' => $this->local_branch->getId(),
            'status_id' => TransactionLog::STATUS_LOCAL,
        );
        // Course.
        $course_string = $input->getOption('course');
        if (strlen($course_string) > 0) {
            $conditions['c_id'] = $course_string;
        }
        // Session.
        $session_string = $input->getOption('session');
        if (strlen($session_string) > 0) {
            $conditions['session_id'] = $session_string;
        }
        return $conditions;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tc = new TransactionLogController();
        $transactions = $tc->load($this->getSelectConditions($input));
        if (empty($transactions)) {
            $output->writeln('No transactions to be exported');
            return 1;
        }

        // Export.
        $export_result = $tc->exportTransactions($transactions);
        if (!empty($export_result['fail'])) {
            $fail_data = print_r($export_result['fail'], 1);
            $output->writeln('Failed exporting some transactions:');
            $output->writeln("$fail_data");
            return 2;
        }

        // Create an evelope and wrap it.
        $wrapper = TransactionLogController::createPlugin('wrapper', $this->local_branch->getPluginEnvelope(), $this->local_branch->getPluginData('wrapper'));
        $envelope_data = array('transactions' => $transactions, 'origin_branch_id' => $this->local_branch->getId());
        $envelope = new Envelope($wrapper, $envelope_data);
        try {
            $envelope->wrap();
        }
        catch (Exception $e) {
            $output->writeln(sprintf('Failed wrapping the envelope: %s.', $e->getMessage()));
            return 3;
        }

        // Finally send it.
        $success = $tc->sendEnvelope($envelope);
        if ($success !== TRUE) {
            $output->writeln('There was a problem while sending the envelope.');
            return 4;
        }
        $output->writeln('Envelope sent!');
    }
}

<?php

namespace ChamiloLMS\Command\Transaction;

use Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A customization of tx:send for specific case.
 *
 * Assumtions:
 * - branch_rel_session table only contains the local branch entries.
 * - branch_rel_session.display_order represents a "turn".
 */
class MineduSendCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('minedu:send')
            ->setDescription('Sends data using tx:send, using custom minedu logic to convert turn numbers into a course/session pair.')
            ->addArgument('turn', InputArgument::REQUIRED, 'The turn to be used.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $turn = $input->getArgument('turn');
        $branch_rel_session_table = Database::get_main_table(TABLE_BRANCH_REL_SESSION);
        $results = Database::select('session_id', $branch_rel_session_table, array('where'=> array('display_order = ?' => array($turn))));
        if (empty($results)) {
            $output->writeln(sprintf('Failed to retrive a session id for the given turn "%s".', $turn));
            return 100;
        }
        $row = array_shift($results);
        $command = $this->getApplication()->find('tx:send');
        $arguments = array(
            'command' => 'tx:send',
            '--session'  => $row['session_id'],
        );
        $input = new ArrayInput($arguments);
        $return_code = $command->run($input, $output);
        if ($return_code !== 0) {
            $output->writeln('Failed trying to send the turn information.');
            return $return_code;
        }
    }
}

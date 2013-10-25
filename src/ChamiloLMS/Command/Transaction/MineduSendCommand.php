<?php

namespace ChamiloLMS\Command\Transaction;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Selects a subset of transactions, wraps and send them.
 *
 * It will use local branch settings.
 */
class MineduSendCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('minedu:send')
            ->setDescription('Send mineduSelects a subset of transactions, wraps and send them using local branch configuration.')
            ->addArgument('turn', InputArgument::REQUIRED, 'The turn to be used.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $turn = $input->getArgument('turn');
        $command = $this->getApplication()->find('tx:send');
        $arguments = array(
            'command' => 'tx:send',
            '--course'  => '',
            '--session'  => '',
        );
        $input = new ArrayInput($arguments);
        $return_code = $command->run($input, $output);
        if ($return_code !== 0) {
            $output->writeln('Failed trying to send the turn information.');
            return $return_code;
        }
    }
}

<?php

namespace ChamiloLMS\Command\Template;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;

class AsseticDumpCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('assetic:dump')
            ->setDescription('Dumps all assets to the filesystem');
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        global $app;

        if (!$app['assetic.enabled']) {
            return false;
        }

        $themes = array('chamilo', 'public_admin');

        foreach ($themes as $theme) {
            $app['app.theme'] = $theme;
            $dumper = $app['assetic.dumper'];

            if (isset($app['twig'])) {
                $dumper->addTwigAssets();
            }
            $dumper->dumpAssets();
        }

        $output->writeln('<info>Dump finished</info>');
    }
}
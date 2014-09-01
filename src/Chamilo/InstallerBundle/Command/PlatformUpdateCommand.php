<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\InstallerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Chamilo\InstallerBundle\CommandExecutor;

/**
 * Class PlatformUpdateCommand
 * * Based in OroInstallBundlePlatformUpdateCommand
 * @package Chamilo\InstallerBundle\Command
 */
class PlatformUpdateCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('chamilo:platform:update')
            ->setDescription('Execute platform application update commands and init platform assets.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forces operation to be executed.'
            )
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Timeout for child command execution', 300);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');

        if ($force) {
            $commandExecutor = new CommandExecutor(
                $input->hasOption('env') ? $input->getOption('env') : null,
                $output,
                $this->getApplication()
                //$this->getContainer()->get('oro_cache.oro_data_cache_manager')
            );
            $commandExecutor->setDefaultTimeout($input->getOption('timeout'));

            $commandExecutor
                ->runCommand('oro:migration:load', array('--process-isolation' => true, '--force' => true))
                //->runCommand('oro:workflow:definitions:load', array('--process-isolation' => true))
                //->runCommand('oro:process:configuration:load', array('--process-isolation' => true))
                ->runCommand('oro:migration:data:load', array('--process-isolation' => true))
                //->runCommand('oro:navigation:init', array('--process-isolation' => true))
              //  ->runCommand('assets:install')
              //  ->runCommand('assetic:dump')
            ;
                //->runCommand('fos:js-routing:dump', array('--target' => 'web/js/routes.js'))
                //->runCommand('oro:localization:dump')
                //->runCommand('oro:translation:dump')
                //->runCommand('oro:requirejs:build', array('--ignore-errors' => true));
        } else {
            $output->writeln(
                '<comment>ATTENTION</comment>: Database backup is highly recommended before executing this command.'
            );
            $output->writeln(
                '           Please make sure that application cache is up-to-date before run this command.'
            );
            $output->writeln('           Use <info>cache:clear</info> if needed.');
            $output->writeln('');
            $output->writeln('To force execution run command with <info>--force</info> option:');
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));
        }
    }
}

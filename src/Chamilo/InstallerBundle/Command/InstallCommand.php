<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\InstallerBundle\Command;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Chamilo\InstallerBundle\CommandExecutor;
use Chamilo\InstallerBundle\ScriptExecutor;

/**
 * Class InstallCommand
 * Based in OroInstallBundle
 * @package Chamilo\InstallerBundle\Command
 */
class InstallCommand extends ContainerAwareCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('chamilo:install')
            ->setDescription('Chamilo installer.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force installation')
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Timeout for child command execution', 300)
            ->addOption(
                'drop-database',
                null,
                InputOption::VALUE_NONE,
                'Database will be dropped and all data will be deleted.'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $forceInstall = $input->getOption('force');

        $commandExecutor = new CommandExecutor(
            $input->hasOption('env') ? $input->getOption('env') : null,
            $output,
            $this->getApplication()
            //$this->getContainer()->get('oro_cache.oro_data_cache_manager')
        );

        $commandExecutor->setDefaultTimeout($input->getOption('timeout'));

        // if there is application is not installed or no --force option
        $isInstalled = $this->getContainer()->hasParameter('installed')
            && $this->getContainer()->getParameter('installed');

        if ($isInstalled && !$forceInstall) {
            $output->writeln('<comment>ATTENTION</comment>: Chamilo is already installed.');
            $output->writeln(
                'To proceed with install - run command with <info>--force</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));
            $output->writeln(
                'To reinstall over existing database - run command with <info>--force --drop-database</info> options:'
            );
            $output->writeln(sprintf('    <info>%s --force --drop-database</info>', $this->getName()));
            $output->writeln(
                '<comment>ATTENTION</comment>: All data will be lost. ' .
                'Database backup is highly recommended before executing this command.'
            );
            $output->writeln('');

            return;
        }

        if ($forceInstall) {
            // if --force option we have to clear cache and set installed to false
            $this->updateInstalledFlag(false);
            $commandExecutor->runCommand(
                'cache:clear',
                array(
                    '--no-optional-warmers' => true,
                    '--process-isolation' => true
                )
            );
        }

        $output->writeln('<info>Installing Chamilo.</info>');
        $output->writeln('');

        $this
            ->checkStep($input, $output)
            ->setupStep($commandExecutor, $input, $output)
            ->finalStep($commandExecutor, $input, $output);

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>Chamilo has been successfully installed in <comment>%s</comment> mode.</info>',
                $input->getOption('env')
            )
        );
        if ('prod' != $input->getOption('env')) {
            $output->writeln(
                '<info>To run application in <comment>prod</comment> mode, ' .
                'please run <comment>cache:clear</comment> command with <comment>--env prod</comment> parameter</info>'
            );
        }
    }

    /**
     * @param string $command
     * @param OutputInterface $output
     * @param array $arguments
     * @return $this
     * @throws \Exception
     */
    protected function runCommand($command, OutputInterface $output, $arguments = array())
    {
        $arguments['command'] = $command;
        $input = new ArrayInput($arguments);

        $this
            ->getApplication()
            ->find($command)
            ->run($input, $output)
        ;

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    protected function checkStep(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Checking system requirements.</info>');

        require_once $this->getContainer()->getParameter('kernel.root_dir')
            . DIRECTORY_SEPARATOR
            . 'ChamiloRequirements.php';

        $collection = new \ChamiloRequirements();

        $this->renderTable($collection->getMandatoryRequirements(), 'Mandatory requirements', $output);
        $this->renderTable($collection->getPhpIniRequirements(), 'PHP settings', $output);
        $this->renderTable($collection->getChamiloRequirements(), 'Chamilo specific requirements', $output);
        $this->renderTable($collection->getRecommendations(), 'Optional recommendations', $output);

        if (count($collection->getFailedRequirements())) {
            throw new \RuntimeException(
                'Some system requirements are not fulfilled. Please check output messages and fix them.'
            );
        }

        $output->writeln('');

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    protected function setupStep(CommandExecutor $commandExecutor, InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Setting up database.</info>');

        /** @var DialogHelper $dialog */
        $dialog  = $this->getHelperSet()->get('dialog');
        $options = $input->getOptions();

        $input->setInteractive(false);

        $schemaDropOptions = array(
            '--force' => true,
            '--process-isolation' => true,
        );

        if ($input->getOption('drop-database')) {
            $schemaDropOptions['--full-database'] = true;
        }

        $commandExecutor
            ->runCommand(
                'doctrine:schema:drop',
                $schemaDropOptions
            )
            //->runCommand('oro:entity-config:cache:clear', array('--no-warmup' => true))
            //->runCommand('oro:entity-extend:cache:clear', array('--no-warmup' => true))
            ->runCommand(
                'oro:migration:load',
                array(
                    '--force' => true,
                    '--process-isolation' => true,
                )
            )
            /*->runCommand(
                'oro:workflow:definitions:load',
                array(
                    '--process-isolation' => true,
                )
            )*/
            /*->runCommand(
                'oro:process:configuration:load',
                array(
                    '--process-isolation' => true
                )
            )*/
            ->runCommand(
                'oro:migration:data:load',
                array(
                    '--process-isolation' => true,
                    '--no-interaction' => true,
                )
            );
        //if ($this->getHelperSet()->get('dialog')->askConfirmation($output, '<question>Load fixtures (Y/N)?</question>', false)) {
        $this->setupFixtures($input, $output);

        // Installing platform settings
        $settingsManager = $this->getContainer()->get('chamilo.settings.manager');
        $schemas = $settingsManager->getSchemas();
        $schemas = array_keys($schemas);
        /**
         * @var string $key
         * @var \Sylius\Bundle\SettingsBundle\Schema\SchemaInterface $schema
         */
        foreach ($schemas as $schema) {
            $settings = $settingsManager->loadSettings($schema);
            $settingsManager->saveSettings($schema, $settings);
        }

        $output->writeln('');
        $output->writeln('<info>Administration setup.</info>');

        $this->setupAdmin($output);

        $this->runCommand('sonata:page:update-core-routes', $output, array('--site' => 'all'));
        $this->runCommand('sonata:page:create-snapshots', $output, array('--site' => 'all'));

        $output->writeln('');

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function setupDatabase(InputInterface $input, OutputInterface $output)
    {
        $this
            ->runCommand('doctrine:database:create', $input, $output)
            ->runCommand('doctrine:schema:create', $input, $output)
            //->runCommand('doctrine:phpcr:repository:init', $input, $output)
            //->runCommand('assets:install', $input, $output)
            //->runCommand('assetic:dump', $input, $output)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function setupFixtures(InputInterface $input, OutputInterface $output)
    {
        $this
            ->runCommand(
                'doctrine:fixtures:load',
                $output,
                array('--no-interaction' => true)
            )
            //->runCommand('doctrine:phpcr:fixtures:load', $input, $output)
        ;
    }

    /**
     * @param OutputInterface $output
     */
    protected function setupAdmin(OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        //$user = new \Chamilo\UserBundle\Entity\User();
        $em = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getManager();
        /** @var \Chamilo\UserBundle\Entity\User $user */
        $user = $em->getRepository('ChamiloUserBundle:User')->findOneById(1);

        $user->setUsername($dialog->ask($output, '<question>Username</question>(admin):', 'admin'));
        $user->setPlainPassword($dialog->ask($output, '<question>Password</question>(admin):', 'admin'));

        $user->setFirstname($dialog->ask($output, '<question>Firstname</question>(Jane):', 'Jane'));
        $user->setLastname($dialog->ask($output, '<question>Lastname</question>(Doe):', 'Doe'));
        $user->setEmail($dialog->ask($output, '<question>Email</question>(admin@example.org):', 'admin@example.org'));
        $user->setEnabled(true);
        $user->addRole('ROLE_SUPER_ADMIN');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($user);
        $em->flush();
    }

    /**
     * @param CommandExecutor $commandExecutor
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return InstallCommand
     */
    protected function finalStep(CommandExecutor $commandExecutor, InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Preparing application.</info>');

        $input->setInteractive(false);

        $commandExecutor
            /*->runCommand(
                'oro:navigation:init',
                array(
                    '--process-isolation' => true,
                )
            )
            ->runCommand(
                'fos:js-routing:dump',
                array(
                    '--target' => 'web/js/routes.js',
                    '--process-isolation' => true,
                )
            )
            ->runCommand('oro:localization:dump')
            */
            ->runCommand('assets:install')
            ->runCommand(
                'assetic:dump',
                array(
                    '--process-isolation' => true,
                )
            )
            /*->runCommand(
                'oro:translation:dump',
                array(
                    '--process-isolation' => true,
                )
            )
            ->runCommand(
                'oro:requirejs:build',
                array(
                    '--ignore-errors' => true,
                    '--process-isolation' => true,
                )
            )*/
            ;

        // run installer scripts
        $this->processInstallerScripts($output, $commandExecutor);

        $this->updateInstalledFlag(date('c'));

        // clear the cache set installed flag in DI container
        $commandExecutor->runCommand(
            'cache:clear',
            array(
                '--process-isolation' => true,
            )
        );

        $output->writeln('');

        return $this;
    }

    /**
     * Process installer scripts
     *
     * @param OutputInterface $output
     * @param CommandExecutor $commandExecutor
     */
    protected function processInstallerScripts(OutputInterface $output, CommandExecutor $commandExecutor)
    {
        $scriptExecutor = new ScriptExecutor($output, $this->getContainer(), $commandExecutor);
        /** @var ScriptManager $scriptManager */
        $scriptManager = $this->getContainer()->get('chamilo_installer.script_manager');
        $scriptFiles   = $scriptManager->getScriptFiles();
        if (!empty($scriptFiles)) {
            foreach ($scriptFiles as $scriptFile) {
                $scriptExecutor->runScript($scriptFile);
            }
        }
    }

    /**
     * Update installed flag in parameters.yml
     *
     * @param bool|string $installed
     */
    protected function updateInstalledFlag($installed)
    {
        $dumper = $this->getContainer()->get('chamilo_installer.yaml_persister');
        $params = $dumper->parse();
        $params['system']['installed'] = $installed;
        $dumper->dump($params);
    }

    /**
     * Render requirements table
     *
     * @param array           $collection
     * @param string          $header
     * @param OutputInterface $output
     */
    protected function renderTable(array $collection, $header, OutputInterface $output)
    {
        /** @var TableHelper $table */
        $table = $this->getHelperSet()->get('table');

        $table
            ->setHeaders(array('Check  ', $header))
            ->setRows(array());

        /** @var \Requirement $requirement */
        foreach ($collection as $requirement) {
            if ($requirement->isFulfilled()) {
                $table->addRow(array('OK', $requirement->getTestMessage()));
            } else {
                $table->addRow(
                    array(
                        $requirement->isOptional() ? 'WARNING' : 'ERROR',
                        $requirement->getHelpText()
                    )
                );
            }
        }

        $table->render($output);
    }

}

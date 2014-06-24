<?php

namespace ChamiloLMS\InstallerBundle\Command;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class InstallCommand
 * @package ChamiloLMS\InstallerBundle\Command
 */
class InstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:install')
            ->setDescription('Chamilo installer.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Installing Chamilo.</info>');
        $output->writeln('');

        $this
            ->checkStep($input, $output)
            ->setupStep($input, $output)
        ;

        $output->writeln('<info>Chamilo has been successfully installed.</info>');
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

        $fulfilled = true;

        // $rootDir = $this->getContainer()->get('kernel')->getRealRootDir();

        $this->runCommand('doctrine:database:drop', $output, array('--force' => true));

        // Make sure we close the original connection because it lost the reference to the database
        $connection = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getConnection();

        if ($connection->isConnected()) {
            $connection->close();
        }

        $this->runCommand('cache:warmup', $output, array('--env' => 'prod', '--no-debug' => true));

        $this
            ->runCommand('doctrine:database:create', $output)
            ->runCommand('doctrine:schema:create', $output)
            //->runCommand('doctrine:phpcr:repository:init', $input, $output)
            //->runCommand('chash:chamilo_install', $inputToInstall, $output)
            ->runCommand('assets:install', $output)

            //->runCommand('assetic:dump', $input, $output)
        ;

        if (!$fulfilled) {
            throw new RuntimeException('Some system requirements are not fulfilled. Please check output messages and fix them.');
        }

        $output->writeln('');

        return $this;
    }

    protected function setupStep(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Setting up database.</info>');

        //$this->setupDatabase($input, $output);

        //if ($this->getHelperSet()->get('dialog')->askConfirmation($output, '<question>Load fixtures (Y/N)?</question>', false)) {
        $this->setupFixtures($input, $output);
        //}

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
            ->runCommand('assets:install', $input, $output)
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
        //$user = new \Application\Sonata\UserBundle\Entity\User();
        $em = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getManager();
        /** @var \Application\Sonata\UserBundle\Entity\User $user */
        $user = $em->getRepository('ApplicationSonataUserBundle:User')->findOneById(1);

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

}

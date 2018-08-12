<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TwoStepVerificationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sonata:user:two-step-verification');
        $this->addArgument('username', InputArgument::REQUIRED, 'The username to protect with a two step verification process');
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Reset the current two step verification token');
        $this->setDescription('Generate a two step verification process to secure an access (Ideal for super admin protection)');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getContainer()->has('sonata.user.google.authenticator.provider')) {
            throw new \RuntimeException('Two Step Verification process is not enabled');
        }

        $helper = $this->getContainer()->get('sonata.user.google.authenticator.provider');
        $manager = $this->getContainer()->get('fos_user.user_manager');

        $user = $manager->findUserByUsernameOrEmail($input->getArgument('username'));

        if (!$user) {
            throw new \RuntimeException(sprintf('Unable to find the username : %s', $input->getArgument('username')));
        }

        if (!$user->getTwoStepVerificationCode() || $input->getOption('reset')) {
            $user->setTwoStepVerificationCode($helper->generateSecret());
            $manager->updateUser($user);
        }

        $output->writeln([
            sprintf('<info>Username</info> : %s', $input->getArgument('username')),
            sprintf('<info>Secret</info> : %s', $user->getTwoStepVerificationCode()),
            sprintf('<info>Url</info> : %s', $helper->getUrl($user)),
        ]);
    }
}

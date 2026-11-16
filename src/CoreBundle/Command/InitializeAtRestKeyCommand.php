<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Security\AtRestCrypto;
use Chamilo\CoreBundle\Service\Security\AtRestKeyVerifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'chamilo:security:initialize-at-rest-key',
    description: 'Provision the private data-at-rest encryption key without replacing existing keys.',
)]
final class InitializeAtRestKeyCommand extends Command
{
    public function __construct(
        private readonly AtRestCrypto $crypto,
        private readonly AtRestKeyVerifier $keyVerifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Create a new key only after safety checks.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if ($this->crypto->hasKey()) {
                $this->keyVerifier->assertKeyMatchesStoredUserData();
                $output->writeln(
                    '<info>Existing key is readable and matches encrypted user field samples. No changes made.</info>'
                );

                return Command::SUCCESS;
            }

            // Fail closed: never silently replace a lost key for existing encrypted records.
            if ($this->keyVerifier->hasEncryptedUserData()) {
                $output->writeln(
                    '<error>Encrypted user data exists but its key is missing. Restore the original key.</error>'
                );

                return Command::FAILURE;
            }

            if (!$input->getOption('apply')) {
                $output->writeln(
                    '<comment>Dry run: no encrypted user fields exist. Add --apply to create the private key.</comment>'
                );

                return Command::SUCCESS;
            }

            $this->crypto->initializeKey();
            $output->writeln(
                '<info>Encryption key initialized. Back up var/data-at-rest/master.key securely, separately from the database.</info>'
            );

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $output->writeln(
                '<error>Encryption key initialization failed. Check key storage and database availability.</error>'
            );

            return Command::FAILURE;
        }
    }
}

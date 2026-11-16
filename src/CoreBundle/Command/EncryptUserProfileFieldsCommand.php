<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Security\AtRestCrypto;
use Chamilo\CoreBundle\Service\Security\UserAtRestEncryptionPolicy;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsCommand(
    name: 'chamilo:security:encrypt-user-profile-fields',
    description: 'Backfill user phone or address data in small batches (dry run by default).',
)]
final class EncryptUserProfileFieldsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AtRestCrypto $crypto,
        private readonly UserAtRestEncryptionPolicy $encryptionPolicy,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('field', null, InputOption::VALUE_REQUIRED, 'Required: phone or address.')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'Restrict to a QA user.')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Rows per batch (1-500).', '100')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Write encrypted values (otherwise dry run).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $field = (string) $input->getOption('field');
        if (!\in_array($field, ['phone', 'address'], true)) {
            $output->writeln('<error>--field must be phone or address.</error>');

            return Command::FAILURE;
        }
        $userOption = $input->getOption('user-id');
        if (null !== $userOption && (!ctype_digit((string) $userOption) || (int) $userOption < 1)) {
            $output->writeln('<error>--user-id must be a positive integer.</error>');

            return Command::FAILURE;
        }
        $batchOption = (string) $input->getOption('batch-size');
        if (!ctype_digit($batchOption) || (int) $batchOption < 1 || (int) $batchOption > 500) {
            $output->writeln('<error>--batch-size must be between 1 and 500.</error>');

            return Command::FAILURE;
        }
        $batchSize = (int) $batchOption;
        $userId = null === $userOption ? null : (int) $userOption;
        $apply = (bool) $input->getOption('apply');

        try {
            // Field comes exclusively from the allowlist above, never raw user-supplied SQL.
            $predicate = $field.' IS NOT NULL AND '.$field." <> '' AND BINARY ".$field.' NOT LIKE BINARY :prefix';
            $params = ['prefix' => 'chamilo-encrypted:%'];
            if (null !== $userId) {
                $predicate .= ' AND id = :userId';
                $params['userId'] = $userId;
            }
            $pending = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user WHERE '.$predicate, $params);
            $already = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM user WHERE BINARY '.$field.' LIKE BINARY :prefix'
                .(null === $userId ? '' : ' AND id = :userId'),
                null === $userId
                    ? ['prefix' => 'chamilo-encrypted:%']
                    : ['prefix' => 'chamilo-encrypted:%', 'userId' => $userId],
            );
            $output->writeln(\sprintf('Field: %s; pending: %d; encrypted: %d.', $field, $pending, $already));

            if (!$apply) {
                $output->writeln('<comment>Dry run: no records were changed.</comment>');

                return Command::SUCCESS;
            }
            if (!$this->encryptionPolicy->isEnabled($field)) {
                $output->writeln('<error>Select the field in Administration > Settings > Security before applying.</error>');

                return Command::FAILURE;
            }

            $this->encryptionPolicy->assertFieldsReady([$field]);

            $lastId = 0;
            $encrypted = 0;
            $conflicts = 0;
            do {
                $rows = $this->connection->fetchAllAssociative(
                    'SELECT id, uuid, '.$field.' AS original_value FROM user WHERE '.$predicate
                    .' AND id > :lastId ORDER BY id ASC LIMIT '.$batchSize,
                    [...$params, 'lastId' => $lastId],
                );
                foreach ($rows as $row) {
                    $lastId = (int) $row['id'];
                    $rawUuid = (string) $row['uuid'];
                    $uuid = 16 === \strlen($rawUuid)
                        ? (string) Uuid::fromBinary($rawUuid)
                        : (string) Uuid::fromString($rawUuid);
                    $original = (string) $row['original_value'];
                    $ciphertext = $this->crypto->encrypt($original, 'user.'.$field.':'.$uuid);
                    $affected = $this->connection->executeStatement(
                        'UPDATE user SET '.$field.' = :ciphertext'
                        .' WHERE id = :userId AND BINARY '.$field.' = BINARY :original',
                        ['ciphertext' => $ciphertext, 'userId' => $lastId, 'original' => $original]
                    );
                    if (1 === $affected) {
                        ++$encrypted;
                    } else {
                        ++$conflicts;
                    }
                }
            } while ([] !== $rows);

            $output->writeln(\sprintf('<info>Encrypted: %d; concurrent changes skipped: %d.</info>', $encrypted, $conflicts));

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $output->writeln('<error>Encryption stopped. Verify field schema, key and stored UUIDs without logging personal data.</error>');

            return Command::FAILURE;
        }
    }
}

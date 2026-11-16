<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Security\AtRestCrypto;
use Chamilo\CoreBundle\Service\Security\UserAtRestEncryptionPolicy;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsCommand(
    name: 'chamilo:security:encrypt-user-biographies',
    description: 'Backfill encrypted user biographies in small batches (dry run by default).',
)]
final class EncryptUserBiographiesCommand extends Command
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
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Encrypt pending biographies. Default is read-only.')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'Limit to one user ID (pilot testing).')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Records per batch (1-500).', '100')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = (bool) $input->getOption('apply');
        $userOption = $input->getOption('user-id');
        $userId = null;
        if (null !== $userOption) {
            if (!ctype_digit((string) $userOption) || (int) $userOption < 1) {
                $output->writeln('<error>--user-id must be a positive integer.</error>');

                return Command::FAILURE;
            }
            $userId = (int) $userOption;
        }

        $batchOption = (string) $input->getOption('batch-size');
        if (!ctype_digit($batchOption) || (int) $batchOption < 1 || (int) $batchOption > 500) {
            $output->writeln('<error>--batch-size must be between 1 and 500.</error>');

            return Command::FAILURE;
        }
        $batchSize = (int) $batchOption;

        try {
            $predicate = "biography IS NOT NULL AND biography <> '' AND BINARY biography NOT LIKE BINARY :prefix";
            $params = ['prefix' => 'chamilo-encrypted:%'];
            if (null !== $userId) {
                $predicate .= ' AND id = :userId';
                $params['userId'] = $userId;
            }

            $pending = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user WHERE '.$predicate, $params);
            $alreadyEncrypted = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM user WHERE BINARY biography LIKE BINARY :prefix'.(null === $userId ? '' : ' AND id = :userId'),
                null === $userId
                    ? ['prefix' => 'chamilo-encrypted:%']
                    : ['prefix' => 'chamilo-encrypted:%', 'userId' => $userId]
            );
            $output->writeln(\sprintf('Pending: %d; encrypted/versioned values: %d.', $pending, $alreadyEncrypted));

            if (!$apply) {
                $output->writeln('<comment>Dry run: no records were changed.</comment>');

                return Command::SUCCESS;
            }
            if (0 === $pending) {
                $output->writeln('<info>Nothing to encrypt; no records were changed.</info>');

                return Command::SUCCESS;
            }
            if (!$this->encryptionPolicy->isEnabled('biography')) {
                $output->writeln(
                    '<error>Select Biography in Administration > Settings > Security before applying.</error>'
                );

                return Command::FAILURE;
            }

            $this->encryptionPolicy->assertFieldsReady(['biography']);

            $lastId = 0;
            $updated = 0;
            $skipped = 0;
            do {
                $sql = 'SELECT id, uuid, biography FROM user WHERE '.$predicate
                    .' AND id > :lastId ORDER BY id ASC LIMIT '.$batchSize;
                $rows = $this->connection->fetchAllAssociative($sql, [...$params, 'lastId' => $lastId]);
                foreach ($rows as $row) {
                    $id = (int) $row['id'];
                    $lastId = $id;
                    $original = (string) $row['biography'];
                    $uuid = $this->decodeUuid((string) $row['uuid']);
                    $ciphertext = $this->crypto->encrypt($original, 'user.biography:'.$uuid);
                    $affected = $this->connection->executeStatement(
                        'UPDATE user SET biography = :ciphertext WHERE id = :id AND BINARY biography = BINARY :original',
                        ['ciphertext' => $ciphertext, 'id' => $id, 'original' => $original]
                    );
                    if (1 === $affected) {
                        ++$updated;
                    } else {
                        ++$skipped;
                    }
                }
            } while ([] !== $rows);

            $output->writeln(\sprintf(
                '<info>Encrypted: %d; skipped due to concurrent changes: %d.</info>',
                $updated,
                $skipped
            ));

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $output->writeln(
                '<error>Biography encryption stopped safely. Check the database, UUID format and encryption key without exposing personal data.</error>'
            );

            return Command::FAILURE;
        }
    }

    private function decodeUuid(string $value): string
    {
        if (16 === \strlen($value)) {
            return (string) Uuid::fromBinary($value);
        }
        if (Uuid::isValid($value)) {
            return (string) Uuid::fromString($value);
        }

        throw new RuntimeException('Invalid stored user UUID; migration stopped without altering that record.');
    }
}

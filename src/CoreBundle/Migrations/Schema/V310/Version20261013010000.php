<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261013010000 extends AbstractMigrationChamilo
{
    private const array COLUMNS = [
        ['table' => 'c_dropbox_file', 'column' => 'filename', 'oldLength' => 190, 'nullable' => false],
        ['table' => 'message', 'column' => 'mail_inbound_id', 'oldLength' => 64, 'nullable' => true],
        ['table' => 'message_rel_user', 'column' => 'mail_tracking_token', 'oldLength' => 64, 'nullable' => true],
        ['table' => 'message_rel_user', 'column' => 'mail_reply_token', 'oldLength' => 64, 'nullable' => true],
        ['table' => 'mobile_push_installation', 'column' => 'token_hash', 'oldLength' => 64, 'nullable' => false],
        ['table' => 'oauth_access_token', 'column' => 'token_hash', 'oldLength' => 64, 'nullable' => false],
        ['table' => 'oauth_authorization_code', 'column' => 'code_hash', 'oldLength' => 64, 'nullable' => false],
        ['table' => 'oauth_client', 'column' => 'client_id', 'oldLength' => 64, 'nullable' => false],
        ['table' => 'oauth_refresh_token', 'column' => 'token_hash', 'oldLength' => 64, 'nullable' => false],
        ['table' => 'settings', 'column' => 'variable', 'oldLength' => 190, 'nullable' => false],
        ['table' => 'settings', 'column' => 'subkey', 'oldLength' => 190, 'nullable' => true],
        ['table' => 'settings_options', 'column' => 'variable', 'oldLength' => 190, 'nullable' => false],
        ['table' => 'settings_options', 'column' => 'value', 'oldLength' => 190, 'nullable' => true],
        ['table' => 'settings_value_template', 'column' => 'variable', 'oldLength' => 190, 'nullable' => false],
        ['table' => 'user', 'column' => 'username', 'oldLength' => 100, 'nullable' => false],
        ['table' => 'user_api_key', 'column' => 'api_key', 'oldLength' => 64, 'nullable' => false],
        ['table' => 'validation_token', 'column' => 'hash', 'oldLength' => 64, 'nullable' => false],
    ];

    public function getDescription(): string
    {
        return 'Normalize indexed Chamilo VARCHAR columns longer than 50 characters to VARCHAR(255)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::COLUMNS as $config) {
            if (!$schema->hasTable($config['table'])) {
                continue;
            }

            $table = $schema->getTable($config['table']);
            if (!$table->hasColumn($config['column'])) {
                continue;
            }

            if (255 === $table->getColumn($config['column'])->getLength()) {
                continue;
            }

            $this->addSql(
                \sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` VARCHAR(255)%s',
                    $config['table'],
                    $config['column'],
                    $config['nullable'] ? ' NULL DEFAULT NULL' : ' NOT NULL'
                )
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::COLUMNS as $config) {
            if (!$schema->hasTable($config['table'])) {
                continue;
            }

            $table = $schema->getTable($config['table']);
            if (!$table->hasColumn($config['column'])) {
                continue;
            }

            $this->assertMaxLength($config['table'], $config['column'], $config['oldLength']);

            $this->addSql(
                \sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` VARCHAR(%d)%s',
                    $config['table'],
                    $config['column'],
                    $config['oldLength'],
                    $config['nullable'] ? ' NULL DEFAULT NULL' : ' NOT NULL'
                )
            );
        }
    }

    private function assertMaxLength(string $tableName, string $columnName, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(`%s`)), 0) FROM `%s`', $columnName, $tableName)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot revert %s.%s to VARCHAR(%d): existing data reaches %d characters.', $tableName, $columnName, $maxLength, $actualMaxLength));
        }
    }
}

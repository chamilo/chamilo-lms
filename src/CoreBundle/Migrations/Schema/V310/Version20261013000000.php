<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261013000000 extends AbstractMigrationChamilo
{
    private const array COLUMNS = [
        [
            'table' => 'block',
            'column' => 'path',
            'oldLength' => 190,
            'nullable' => false,
        ],
        [
            'table' => 'block',
            'column' => 'controller',
            'oldLength' => 100,
            'nullable' => false,
        ],
        [
            'table' => 'branch_sync',
            'column' => 'admin_mail',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'branch_sync',
            'column' => 'admin_name',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'branch_sync',
            'column' => 'admin_phone',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'branch_sync',
            'column' => 'ssl_pub_key',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'branch_sync',
            'column' => 'branch_type',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'course_request',
            'column' => 'tutor_name',
            'oldLength' => 200,
            'nullable' => true,
        ],
        [
            'table' => 'c_attendance_sheet_log',
            'column' => 'lastedit_type',
            'oldLength' => 200,
            'nullable' => false,
        ],
        [
            'table' => 'c_blog',
            'column' => 'blog_subtitle',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'c_dropbox_file',
            'column' => 'description',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'c_dropbox_file',
            'column' => 'author',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'c_lp_item',
            'column' => 'audio',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'c_survey_answer',
            'column' => 'user',
            'oldLength' => 250,
            'nullable' => false,
        ],
        [
            'table' => 'c_survey_invitation',
            'column' => 'invitation_code',
            'oldLength' => 250,
            'nullable' => false,
        ],
        [
            'table' => 'c_survey_question',
            'column' => 'type',
            'oldLength' => 250,
            'nullable' => false,
        ],
        [
            'table' => 'c_survey_template_question',
            'column' => 'type',
            'oldLength' => 250,
            'nullable' => false,
        ],
        [
            'table' => 'c_toolbox_version',
            'column' => 'provider',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'grade_model',
            'column' => 'default_external_eval_prefix',
            'oldLength' => 140,
            'nullable' => true,
        ],
        [
            'table' => 'oauth_authorization_code',
            'column' => 'code_challenge',
            'oldLength' => 128,
            'nullable' => false,
        ],
        [
            'table' => 'oauth_client',
            'column' => 'client_secret_hash',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'oauth_client',
            'column' => 'software_version',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'plugin_buycourses_global_config',
            'column' => 'seller_vat_number',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'plugin_buycourses_global_config',
            'column' => 'vat_maxmind_account_id',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'plugin_buycourses_service_sale',
            'column' => 'buyer_vat_number',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'plugin_buycourses_service_sale',
            'column' => 'vat_treatment',
            'oldLength' => 128,
            'nullable' => true,
        ],
        [
            'table' => 'plugin_oel_tools_teachdoc',
            'column' => 'local_folder',
            'oldLength' => 60,
            'nullable' => false,
        ],
        [
            'table' => 'search_engine_field_value',
            'column' => 'value',
            'oldLength' => 200,
            'nullable' => false,
        ],
        [
            'table' => 'skill',
            'column' => 'short_code',
            'oldLength' => 100,
            'nullable' => false,
        ],
        [
            'table' => 'sys_announcement',
            'column' => 'lang',
            'oldLength' => 70,
            'nullable' => true,
        ],
        [
            'table' => 'templates',
            'column' => 'description',
            'oldLength' => 250,
            'nullable' => false,
        ],
        [
            'table' => 'track_e_login_record',
            'column' => 'username',
            'oldLength' => 100,
            'nullable' => false,
        ],
        [
            'table' => 'user',
            'column' => 'firstname',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'user',
            'column' => 'lastname',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'user',
            'column' => 'username_canonical',
            'oldLength' => 180,
            'nullable' => false,
        ],
        [
            'table' => 'user',
            'column' => 'timezone',
            'oldLength' => 64,
            'nullable' => false,
        ],
        [
            'table' => 'user',
            'column' => 'email_canonical',
            'oldLength' => 100,
            'nullable' => false,
        ],
        [
            'table' => 'user',
            'column' => 'email',
            'oldLength' => 100,
            'nullable' => false,
        ],
        [
            'table' => 'user',
            'column' => 'phone',
            'oldLength' => 64,
            'nullable' => true,
        ],
        [
            'table' => 'user',
            'column' => 'address',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'user',
            'column' => 'picture_uri',
            'oldLength' => 250,
            'nullable' => true,
        ],
        [
            'table' => 'user',
            'column' => 'productions',
            'oldLength' => 250,
            'nullable' => true,
        ],
    ];

    public function getDescription(): string
    {
        return 'Normalize non-indexed VARCHAR columns longer than 50 characters to VARCHAR(255)';
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

            $column = $table->getColumn($config['column']);
            $length = $column->getLength();

            if (255 === $length) {
                continue;
            }

            $this->assertColumnIsNotIndexed($table->getIndexes(), $config['table'], $config['column']);

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

            $this->assertColumnIsNotIndexed($table->getIndexes(), $config['table'], $config['column']);
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

    /**
     * @param array<string, Index> $indexes
     */
    private function assertColumnIsNotIndexed(array $indexes, string $tableName, string $columnName): void
    {
        foreach ($indexes as $index) {
            if (\in_array($columnName, $index->getColumns(), true)) {
                throw new RuntimeException(\sprintf('Cannot normalize %s.%s automatically because index %s includes the column.', $tableName, $columnName, $index->getName()));
            }
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

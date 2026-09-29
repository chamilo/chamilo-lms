<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * The local LRS now stores its statements in the xapi_* tables instead of the
 * PHP session, so GET /statements queries them on every request: by
 * registration, verb, activity or agent, ordered by "stored", and always
 * excluding the statements targeted by a voiding statement. None of those
 * columns was indexed, because until now nothing read them.
 */
final class Version20260806010000 extends AbstractMigrationChamilo
{
    /**
     * @var array<string, array<string, string>>
     */
    private const INDEXES = [
        'xapi_statement' => [
            'idx_xapi_statement_stored' => '`stored`',
        ],
        'xapi_verb' => [
            'idx_xapi_verb_id' => 'id',
        ],
        'xapi_object' => [
            'idx_xapi_object_activity_id' => 'activity_id',
            'idx_xapi_object_referenced_statement' => 'referenced_statement_id',
            'idx_xapi_object_mbox' => 'mbox',
            'idx_xapi_object_account_name' => 'account_name',
        ],
        'xapi_context' => [
            'idx_xapi_context_registration' => 'registration',
        ],
    ];

    public function getDescription(): string
    {
        return 'Index the xAPI statement columns used by the local LRS queries.';
    }

    public function up(Schema $schema): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);

            foreach ($indexes as $indexName => $column) {
                if ($table->hasIndex($indexName)) {
                    continue;
                }

                $this->addSql("CREATE INDEX $indexName ON $tableName ($column)");
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);

            foreach (array_keys($indexes) as $indexName) {
                if (!$table->hasIndex($indexName)) {
                    continue;
                }

                $this->addSql("DROP INDEX $indexName ON $tableName");
            }
        }
    }
}

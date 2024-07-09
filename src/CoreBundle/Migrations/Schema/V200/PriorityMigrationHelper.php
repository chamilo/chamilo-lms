<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Psr\Log\LoggerInterface;

/**
 * Priority migrations are database changes that *need* to happen before anything else
 * because the related Entities in the code used for the migration already use
 * the new structure (entities cannot be changed on the fly).
 * An instance of this class is called at the beginning of any migration process.
 */
class PriorityMigrationHelper
{
    private Connection $connection;
    private LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function executeUp(Schema $schema): void
    {
        $this->logger->info('Executing PriorityMigrationHelper up method.');
        $this->renameSettingsTableUp();
        $this->addDurationFields($schema);
    }

    public function executeDown(Schema $schema): void
    {
        $this->logger->info('Executing PriorityMigrationHelper down method.');
        $this->renameSettingsTableDown();
        $this->removeDurationFields($schema);
    }

    private function addDurationFields(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $tables = [
            'course',
            'c_survey',
            'c_quiz',
            'c_quiz_question',
            'c_lp',
            'c_lp_item',
            'c_student_publication',
            'c_attendance_calendar',
        ];

        foreach ($tables as $tableName) {
            $columns = $schemaManager->listTableColumns($tableName);
            if (!\array_key_exists('duration', $columns)) {
                $this->connection->executeQuery("ALTER TABLE $tableName ADD duration INT DEFAULT NULL");
            }
        }
    }

    private function removeDurationFields(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $tables = [
            'course',
            'c_survey',
            'c_quiz',
            'c_quiz_question',
            'c_lp',
            'c_lp_item',
            'c_student_publication',
            'c_attendance_calendar',
        ];

        foreach ($tables as $tableName) {
            $columns = $schemaManager->listTableColumns($tableName);
            if (\array_key_exists('duration', $columns)) {
                $this->connection->executeQuery("ALTER TABLE $tableName DROP COLUMN duration");
            }
        }
    }

    private function renameSettingsTableUp(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['settings_current']) && !$schemaManager->tablesExist(['settings'])) {
            $this->connection->executeQuery('RENAME TABLE settings_current TO settings');
        }
    }

    private function renameSettingsTableDown(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['settings']) && !$schemaManager->tablesExist(['settings_current'])) {
            $this->connection->executeQuery('RENAME TABLE settings TO settings_current');
        }
    }
}

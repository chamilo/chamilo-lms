<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;

$plugin = MigrationMoodlePlugin::create();

try {
    createMoodlePasswordExtraField();
    createPluginTables();
} catch (Exception $exception) {
    $message = sprintf(
        $plugin->get_lang('InstallError'),
        $exception->getMessage()
    );

    echo Display::return_message($message, 'error');
}

function createMoodlePasswordExtraField(): void
{
    $em = Database::getManager();

    $extraField = $em
        ->getRepository(ExtraField::class)
        ->findOneBy([
            'variable' => 'moodle_password',
            'extraFieldType' => ExtraField::USER_FIELD_TYPE,
        ]);

    if ($extraField) {
        return;
    }

    $plugin = MigrationMoodlePlugin::create();

    UserManager::create_extra_field(
        'moodle_password',
        ExtraField::FIELD_TYPE_TEXT,
        $plugin->get_lang('MoodlePassword'),
        ''
    );
}

/**
 * Create database tables for this plugin.
 */
function createPluginTables(): void
{
    $queries = [];
    $queries[] = "CREATE TABLE IF NOT EXISTS plugin_migrationmoodle_task (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB";
    $queries[] = "CREATE TABLE IF NOT EXISTS plugin_migrationmoodle_item (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            hash VARCHAR(255) NOT NULL,
            extracted_id INT NOT NULL,
            loaded_id INT NOT NULL,
            INDEX IDX_HASH (hash),
            INDEX IDX_EXTRACTED_LOADED (extracted_id, loaded_id),
            INDEX IDX_LOADED (loaded_id),
            INDEX IDX_TASK (task_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB";

    foreach ($queries as $query) {
        Database::query($query);
    }

    if (!migrationMoodleForeignKeyExists()) {
        Database::query(
            "ALTER TABLE plugin_migrationmoodle_item
                ADD CONSTRAINT FK_TASK
                FOREIGN KEY (task_id)
                REFERENCES plugin_migrationmoodle_task (id)
                ON DELETE CASCADE"
        );
    }
}

function migrationMoodleForeignKeyExists(): bool
{
    $sql = "SELECT COUNT(*) AS total
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'plugin_migrationmoodle_item'
              AND CONSTRAINT_NAME = 'FK_TASK'";
    $row = Database::fetch_assoc(Database::query($sql));

    return !empty($row) && (int) $row['total'] > 0;
}

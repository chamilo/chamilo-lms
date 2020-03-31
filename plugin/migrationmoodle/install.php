<?php
/* For licensing terms, see /license.txt */

$plugin = MigrationMoodlePlugin::create();

try {
    UserManager::create_extra_field(
        'moodle_password',
        ExtraField::FIELD_TYPE_TEXT,
        $plugin->get_lang('MoodlePassword'),
        ''
    );

    createPluginTables();
} catch (Exception $exception) {
    $message = sprintf(
        $plugin->get_lang('InstallError'),
        $exception->getMessage()
    );

    echo Display::return_message($message, 'error');
}

/**
 * Create database tables for this plugin.
 */
function createPluginTables()
{
    $installed = AppPlugin::getInstance()->isInstalled('migrationmoodle');

    if ($installed) {
        return;
    }

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
    $queries[] = "ALTER TABLE plugin_migrationmoodle_item ADD CONSTRAINT FK_TASK FOREIGN KEY (task_id)
        REFERENCES plugin_migrationmoodle_task (id) ON DELETE CASCADE";

    foreach ($queries as $query) {
        Database::query($query);
    }
}

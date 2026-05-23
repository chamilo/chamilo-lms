<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;

$plugin = MigrationMoodlePlugin::create();

try {
    removeExtraField();
    removePluginTables();
} catch (Exception $exception) {
    $message = sprintf(
        $plugin->get_lang('UninstallError'),
        $exception->getMessage()
    );

    echo Display::return_message($message, 'error');
}

function removeExtraField(): void
{
    $em = Database::getManager();

    /** @var ExtraField|null $extraField */
    $extraField = $em
        ->getRepository(ExtraField::class)
        ->findOneBy([
            'variable' => 'moodle_password',
            'extraFieldType' => ExtraField::USER_FIELD_TYPE,
        ]);

    if (!$extraField) {
        return;
    }

    $values = $em
        ->getRepository(ExtraFieldValues::class)
        ->findBy(['field' => $extraField]);

    foreach ($values as $value) {
        $em->remove($value);
    }

    $em->remove($extraField);
    $em->flush();
}

/**
 * Drop database table created by this plugin.
 */
function removePluginTables(): void
{
    $queries = [];
    $queries[] = "DROP TABLE IF EXISTS plugin_migrationmoodle_item";
    $queries[] = "DROP TABLE IF EXISTS plugin_migrationmoodle_task";

    foreach ($queries as $query) {
        Database::query($query);
    }
}

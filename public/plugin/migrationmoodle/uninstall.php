<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;

$plugin = MigrationMoodlePlugin::create();

try {
    removeExtraField();
    removePluginTables();

    $plugin->uninstallHook();
} catch (Exception $exception) {
    $message = sprintf(
        $plugin->get_lang('UninstallError'),
        $exception->getMessage()
    );

    echo Display::return_message($message, 'error');
}

/**
 * @throws \Doctrine\ORM\ORMException
 * @throws \Doctrine\ORM\OptimisticLockException
 */
function removeExtraField()
{
    $em = Database::getManager();

    /** @var ExtraField $extraField */
    $extraField = $em
        ->getRepository('ChamiloCoreBundle:ExtraField')
        ->findOneBy(['variable' => 'moodle_password', 'extraFieldType' => ExtraField::USER_FIELD_TYPE]);

    if ($extraField) {
        $em
            ->createQuery('DELETE FROM ChamiloCoreBundle:ExtraFieldValues efv WHERE efv.field = :field')
            ->execute(['field' => $extraField]);

        $em->remove($extraField);
        $em->flush();
    }
}

/**
 * Drop database table created by this plugin.
 */
function removePluginTables()
{
    $queries = [];
    $queries[] = "DROP TABLE IF EXISTS plugin_migrationmoodle_item";
    $queries[] = "DROP TABLE IF EXISTS plugin_migrationmoodle_task";

    foreach ($queries as $query) {
        Database::query($query);
    }
}

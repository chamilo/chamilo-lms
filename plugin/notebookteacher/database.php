<?php
/* For license terms, see /license.txt */

/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php.
 *
 * @package chamilo.plugin.notebookteacher
 */
/**
 * Check if script can be called.
 */
if (!function_exists('api_get_path')) {
    exit('This script must be loaded through the Chamilo plugin installer sequence');
}

$entityManager = Database::getManager();
$pluginSchema = new \Doctrine\DBAL\Schema\Schema();
$connection = $entityManager->getConnection();
$platform = $connection->getDatabasePlatform();

if ($pluginSchema->hasTable(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER)) {
    return;
}

//Create tables
$notebookTable = $pluginSchema->createTable(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);
$notebookTable->addColumn('id', \Doctrine\DBAL\Types\Type::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
$notebookTable->addColumn('c_id', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$notebookTable->addColumn('session_id', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$notebookTable->addColumn('user_id', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$notebookTable->addColumn('student_id', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$notebookTable->addColumn('course', \Doctrine\DBAL\Types\Type::STRING);
$notebookTable->addColumn('title', \Doctrine\DBAL\Types\Type::STRING);
$notebookTable->addColumn('description', \Doctrine\DBAL\Types\Type::TEXT);
$notebookTable->addColumn('creation_date', \Doctrine\DBAL\Types\Type::DATETIME);
$notebookTable->addColumn('update_date', \Doctrine\DBAL\Types\Type::DATETIME);
$notebookTable->addColumn('status', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$notebookTable->addIndex(['c_id']);
$notebookTable->setPrimaryKey(['id']);

$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

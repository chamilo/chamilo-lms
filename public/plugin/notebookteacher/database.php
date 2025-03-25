<?php

/* For license terms, see /license.txt */

/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php.
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
$notebookTable->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
$notebookTable->addColumn('c_id', 'integer', ['unsigned' => true]);
$notebookTable->addColumn('session_id', 'integer', ['unsigned' => true]);
$notebookTable->addColumn('user_id', 'integer', ['unsigned' => true]);
$notebookTable->addColumn('student_id', 'integer', ['unsigned' => true]);
$notebookTable->addColumn('course', 'string');
$notebookTable->addColumn('title', 'string');
$notebookTable->addColumn('description', 'text');
$notebookTable->addColumn('creation_date', 'datetime');
$notebookTable->addColumn('update_date', 'datetime');
$notebookTable->addColumn('status', 'integer', ['unsigned' => true]);
$notebookTable->addIndex(['c_id']);
$notebookTable->setPrimaryKey(['id']);

$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

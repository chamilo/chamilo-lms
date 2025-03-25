<?php
/* For license terms, see /license.txt */

use Doctrine\DBAL\Types\Type;

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

if ($pluginSchema->hasTable(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE)) {
    return;
}

//Create tables
$certificateTable = $pluginSchema->createTable(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
$certificateTable->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
$certificateTable->addColumn('access_url_id', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('c_id', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('session_id', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('content_course', 'text');
$certificateTable->addColumn('contents_type', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('contents', 'text');
$certificateTable->addColumn('date_change', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('date_start', 'datetime');
$certificateTable->addColumn('date_end', 'datetime');
$certificateTable->addColumn('type_date_expediction', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('place', 'string');
$certificateTable->addColumn('day', 'string', ['notnull' => false]);
$certificateTable->addColumn('month', 'string', ['notnull' => false]);
$certificateTable->addColumn('year', 'string', ['notnull' => false]);
$certificateTable->addColumn('logo_left', 'string');
$certificateTable->addColumn('logo_center', 'string');
$certificateTable->addColumn('logo_right', 'string');
$certificateTable->addColumn('seal', 'string');
$certificateTable->addColumn('signature1', 'string');
$certificateTable->addColumn('signature2', 'string');
$certificateTable->addColumn('signature3', 'string');
$certificateTable->addColumn('signature4', 'string');
$certificateTable->addColumn('signature_text1', 'string');
$certificateTable->addColumn('signature_text2', 'string');
$certificateTable->addColumn('signature_text3', 'string');
$certificateTable->addColumn('signature_text4', 'string');
$certificateTable->addColumn('background', 'string');
$certificateTable->addColumn('margin_left', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('margin_right', 'integer', ['unsigned' => true]);
$certificateTable->addColumn('certificate_default', 'integer', ['unsigned' => true]);
$certificateTable->addIndex(['c_id', 'session_id']);
$certificateTable->setPrimaryKey(['id']);

$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

<?php
/* For license terms, see /license.txt */

use Doctrine\DBAL\Types\Type;

/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php.
 *
 * @package chamilo.plugin.customcertificate
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
$certificateTable->addColumn('id', Type::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
$certificateTable->addColumn('access_url_id', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('c_id', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('session_id', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('content_course', Type::TEXT);
$certificateTable->addColumn('contents_type', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('contents', Type::TEXT);
$certificateTable->addColumn('date_change', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('date_start', Type::DATETIME);
$certificateTable->addColumn('date_end', Type::DATETIME);
$certificateTable->addColumn('type_date_expediction', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('place', Type::STRING);
$certificateTable->addColumn('day', Type::STRING, ['notnull' => false]);
$certificateTable->addColumn('month', Type::STRING, ['notnull' => false]);
$certificateTable->addColumn('year', Type::STRING, ['notnull' => false]);
$certificateTable->addColumn('logo_left', Type::STRING);
$certificateTable->addColumn('logo_center', Type::STRING);
$certificateTable->addColumn('logo_right', Type::STRING);
$certificateTable->addColumn('seal', Type::STRING);
$certificateTable->addColumn('signature1', Type::STRING);
$certificateTable->addColumn('signature2', Type::STRING);
$certificateTable->addColumn('signature3', Type::STRING);
$certificateTable->addColumn('signature4', Type::STRING);
$certificateTable->addColumn('signature_text1', Type::STRING);
$certificateTable->addColumn('signature_text2', Type::STRING);
$certificateTable->addColumn('signature_text3', Type::STRING);
$certificateTable->addColumn('signature_text4', Type::STRING);
$certificateTable->addColumn('background', Type::STRING);
$certificateTable->addColumn('margin_left', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('margin_right', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('certificate_default', Type::INTEGER, ['unsigned' => true]);
$certificateTable->addIndex(['c_id', 'session_id']);
$certificateTable->setPrimaryKey(['id']);

$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

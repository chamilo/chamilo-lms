<?php
/* For license terms, see /license.txt */

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
    die('This script must be loaded through the Chamilo plugin installer sequence');
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
$certificateTable->addColumn('id', \Doctrine\DBAL\Types\Type::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
$certificateTable->addColumn('access_url_id', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('c_id', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('session_id', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('content_course', \Doctrine\DBAL\Types\Type::TEXT);
$certificateTable->addColumn('contents_type', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('contents', \Doctrine\DBAL\Types\Type::TEXT);
$certificateTable->addColumn('date_change', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('date_start', \Doctrine\DBAL\Types\Type::DATETIME);
$certificateTable->addColumn('date_end', \Doctrine\DBAL\Types\Type::DATETIME);
$certificateTable->addColumn('type_date_expediction', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('place', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('day', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('month', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('year', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('logo_left', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('logo_center', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('logo_right', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('seal', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature1', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature2', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature3', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature4', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature_text1', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature_text2', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature_text3', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('signature_text4', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('background', \Doctrine\DBAL\Types\Type::STRING);
$certificateTable->addColumn('margin_left', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('margin_right', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addColumn('certificate_default', \Doctrine\DBAL\Types\Type::INTEGER, ['unsigned' => true]);
$certificateTable->addIndex(['c_id', 'session_id']);
$certificateTable->setPrimaryKey(['id']);

$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

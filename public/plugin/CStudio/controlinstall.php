<?php

declare(strict_types=1);
/* idempotent control */
/* For licensing terms, see /license.txt */

require_once __DIR__.'/0_dal/dal.global_lib.php';

require_once __DIR__.'/teachdoc_hub.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

$h = '<h2>Chamilo Studio Health Checkup</h2><br><ul>';

$sqlToken = 'CREATE TABLE IF NOT EXISTS plugin_oel_tools_token(
    id INT NOT NULL AUTO_INCREMENT,
    id_user INT,
    token VARCHAR(50),
    PRIMARY KEY (id));';
Database::query($sqlToken);

$oel_tools = tableExistOnBase('plugin_oel_tools_token');
if ($oel_tools) {
    $h .= "<li>plugin_oel_tools_token Is <span style='font-weight:bold;color:green;' >OK</span></li>";
} else {
    $h .= "<li>plugin_oel_tools_token Is <span style='font-weight:bold;color:red;' >KO</span></li>";
}

$oel_tools = tableExistOnBase('plugin_oel_tools_teachdoc');

if ($oel_tools) {
    $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >OK</span></li>";

    if (!columnExistOntable('plugin_oel_tools_teachdoc', 'options')) {
        $inSql = 'ALTER TABLE `plugin_oel_tools_teachdoc` ADD `options` VARCHAR(512) NOT NULL;';
        Database::query($inSql);
        $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >Options is install</span></li>";
    } else {
        $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >Options is OK</span></li>";
    }

    if (!columnExistOntable('plugin_oel_tools_teachdoc', 'quizztheme')) {
        $inSql = 'ALTER TABLE `plugin_oel_tools_teachdoc` ADD `quizztheme` VARCHAR(25) NOT NULL;';
        Database::query($inSql);
        $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >Quizztheme is install</span></li>";
    } else {
        $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >Quizztheme is OK</span></li>";
    }

    if (!columnExistOntable('plugin_oel_tools_teachdoc', 'leveldoc')) {
        $inSql = "ALTER TABLE plugin_oel_tools_teachdoc ADD leveldoc TINYINT NOT NULL DEFAULT '0';";
        Database::query($inSql);
        $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >leveldoc is install</span></li>";
    } else {
        $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >leveldoc is OK</span></li>";
    }
} else {
    $h .= "<li>oel_tools Is <span style='font-weight:bold;color:red;' >KOK</span></li>";
}

$oel_logs = tableExistOnBase('plugin_oel_tools_logs');

if (!$oel_logs) {
    $sql = "CREATE TABLE IF NOT EXISTS plugin_oel_tools_logs(
        id INT NOT NULL AUTO_INCREMENT,
        id_user INT,
        id_page INT NOT NULL DEFAULT '0',
        id_project INT NOT NULL DEFAULT '0',
        type_log TINYINT NOT NULL DEFAULT '0',
        title VARCHAR(255),
        logs VARCHAR(1080),
        result TINYINT NOT NULL DEFAULT '0',
        date_create INT,
        send_xapi TINYINT,
        PRIMARY KEY (id));";
    Database::query($sql);
    $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >plugin_oel_tools_logs is install</span></li>";
} else {
    $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >plugin_oel_tools_logs is OK</span></li>";
    if (!columnExistOntable('plugin_oel_tools_logs', 'result')) {
        $inSql = "ALTER TABLE plugin_oel_tools_logs ADD result TINYINT NOT NULL DEFAULT '0';";
        Database::query($inSql);
        $h .= "<li>oel_tools Is <span style='font-weight:bold;color:green;' >result in plugin_oel_tools_logs is install</span></li>";
    }
}

$h .= '</ul>';

echo $h;

function tableExistOnBase($tableName)
{
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    $returnExits = false;
    $sqlCtr = "SELECT table_name ,
                       table_schema AS schema_name
                FROM   information_schema.tables
                WHERE  table_schema NOT LIKE 'pg\\_%'
                AND    table_schema != 'information_schema'
                AND    table_name != 'geometry_columns'
                AND    table_name != 'spatial_ref_sys'
                AND    table_type != 'VIEW'
                AND TABLE_NAME LIKE '%$tableName%' ";

    $resultSetCtr = Database::query($sqlCtr);
    while ($rowCtr = Database::fetch_array($resultSetCtr)) {
        $returnExits = true;
    }

    return $returnExits;
}

function columnExistOntable($tableName, $columnName)
{
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    $columnName = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
    $returnExits = false;
    $sqlCtr = "SELECT table_name ,
                       table_schema AS schema_name
                FROM   information_schema.COLUMNS
                WHERE  column_name LIKE '%$columnName%'
                AND TABLE_NAME LIKE '%$tableName%' ";

    $resultSetCtr = Database::query($sqlCtr);
    while ($rowCtr = Database::fetch_array($resultSetCtr)) {
        $returnExits = true;
    }

    return $returnExits;
}

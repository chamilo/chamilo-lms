<?php
/* For license terms, see /license.txt */
/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php
 * @package chamilo.plugin.test2pdf
 */
/**
 * Check if script can be called
 */
if (!function_exists('api_get_path')) {
    die('This script must be loaded through the Chamilo plugin installer sequence');
}
/**
 * Create the script context, then execute database queries to enable
 */

$table = Database::get_main_table(TABLE_TEST2PDF);
/*
$sql = "CREATE TABLE IF NOT EXISTS $table (
    id INT unsigned NOT NULL auto_increment PRIMARY KEY,
    tool_id INT unsigned NOT NULL DEFAULT '0',
    score INT,
    enable INT(1)
    )";
Database::query($sql);
*/

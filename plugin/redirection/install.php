<?php
/* For licensing terms, see /license.txt */
/**
 * Install
 * @author Enrique Alcaraz Lopez
 * @package chamilo.plugin.redirection
 */

$table = Database::get_main_table('plugin_redirection');

$sql = "CREATE TABLE IF NOT EXISTS $table (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            user_id INT unsigned NOT NULL DEFAULT 0,
            url VARCHAR(255) NOT NULL DEFAULT ''
        )";

Database::query($sql);

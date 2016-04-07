<?php
/* PHP code to uninstall the plugin */

$table = 'vchamilo';
$tablename = Database::get_main_table($table);
$sql = " DROP TABLE IF EXISTS $tablename ";

Database::query($sql);
 
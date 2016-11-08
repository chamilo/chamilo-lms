<?php
/* PHP code to uninstall the plugin */

api_protect_admin_script();

$table = Database::get_main_table('vchamilo');
$sql = " DROP TABLE IF EXISTS $table";

Database::query($sql);

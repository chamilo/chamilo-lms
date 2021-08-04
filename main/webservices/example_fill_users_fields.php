<?php
/**
 * This script populates the user_extra_fields_value table with a new field which
 * contains the username for each user. This allows you to use web
 * services to update users based on their username (which is assumed
 * to be the same as in the application which calls the webservice).
 * This script should be called any time a new user (or a large group of new
 * users) is added to the database.
 *
 * @package chamilo.webservices
 */
//remove the next line to enable the script (this can harm your database so
// don't enable unless you know what you're doing and you have a backup)
exit();
// update this ID after you create the corresponding field through the Chamilo
// profile fields manager (admin page, users section) as text field.
// Give this field a name you will later use in original_field_id_name, while
// you will use the normal username of Chamilo users.
$extra_field_id = 9;
require_once '../inc/global.inc.php';
$tuser = Database::get_main_table(TABLE_MAIN_USER);
$tuserfv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
$sql = "SELECT user_id, username FROM $tuser ORDER BY user_id";
$res = Database::query($sql);
while ($row = Database::fetch_array($res)) {
    $sql2 = "INSERT INTO $tuserfv (item_id, field_id, value)
           VALUES (".$row['user_id'].", 11,'".$row['username']."')";
    $res2 = Database::query($sql2);
}

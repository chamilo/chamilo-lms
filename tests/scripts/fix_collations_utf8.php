<?php
/* For licensing terms, check /license.txt */
/**
 * This script fixes an issue with systems that have been migrated from one
 * server to another and *then* updated to a later major version, which
 * created new tables in a different collation (see BT#16375 & BT#16253).
 * This problem is usually associated with an error message similar to:
 * SQLSTATE[HY000]: General error: 1267 Illegal mix of collations (latin1_swedish_ci,IMPLICIT) and (utf8_general_ci,COERCIBLE)
 * This script will list the tables and convert them all to utf8_unicode_ci 
 * (which is now explicit in new tables creations in Chamilo)
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
// To launch, remove the following line
exit;
require __DIR__.'/../../main/inc/global.inc.php';
$sql = "SELECT table_name, table_collation FROM information_schema.tables where TABLE_SCHEMA='".$_configuration['main_database']."'";
$res = Database::query($sql);
while ($row = Database::fetch_assoc($res)) {
    if ($row['table_collation'] == 'utf8_unicode_ci') {
        continue; //do nothing
    }
    $sqlu = 'ALTER TABLE '.$row['table_name'].' CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci';
    $resu = Database::query($sqlu);
    echo $row['table_name']." converted\n";
}


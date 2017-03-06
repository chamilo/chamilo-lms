<?php
/**
 * This script kills all queries to which the Chamilo database user has access
 * through processlist.
 * Use only when you have an impossible situation with an urgent need to
 * restart or stop the database, and it just won't stop because it want to
 * finish queries first, and these queries are waiting for a lock on a table
 * that seems to never free itself.
 * In this case, disable the exit(); line below, run this script and then you
 * should be able to quickly restart your database.
 */
exit;
require_once '../../main/inc/global.inc.php';
$result = Database::query("SHOW FULL PROCESSLIST");
while ($row=Database::fetch_array($result)) {
    $process_id=$row["Id"];
    if ($row["Time"] > 200 ) {
        $sql="KILL $process_id";
        Database::query($sql);
    }
}
echo "All queries by this user have been killed".PHP_EOL;

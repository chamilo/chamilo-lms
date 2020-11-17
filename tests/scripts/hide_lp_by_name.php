<?php

/* For licensing terms, see /license.txt */

/**
 * Batch to hide all LPs with the $nameToSearch name.
 */

exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}


require_once __DIR__.'/../../main/inc/global.inc.php';

/*
 * Arguments for script
 */
$nameToSearch = 'My LP to hide';
$userId = 1;

/*
 * Processing
 */
$tblLP = Database::get_course_table(TABLE_LP_MAIN);
$name = Database::escape_string($nameToSearch);

$sql = "SELECT iid, c_id FROM $tblLP WHERE name = '$nameToSearch'";

$result = Database::query($sql);

while ($lp = Database::fetch_assoc($result)) {
    $updated = api_item_property_update(
        api_get_course_info_by_id($lp['c_id']),
        TOOL_LEARNPATH,
        $lp['iid'],
        'invisible',
        $userId
    );

    echo '['.time().'] ';

    if (!$updated) {
        echo "LP not updated ({$lp['iid']})";
    }

    echo "LP ({$lp['iid']}) updated";
    echo PHP_EOL;
}

echo 'Done'.PHP_EOL;

<?php
/* For licensing terms, see /license.txt */
/**
 * Fix a very weird case where the following queries seem to have been executed
 * twice during a database migration from 1.9 to 1.10
 * UPDATE c_item_property cip SET cip.to_group_id = (SELECT cgi.iid FROM c_group_info cgi WHERE cgi.c_id = cip.c_id AND cgi.id = cip.to_group_id);
 * DELETE FROM c_item_property WHERE to_group_id IS NOT NULL AND to_group_id <> 0 AND to_group_id NOT IN (SELECT iid FROM c_group_info);
 * These queries, as explained in BT#13243, when executed twice, break the
 * relationship of events to groups, and maybe other group resources, and then
 * deletes them.
 *
 * To fix, we need access to the previous database (version 1.9) and an active
 * connection to the migrated database (version 1.11 in this case). The script
 * scans through events in the old database, checks if they still exist in the
 * c_item_property in the new database, and if they don't tries to recreate the
 * c_item_property records needed to recover them.
 *
 * Due to the active use of the v1.11 configuration file through global.inc.php,
 * settings for the old database have to be added manually.
 */
/**
 * Context initialization
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}
require __DIR__.'/../../main/inc/global.inc.php';

/**
 * Old database configuration
 */
$oldDBHost = 'localhost';
$oldDBName = 'db1.9';
$oldDBUser = 'db1.9';
$oldDBPass = 'db1.9';
try {
    $oldDBH = new PDO('mysql:host=localhost;dbname='.$oldDBName, $oldDBUser, $oldDBPass);
} catch (PDOException $e) {
    echo "Error connecting to old database: ".$e->getMessage().PHP_EOL;
}

/**
 * New database configuration
 */
$newDBHost = 'localhost';
$newDBName = 'db1.11';
$newDBUser = 'db1.11';
$newDBPass = 'db1.11';
try {
    $newDBH = new PDO('mysql:host=localhost;dbname='.$newDBName, $newDBUser, $newDBPass);
} catch (PDOException $e) {
    echo "Error connecting to new database: ".$e->getMessage().PHP_EOL;
}

/**
 * Start looking for group events
 */
$foundCount = 0;
$totalCount = 0;
// Get a list of groups by course (TODO add sessions support)
$groups = getOldGroupsByCourse();

// Set this optional filter to something else than empty if you want to only
// check items of one course
$optionalCourseFilter = '';
$sqlOld = "SELECT * FROM c_item_property WHERE to_group_id IS NOT NULL AND to_group_id != 0";
if (!empty($optionalCourseFilter)) {
    $sqlOld .= ' AND c_id = '.$optionalCourseFilter;
}
foreach ($oldDBH->query($sqlOld) as $oldRow) {
    echo $oldRow['c_id'].' '.$oldRow['id'].' '.$oldRow['tool'].' '.$oldRow['ref'].' '.$oldRow['to_group_id'].PHP_EOL;
    $totalCount++;

    $sessionSubSelect = '';
    $sessionInsert = '';
    if (!empty($oldRow['id_session'])) {
        $sessionSubSelect = ' AND session_id = "'.$oldRow['id_session'].'" ';
        $sessionInsert = $oldRow['id_session'];
    } else {
        $sessionSubSelect = ' AND session_id IS NULL ';
        $sessionInsert = 'NULL';
    }
    $sqlNew = "SELECT iid, to_group_id FROM c_item_property
        WHERE c_id = ".$oldRow['c_id'].
        $sessionSubSelect.
        " AND tool = '".$oldRow['tool']."' 
        AND ref = ".$oldRow['ref'];

    //echo trim(str_replace("\n", '', $sqlNew)).PHP_EOL;
    $q = $newDBH->query($sqlNew);
    // Two situations arise: the record is found and is pointing to the wrong group -> update
    // or the record is not found and we need to create it based on the new iid of the old group's id -> insert
    if ($q->rowCount() > 0) {
        $newRow = $q->fetch();
        //echo "--> Found corresponding c_item_property as ".$newRow['iid'].PHP_EOL;
        $foundCount++;

        // First check if the group referenced in the old database still exists.
        // This is originally the c_group_info.id, NOT the c_group_info.iid, so
        // we need to check the existence of a group with that id in course c_id
        // If the group doesn't exist anymore, skip the update/insertion (maybe
        // we should even delete it?)

        if (isset($groups[$oldRow['c_id']][$oldRow['to_group_id']])) {
            $newGroupId = $groups[$oldRow['c_id']][$oldRow['to_group_id']];
            // also check if the new ID is different, otherwise we can avoid an update
            if ($oldRow['to_group_id'] != $newGroupId) {
                // Update this row to attach it to the right group
                $sqlFix = 'UPDATE c_item_property SET to_group_id = '.$newGroupId.' WHERE iid = '.$newRow['iid'];
                //echo $sqlFix.PHP_EOL;
                $newDBH->query($sqlFix);
            }
        }
    } else {
        echo "xx> No corresponding c_item_property found".PHP_EOL;

        // First check if the group referenced in the old database still exists.
        // This is originally the c_group_info.id, NOT the c_group_info.iid, so
        // we need to check the existence of a group with that id in course c_id
        // If the group doesn't exist anymore, skip the update/insertion (maybe
        // we should even delete it?)

        if (isset($groups[$oldRow['c_id']][$oldRow['to_group_id']])) {
            $newGroupId = $groups[$oldRow['c_id']][$oldRow['to_group_id']];

            // Insert a new row to make the group calendar event visible again
            $sqlFix = 'INSERT INTO c_item_property(c_id, to_group_id, to_user_id, '.
                ' insert_user_id, session_id, tool, insert_date, lastedit_date, ref,'.
                ' lastedit_type, lastedit_user_id, visibility, start_visible, end_visible)'.
                " VALUES ("
                .$oldRow['c_id'].', '
                .$newGroupId.', '
                .' NULL, '
                .$oldRow['insert_user_id'].', '
                .$sessionInsert.', '
                .'\''.$oldRow['tool'].'\', '
                .'\''.$oldRow['insert_date'].'\', '
                .'\''.$oldRow['lastedit_date'].'\', '
                .$oldRow['ref'].', '
                .'\''.$oldRow['lastedit_type'].'\', '
                .$oldRow['lastedit_user_id'].', '
                .$oldRow['visibility'].', '
                .'\''.$oldRow['start_visible'].'\', '
                .'\''.$oldRow['end_visible'].'\''
                .')';
            //echo $sqlFix.PHP_EOL;
            $newDBH->query($sqlFix);
        }
    }
}

echo PHP_EOL;
$diff = $totalCount - $foundCount;
echo "Found $foundCount corresponding c_item_property on a total of $totalCount items searched for (we're missing $diff)".PHP_EOL;

/**
 * Helper function to get a list of existing groups from the c_group_info table
 * @return array
 */
function getOldGroupsByCourse() {
    global $oldDBH;
    global $newDBH;
    $groups = [];
    $courses = [];
    $sql = "SELECT id FROM course";
    foreach ($oldDBH->query($sql) as $course) {
        $sqlGroup = "SELECT id, iid FROM c_group_info WHERE c_id = ".$course['id'];
        foreach ($newDBH->query($sqlGroup) as $group) {
            if (!isset($courses[$course['id']])) {
                $courses[$course['id']] = [];
            }
            $courses[$course['id']][$group['id']] = $group['iid'];
        }
    }
    return $courses;
}

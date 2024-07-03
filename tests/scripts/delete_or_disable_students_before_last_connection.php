<?php

/* For licensing terms, see /license.txt */

/*
 * Delete or disable the students whose last login date is before the indicated date.
 * If the action to take is delete, the user's personal directory is also deleted from the file system.
 *
 * Usage:
 *      php delete_or_disable_students_before_last_connection.php delete "2020-12-31"
 *      php delete_or_disable_students_before_last_connection.php disable "2020-12-31" --force
 */

// Remove the following line to enable
exit;

if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line'.PHP_EOL);
}

if (empty($argv[1]) || empty($argv[2])) {
    die(
        'This script need the action and date parameters to work,'.PHP_EOL
            .'e.g., delete_or_disable_students_before_last_connection.php.php delete "2020-01-01"'.PHP_EOL
    );
}

$action = $argv[1];
$date = $argv[2];
$force = '--force' === ($argv[3] ?? '');

if (!in_array($action, ['disable', 'delete'])) {
    die('Action not allowed'.PHP_EOL);
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$date = api_get_utc_datetime($date);

$tblUser = Database::get_main_table(TABLE_MAIN_USER);

$result = Database::query(
    sprintf(
        "SELECT id, username FROM $tblUser
            WHERE last_login <= '%s' AND active = %d AND status = %d
           ORDER BY last_login",
        $date,
        1,
        STUDENT
    )
);
$nbrRows = Database::num_rows($result);

echo "Number of users to $action: $nbrRows".PHP_EOL;

switch ($action) {
    case 'disable':
        while ($row = Database::fetch_assoc($result)) {
            if ($force) {
                UserManager::disable($row['id']);
            }

             printf(
                 "Student disabled: %d - %s".PHP_EOL,
                 $row['id'],
                 $row['username']
             );
        }
        break;
    case 'delete':
        while ($row = Database::fetch_assoc($result)) {
            if ($force) {
                UserManager::deleteUserFiles($row['id']);
                UserManager::delete_user($row['id']);
            }

            printf(
                "Student deleted: %d - %s (%s)".PHP_EOL,
                $row['id'],
                $row['username'],
                UserManager::getUserPathById($row['id'], 'system')
            );
        }
        break;
}

if (!$force) {
    echo "To proceed to $action, execute script with --force".PHP_EOL;
} else {
    echo 'Done'.PHP_EOL;
}

<?php
/**
 * This script compares usernames and passwords taking the assumption that
 * these should be indentical, and using the bcrypt algorithm.
 * It then show a list of all the users (with status and registration date)
 * where these do not match, and an option allows you to *make* them match.
 */
exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

// Expected password to compare to. If this is empty, assumes the expected
// password is the same as the username
$expectedPass = 'secret';
// For those who have a password that does *not* match, decide whether to
// replace it with the username (will only work on students)
$replace = false;
// *IF* we want to replace, then set the replacement string (or "username" to
// use each user's username as pass). If anything else than 'username', it will
// use the *same* fixed string for everyone that matches
$replacement = 'username';
// Use the username with this prefix, if defined, as a password
$prefix = '';
// Use the username with this suffix, if defined, as a password
$suffix = '';

$counterStudents = 0;
$counterOthers = 0;
$countAll = 0;

$usersTable = Database::get_main_table(TABLE_MAIN_USER);
$sql = "SELECT id, username, password, salt, status, registration_date FROM $usersTable";
$result = Database::query($sql);
while ($row = Database::fetch_assoc($result)) {
    //echo $row['id'].' '.$row['username'].' '.$row['password'].PHP_EOL;
    $expectedPassLocal = $expectedPass;
    if (empty($expectedPass)) {
        $expectedPassLocal = $row['username'];
    }

    if (UserManager::isPasswordValid($row['password'], $expectedPassLocal, null)) {
        echo "Password for user ".$row['username']." is the expected '".$expectedPassLocal."'".PHP_EOL;

        if ($row['status'] == 5) {
            $counterStudents++;

            // If we expected this password and want to replace it, this means
            // we have to do the opposite: 
            // - if it was the username, use the expected password,
            // - if it was the expected password, use the username
            if ($replace) {
                if ($replacement == 'username') {
                    UserManager::updatePassword($row['id'], $row['username']);
                    echo "  Replaced by ".$row['username'].PHP_EOL;
                } else {
                    UserManager::updatePassword($row['id'], $replacement);
                    echo "  Replaced by ".$replacement.PHP_EOL;
                }
            }
        } else {
            $counterOthers++;
        }
    }
    $countAll++;
}
echo "Done for a total of $countAll users.".PHP_EOL;
echo "$counterStudents students were found to have the expected password.".PHP_EOL;
echo "$counterOthers others were found to have the expected password.".PHP_EOL;
$expectedPassFinal = $expectedPass;
if (empty($expectedPass)) {
    $expectedPassFinal = 'the username';
}
echo "The expected password was '$expectedPassFinal'.".PHP_EOL;

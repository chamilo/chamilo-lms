<?php

/* For licensing terms, see /license.txt */

/**
 * This script takes a single-URL portal and changes the database so the
 * original portal becomes a secondary URL (not the main admin URL).
 * This means creating a new URL of ID 1 and moving all previous records
 * referencing ID 1 to ID 2.
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';

$tableAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
$tableUserRelCourseVote = Database::get_main_table(TABLE_MAIN_USER_REL_COURSE_VOTE);
$tableTrackOnline = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
$tableAnnouncement = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
$tableSkill = Database::get_main_table(TABLE_MAIN_SKILL);
$tableAccessUrlRelCourse = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
$tableAccessUrlRelCourseCategory = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
$tableAccessUrlRelSession = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
$tableAccessUrlRelUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
$tableAccessUrlRelUserGroup = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);
$tableBranchSync = Database::get_main_table(TABLE_BRANCH);
$tableMailTemplate = 'mail_template'; //does not necessarily exist //url_id field
$tableSessionCategory = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
$tableCalendar = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
$tableTrackCourseRanking = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING); //url_id


// Usage
echo "This script converts a single-URL portal to a multi-URL portal by making".PHP_EOL;
echo "the current URL into the secondary URL and creating a new URL as admin URL.".PHP_EOL;
echo "Please take a database backup before launching this script.".PHP_EOL;PHP_EOL;

$adminUrl = '';
if (!empty($argv[1])) {
    $adminUrl = Database::escape_string($argv[1]);
} else {
    echo "New admin URL was not defined. Please try again, passing it as argument to this script".PHP_EOL;
    echo "Usage: php multi_url_conversion.php URL [admin-username]".PHP_EOL;
    echo "  URL         The URL (with http(s):// and trailing slash of the (new) admin portal".PHP_EOL;
    echo "  username    The username of the global admin (to be set as admin of new portal)".PHP_EOL.PHP_EOL;
    exit();
}

$userId = 1;
if (!empty($argv[2])) {
    $username = $argv[2];
    $user = api_get_user_info_from_username($username);
    $userId = $user['id'];
} else {
    echo "No admin username provided. Using admin of ID 1".PHP_EOL;
}

$mainUrl = api_get_path(WEB_PATH);

echo "Converting portal to multi-URL. Processing...".PHP_EOL;
$sql = "SELECT id FROM $tableAccessUrl";
$result = Database::query($sql);
if (Database::num_rows($result) > 1) {
    echo "There are already more than one URL on this portal. Process cancelled".PHP_EOL;
    exit();
}

echo "Updating default URL to ".$mainUrl.PHP_EOL;
while ($row = Database::fetch_assoc($result)) {
    $sqlU = "UPDATE $tableAccessUrl SET url = '$adminUrl', description = 'The main admin URL' WHERE id = ".$row['id'];
    $resU = Database::query($sqlU);
    if ($resU === false) {
        echo "Found issue executing the following query. Process cancelled: $sqlU".PHP_EOL;
        exit();
    } else {
        $adminUrlId = $row['id'];
        echo "...done!".PHP_EOL;
        break;
    }
}

$date = api_get_utc_datetime();
$oldUrlId = $adminUrlId + 1;
echo "Creating new URL and converting previous URL (".$adminUrlId.") to secondary. Processing...".PHP_EOL;

$sqlI = "INSERT INTO $tableAccessUrl (id, url, description, active, created_by, tms, url_type)".
        " VALUES ($oldUrlId, '$mainUrl', '', 1, 1, '$date', null)";
$resI = Database::query($sqlI);

echo "Updating all relevant tables to define the secondary URL".PHP_EOL;

$sqlU = "UPDATE $tableUserRelCourseVote SET url_id = $oldUrlId WHERE url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableUserRelCourseVote updated".PHP_EOL;

$sqlU = "UPDATE $tableTrackOnline SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableTrackOnline updated".PHP_EOL;

$sqlU = "UPDATE $tableAnnouncement SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableAnnouncement updated".PHP_EOL;

$sqlU = "UPDATE $tableSkill SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableSkill updated".PHP_EOL;

//$sqlU = "UPDATE settings_current SET access_url = $oldUrlId WHERE access_url = $adminUrlId";

$sqlU = "UPDATE $tableAccessUrlRelCourse SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableAccessUrlRelCourse updated".PHP_EOL;

$sqlU = "UPDATE $tableAccessUrlRelCourseCategory SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableAccessUrlRelCourseCategory updated".PHP_EOL;

$sqlU = "UPDATE $tableAccessUrlRelSession SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableAccessUrlRelSession updated".PHP_EOL;

$sqlU = "UPDATE $tableAccessUrlRelUser SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
$sqlI = "INSERT INTO $tableAccessUrlRelUser (access_url_id, user_id) VALUES ($adminUrlId, $userId)";
$resI = Database::query($sqlI);
echo "Table $tableAccessUrlRelUser updated".PHP_EOL;

$sqlU = "UPDATE $tableAccessUrlRelUserGroup SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);
echo "Table $tableAccessUrlRelUserGroup updated".PHP_EOL;

$sqlU = "UPDATE $tableBranchSync SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);

// only if mail_template table exists
//$sqlU = "UPDATE $tableMailTemplate SET url_id = $oldUrlId WHERE url_id = $adminUrlId";
//$resU = Database::query($sqlU);

$sqlU = "UPDATE $tableSessionCategory SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);

$sqlU = "UPDATE $tableCalendar SET access_url_id = $oldUrlId WHERE access_url_id = $adminUrlId";
$resU = Database::query($sqlU);

$sqlU = "UPDATE $tableTrackCourseRanking SET url_id = $oldUrlId WHERE url_id = $adminUrlId";
$resU = Database::query($sqlU);

echo "Database updated.".PHP_EOL;
echo "Please set \$_configuration['multiple_access_urls'] to true in the app/config/configuration.php file".PHP_EOL;

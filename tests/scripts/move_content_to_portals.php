<?php

/* For licensing terms, see /license.txt */
/**
 * Inter-URL migration script for multi-URL portals.
 * This script migrates all content from one (or several) URL(s) to a given
 * destination URL by updating the access_url_id (or similar) field.
 * This does *NOT* include settings_current, for which we assume the destination
 * portal *does* have the right settings.
 */
exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$test = false;
$urlSource = [2];
$urlDestinationId = 3;

$urlDestination = UrlManager::get_url_data_from_id($urlDestinationId);
if (empty($urlDestination)) {
    echo 'Portal not found';
    exit;
}

if ($test) {
    echo '----'.PHP_EOL;
    echo '----No DB changes'.PHP_EOL;
    echo '----'.PHP_EOL;
}

foreach ($urlSource as $sourceId) {
    $sourceId = (int) $sourceId;
    $urlInfo = UrlManager::get_url_data_from_id($sourceId);
    if (empty($urlInfo)) {
        echo 'Portal not found';
        continue;
    }

    $tables = [
        'access_url_rel_course' => 'c_id',
        'access_url_rel_course_category' => 'course_category_id',
        'access_url_rel_session' => 'session_id',
        'access_url_rel_user' => 'user_id',
        'access_url_rel_usergroup' => 'usergroup_id',
    ];

    // Move all content to the destination URL except those for which
    // the contents already exist (use 3-levels query to avoid derived merge 
    // optimization issues)
    foreach ($tables as $table => $checker) {
        $sql = "UPDATE $table SET access_url_id = $urlDestinationId
                WHERE access_url_id = $sourceId AND $checker NOT IN (
                    SELECT $checker FROM (
                        SELECT DISTINCT $checker FROM $table WHERE access_url_id = $urlDestinationId) as c
                )";
        echo $sql.PHP_EOL;
        if (!$test) {
            $query = Database::query($sql);
            echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
        }

        // Delete doubles
        $sql = "DELETE FROM $table WHERE access_url_id = $sourceId";
        echo $sql.PHP_EOL;
        if (!$test) {
            $query = Database::query($sql);
            echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
        }
    }

    $sql = "UPDATE plugin_bbb_meeting SET access_url = $urlDestinationId
            WHERE access_url = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE session_category SET access_url_id = $urlDestinationId
            WHERE access_url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE branch_sync SET access_url_id = $urlDestinationId
            WHERE access_url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE skill SET access_url_id = $urlDestinationId
            WHERE access_url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE sys_announcement SET access_url_id = $urlDestinationId
            WHERE access_url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE sys_calendar SET access_url_id = $urlDestinationId
            WHERE access_url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE track_e_online SET access_url_id = $urlDestinationId
            WHERE access_url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE track_course_ranking SET url_id = $urlDestinationId
            WHERE url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }

    $sql = "UPDATE user_rel_course_vote SET url_id = $urlDestinationId
            WHERE url_id = $sourceId";
    echo $sql.PHP_EOL;
    if (!$test) {
        $query = Database::query($sql);
        echo "-> ".Database::affected_rows($query)." rows moved".PHP_EOL;
    }
}

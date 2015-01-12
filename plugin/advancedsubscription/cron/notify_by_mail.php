<?php
/**
 * This script fixes a bloody issue with passwords getting out of sync between
 * course-subscriber and chamilo
 * @package chamilo
 * @author Daniel Barreto <daniel.barreto@beeznest.com>
 */

/**
 * Init
 */
require_once '../../../main/inc/global.inc.php';

/**
 * Get database handle
 */

$nowUtc = api_get_utc_datetime();
$weekAgoUtc = api_get_utc_datetime(strtotime('-1 week'));
$sTable = Database::get_main_table(TABLE_MAIN_SESSION);
$uUserTable = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$limitArea = 'limit_area';
//define('USER_RELATION_TYPE_BOSS',       8);
//define('TABLE_ADV_SUB_QUEUE', 'plugin_advsub_queue');
//define('TABLE_ADV_SUB_MAIL', 'plugin_advsub_mail');

$sessionAdvSub = array();
$sessionQueue = array();
$userAdmins = array();

$advSubQueueTable = TABLE_ADVSUB_QUEUE;
$advSubMailTable = TABLE_ADVSUB_MAIL;
$advSubMails = Database::select('id, created_at, user_id, status', $advSubMailTable);
$advSubQueue = Database::select('id, status_id, user_id, updated_at', $advSubQueueTable, array('where' => array('start_date > ?' => $nowUtc)));
$sessionIds = Database::select('id', $sTable, array('where' => array('start_date > ?' => $nowUtc)));
$users = Database::select('user_id ASS boss_id, friend_user_id AS id', $uUserTable, array('where' => array('relation_type = ?' => USER_RELATION_TYPE_BOSS)));
$sField = new ExtraField('session');
$sFieldValue = new ExtraFieldValue('session');
$areaCounts = array();
foreach ($users as $userId => $bossId) {
    $areaCounts[$bossId] ++;
}
foreach ($advSubMails as $id => $advSubMail) {
    $userSubMail[$advSubMail['user_id']][] = $id;
}
foreach ($sessionIds as $sessionId) {
    $bossId = $advSubQueue[$sessionQueue[$sessionId]]['user_id'];
    $sLimitArea = $sFieldValue->get_values_by_handler_and_field_variable($sessionId, $limitArea);
    if ($sLimitArea > $sQueueCount[$bossId]) {
    // Register data
        $chooseUser = true;
    }
    if ($advSubMail[end($userSubMail[$bossId])]['created_at'] < $weekAgoUtc) {
        // Send mail again
        // Session x Boss -> user, status, buttons
        if ($chooseUser) {
            // Use choose user tpl
        }
    }
    foreach ($userAdmins as $adminId => $userAdmin) {
        if ($advSubMail[end($userSubMail[$adminId])]['created_at'] < $weekAgoUtc) {
            // send queue status 2
        }
    }
}

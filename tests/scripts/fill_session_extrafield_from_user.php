<?php
/* For license terms, see /license.txt */
/**
 * This script allows prefill the session extra fields related with
 * the user creator
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$fillExtraField = api_get_configuration_value('session_creation_user_course_extra_field_relation_to_prefill');

$sessions = SessionManager::get_sessions_list();
if (!empty($sessions)) {
    foreach ($sessions as $session) {
        $sessionId = $session['id'];
        $creatorId = getSessionCreatorId($sessionId);

        // Relation to prefill session extra field with user extra field
        if (false !== $fillExtraField && !empty($fillExtraField['fields'])) {
            foreach ($fillExtraField['fields'] as $sessionVariable => $userVariable) {
                $extraValue = UserManager::get_extra_user_data_by_field($creatorId, $userVariable);
                if (isset($extraValue[$userVariable])) {
                    $saved = SessionManager::update_session_extra_field_value($sessionId, $sessionVariable, $extraValue[$userVariable]);
                    if ($saved) {
                        echo "Updated $sessionId with creator user_id $creatorId, user_field_variable : $userVariable , user_field_value : {$extraValue[$userVariable]}".PHP_EOL;
                    }
                }
            }
        }
    }
}

/**
 * Get the user who creates the session
 *
 * @param $sessionId
 * @return int
 */
function getSessionCreatorId($sessionId):int
{
    $tblTrackDefault = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);

    $sql = "SELECT
            default_user_id
        FROM $tblTrackDefault
        WHERE default_value_type = 'session_id' AND
              default_value = '$sessionId' AND
              default_event_type = '".LOG_SESSION_CREATE."'";
    $rs = Database::query($sql);
    $creatorId = Database::result($rs, 0, 0);

    return $creatorId;
}

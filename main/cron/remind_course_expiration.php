<?php
/* For licensing terms, see /license.txt */
/**
 * Course expiration reminder.
 * @package chamilo.cron
 * @author Imanol Losada <imanol.losada@beeznest.com>
 */
require_once __DIR__ . '/../inc/global.inc.php';

/**
 * Initialization
 */
if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

// Days before expiration date to send reminders
define("OFFSET", 2);
$today = gmdate("Y-m-d");
$expirationDate = gmdate("Y-m-d", strtotime($today." + ".OFFSET." day"));

$query = "SELECT DISTINCT category.session_id, certificate.user_id
          FROM ".Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY)." AS category
          LEFT JOIN ".Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE)." AS certificate
          ON category.id = certificate.cat_id
          INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION)." AS session
          ON category.session_id = session.id
          WHERE
            session.access_end_date BETWEEN '$today' AND
            '$expirationDate' AND
            category.session_id IS NOT NULL";

$sessionId = 0;
$userIds = array();
$sessions = array();
$result = Database::query($query);

while ($row = Database::fetch_array($result)) {
    if ($sessionId != $row['session_id']) {
        $sessionId = $row['session_id'];
        $userIds = array();
    }
    if (!is_null($row['user_id'])) {
        array_push($userIds, $row['user_id']);
    }
    $sessions[$sessionId] = $userIds;
}

$usersToBeReminded = array();

foreach ($sessions as $sessionId => $userIds) {
    $userId = 0;
    $userIds = $userIds ? " AND sessionUser.user_id NOT IN (".implode(",", $userIds).")" : null;
    $query = "SELECT sessionUser.session_id, sessionUser.user_id, session.name, session.access_end_date FROM ".
        Database::get_main_table(TABLE_MAIN_SESSION_USER)." AS sessionUser
        INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION)." AS session
        ON sessionUser.session_id = session.id
        WHERE session_id = $sessionId$userIds";
    $result = Database::query($query);
    while ($row = Database::fetch_array($result)) {
        $usersToBeReminded[$row['user_id']][$row['session_id']] =
        array(
            'name' => $row['name'],
            'access_end_date' => $row['access_end_date']
        );
    }
}

if ($usersToBeReminded) {
    $today = date_create($today);
    $platformLanguage = api_get_setting("platformLanguage");
    $subject = sprintf(
        get_lang("MailCronCourseExpirationReminderSubject", null, $platformLanguage),
        api_get_setting("Institution")
    );
    $administrator = array(
        'completeName' => api_get_person_name(
            api_get_setting("administratorName"),
            api_get_setting("administratorSurname"),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        ),
        'email' => api_get_setting("emailAdministrator")
    );
    echo "\n======================================================================\n\n";
    foreach ($usersToBeReminded as $userId => $sessions) {
        $user = api_get_user_info($userId);
        $userCompleteName = api_get_person_name(
            $user['firstname'],
            $user['lastname'],
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        foreach ($sessions as $sessionId => $session) {
            $daysRemaining = date_diff($today, date_create($session['access_end_date']));
            $join = " INNER JOIN ".Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION)."ON id = access_url_id";
            $result = Database::select(
                'url',
                Database::get_main_table(TABLE_MAIN_ACCESS_URL).$join,
                array(
                    'where' => array(
                        'session_id = ?' => array(
                            $sessionId
                        )
                    ),
                    'limit' => '1'
                )
            );
            $body = sprintf(
                get_lang('MailCronCourseExpirationReminderBody', null, $platformLanguage),
                $userCompleteName,
                $session['name'],
                $session['access_end_date'],
                $daysRemaining->format("%d"),
                $result[0]['url'],
                api_get_setting("siteName")
            );
            api_mail_html(
                $userCompleteName,
                $user['email'],
                $subject,
                $body,
                $administrator['completeName'],
                $administrator['email']
            );
            echo "Email sent to $userCompleteName (".$user['email'].")\n";
            echo "Session: ".$session['name']."\n";
            echo "Date end: ".$session['access_end_date']."\n";
            echo "Days remaining: ".$daysRemaining->format("%d")."\n\n";
        }
        echo "======================================================================\n\n";
    }
} else {
    echo "No users to be reminded\n";
}

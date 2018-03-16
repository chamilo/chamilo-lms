<?php
/* For licensing terms, see /license.txt */
/**
 * Course expiration reminder.
 *
 * @package chamilo.cron
 *
 * @author Imanol Losada <imanol.losada@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Initialization.
 */
if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

$isActive = api_get_setting('cron_remind_course_expiration_activate') === 'true';

if (!$isActive) {
    exit;
}

$frequency = api_get_setting('cron_remind_course_expiration_frequency');

// Days before expiration date to send reminders
$today = gmdate("Y-m-d");
$expirationDate = gmdate("Y-m-d", strtotime("$today + $frequency day"));

$gradebookTable = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
$certificateTable = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
$sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
$sessionUserTable = Database::get_main_table(TABLE_MAIN_SESSION_USER);

$query = "
    SELECT DISTINCT category.session_id, certificate.user_id
    FROM $gradebookTable AS category
    LEFT JOIN $certificateTable AS certificate
    ON category.id = certificate.cat_id
    INNER JOIN $sessionTable AS session
    ON category.session_id = session.id
    WHERE
        session.access_end_date BETWEEN '$today' AND
        '$expirationDate' AND
        category.session_id IS NOT NULL";
$sessionId = 0;
$userIds = [];
$sessions = [];
$result = Database::query($query);
$urlSessionTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
$urlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL);

while ($row = Database::fetch_array($result)) {
    if ($sessionId != $row['session_id']) {
        $sessionId = $row['session_id'];
        $userIds = [];
    }
    if (!is_null($row['user_id'])) {
        array_push($userIds, $row['user_id']);
    }
    $sessions[$sessionId] = $userIds;
}

$usersToBeReminded = [];

foreach ($sessions as $sessionId => $userIds) {
    $userId = 0;
    $userIds = $userIds ? " AND sessionUser.user_id NOT IN (".implode(", ", $userIds).")" : null;
    $query = "
        SELECT sessionUser.session_id, sessionUser.user_id, session.name, session.access_end_date 
        FROM $sessionUserTable AS sessionUser
        INNER JOIN $sessionTable AS session
        ON sessionUser.session_id = session.id
        WHERE
            session_id = $sessionId$userIds";
    $result = Database::query($query);
    while ($row = Database::fetch_array($result)) {
        $usersToBeReminded[$row['user_id']][$row['session_id']] = [
            'name' => $row['name'],
            'access_end_date' => $row['access_end_date'],
        ];
    }
}

if ($usersToBeReminded) {
    $today = date_create($today);
    $administrator = [
        'completeName' => api_get_person_name(
            api_get_setting("administratorName"),
            api_get_setting("administratorSurname"),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        ),
        'email' => api_get_setting("emailAdministrator"),
    ];
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
            $join = " INNER JOIN $urlSessionTable ON id = access_url_id";
            $result = Database::select(
                'url',
                "$urlTable $join",
                [
                    'where' => [
                        'session_id = ?' => [
                            $sessionId,
                        ],
                    ],
                    'limit' => '1',
                ]
            );

            $subjectTemplate = new Template(null, false, false, false, false, false);
            $subjectTemplate->assign('session_name', $session['name']);
            $subjectTemplate->assign(
                'session_access_end_date',
                $session['access_end_date']
            );
            $subjectTemplate->assign(
                'remaining_days',
                $daysRemaining->format("%d")
            );

            $subjectLayout = $subjectTemplate->get_template(
                'mail/cron_remind_course_expiration_subject.tpl'
            );

            $bodyTemplate = new Template(null, false, false, false, false, false);
            $bodyTemplate->assign('complete_user_name', $userCompleteName);
            $bodyTemplate->assign('session_name', $session['name']);
            $bodyTemplate->assign(
                'session_access_end_date',
                $session['access_end_date']
            );
            $bodyTemplate->assign(
                'remaining_days',
                $daysRemaining->format("%d")
            );

            $bodyLayout = $bodyTemplate->get_template(
                'mail/cron_remind_course_expiration_body.tpl'
            );

            api_mail_html(
                $userCompleteName,
                $user['email'],
                $subjectTemplate->fetch($subjectLayout),
                $bodyTemplate->fetch($bodyLayout),
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

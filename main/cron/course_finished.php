<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;

/**
 * Cron for send a email when the course are finished.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

if (php_sapi_name() !== 'cli') {
    exit; //do not run from browser
}

$isActive = api_get_setting('cron_remind_course_expiration_activate') === 'true';

if (!$isActive) {
    exit;
}

$endDate = new DateTime('now', new DateTimeZone('UTC'));
$endDate = $endDate->format('Y-m-d');

$entityManager = Database::getManager();
$sessionRepo = $entityManager->getRepository('ChamiloCoreBundle:Session');
$accessUrlRepo = $entityManager->getRepository('ChamiloCoreBundle:AccessUrl');

/** @var Session[] $sessions */
$sessions = $sessionRepo->createQueryBuilder('s')
    ->where('s.accessEndDate LIKE :date')
    ->setParameter('date', "$endDate%")
    ->getQuery()
    ->getResult();

if (empty($sessions)) {
    echo "No sessions finishing today $endDate".PHP_EOL;
    exit;
}

$administrator = [
    'complete_name' => api_get_person_name(
        api_get_setting('administratorName'),
        api_get_setting('administratorSurname'),
        null,
        PERSON_NAME_EMAIL_ADDRESS
    ),
    'email' => api_get_setting('emailAdministrator'),
];

foreach ($sessions as $session) {
    $sessionUsers = $session->getUsers();

    if (empty($sessionUsers)) {
        echo 'No users to send mail'.PHP_EOL;
        exit;
    }

    foreach ($sessionUsers as $sessionUser) {
        $user = $sessionUser->getUser();

        $subjectTemplate = new Template(null, false, false, false, false, false);
        $subjectTemplate->assign('session_name', $session->getName());

        $subjectLayout = $subjectTemplate->get_template('mail/cron_course_finished_subject.tpl');

        $bodyTemplate = new Template(null, false, false, false, false, false);
        $bodyTemplate->assign('complete_user_name', UserManager::formatUserFullName($user));
        $bodyTemplate->assign('session_name', $session->getName());

        $bodyLayout = $bodyTemplate->get_template('mail/cron_course_finished_body.tpl');

        api_mail_html(
            UserManager::formatUserFullName($user),
            $user->getEmail(),
            $subjectTemplate->fetch($subjectLayout),
            $bodyTemplate->fetch($bodyLayout),
            $administrator['complete_name'],
            $administrator['email']
        );

        echo '============'.PHP_EOL;
        echo "Email sent to: ".UserManager::formatUserFullName($user)." ({$user->getEmail()})".PHP_EOL;
        echo "Session: {$session->getName()}".PHP_EOL;
        echo "End date: {$session->getAccessEndDate()->format('Y-m-d h:i')}".PHP_EOL;
    }
}

<?php

/* For licensing terms, see /license.txt */
/**
 * Cron for send a email when the course are finished
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.cron
 */
require_once __DIR__ . '/../inc/global.inc.php';

if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

$endDate = new DateTime('now', new DateTimeZone('UTC'));
$endDate = $endDate->format('Y-m-d');

$entityManager = Database::getManager();
$sessionRepo = $entityManager->getRepository('ChamiloCoreBundle:Session');
$accessUrlRepo = $entityManager->getRepository('ChamiloCoreBundle:AccessUrl');

$sessions = $sessionRepo->createQueryBuilder('s')
    ->where('s.accessEndDate LIKE :date')
    ->setParameter('date', "$endDate%")
    ->getQuery()
    ->getResult();

if (empty($sessions)) {
    echo "No sessions finishing today $endDate" . PHP_EOL;
    exit;
}

$administrator = [
    'complete_name' => api_get_person_name(
        api_get_setting('administratorName'),
        api_get_setting('administratorSurname'),
        null,
        PERSON_NAME_EMAIL_ADDRESS
    ),
    'email' => api_get_setting('emailAdministrator')
];

foreach ($sessions as $session) {
    $mailSubject = sprintf(
        get_lang('MailCronCourseFinishedSubject'),
        $session->getName()
    );

    $accessUrls = $accessUrlRepo->createQueryBuilder('au')
        ->select('au')
        ->innerJoin(
            'ChamiloCoreBundle:AccessUrlRelSession',
            'aus',
            Doctrine\ORM\Query\Expr\Join::WITH,
            'au.id = aus.accessUrlId'
        )
        ->where('aus.sessionId = :session')
        ->setParameter('session', $session)
        ->setMaxResults(1)
        ->getQuery()
        ->getResult();

    $accessUrl = current($accessUrls);

    $sessionUsers = $session->getUsers();

    if (empty($sessionUsers)) {
        echo 'No users to send mail' . PHP_EOL;
        exit;
    }

    foreach ($sessionUsers as $sessionUser) {
        $user = $sessionUser->getUser();

        $mailBody = vsprintf(
            get_lang('MailCronCourseFinishedBody'),
            [
                $user->getCompleteName(),
                $session->getName(),
                $accessUrl->getUrl(),
                api_get_setting("siteName")
            ]
        );

        api_mail_html(
            $user->getCompleteName(),
            $user->getEmail(),
            $mailSubject,
            $mailBody,
            $administrator['complete_name'],
            $administrator['email']
        );

        echo '============' . PHP_EOL;
        echo "Email sent to: {$user->getCompleteName()} ({$user->getEmail()})" . PHP_EOL;
        echo "Session: {$session->getName()}" . PHP_EOL;
        echo "End date: {$session->getAccessEndDate()->format('Y-m-d h:i')}" . PHP_EOL;
    }
}

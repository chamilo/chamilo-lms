<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\DocumentData;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\State;
use Xabbuh\XApi\Model\StateDocument;
use Xabbuh\XApi\Serializer\Symfony\Serializer;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$request = HttpRequest::createFromGlobals();

$user = api_get_user_entity(api_get_user_id());

$em = Database::getManager();

$attemptId = $request->request->get('attempt_id');
$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->request->getInt('id')
);

if (empty($attemptId)
    || null === $toolLaunch
    || $toolLaunch->getCourse()->getId() !== api_get_course_entity()->getId()
) {
    api_not_allowed(true);
}

$plugin = XApiPlugin::create();

$activity = new Activity(
    IRI::fromString($toolLaunch->getActivityId())
);
$actor = new Agent(
    InverseFunctionalIdentifier::withMbox(
        IRI::fromString('mailto:'.$user->getEmail())
    ),
    $user->getCompleteName()
);
$state = new State(
    $activity,
    $actor,
    $plugin->generateIri('tool-'.$toolLaunch->getId(), 'state')
);

$nowDate = api_get_utc_datetime(null, false, true)->format('c');

try {
    $stateDocument = $plugin->getXApiStateClient()->getDocument($state);

    $data = $stateDocument->getData()->getData();

    if ($stateDocument->offsetExists($attemptId)) {
        $data[$attemptId][XApiPlugin::STATE_LAST_LAUNCH] = $nowDate;
    } else {
        $data[$attemptId] = [
            XApiPlugin::STATE_FIRST_LAUNCH => $nowDate,
            XApiPlugin::STATE_LAST_LAUNCH => $nowDate,
        ];
    }

    uasort($data, function ($attemptA, $attemptB) {
        $timeA = strtotime($attemptA[XApiPlugin::STATE_LAST_LAUNCH]);
        $timeB = strtotime($attemptB[XApiPlugin::STATE_LAST_LAUNCH]);

        return $timeB - $timeA;
    });

    $documentData = new DocumentData($data);
} catch (NotFoundException $notFoundException) {
    $documentData = new DocumentData(
        [
            $attemptId => [
                XApiPlugin::STATE_FIRST_LAUNCH => $nowDate,
                XApiPlugin::STATE_LAST_LAUNCH => $nowDate,
            ],
        ]
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );

    header('Location: '.api_get_course_url());
    exit;
}

try {
    $plugin
        ->getXApiStateClient()
        ->createOrReplaceDocument(
            new StateDocument($state, $documentData)
        );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );

    header('Location: '.api_get_course_url());
    exit;
}

$authString = $plugin->get(XApiPlugin::SETTING_LRS_AUTH);
$parts = explode(':', $authString);

$activityLaunchUrl = $toolLaunch->getLaunchUrl().'?'
    .http_build_query(
        [
            'endpoint' => $plugin->get(XApiPlugin::SETTING_LRS_URL),
            'auth' => 'Basic '.base64_encode("{$parts[1]}:{$parts[2]}"),
            'actor' => Serializer::createSerializer()->serialize($actor, 'json'),
            'registration' => $attemptId,
            'activity_id' => $toolLaunch->getActivityId(),
        ],
        '',
        '&',
        PHP_QUERY_RFC3986
    );

header("Location: $activityLaunchUrl");

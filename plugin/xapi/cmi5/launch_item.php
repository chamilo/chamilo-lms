<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\Cmi5Item;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\DocumentData;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\State;
use Xabbuh\XApi\Model\StateDocument;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\Uuid;
use Xabbuh\XApi\Model\Verb;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$request = HttpRequest::createFromGlobals();

$em = Database::getManager();

$item = $em->find(Cmi5Item::class, $request->query->getInt('id'));
$toolLaunch = $item->getTool();

if ($toolLaunch->getId() !== $request->query->getInt('tool')) {
    api_not_allowed(
        false,
        Display::return_message(get_lang('NotAllwed'), 'error')
    );
}

$plugin = XApiPlugin::create();
$user = api_get_user_entity(api_get_user_id());
$nowDate = api_get_utc_datetime(null, false, true)->format('c');

$registration = (string) Uuid::uuid4();
$actor = new Agent(
    InverseFunctionalIdentifier::withAccount(
        new Account(
            $user->getCompleteName(),
            IRL::fromString(api_get_path(WEB_PATH))
        )
    ),
    $user->getCompleteName()
);
$verb = new Verb(
    IRI::fromString('http://adlnet.gov/expapi/verbs/launched'),
    LanguageMap::create($plugin->getLangMap('Launched'))
);
$customActivityId = $plugin->generateIri($item->getId(), 'cmi5_item');

$activity = new Activity(
    $customActivityId,
    new Definition(
        LanguageMap::create($item->getTitle()),
        LanguageMap::create($item->getDescription()),
        IRI::fromString($item->getIdentifier())
    )
);

$context = (new Context())
    ->withPlatform(
        api_get_setting('Institution').' - '.api_get_setting('siteName')
    )
    ->withLanguage(api_get_language_isocode())
    ->withRegistration($registration);

$statementUuid = Uuid::uuid5(
    $plugin->get(XApiPlugin::SETTING_UUID_NAMESPACE),
    "cmi5_item/{$item->getId()}"
);

$statement = new Statement(
    StatementId::fromUuid($statementUuid),
    $actor,
    $verb,
    $activity,
    null,
    null,
    api_get_utc_datetime(null, false, true),
    null,
    $context
);

$statementClient = XApiPlugin::create()->getXApiStatementClient();

//try {
//    $statementClient->storeStatement($statement);
//} catch (ConflictException $e) {
//    echo Display::return_message($e->getMessage(), 'error');
//
//    exit;
//} catch (XApiException $e) {
//    echo Display::return_message($e->getMessage(), 'error');
//
//    exit;
//}

$viewSessionId = (string) Uuid::uuid4();

$state = new State(
    $activity,
    $actor,
    'LMS.LaunchData',
    (string) $registration
);

$documentDataData = [];
$documentDataData['contentTemplate'] = [
    'extensions' => [
        'https://w3id.org/xapi/cmi5/context/extensions/sessionid' => $viewSessionId,
    ],
];
$documentDataData['launchMode'] = 'Normal';
$documentDataData['launchMethod'] = $item->getLaunchMethod();

if ($item->getLaunchParameters()) {
    $documentDataData['launchParameteres'] = $item->getLaunchParameters();
}

if ($item->getMasteryScore()) {
    $documentDataData['masteryScore'] = $item->getMasteryScore();
}

if ($item->getEntitlementKey()) {
    $documentDataData['entitlementKey'] = [
        'courseStructure' => $item->getEntitlementKey(),
    ];
}

$documentData = new DocumentData($documentDataData);

try {
    $plugin
        ->getXApiStateClient()
        ->createOrReplaceDocument(
            new StateDocument($state, $documentData)
        );
} catch (Exception $exception) {
    echo Display::return_message($exception->getMessage(), 'error');

    exit;
}

$launchUrl = $plugin->generateLaunchUrl(
    'cmi5',
    $item->getUrl(),
    $customActivityId->getValue(),
    $actor,
    $registration,
    $toolLaunch->getLrsUrl(),
    $toolLaunch->getLrsAuthUsername(),
    $toolLaunch->getLrsAuthPassword(),
    $viewSessionId
);

if ('OwnWindow' === $item->getLaunchMethod()) {
    Display::display_reduced_header();

    echo '<br><p class="text-center">';
    echo Display::toolbarButton(
        $plugin->get_lang('LaunchNewAttempt'),
        $launchUrl,
        'external-link fa-fw',
        'success',
        [
            'target' => '_blank',
        ]
    );
    echo '</div>';

    Display::display_reduced_footer();

    exit;
}

header("Location: $launchUrl");

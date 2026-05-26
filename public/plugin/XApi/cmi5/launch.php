<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiCmi5Item;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Uid\Uuid;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$request = Container::getRequest();
$em = Database::getManager();

/** @var XApiCmi5Item|null $item */
$item = $em->find(XApiCmi5Item::class, $request->query->getInt('id'));

if (null === $item) {
    api_not_allowed(
        false,
        Display::return_message(get_lang('Item not found'), 'error')
    );
}

$toolLaunch = $item->getTool();

if (null === $toolLaunch || $toolLaunch->getId() !== $request->query->getInt('tool')) {
    api_not_allowed(
        false,
        Display::return_message(
            get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'),
            'error'
        )
    );
}

$plugin = XApiPlugin::create();
$user = api_get_user_entity();

if (null === $user) {
    api_not_allowed(
        false,
        Display::return_message(get_lang('User not found'), 'error')
    );
}

$registration = Uuid::v4()->toRfc4122();
$viewSessionId = Uuid::v4()->toRfc4122();

$customActivityId = $plugin->generateIri((string) $item->getId(), 'cmi5_item');
$actor = $plugin->buildActorPayload($user);

/**
 * Resolve the AU URL.
 * Prefer the persisted URL field, but fallback to identifier
 * because some imported cmi5 items store the AU launch URL there.
 */
$itemUrl = trim((string) $item->getUrl());

if ('' === $itemUrl) {
    $itemUrl = trim((string) $item->getIdentifier());
}

if ('' === $itemUrl) {
    echo Display::return_message('The cmi5 AU launch URL is empty.', 'error');
    exit;
}

$launchData = [
    'contextTemplate' => [
        'extensions' => [
            'https://w3id.org/xapi/cmi5/context/extensions/sessionid' => $viewSessionId,
        ],
    ],
    'launchMode' => 'Normal',
    'launchMethod' => $item->getLaunchMethod() ?: 'AnyWindow',
];

if ($item->getLaunchParameters()) {
    $launchData['launchParameters'] = $item->getLaunchParameters();
}

if (null !== $item->getMasteryScore()) {
    $launchData['masteryScore'] = $item->getMasteryScore();
}

if ($item->getEntitlementKey()) {
    $launchData['entitlementKey'] = [
        'courseStructure' => $item->getEntitlementKey(),
    ];
}

$lrsUrl = $plugin->normalizeLrsUrl($toolLaunch->getLrsUrl());
$lrsUsername = $toolLaunch->getLrsAuthUsername();
$lrsPassword = $toolLaunch->getLrsAuthPassword();

try {
    $plugin->storeCmi5LaunchDataDocument(
        $customActivityId,
        $actor,
        $registration,
        $launchData,
        !empty($lrsUrl) ? $lrsUrl : null,
        !empty($lrsUsername) ? $lrsUsername : null,
        !empty($lrsPassword) ? $lrsPassword : null
    );
} catch (Exception $exception) {
    echo Display::return_message($exception->getMessage(), 'error');
    exit;
}

$launchUrl = $plugin->generateLaunchUrl(
    'cmi5',
    $itemUrl,
    $customActivityId,
    $actor,
    $registration,
    !empty($lrsUrl) ? $lrsUrl : null,
    !empty($lrsUsername) ? $lrsUsername : null,
    !empty($lrsPassword) ? $lrsPassword : null,
    $viewSessionId
);

if ('OwnWindow' === $item->getLaunchMethod()) {
    Display::display_reduced_header();

    echo '<div class="mx-auto max-w-3xl px-6 py-10">';
    echo '<div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">';
    echo '<h1 class="mb-2 text-2xl font-semibold text-gray-900">'.Security::remove_XSS($toolLaunch->getTitle()).'</h1>';
    echo '<p class="mb-6 text-sm text-gray-500">'.get_lang('This activity opens in a new window').'</p>';
    echo Display::url(
        get_lang('Launch'),
        $launchUrl,
        [
            'target' => '_blank',
            'class' => 'inline-flex items-center rounded-lg bg-primary px-4 py-2 text-white no-underline hover:text-white',
        ]
    );
    echo '</div>';
    echo '</div>';

    Display::display_reduced_footer();
    exit;
}

header('Location: '.$launchUrl);
exit;

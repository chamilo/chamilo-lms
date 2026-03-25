<?php

/* For license terms, see /license.txt */

/**
 * CardGame widget entry point.
 *
 * The plugin is injected globally through a region.
 * Visibility is handled on the frontend so the widget can survive SPA-like
 * navigation without requiring a full backend re-render on each route change.
 */

require_once __DIR__.'/../../main/inc/global.inc.php';

if (api_is_anonymous()) {
    return;
}

if (defined('CARDGAME_WIDGET_RENDERED')) {
    return;
}

define('CARDGAME_WIDGET_RENDERED', true);

require_once __DIR__.'/CardGame.php';

$cardGame = CardGame::create();
$userId = (int) api_get_user_id();

if ($userId <= 0) {
    return;
}

$progress = $cardGame->getOrCreateProgress($userId);
$canPlayToday = $cardGame->canPlayToday($progress);
$pluginWebPath = api_get_path(WEB_PLUGIN_PATH).'CardGame/resources/';
$version = '?v=20260324_03';

$dataAttributes = [
    'endpoint' => $pluginWebPath.'ajax.card.php',
    'can-play' => $canPlayToday ? '1' : '0',
    'pan' => (string) ($progress['pan'] ?? 1),
    'display-pan' => (string) $cardGame->getDisplayPan((int) ($progress['pan'] ?? 1)),
    'parts' => CardGame::serializeParts($progress['parts'] ?? []),
    'title' => $cardGame->get_lang('cardGameTitle'),
    'open-message' => $cardGame->get_lang('openDeckCardGame'),
    'engage-message' => $cardGame->get_lang('engageDeckCardGame'),
    'duplicate-message' => $cardGame->get_lang('cardgameloose'),
    'reveal-label' => $cardGame->get_lang('revealPieceCardGame'),
    'close-label' => $cardGame->get_lang('closeCardGame'),
    'completed-label' => $cardGame->get_lang('completedPanelsCardGame'),
    'piece-revealed-label' => $cardGame->get_lang('pieceRevealedCardGame'),
    'panel-completed-label' => $cardGame->get_lang('panelCompletedCardGame'),
    'loading-error-label' => $cardGame->get_lang('cardGameLoadingError'),
];

$attributesHtml = '';
foreach ($dataAttributes as $name => $value) {
    $attributesHtml .= ' data-'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'="'
        .htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'"';
}

echo '<link href="'
    .htmlspecialchars($pluginWebPath.'css/cardgame.css'.$version, ENT_QUOTES, 'UTF-8')
    .'" rel="stylesheet" type="text/css">';

echo '<div id="cardgame-root"'.$attributesHtml.'></div>';

echo '<script type="text/javascript" src="'
    .htmlspecialchars($pluginWebPath.'js/cardgame.js'.$version, ENT_QUOTES, 'UTF-8')
    .'"></script>';

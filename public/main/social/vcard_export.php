<?php

/* For licensing terms, see /license.txt */

use JeroenDesloovere\VCard\VCard;

/**
 * VCard Generator.
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

if (api_is_anonymous()) {
    http_response_code(401);
    echo get_lang('You must be logged in to access this page.');
    exit;
}

api_block_inactive_user();

$userId = isset($_REQUEST['userId']) ? (int) $_REQUEST['userId'] : 0;
if ($userId <= 0) {
    http_response_code(400);
    echo get_lang('Invalid user');
    exit;
}

// Return User Info to vCard Export
$userInfo = api_get_user_info($userId, true, false, true);
if (empty($userInfo)) {
    http_response_code(404);
    echo get_lang('User not found');
    exit;
}

/* Get the relationship between current user and vCard user */
$currentUserId = api_get_user_id();
$hasRelation = 0 !== SocialManager::get_relation_between_contacts(
    $currentUserId,
    $userId
);

if ($currentUserId !== $userId && !api_is_platform_admin() && !$hasRelation) {
    http_response_code(403);
    echo get_lang('You are not allowed to see this page.');
    exit;
}

// Pre-Loaded User Info
$language = get_lang('Language').': '.$userInfo['language'];

// Instance the vCard Class
$vcard = new VCard();
// Adding the User Info to the vCard
$vcard->addName($userInfo['firstname'], $userInfo['lastname']);

if ('true' == api_get_setting('show_email_addresses')) {
    $vcard->addEmail($userInfo['email']);
}

$vcard->addPhoneNumber($userInfo['phone'], 'CELL');
$vcard->addNote($language);

// Generate the vCard
return $vcard->download();

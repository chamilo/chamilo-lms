<?php
/* For licensing terms, see /license.txt */

use JeroenDesloovere\VCard\VCard;

/**
 * VCard Generator.
 *
 * @package chamilo.social
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (isset($_REQUEST['userId'])) {
    $userId = intval($_REQUEST['userId']);
} else {
    api_not_allowed(true);
}

// Return User Info to vCard Export
$userInfo = api_get_user_info($userId, true, false, true);

/* Get the relationship between current user and vCard user */
$currentUserId = api_get_user_id();
$hasRelation = SocialManager::get_relation_between_contacts(
    $currentUserId,
    $userId,
    true
);
if ($userId !== $currentUserId && $hasRelation == 0) {
    /* if not the same user && has no relationship, bypass only if admin */
    api_protect_admin_script();
}

if (empty($userInfo)) {
    api_not_allowed(true);
}
if (api_get_user_id() != $userId && !SocialManager::get_relation_between_contacts(api_get_user_id(), $userId)) {
    api_not_allowed(true);
}
// Pre-Loaded User Info
$language = get_lang('Language').': '.$userInfo['language'];

// Instance the vCard Class
$vcard = new VCard();
// Adding the User Info to the vCard
$vcard->addName($userInfo['firstname'], $userInfo['lastname']);

if (api_get_setting('show_email_addresses') == 'true') {
    $vcard->addEmail($userInfo['email']);
}

$vcard->addPhoneNumber($userInfo['phone'], 'CELL');
$vcard->addNote($language);

// Generate the vCard
return $vcard->download();

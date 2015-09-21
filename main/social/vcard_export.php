<?php
/* For licensing terms, see /license.txt */

/**
 * VCard Generator
 * @package chamilo.social
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 */

require_once '../inc/global.inc.php';

use JeroenDesloovere\VCard\VCard;

api_block_anonymous_users();

if (isset($_REQUEST['userId'])) {
	$userId = intval($_REQUEST['userId']);
} else {
    api_not_allowed();
}

// Return User Info to vCard Export
$userInfo = api_get_user_info($userId, true, false, true);

// Pre-Loaded User Info
$firstname = $userInfo['firstname'];
$lastname = $userInfo['lastname'];
$email = $userInfo['email'];
$phone = $userInfo['phone'];
$language = get_lang('Language').': '.$userInfo['language'];

// Instance the vCard Class
$vcard = new VCard();

// Adding the User Info to the vCard
$vcard->addName($lastname, $firstname);
$vcard->addEmail($email);
$vcard->addPhoneNumber($phone, 'CELL');
$vcard->addNote($language);

// Generate the vCard
return $vcard->download();

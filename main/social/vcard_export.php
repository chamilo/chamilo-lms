<?php

/* For licensing terms, see /license.txt */

/**
 * VCard Generator
 * 
 *  @package chamilo.social
 * 
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 */

require_once '../inc/global.inc.php';
require_once api_get_path(WEB_PATH).'vendor/autoload.php';
require_once api_get_path(WEB_PATH).'vendor/jeroendesloovere/vcard/src/VCard.php';

use JeroenDesloovere\VCard\VCard;

parse_str($_SERVER['QUERY_STRING'], $params);

if(isset($params['userId'])) {
	$userId = $params['userId'];
}
else {
	api_not_allowed();
	die();
}

//Return User Info to vCard Export
$userInfo = api_get_user_info($userId, true, false, true);

//Pre-Loaded User Info
$firstname = $userInfo['firstname'];
$lastname = $userInfo['lastname'];
$email = $userInfo['email'];
$phone = $userInfo['phone'];
$language = get_lang('Language').': '.$userInfo['language'];


//Instance the vCard Class
$vcard = new VCard();

//Adding the User Info to the vCard
$vcard->addName($lastname, $firstname);
$vcard->addEmail($email);
$vcard->addPhoneNumber($phone, 'CELL');
$vcard->addNote($language);

//Generate the vCard
return $vcard->download();

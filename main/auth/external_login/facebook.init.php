<?php 
/**
 * Licence: GPL
 * Please contact CBlue regarding any licences issues.
 * Author: noel@cblue.be
 *  Copyright: CBlue SPRL, 20XX
 *
 * External login module : FACEBOOK
 *
 * Configuration file 
 * Please edit this file to match with your FACEBOOK settings
 **/

/** 
 * Facebook application setting
 **/
require_once dirname(__FILE__).'/facebook-php-sdk/src/facebook.php';

global $facebook_config; 

//Loads the portal facebook settings
require_once dirname(__FILE__).'../../inc/conf/auth.conf.php';

/** 
 * See facebook section of the auth.conf.php file
*/
global $facebook;
$facebook = new Facebook(array(
			'appId'  => $facebook_config['appId'],
			'secret' => $facebook_config['secret']
			));
require_once dirname(__FILE__).'/facebook.inc.php';
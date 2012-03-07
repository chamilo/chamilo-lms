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
/** 
 * Decomment those lines and put your facebook app parameters here
 * Find them here : https://developers.facebook.com/apps/
 **/
/*$facebook_config = array('appId'=> 'APPID',
		'secret' => 'secret app',
		'return_url' => api_get_path(WEB_PATH).'?action=fbconnect');
 */

global $facebook;
$facebook = new Facebook(array(
			'appId'  => $facebook_config['appId'],
			'secret' => $facebook_config['secret']
			));
require_once dirname(__FILE__).'/facebook.inc.php';
?>

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
 * */
/**
 * Facebook application setting
 * */


//Loads the portal facebook settings
/**
 * Facebook application setting
 * Loads the portal facebook settings
 * See facebook section of the auth.conf.php file
 */

$confFile = dirname(__FILE__) . '/../../inc/conf/auth.conf.php';

if (file_exists($confFile)) {
    require_once $confFile;
}


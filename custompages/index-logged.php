<?php 
/* For licensing terms, see /license.txt */
/**
 * Redirect to normal Chamilo
 * @package chamilo.custompages
 */
/**
 * Initialization
 */
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');

$www = api_get_path('WEB_PATH');
/**
 * Redirect
 */
header("Location: $www/user_portal.php");

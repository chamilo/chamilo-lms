<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.notification
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file = array('userInfo');

require_once '../inc/global.inc.php';
/**
 * Notification sending
 */
$notify = new Notification();
$notify->send();

<?php

/* For license terms, see /license.txt */
/**
 * Render an email from data.
 */

/**
 * Init.
 */
require_once __DIR__.'/../config.php';

$plugin = AdvancedSubscriptionPlugin::create();
// Get validation hash
$hash = Security::remove_XSS($_REQUEST['v']);
// Get data from request (GET or POST)
$data['queueId'] = (int) ($_REQUEST['q']);
// Check if data is valid or is for start subscription
$verified = $plugin->checkHash($data, $hash);
if ($verified) {
    // Render mail
    //$message = MessageManager::get_message_by_id($data['queueId']);
    //$message = str_replace(['<br /><hr>', '<br />', '<br/>'], '', $message['content']);
    //echo $message;
}

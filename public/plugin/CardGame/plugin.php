<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin_info = CardGame::create()->get_info();
$plugin_info['title'] = $plugin_info['title'] ?? 'Card Game';
$plugin_info['comment'] = $plugin_info['comment'] ?? 'Daily card game widget for authenticated users.';
$plugin_info['version'] = $plugin_info['version'] ?? '1.0';

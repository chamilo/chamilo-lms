<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about the OpenBadge issuer
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.badge
 */
require_once __DIR__.'/../inc/global.inc.php';

header('Content-Type: application/json');

$json = array(
    'name' => api_get_setting('Institution'),
    'url' => api_get_path(WEB_PATH)
);

echo json_encode($json);
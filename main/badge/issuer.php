<?php
/* For licensing terms, see /license.txt */

/**
 * Show information about the OpenBadge issuer.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$json = [
    'name' => api_get_setting('Institution'),
    'url' => api_get_path(WEB_PATH),
];

header('Content-Type: application/json');

echo json_encode($json);

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

$_SESSION['exercise_keep_alive'] = time();

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
]);

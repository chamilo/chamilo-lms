<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

http_response_code(410);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');

echo json_encode(
    [
        'error' => 'The legacy Course Progress AJAX endpoint is no longer available.',
    ],
    \JSON_THROW_ON_ERROR,
);

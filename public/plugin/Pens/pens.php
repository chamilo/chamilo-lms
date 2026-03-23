<?php

/* For licensing terms, see /license.txt. */

/**
 * Legacy file kept only for backward compatibility.
 *
 * Preferred endpoint:
 *   POST /plugin/pens/collect
 */

require_once __DIR__.'/lib/PensProcessor.php';

header('Content-Type: text/plain; charset=UTF-8');

try {
    $payload = $_POST;

    if (empty($payload)) {
        parse_str((string) file_get_contents('php://input'), $payload);
    }

    $processor = new PensProcessor();
    echo $processor->handle($payload);
} catch (Throwable $exception) {
    echo "error=1432\n";
    echo "error-text=Internal package error\n";
    echo "version=1.0.0\n";
    echo "pens-data=\n";
}

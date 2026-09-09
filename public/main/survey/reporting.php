<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

// Deprecated: survey reporting moved to the Vue SPA
// (/resources/survey/{nodeId}/reporting), which escapes answers on render. Kept
// only to deny access, so this legacy entry point stays unreachable.
api_not_allowed(true);

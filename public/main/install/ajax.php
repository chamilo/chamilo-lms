<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/*
 * Deprecated and neutralized: this legacy pre-Vue installer AJAX endpoint was superseded by
 * public/main/inc/ajax/install.ajax.php (which gates access on APP_INSTALLED) and is no longer
 * referenced anywhere in the codebase. It used to accept db_host/db_username/db_pass/db_name/
 * db_port from an unauthenticated POST, connect to that database, and — for action
 * "remove_crs_tables" — DROP every table matching a wildcard, with no login, no CSRF token and
 * no check that an installation was even in progress. The file is kept in place (not deleted)
 * because some installations upgrade by overwriting the release directory rather than through
 * Git, so a deleted file would otherwise survive untouched on disk.
 */
http_response_code(404);

<?php

/* For licensing terms, see /license.txt */

/**
 * Compatibility entry point for the migrated global question bank.
 *
 * The actual page and all data operations live in Vue, API Platform, Symfony
 * services and Doctrine. This file only preserves old bookmarks and links.
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$user = api_get_current_user();
if (!api_is_platform_admin() && (!$user || !$user->hasRole('ROLE_QUESTION_MANAGER'))) {
    api_not_allowed(true);
}

api_block_inactive_user();

$query = $_GET;
$action = (string) ($query['action'] ?? '');
unset(
    $query['action'],
    $query['questionId'],
    $query['admin_questions_delete_sec_token'],
    $query['sec_token']
);

if ('export_pdf' === $action) {
    $query['form_sent'] = 1;
    header('Location: /api/admin/questions/export.pdf?'.http_build_query($query));
    exit;
}

header('Location: /admin/questions'.([] === $query ? '' : '?'.http_build_query($query)));
exit;

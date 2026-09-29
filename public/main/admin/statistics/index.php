<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../inc/global.inc.php';

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

api_block_inactive_user();

$query = $_GET;
$report = (string) ($query['report'] ?? '');

if ('activities' === $report) {
    unset($query['report']);
    $target = api_get_path(WEB_CODE_PATH).'admin/activities_audit.php';
    if ([] !== $query) {
        $target .= '?'.http_build_query($query);
    }

    header('Location: '.$target);
    exit;
}

$webPath = api_get_path(WEB_PATH);
$exportTarget = null;
$exportQuery = [];

// Preserve legacy export bookmarks while moving export generation to Symfony.
if ('session_by_date' === $report && 'export' === (string) ($query['action'] ?? '')) {
    $exportTarget = $webPath.'api/admin/statistics/session-by-date.xls';
    $exportQuery = [
        'rangeStart' => (string) ($query['range_start'] ?? ''),
        'rangeEnd' => (string) ($query['range_end'] ?? ''),
        'statusId' => (int) ($query['status_id'] ?? 0),
    ];
} elseif ('users_active' === $report && 'export' === (string) ($query['action_table'] ?? '')) {
    $exportTarget = $webPath.'api/admin/statistics/export/users_active.xls';
    $exportQuery = [
        'rangeStart' => (string) ($query['daterange_start'] ?? $query['range_start'] ?? ''),
        'rangeEnd' => (string) ($query['daterange_end'] ?? $query['range_end'] ?? ''),
    ];
} elseif ('logins_by_date' === $report && 'xls' === (string) ($query['export'] ?? '')) {
    $exportTarget = $webPath.'api/admin/statistics/export/logins_by_date.xls';
    $exportQuery = [
        'rangeStart' => (string) ($query['start'] ?? ''),
        'rangeEnd' => (string) ($query['end'] ?? ''),
    ];
} elseif ('duplicated_users' === $report && isset($query['action_table'])) {
    $actionTable = (string) $query['action_table'];
    if ('export_csv' === $actionTable || 'export_excel' === $actionTable) {
        $format = 'export_csv' === $actionTable ? 'csv' : 'xls';
        $exportTarget = $webPath.'api/admin/statistics/export/duplicated_users.'.$format;
        $exportQuery = [
            'dupMode' => (string) ($query['dup_mode'] ?? 'name'),
            'extraFieldId' => (int) ($query['extra_field_id'] ?? 0),
        ];

        if (isset($query['additional_profile_field'])) {
            $additionalFields = is_array($query['additional_profile_field'])
                ? $query['additional_profile_field']
                : [$query['additional_profile_field']];
            $additionalFields = array_values(array_filter(array_map(
                static fn ($value): int => (int) $value,
                $additionalFields
            ), static fn (int $value): bool => 0 < $value));

            if ([] !== $additionalFields) {
                $exportQuery['additionalProfileFields'] = implode(',', $additionalFields);
            }
        }
    }
}

if (null !== $exportTarget) {
    $exportQuery = array_filter(
        $exportQuery,
        static fn ($value): bool => '' !== (string) $value && '0' !== (string) $value
    );
    if ([] !== $exportQuery) {
        $exportTarget .= '?'.http_build_query($exportQuery);
    }

    header('Location: '.$exportTarget, true, 302);
    exit;
}

// Keep old bookmarks working. Legacy destructive GET actions are not replayed here;
// they are available in the Vue page through the secured POST action endpoint.
$target = $webPath.'admin/statistics';
unset(
    $query['action'],
    $query['action_table'],
    $query['export'],
    $query['sec_token'],
    $query['user_id'],
    $query['unify_user_id'],
    $query['keep_user_id'],
    $query['merge_user_id']
);
if ([] !== $query) {
    $target .= '?'.http_build_query($query);
}

header('Location: '.$target, true, 302);
exit;

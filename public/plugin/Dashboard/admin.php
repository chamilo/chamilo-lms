<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/src/DashboardPlugin.php';

api_protect_admin_script(true);

$plugin = DashboardPlugin::create();

if (!$plugin->isEnabled()) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('PluginIsNotEnabled'), 'warning')
    );
}

$connection = Database::getManager()->getConnection();

function dashboard_table_exists(object $connection, string $table): bool
{
    try {
        return false !== $connection->fetchOne('SHOW TABLES LIKE ?', [$table]);
    } catch (Throwable $exception) {
        error_log('[Dashboard] Unable to check table '.$table.': '.$exception->getMessage());

        return false;
    }
}

function dashboard_count_table(object $connection, string $table, string $where = ''): int
{
    if (!dashboard_table_exists($connection, $table)) {
        return 0;
    }

    try {
        $sql = 'SELECT COUNT(*) FROM `'.$table.'`';
        if ('' !== $where) {
            $sql .= ' WHERE '.$where;
        }

        return (int) $connection->fetchOne($sql);
    } catch (Throwable $exception) {
        error_log('[Dashboard] Unable to count '.$table.': '.$exception->getMessage());

        return 0;
    }
}

function dashboard_fetch_rows(object $connection, string $table, string $columns, string $orderColumn, int $limit = 5): array
{
    if (!dashboard_table_exists($connection, $table)) {
        return [];
    }

    try {
        $sql = 'SELECT '.$columns.' FROM `'.$table.'` ORDER BY `'.$orderColumn.'` DESC LIMIT '.(int) $limit;

        return $connection->fetchAllAssociative($sql);
    } catch (Throwable $exception) {
        error_log('[Dashboard] Unable to fetch rows from '.$table.': '.$exception->getMessage());

        return [];
    }
}

function dashboard_format_number(int $value): string
{
    return number_format($value, 0, '.', ' ');
}

function dashboard_percentage(int $part, int $total): int
{
    if (0 >= $total) {
        return 0;
    }

    return (int) round(($part / $total) * 100);
}

function dashboard_card(string $title, int $value, string $icon, string $description = ''): string
{
    $safeTitle = Security::remove_XSS($title);
    $safeDescription = Security::remove_XSS($description);

    return '
        <div class="group rounded-2xl border border-gray-25 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="m-0 text-body-2 font-semibold text-gray-50">'.$safeTitle.'</p>
                    <p class="m-0 mt-2 text-3xl font-bold text-gray-90">'.dashboard_format_number($value).'</p>
                    '.('' !== $safeDescription ? '<p class="m-0 mt-2 text-body-2 text-gray-50">'.$safeDescription.'</p>' : '').'
                </div>
                <span class="mdi '.$icon.' ch-tool-icon rounded-xl bg-support-2 p-2 text-3xl text-primary" aria-hidden="true"></span>
            </div>
        </div>
    ';
}

function dashboard_bar_row(string $label, int $value, int $maxValue, string $description = ''): string
{
    $percent = 0 < $maxValue ? max(2, dashboard_percentage($value, $maxValue)) : 0;
    $safeLabel = Security::remove_XSS($label);
    $safeDescription = Security::remove_XSS($description);

    return '
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="m-0 text-body-2 font-semibold text-gray-90">'.$safeLabel.'</p>
                    '.('' !== $safeDescription ? '<p class="m-0 text-caption text-gray-50">'.$safeDescription.'</p>' : '').'
                </div>
                <span class="rounded-full bg-support-1 px-3 py-1 text-caption font-semibold text-primary">'.dashboard_format_number($value).'</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-support-2">
                <div class="h-3 rounded-full bg-primary" style="width: '.$percent.'%;"></div>
            </div>
        </div>
    ';
}

function dashboard_chart_panel(string $title, string $subtitle, array $items): string
{
    $maxValue = 0;
    foreach ($items as $item) {
        $maxValue = max($maxValue, (int) ($item['value'] ?? 0));
    }

    $html = '
        <div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="m-0 text-lg font-semibold text-gray-90">'.Security::remove_XSS($title).'</h3>
                    <p class="m-0 mt-1 text-body-2 text-gray-50">'.Security::remove_XSS($subtitle).'</p>
                </div>
                <span class="mdi mdi-chart-bar ch-tool-icon rounded-xl bg-support-2 p-2 text-2xl text-primary" aria-hidden="true"></span>
            </div>
            <div class="space-y-4">
    ';

    if (empty($items)) {
        $html .= '<p class="m-0 text-body-2 text-gray-50">'.get_lang('NoDataAvailable').'</p>';
    } else {
        foreach ($items as $item) {
            $html .= dashboard_bar_row(
                (string) ($item['label'] ?? ''),
                (int) ($item['value'] ?? 0),
                $maxValue,
                (string) ($item['description'] ?? '')
            );
        }
    }

    $html .= '
            </div>
        </div>
    ';

    return $html;
}

function dashboard_status_panel(string $title, string $subtitle, int $activeUsers, int $inactiveUsers): string
{
    $total = $activeUsers + $inactiveUsers;
    $activePercent = dashboard_percentage($activeUsers, $total);
    $inactivePercent = dashboard_percentage($inactiveUsers, $total);

    return '
        <div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="m-0 text-lg font-semibold text-gray-90">'.Security::remove_XSS($title).'</h3>
                    <p class="m-0 mt-1 text-body-2 text-gray-50">'.Security::remove_XSS($subtitle).'</p>
                </div>
                <span class="mdi mdi-account-check-outline ch-tool-icon rounded-xl bg-support-2 p-2 text-2xl text-primary" aria-hidden="true"></span>
            </div>
            <div class="mb-4 overflow-hidden rounded-full bg-support-2">
                <div class="h-4 bg-primary" style="width: '.$activePercent.'%;"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-support-1 p-4">
                    <p class="m-0 text-caption font-semibold text-gray-50">'.Security::remove_XSS(get_lang('ActiveUsers')).'</p>
                    <p class="m-0 mt-1 text-2xl font-bold text-gray-90">'.dashboard_format_number($activeUsers).'</p>
                    <p class="m-0 mt-1 text-caption text-primary">'.$activePercent.'%</p>
                </div>
                <div class="rounded-xl bg-support-2 p-4">
                    <p class="m-0 text-caption font-semibold text-gray-50">'.Security::remove_XSS(get_lang('InactiveUsers')).'</p>
                    <p class="m-0 mt-1 text-2xl font-bold text-gray-90">'.dashboard_format_number($inactiveUsers).'</p>
                    <p class="m-0 mt-1 text-caption text-primary">'.$inactivePercent.'%</p>
                </div>
            </div>
        </div>
    ';
}

function dashboard_rows_table(string $title, array $headers, array $rows, callable $rowRenderer, string $emptyMessage): string
{
    $html = '
        <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
            <div class="border-b border-gray-25 bg-support-2 px-5 py-4">
                <h3 class="m-0 text-lg font-semibold text-gray-90">'.Security::remove_XSS($title).'</h3>
            </div>
            <div class="overflow-x-auto">
    ';

    if (empty($rows)) {
        $html .= '<div class="px-5 py-4 text-body-2 text-gray-50">'.Security::remove_XSS($emptyMessage).'</div>';
    } else {
        $html .= '<table class="table table-hover m-0 w-full">';
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.Security::remove_XSS($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= $rowRenderer($row);
        }

        $html .= '</tbody></table>';
    }

    $html .= '
            </div>
        </div>
    ';

    return $html;
}

$totalUsers = dashboard_count_table($connection, 'user');
$activeUsers = dashboard_count_table($connection, 'user', '`active` = 1');
$inactiveUsers = max(0, $totalUsers - $activeUsers);
$totalCourses = dashboard_count_table($connection, 'course');
$totalSessions = dashboard_count_table($connection, 'session');
$totalLearningPaths = dashboard_count_table($connection, 'c_lp');
$totalExercises = dashboard_count_table($connection, 'c_quiz');
$totalResourceFiles = dashboard_count_table($connection, 'resource_file');
$totalAssets = dashboard_count_table($connection, 'asset');

$recentUsers = dashboard_fetch_rows(
    $connection,
    'user',
    '`id`, `username`, `firstname`, `lastname`, `email`, `active`, `status`',
    'id'
);
$recentCourses = dashboard_fetch_rows(
    $connection,
    'course',
    '`id`, `title`, `code`',
    'id'
);
$recentSessions = dashboard_fetch_rows(
    $connection,
    'session',
    '`id`, `title`, `status`',
    'id'
);

$contentItems = [
    [
        'label' => $plugin->get_lang('LearningPaths'),
        'value' => $totalLearningPaths,
        'description' => $plugin->get_lang('LearningPathsDescription'),
    ],
    [
        'label' => $plugin->get_lang('Exercises'),
        'value' => $totalExercises,
        'description' => $plugin->get_lang('ExercisesDescription'),
    ],
    [
        'label' => $plugin->get_lang('Courses'),
        'value' => $totalCourses,
        'description' => $plugin->get_lang('CoursesDescription'),
    ],
];

$storageItems = [
    [
        'label' => $plugin->get_lang('ResourceFiles'),
        'value' => $totalResourceFiles,
        'description' => $plugin->get_lang('ResourceFilesDescription'),
    ],
    [
        'label' => $plugin->get_lang('Assets'),
        'value' => $totalAssets,
        'description' => $plugin->get_lang('AssetsDescription'),
    ],
];

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_PLUGIN_PATH).'Dashboard/css/default.css">';

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];

Display::display_header($plugin->get_lang('plugin_title'));

echo '<section class="dashboard-plugin space-y-6">';
echo '
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="m-0 text-2xl font-semibold text-gray-90">'.$plugin->get_lang('plugin_title').'</h2>
                <p class="m-0 mt-2 max-w-4xl text-body-2 text-gray-50">'.$plugin->get_lang('DashboardIntro').'</p>
            </div>
            <a class="btn btn--plain" href="'.api_get_path(WEB_CODE_PATH).'admin/index.php">
                <span class="mdi mdi-arrow-left" aria-hidden="true"></span>
                '.$plugin->get_lang('BackToAdministration').'
            </a>
        </div>
    </div>
';

echo '<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">';
echo dashboard_card($plugin->get_lang('Users'), $totalUsers, 'mdi-account-group-outline', $plugin->get_lang('ActiveUsers').': '.dashboard_format_number($activeUsers));
echo dashboard_card($plugin->get_lang('Courses'), $totalCourses, 'mdi-book-open-page-variant-outline');
echo dashboard_card($plugin->get_lang('Sessions'), $totalSessions, 'mdi-calendar-range-outline');
echo dashboard_card($plugin->get_lang('LearningPaths'), $totalLearningPaths, 'mdi-routes');
echo dashboard_card($plugin->get_lang('Exercises'), $totalExercises, 'mdi-format-list-checks');
echo dashboard_card($plugin->get_lang('ResourceFiles'), $totalResourceFiles, 'mdi-file-document-outline');
echo dashboard_card($plugin->get_lang('Assets'), $totalAssets, 'mdi-image-outline');
echo dashboard_card($plugin->get_lang('InactiveUsers'), $inactiveUsers, 'mdi-account-off-outline');
echo '</div>';

echo '<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">';
echo dashboard_status_panel($plugin->get_lang('UserStatus'), $plugin->get_lang('UserStatusSubtitle'), $activeUsers, $inactiveUsers);
echo dashboard_chart_panel(
    $plugin->get_lang('ContentOverview'),
    $plugin->get_lang('ContentOverviewSubtitle'),
    $contentItems
);
echo dashboard_chart_panel(
    $plugin->get_lang('StorageOverview'),
    $plugin->get_lang('StorageOverviewSubtitle'),
    $storageItems
);
echo '</div>';

echo '<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">';

echo dashboard_rows_table(
    $plugin->get_lang('RecentUsers'),
    [$plugin->get_lang('User'), $plugin->get_lang('Email'), $plugin->get_lang('Status')],
    $recentUsers,
    static function (array $row): string {
        $fullName = trim(($row['firstname'] ?? '').' '.($row['lastname'] ?? ''));
        $label = '' !== $fullName ? $fullName : ($row['username'] ?? '');
        $active = 1 === (int) ($row['active'] ?? 0);

        return '<tr>'
            .'<td>'.Security::remove_XSS((string) $label).'<br><span class="text-body-2 text-gray-50">@'.Security::remove_XSS((string) ($row['username'] ?? '')).'</span></td>'
            .'<td>'.Security::remove_XSS((string) ($row['email'] ?? '')).'</td>'
            .'<td><span class="badge '.($active ? 'badge--success' : 'badge--warning').'">'.($active ? get_lang('Active') : get_lang('Inactive')).'</span></td>'
            .'</tr>';
    },
    $plugin->get_lang('NoRecentUsers')
);

echo dashboard_rows_table(
    $plugin->get_lang('RecentCourses'),
    [$plugin->get_lang('Course'), $plugin->get_lang('Code')],
    $recentCourses,
    static function (array $row): string {
        return '<tr>'
            .'<td>'.Security::remove_XSS((string) ($row['title'] ?? '')).'</td>'
            .'<td><code>'.Security::remove_XSS((string) ($row['code'] ?? '')).'</code></td>'
            .'</tr>';
    },
    $plugin->get_lang('NoRecentCourses')
);

echo dashboard_rows_table(
    $plugin->get_lang('RecentSessions'),
    [$plugin->get_lang('Session'), $plugin->get_lang('Status')],
    $recentSessions,
    static function (array $row): string {
        return '<tr>'
            .'<td>'.Security::remove_XSS((string) ($row['title'] ?? '')).'</td>'
            .'<td><span class="badge badge--info">'.Security::remove_XSS((string) ($row['status'] ?? '0')).'</span></td>'
            .'</tr>';
    },
    $plugin->get_lang('NoRecentSessions')
);

echo '</div>';

echo '</section>';

Display::display_footer();

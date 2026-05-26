<?php

/* For licensing terms, see /license.txt */

/**
 * Reports catalog and initial report/role matrix.
 *
 * This page is an administrative registry of reporting pages. It documents the
 * expected audience and exposes canonical report URLs. Those canonical URLs
 * enforce the documented role matrix without changing the legacy report pages.
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../inc/lib/reports.lib.php';

api_protect_admin_script();

$view = isset($_GET['view']) ? (string) $_GET['view'] : 'catalog';
$allowedViews = ['catalog', 'matrix', 'permissions'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'catalog';
}

$toolName = get_lang('Reports catalog');

$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];

Display::display_header($toolName);
echo Display::page_header($toolName);

$baseUrl = api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php';

$actionBar = Display::toolbarAction(
    'reports-catalog-action-bar',
    [
        implode('', [
            Display::toolbarButton(
                get_lang('Reports catalog'),
                $baseUrl,
                'format-list-bulleted',
                'secondary-outline'
            ),
            Display::toolbarButton(
                get_lang('Report role matrix'),
                $baseUrl.'?view=matrix',
                'table',
                'secondary-outline'
            ),
            Display::toolbarButton(
                get_lang('Report permission categories'),
                $baseUrl.'?view=permissions',
                'shield-key-outline',
                'secondary-outline'
            ),
        ]),
        Display::toolbarButton(
            get_lang('Back'),
            api_get_path(WEB_CODE_PATH).'admin/index.php',
            'arrow-left',
            'secondary-outline'
        ),
    ]
);

echo '<div class="w-full px-4 md:px-8 pb-8 space-y-5">';
echo $actionBar;

echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
echo '<h2 class="text-2xl font-semibold mb-2">'.Security::remove_XSS($toolName).'</h2>';
echo '<p class="text-sm text-gray-600 m-0">'
    .Security::remove_XSS(get_lang('This registry lists known reporting pages, their category, canonical URL, legacy URL and expected role audience. Canonical URLs enforce the documented report role matrix.'))
    .'</p>';
echo '</section>';

if ('matrix' === $view) {
    renderReportsRoleMatrix();
} elseif ('permissions' === $view) {
    renderReportsPermissionCategories();
} else {
    renderReportsCatalog();
}

echo '</div>';

Display::display_footer();

function renderReportsCatalog(): void
{
    $categories = ReportRegistry::getCategories();
    $reportsByCategory = ReportRegistry::getReportsByCategory();

    foreach ($categories as $categoryId => $categoryLabel) {
        $reports = $reportsByCategory[$categoryId] ?? [];
        if (empty($reports)) {
            continue;
        }

        echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
        echo '<div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between mb-4">';
        echo '<h3 class="text-xl font-semibold m-0">'.Security::remove_XSS($categoryLabel).'</h3>';
        echo '<span class="inline-flex self-start md:self-auto rounded-full bg-gray-15 px-3 py-1 text-xs font-semibold text-gray-700">'
            .count($reports).' '.Security::remove_XSS(get_lang('reports'))
            .'</span>';
        echo '</div>';

        echo '<div class="overflow-x-auto">';
        echo '<table class="data_table w-full">';
        echo '<thead><tr>';
        echo '<th>'.get_lang('Report').'</th>';
        echo '<th>'.get_lang('Description').'</th>';
        echo '<th>'.get_lang('Canonical URL').'</th>';
        echo '<th>'.get_lang('Legacy URL').'</th>';
        echo '<th>'.get_lang('Permission category').'</th>';
        echo '<th>'.get_lang('Roles').'</th>';
        echo '</tr></thead><tbody>';

        foreach ($reports as $report) {
            $url = ReportRegistry::getFriendlyUrl($report);
            $legacyUrl = ReportRegistry::getLegacyUrl($report);
            $roles = renderRoleBadges($report['roles'] ?? []);

            echo '<tr>';
            echo '<td class="font-semibold">'.Security::remove_XSS($report['title']).'</td>';
            echo '<td>'.Security::remove_XSS($report['description'] ?? '').'</td>';
            echo '<td><a href="'.Security::remove_XSS($url).'" class="text-primary underline">'
                .Security::remove_XSS($url)
                .'</a></td>';
            echo '<td><code>'.Security::remove_XSS($legacyUrl).'</code></td>';
            echo '<td><code>'.Security::remove_XSS($report['permission'] ?? '').'</code></td>';
            echo '<td>'.$roles.'</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '</section>';
    }
}

function renderReportsRoleMatrix(): void
{
    $roles = ReportRegistry::getRoles();
    $matrix = ReportRegistry::getRoleMatrix();

    echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
    echo '<h3 class="text-xl font-semibold mb-3">'.get_lang('Report role matrix').'</h3>';
    echo '<p class="text-sm text-gray-600">'
        .Security::remove_XSS(get_lang('This matrix documents the expected audience for each report and is enforced by the canonical report entry point.'))
        .'</p>';

    echo '<div class="overflow-x-auto">';
    echo '<table class="data_table w-full">';
    echo '<thead><tr>';
    echo '<th>'.get_lang('Report').'</th>';
    foreach ($roles as $roleLabel) {
        echo '<th class="text-center">'.Security::remove_XSS($roleLabel).'</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($matrix as $row) {
        $report = $row['report'];
        echo '<tr>';
        echo '<td>';
        echo '<div class="font-semibold">'.Security::remove_XSS($report['title']).'</div>';
        echo '<div class="text-xs text-gray-600">'.Security::remove_XSS($report['permission'] ?? '').'</div>';
        echo '</td>';

        foreach (array_keys($roles) as $role) {
            $allowed = !empty($row['roles'][$role]);
            echo '<td class="text-center">';
            echo $allowed
                ? '<span class="mdi mdi-check-circle text-success" title="'.get_lang('Allowed').'"></span>'
                : '<span class="mdi mdi-minus-circle-outline text-gray-400" title="'.get_lang('Not allowed').'"></span>';
            echo '</td>';
        }

        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
    echo '</section>';
}

function renderReportsPermissionCategories(): void
{
    $permissions = ReportRegistry::getPermissionCategories();

    echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
    echo '<h3 class="text-xl font-semibold mb-3">'.get_lang('Report permission categories').'</h3>';
    echo '<p class="text-sm text-gray-600">'
        .Security::remove_XSS(get_lang('These keys group reports by access scope and are used by the report registry. They can later be mapped to finer-grained platform permissions.'))
        .'</p>';

    echo '<div class="grid gap-4 md:grid-cols-2">';
    foreach ($permissions as $permissionKey => $permission) {
        echo '<article class="rounded-xl border border-gray-25 bg-gray-10 p-4">';
        echo '<div class="mb-2"><code>'.Security::remove_XSS($permissionKey).'</code></div>';
        if (!empty($permission['permission_slug'])) {
            echo '<div class="mb-2 text-xs text-gray-600">'.get_lang('Permission').': <code>'
                .Security::remove_XSS($permission['permission_slug'])
                .'</code></div>';
        }
        echo '<h4 class="text-base font-semibold m-0 mb-2">'.Security::remove_XSS($permission['label']).'</h4>';
        echo '<p class="text-sm text-gray-600">'.Security::remove_XSS($permission['description']).'</p>';
        echo '<div class="mt-3">'.renderRoleBadges($permission['roles'] ?? []).'</div>';
        echo '</article>';
    }
    echo '</div>';
    echo '</section>';
}

function renderRoleBadges(array $roles): string
{
    $labels = ReportRegistry::getRoles();
    $html = '';

    foreach ($roles as $role) {
        $label = $labels[$role] ?? $role;
        $html .= '<span class="inline-flex rounded-full bg-support-2 px-2.5 py-1 text-xs font-semibold text-gray-800 mr-1 mb-1">'
            .Security::remove_XSS($label)
            .'</span>';
    }

    return $html ?: '<span class="text-gray-500">-</span>';
}

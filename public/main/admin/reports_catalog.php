<?php

/* For licensing terms, see /license.txt */

/**
 * Reports catalog and initial report/role matrix.
 *
 * This page is an administrative registry of reporting pages. It documents the
 * expected audience and exposes stable canonical report URLs. Canonical URLs
 * route to the current implementation while the legacy URL remains visible as
 * an audit/reference value.
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
    .Security::remove_XSS(get_lang('This registry lists known reporting pages, their category, canonical URL, legacy URL and expected role audience. Canonical URLs route to the current report implementation and enforce the documented report role matrix.'))
    .'</p>';
echo '</section>';

if ('matrix' === $view) {
    renderReportsRoleMatrix();
} elseif ('permissions' === $view) {
    renderReportsPermissionCategories();
} else {
    renderReportsCatalog();
    renderCourseContextDialog();
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
            $canonicalLinkAttributes = '';
            if (ReportRegistry::requiresCourseContext($report)) {
                $canonicalLinkAttributes = ' data-report-course-context="1"'
                    .' data-report-id="'.htmlspecialchars((string) ($report['id'] ?? ''), ENT_QUOTES, 'UTF-8').'"'
                    .' data-report-title="'.htmlspecialchars((string) ($report['title'] ?? ''), ENT_QUOTES, 'UTF-8').'"';
            }

            echo '<td><a href="'.Security::remove_XSS($url).'" class="text-primary underline"'.$canonicalLinkAttributes.'>'
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

function renderCourseContextDialog(): void
{
    $contexts = ReportRegistry::getSelectableCourseContexts();
    $reportUrl = api_get_path(WEB_CODE_PATH).'admin/report.php';

    echo '<dialog id="report-course-context-dialog" class="w-full max-w-2xl rounded-xl border border-gray-25 bg-white p-0 shadow-xl">';
    echo '<form method="get" action="'.Security::remove_XSS($reportUrl).'" class="m-0">';
    echo '<input type="hidden" id="report-course-context-id" name="id" value="">';

    echo '<div class="border-b border-gray-25 px-5 py-4">';
    echo '<h3 id="report-course-context-title" class="m-0 text-xl font-semibold"></h3>';
    echo '</div>';

    echo '<div class="space-y-4 px-5 py-5">';
    if (empty($contexts)) {
        echo Display::return_message(get_lang('No results available'), 'warning');
    } else {
        echo '<div>';
        echo '<label for="report-course-context-select" class="mb-2 block font-semibold">'.get_lang('Course').'</label>';
        echo '<select id="report-course-context-select" name="course_context" class="form-control" required>';
        echo '<option value="">'.get_lang('Select').'</option>';

        foreach ($contexts as $key => $context) {
            $label = $context['title'];

            if ($context['session_id'] > 0 && '' !== $context['session_name']) {
                $label .= ' - '.$context['session_name'];
            }

            echo '<option value="'.htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8').'">'
                .Security::remove_XSS($label)
                .'</option>';
        }

        echo '</select>';
        echo '</div>';
    }
    echo '</div>';

    echo '<div class="flex justify-end gap-2 border-t border-gray-25 px-5 py-4">';
    echo '<button type="button" id="report-course-context-cancel" class="btn btn--secondary-outline">'.get_lang('Cancel').'</button>';
    if (!empty($contexts)) {
        echo '<button type="submit" class="btn btn--primary">'.get_lang('Select').'</button>';
    }
    echo '</div>';
    echo '</form>';
    echo '</dialog>';

    echo <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('report-course-context-dialog')
    const reportIdInput = document.getElementById('report-course-context-id')
    const title = document.getElementById('report-course-context-title')
    const courseSelect = document.getElementById('report-course-context-select')
    const cancelButton = document.getElementById('report-course-context-cancel')

    if (!dialog || typeof dialog.showModal !== 'function') {
        return
    }

    document.querySelectorAll('[data-report-course-context="1"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault()

            reportIdInput.value = link.dataset.reportId || ''
            title.textContent = link.dataset.reportTitle || ''

            if (courseSelect) {
                courseSelect.value = ''
            }

            dialog.showModal()
        })
    })

    cancelButton?.addEventListener('click', () => dialog.close())

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close()
        }
    })
})
</script>
HTML;
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

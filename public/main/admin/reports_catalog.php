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
                $contextRequirements = ReportRegistry::getContextRequirements($report);
                $canonicalLinkAttributes = ' data-report-course-context="1"'
                    .' data-report-id="'.htmlspecialchars((string) ($report['id'] ?? ''), ENT_QUOTES, 'UTF-8').'"'
                    .' data-report-title="'.htmlspecialchars((string) ($report['title'] ?? ''), ENT_QUOTES, 'UTF-8').'"'
                    .' data-report-context-requirements="'
                    .htmlspecialchars(implode(',', $contextRequirements), ENT_QUOTES, 'UTF-8')
                    .'"';
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
    $apiBaseUrl = rtrim(api_get_path(WEB_PATH), '/').'/api';
    $autoReportId = isset($_GET['select_report']) ? (string) $_GET['select_report'] : '';
    $autoCourseContext = isset($_GET['course_context']) ? (string) $_GET['course_context'] : '';

    echo '<dialog id="report-course-context-dialog" class="w-full max-w-2xl rounded-xl border border-gray-25 bg-white p-0 shadow-xl">';
    echo '<form method="get" action="'.Security::remove_XSS($reportUrl).'" class="m-0" id="report-context-form">';
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

            echo '<option value="'.htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8').'"'
                .' data-course-resource-node-id="'.(int) ($context['resource_node_id'] ?? 0).'">'
                .Security::remove_XSS($label)
                .'</option>';
        }

        echo '</select>';
        echo '</div>';

        echo '<div id="report-user-context-wrapper" class="hidden">';
        echo '<label for="report-user-context-select" class="mb-2 block font-semibold">'.get_lang('User').'</label>';
        echo '<select id="report-user-context-select" name="user_id" class="form-control" disabled>';
        echo '<option value="">'.get_lang('Select').'</option>';
        echo '</select>';
        echo '</div>';

        echo '<div id="report-exercise-context-wrapper" class="hidden">';
        echo '<label for="report-exercise-context-select" class="mb-2 block font-semibold">'.get_lang('Exercise').'</label>';
        echo '<select id="report-exercise-context-select" name="exercise_id" class="form-control" disabled>';
        echo '<option value="">'.get_lang('Select').'</option>';
        echo '</select>';
        echo '</div>';

        echo '<div id="report-learning-path-context-wrapper" class="hidden">';
        echo '<label for="report-learning-path-context-select" class="mb-2 block font-semibold">'.get_lang('Learning path').'</label>';
        echo '<select id="report-learning-path-context-select" name="learning_path_id" class="form-control" disabled>';
        echo '<option value="">'.get_lang('Select').'</option>';
        echo '</select>';
        echo '</div>';

        echo '<div id="report-attempt-context-wrapper" class="hidden">';
        echo '<label for="report-attempt-context-select" class="mb-2 block font-semibold">'.get_lang('Attempt').'</label>';
        echo '<select id="report-attempt-context-select" name="attempt_id" class="form-control" disabled>';
        echo '<option value="">'.get_lang('Select').'</option>';
        echo '</select>';
        echo '</div>';

        echo '<p id="report-context-status" class="m-0 hidden text-sm text-gray-600"></p>';
    }
    echo '</div>';

    echo '<div class="flex justify-end gap-2 border-t border-gray-25 px-5 py-4">';
    echo '<button type="button" id="report-course-context-cancel" class="btn btn--secondary-outline">'.get_lang('Cancel').'</button>';
    if (!empty($contexts)) {
        echo '<button type="submit" id="report-context-submit" class="btn btn--primary" disabled>'.get_lang('Open').'</button>';
    }
    echo '</div>';
    echo '</form>';
    echo '</dialog>';

    $jsConfig = json_encode(
        [
            'apiBaseUrl' => $apiBaseUrl,
            'autoReportId' => $autoReportId,
            'autoCourseContext' => $autoCourseContext,
            'selectLabel' => get_lang('Select'),
            'loadingLabel' => get_lang('Loading'),
            'noResultsLabel' => get_lang('No results available'),
            'errorLabel' => get_lang('An error occurred'),
        ],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    echo '<script>window.reportCatalogContextConfig = '.$jsConfig.';</script>';

    echo <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const config = window.reportCatalogContextConfig || {}
    const dialog = document.getElementById('report-course-context-dialog')
    const form = document.getElementById('report-context-form')
    const reportIdInput = document.getElementById('report-course-context-id')
    const title = document.getElementById('report-course-context-title')
    const courseSelect = document.getElementById('report-course-context-select')
    const userWrapper = document.getElementById('report-user-context-wrapper')
    const userSelect = document.getElementById('report-user-context-select')
    const exerciseWrapper = document.getElementById('report-exercise-context-wrapper')
    const exerciseSelect = document.getElementById('report-exercise-context-select')
    const learningPathWrapper = document.getElementById('report-learning-path-context-wrapper')
    const learningPathSelect = document.getElementById('report-learning-path-context-select')
    const attemptWrapper = document.getElementById('report-attempt-context-wrapper')
    const attemptSelect = document.getElementById('report-attempt-context-select')
    const status = document.getElementById('report-context-status')
    const submitButton = document.getElementById('report-context-submit')
    const cancelButton = document.getElementById('report-course-context-cancel')

    if (!dialog || typeof dialog.showModal !== 'function' || !form || !courseSelect) {
        return
    }

    let requirements = new Set()
    let loadSequence = 0

    const setStatus = (message = '') => {
        if (!status) {
            return
        }

        status.textContent = message
        status.classList.toggle('hidden', !message)
    }

    const clearSelect = (select) => {
        if (!select) {
            return
        }

        select.innerHTML = ''
        const option = document.createElement('option')
        option.value = ''
        option.textContent = config.selectLabel || 'Select'
        select.append(option)
        select.value = ''
    }

    const setSelectLoading = (select) => {
        if (!select) {
            return
        }

        select.innerHTML = ''
        const option = document.createElement('option')
        option.value = ''
        option.textContent = config.loadingLabel || 'Loading'
        select.append(option)
        select.disabled = true
    }

    const fillSelect = (select, items, valueKey, labelBuilder) => {
        clearSelect(select)

        items.forEach((item) => {
            const value = Number(item?.[valueKey] || 0)
            if (value <= 0) {
                return
            }

            const option = document.createElement('option')
            option.value = String(value)
            option.textContent = labelBuilder(item)
            select.append(option)
        })

        select.disabled = false

        if (select.options.length <= 1) {
            setStatus(config.noResultsLabel || 'No results available')
        }
    }

    const parseCourseContext = () => {
        const match = String(courseSelect.value || '').match(/^(\d+):(\d+)$/)
        if (!match) {
            return null
        }

        const selectedOption = courseSelect.options[courseSelect.selectedIndex]

        return {
            cid: Number(match[1]),
            sid: Number(match[2]),
            node: Number(selectedOption?.dataset?.courseResourceNodeId || 0),
        }
    }

    const updateSubmitState = () => {
        if (!submitButton) {
            return
        }

        let ready = Boolean(parseCourseContext())

        if (requirements.has('user')) {
            ready = ready && Number(userSelect?.value || 0) > 0
        }

        if (requirements.has('exercise')) {
            ready = ready && Number(exerciseSelect?.value || 0) > 0
        }

        if (requirements.has('attempt')) {
            ready = ready && Number(attemptSelect?.value || 0) > 0
        }

        if (requirements.has('learning_path')) {
            ready = ready && Number(learningPathSelect?.value || 0) > 0
        }

        submitButton.disabled = !ready
    }

    const requestJson = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }

        return await response.json()
    }

    const loadUsers = async (context, sequence) => {
        if (!requirements.has('user') || !userSelect) {
            return
        }

        setSelectLoading(userSelect)

        const params = new URLSearchParams({
            cid: String(context.cid),
            sid: String(context.sid),
            page: '1',
            itemsPerPage: '1000',
        })

        const payload = await requestJson(`${config.apiBaseUrl}/course-reporting/learners?${params.toString()}`)
        if (sequence !== loadSequence) {
            return
        }

        fillSelect(
            userSelect,
            Array.isArray(payload.items) ? payload.items : [],
            'id',
            (item) => item.fullName || [item.firstname, item.lastname].filter(Boolean).join(' ') || item.username || `#${item.id}`,
        )
    }

    const loadExercises = async (context, sequence) => {
        if (!requirements.has('exercise') || !exerciseSelect) {
            return
        }

        setSelectLoading(exerciseSelect)

        const params = new URLSearchParams({
            cid: String(context.cid),
            sid: String(context.sid),
            gid: '0',
        })

        const payload = await requestJson(`${config.apiBaseUrl}/exercise/list?${params.toString()}`)
        if (sequence !== loadSequence) {
            return
        }

        fillSelect(
            exerciseSelect,
            Array.isArray(payload.items) ? payload.items : [],
            'iid',
            (item) => item.title || `#${item.iid}`,
        )
    }

    const loadLearningPaths = async (context, sequence) => {
        if (!requirements.has('learning_path') || !learningPathSelect) {
            return
        }

        setSelectLoading(learningPathSelect)

        if (Number(context.node || 0) <= 0) {
            clearSelect(learningPathSelect)
            learningPathSelect.disabled = true
            setStatus(config.errorLabel || 'An error occurred')
            return
        }

        const params = new URLSearchParams({
            'resourceNode.parent': String(context.node),
            cid: String(context.cid),
            sid: String(context.sid),
            gid: '0',
            pagination: 'false',
        })

        const payload = await requestJson(`${config.apiBaseUrl}/learning_paths?${params.toString()}`)
        if (sequence !== loadSequence) {
            return
        }

        const items = Array.isArray(payload)
            ? payload
            : Array.isArray(payload.items)
                ? payload.items
                : Array.isArray(payload.member)
                    ? payload.member
                    : Array.isArray(payload['hydra:member'])
                        ? payload['hydra:member']
                        : []

        fillSelect(
            learningPathSelect,
            items,
            'iid',
            (item) => item.title || `#${item.iid}`,
        )
    }

    const loadAttempts = async () => {
        if (!requirements.has('attempt') || !attemptSelect) {
            return
        }

        const context = parseCourseContext()
        const exerciseId = Number(exerciseSelect?.value || 0)

        clearSelect(attemptSelect)
        attemptSelect.disabled = true
        updateSubmitState()

        if (!context || exerciseId <= 0) {
            return
        }

        const sequence = ++loadSequence
        setStatus('')
        setSelectLoading(attemptSelect)

        const params = new URLSearchParams({
            cid: String(context.cid),
            sid: String(context.sid),
            gid: '0',
        })

        try {
            const payload = await requestJson(
                `${config.apiBaseUrl}/exercise/runtime/${exerciseId}/attempts?${params.toString()}`,
            )

            if (sequence !== loadSequence) {
                return
            }

            fillSelect(
                attemptSelect,
                Array.isArray(payload.attempts) ? payload.attempts : [],
                'attemptId',
                (item) => {
                    const learner = item.fullName || item.username || `#${item.userId || ''}`
                    const date = item.completedAt || item.startedAt || ''
                    return `#${item.attemptId} - ${learner}${date ? ` - ${date}` : ''}`
                },
            )
        } catch (error) {
            if (sequence === loadSequence) {
                clearSelect(attemptSelect)
                attemptSelect.disabled = true
                setStatus(config.errorLabel || 'An error occurred')
            }
        } finally {
            updateSubmitState()
        }
    }

    const loadCourseDependencies = async () => {
        const context = parseCourseContext()
        const sequence = ++loadSequence

        clearSelect(userSelect)
        clearSelect(exerciseSelect)
        clearSelect(learningPathSelect)
        clearSelect(attemptSelect)

        if (userSelect) {
            userSelect.disabled = !requirements.has('user')
        }

        if (exerciseSelect) {
            exerciseSelect.disabled = !requirements.has('exercise')
        }

        if (learningPathSelect) {
            learningPathSelect.disabled = !requirements.has('learning_path')
        }

        if (attemptSelect) {
            attemptSelect.disabled = true
        }

        setStatus('')
        updateSubmitState()

        if (!context) {
            return
        }

        try {
            const jobs = []

            if (requirements.has('user')) {
                jobs.push(loadUsers(context, sequence))
            }

            if (requirements.has('exercise')) {
                jobs.push(loadExercises(context, sequence))
            }

            if (requirements.has('learning_path')) {
                jobs.push(loadLearningPaths(context, sequence))
            }

            await Promise.all(jobs)
        } catch (error) {
            if (sequence === loadSequence) {
                setStatus(config.errorLabel || 'An error occurred')
            }
        } finally {
            updateSubmitState()
        }
    }

    const openReportDialog = (link, preferredCourseContext = '') => {
        reportIdInput.value = link.dataset.reportId || ''
        title.textContent = link.dataset.reportTitle || ''

        requirements = new Set(
            String(link.dataset.reportContextRequirements || '')
                .split(',')
                .map((value) => value.trim())
                .filter(Boolean),
        )

        userWrapper?.classList.toggle('hidden', !requirements.has('user'))
        exerciseWrapper?.classList.toggle('hidden', !requirements.has('exercise'))
        learningPathWrapper?.classList.toggle('hidden', !requirements.has('learning_path'))
        attemptWrapper?.classList.toggle('hidden', !requirements.has('attempt'))

        clearSelect(userSelect)
        clearSelect(exerciseSelect)
        clearSelect(learningPathSelect)
        clearSelect(attemptSelect)

        if (userSelect) {
            userSelect.disabled = true
        }
        if (exerciseSelect) {
            exerciseSelect.disabled = true
        }
        if (learningPathSelect) {
            learningPathSelect.disabled = true
        }
        if (attemptSelect) {
            attemptSelect.disabled = true
        }

        courseSelect.value = preferredCourseContext || ''
        setStatus('')
        updateSubmitState()
        dialog.showModal()

        if (courseSelect.value) {
            loadCourseDependencies()
        }
    }

    document.querySelectorAll('[data-report-course-context="1"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault()
            openReportDialog(link)
        })
    })

    courseSelect.addEventListener('change', loadCourseDependencies)
    exerciseSelect?.addEventListener('change', async () => {
        updateSubmitState()

        if (requirements.has('attempt')) {
            await loadAttempts()
        }
    })
    userSelect?.addEventListener('change', updateSubmitState)
    learningPathSelect?.addEventListener('change', updateSubmitState)
    attemptSelect?.addEventListener('change', updateSubmitState)

    cancelButton?.addEventListener('click', () => dialog.close())

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close()
        }
    })

    if (config.autoReportId) {
        const link = Array.from(document.querySelectorAll('[data-report-id]')).find(
            (candidate) => candidate.dataset.reportId === String(config.autoReportId),
        )

        if (link) {
            openReportDialog(link, String(config.autoCourseContext || ''))
        }
    }
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

<?php

/* For licensing terms, see /license.txt */

/**
 * Canonical report entry point.
 *
 * This router gives reports a cohesive URL and routes each catalog entry to
 * the current implementation, preferring modern Vue/Symfony reporting pages
 * when they exist. It also applies the documented role matrix for links opened
 * through the reports catalog.
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../inc/lib/reports.lib.php';

$reportId = isset($_GET['id']) ? (string) $_GET['id'] : '';
if ('' === $reportId) {
    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php');
    exit;
}

$report = ReportRegistry::assertCurrentUserCanAccessReport($reportId);

$query = $_GET;
unset($query['id']);

$target = ReportRegistry::getEntryUrl($report);

if (ReportRegistry::requiresCourseContext($report)) {
    $courseContexts = ReportRegistry::getSelectableCourseContexts();
    $selectedContext = isset($query['course_context']) ? (string) $query['course_context'] : '';
    unset($query['course_context']);

    $courseId = isset($query['cid']) ? (int) $query['cid'] : 0;
    $sessionId = isset($query['sid']) ? (int) $query['sid'] : 0;

    if ('' !== $selectedContext) {
        [$courseId, $sessionId] = parseCourseContext($selectedContext);
    }

    if ($courseId <= 0) {
        displayCourseContextSelector($report, $courseContexts);
        exit;
    }

    $resolvedContext = resolveCourseContext($courseContexts, $courseId, $sessionId);
    if (null === $resolvedContext) {
        api_not_allowed(true);
    }

    $query['cid'] = $resolvedContext['course_id'];

    if ($resolvedContext['session_id'] > 0) {
        $query['sid'] = $resolvedContext['session_id'];
    } else {
        unset($query['sid']);
    }

    $target = resolveCourseAwareReportTarget($target, $resolvedContext['course_id']);
}

if (!empty($query)) {
    $target .= (str_contains($target, '?') ? '&' : '?').http_build_query($query);
}

header('Location: '.$target);
exit;

/**
 * Resolve placeholders used by modern course tool routes.
 */
function resolveCourseAwareReportTarget(string $target, int $courseId): string
{
    if (!str_contains($target, '{course_resource_node_id}')) {
        return $target;
    }

    $course = api_get_course_entity($courseId);
    $resourceNodeId = (int) ($course?->getResourceNode()?->getId() ?? 0);

    if ($resourceNodeId <= 0) {
        api_not_allowed(true);
    }

    return str_replace('{course_resource_node_id}', (string) $resourceNodeId, $target);
}

/**
 * @return array{0: int, 1: int}
 */
function parseCourseContext(string $value): array
{
    if (!preg_match('/^(\d+):(\d+)$/', $value, $matches)) {
        api_not_allowed(true);
    }

    return [(int) $matches[1], (int) $matches[2]];
}

function resolveCourseContext(array $contexts, int $courseId, int $sessionId): ?array
{
    $exactKey = $courseId.':'.$sessionId;
    if (isset($contexts[$exactKey])) {
        return $contexts[$exactKey];
    }

    if ($sessionId > 0) {
        return null;
    }

    $matches = array_values(array_filter(
        $contexts,
        static fn (array $context): bool => $context['course_id'] === $courseId
    ));

    if (1 === count($matches)) {
        return $matches[0];
    }

    return null;
}

function displayCourseContextSelector(array $report, array $contexts): void
{
    global $interbreadcrumb;

    $toolName = (string) ($report['title'] ?? get_lang('Reports catalog'));

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php',
        'name' => get_lang('Reports catalog'),
    ];

    Display::display_header($toolName);
    echo Display::page_header($toolName);

    echo '<div class="w-full px-4 md:px-8 pb-8 space-y-5">';
    echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
    echo '<p class="text-sm text-gray-600 mb-4">'
        .Security::remove_XSS((string) ($report['description'] ?? ''))
        .'</p>';

    if (empty($contexts)) {
        echo Display::return_message(get_lang('No results available'), 'warning');
    } else {
        echo '<form method="get" action="'.Security::remove_XSS(api_get_self()).'" class="max-w-2xl space-y-4">';
        echo '<input type="hidden" name="id" value="'.Security::remove_XSS((string) ($report['id'] ?? '')).'">';

        echo '<div>';
        echo '<label for="course_context" class="block font-semibold mb-2">'.get_lang('Course').'</label>';
        echo '<select id="course_context" name="course_context" class="form-control" required>';
        echo '<option value="">'.get_lang('Select').'</option>';

        foreach ($contexts as $key => $context) {
            $label = $context['title'];

            if ($context['session_id'] > 0 && '' !== $context['session_name']) {
                $label .= ' - '.$context['session_name'];
            }

            echo '<option value="'.Security::remove_XSS($key).'">'.Security::remove_XSS($label).'</option>';
        }

        echo '</select>';
        echo '</div>';

        echo '<div class="flex gap-2">';
        echo '<button type="submit" class="btn btn--primary">'.get_lang('Select').'</button>';
        echo '<a class="btn btn--secondary-outline" href="'
            .Security::remove_XSS(api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php')
            .'">'.get_lang('Back').'</a>';
        echo '</div>';
        echo '</form>';
    }
    echo '</section>';
    echo '</div>';

    Display::display_footer();
}

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

    $courseId = (int) $resolvedContext['course_id'];
    $sessionId = (int) $resolvedContext['session_id'];
    $query['cid'] = $courseId;

    if ($sessionId > 0) {
        $query['sid'] = $sessionId;
    } else {
        unset($query['sid']);
    }

    $contextValues = collectReportContextValues($query);
    $requirements = ReportRegistry::getContextRequirements($report);

    foreach ($requirements as $requirement) {
        $valueKey = $requirement.'_id';
        if (($contextValues[$valueKey] ?? 0) <= 0) {
            redirectToCatalogContextDialog($reportId, $courseId, $sessionId);
        }
    }

    if (
        in_array('user', $requirements, true)
        && !isUserInCourseContext($courseId, $sessionId, $contextValues['user_id'])
    ) {
        api_not_allowed(true);
    }

    if (
        in_array('exercise', $requirements, true)
        && !isExerciseInCourseContext($courseId, $sessionId, $contextValues['exercise_id'])
    ) {
        api_not_allowed(true);
    }

    if (
        in_array('attempt', $requirements, true)
        && !isAttemptInExerciseContext(
            $courseId,
            $sessionId,
            $contextValues['exercise_id'],
            $contextValues['attempt_id']
        )
    ) {
        api_not_allowed(true);
    }

    if (
        in_array('learning_path', $requirements, true)
        && !isLearningPathInCourseContext($courseId, $sessionId, $contextValues['learning_path_id'])
    ) {
        api_not_allowed(true);
    }

    $target = resolveCourseAwareReportTarget(
        $target,
        $report,
        $courseId,
        $sessionId,
        $contextValues
    );

    if (str_contains(ReportRegistry::getEntryUrl($report), '{session_id}')) {
        unset($query['sid']);
    }

    unset(
        $query['exercise_id'],
        $query['attempt_id'],
        $query['user_id'],
        $query['learning_path_id']
    );
}

if (!empty($query)) {
    $target .= (str_contains($target, '?') ? '&' : '?').http_build_query($query);
}

header('Location: '.$target);
exit;

/**
 * Resolve placeholders used by canonical course-aware report targets.
 *
 * The catalogue keeps legacy URLs as references, but canonical routing can
 * point to modern Vue pages or to a more specific legacy report when the
 * required context is known.
 *
 * @param array<string, mixed> $report
 * @param array<string, int>   $contextValues
 */
function resolveCourseAwareReportTarget(
    string $target,
    array $report,
    int $courseId,
    int $sessionId,
    array $contextValues
): string {
    if (str_contains($target, '{course_resource_node_id}')) {
        $course = api_get_course_entity($courseId);
        $resourceNodeId = (int) ($course?->getResourceNode()?->getId() ?? 0);

        if ($resourceNodeId <= 0) {
            api_not_allowed(true);
        }

        $target = str_replace('{course_resource_node_id}', (string) $resourceNodeId, $target);
    }

    if (str_contains($target, '{gradebook_category_id}')) {
        $categoryId = resolveGradebookRootCategoryId($courseId, $sessionId);

        if ($categoryId <= 0) {
            $fallback = (string) ($report['fallback_entry_url'] ?? '');
            if ('' === $fallback) {
                api_not_allowed(true);
            }

            return $fallback;
        }

        $target = str_replace('{gradebook_category_id}', (string) $categoryId, $target);
    }

    $target = str_replace('{session_id}', (string) $sessionId, $target);

    foreach (['user_id', 'exercise_id', 'attempt_id', 'learning_path_id'] as $key) {
        $placeholder = '{'.$key.'}';
        if (!str_contains($target, $placeholder)) {
            continue;
        }

        $value = (int) ($contextValues[$key] ?? 0);
        if ($value <= 0) {
            api_not_allowed(true);
        }

        $target = str_replace($placeholder, (string) $value, $target);
    }

    return $target;
}

/**
 * @param array<string, mixed> $query
 *
 * @return array{user_id: int, exercise_id: int, attempt_id: int, learning_path_id: int}
 */
function collectReportContextValues(array $query): array
{
    return [
        'user_id' => isset($query['user_id']) ? (int) $query['user_id'] : 0,
        'exercise_id' => isset($query['exercise_id']) ? (int) $query['exercise_id'] : 0,
        'attempt_id' => isset($query['attempt_id']) ? (int) $query['attempt_id'] : 0,
        'learning_path_id' => isset($query['learning_path_id']) ? (int) $query['learning_path_id'] : 0,
    ];
}

function redirectToCatalogContextDialog(string $reportId, int $courseId, int $sessionId): never
{
    $url = api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php?'.http_build_query([
        'select_report' => $reportId,
        'course_context' => $courseId.':'.$sessionId,
    ]);

    header('Location: '.$url);
    exit;
}

function resolveGradebookRootCategoryId(int $courseId, int $sessionId): int
{
    $categories = Category::load(
        null,
        null,
        $courseId,
        null,
        null,
        $sessionId,
        'ORDER BY id'
    );

    if (empty($categories)) {
        $categories = Category::load(
            0,
            null,
            $courseId,
            null,
            null,
            $sessionId,
            'ORDER BY id'
        );
    }

    if (empty($categories) || !isset($categories[0])) {
        return 0;
    }

    return (int) $categories[0]->get_id();
}

function isUserInCourseContext(int $courseId, int $sessionId, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $statusToFilter = $sessionId > 0 ? 0 : STUDENT;
    $users = CourseManager::getUserListFromCourseId(
        $courseId,
        $sessionId,
        null,
        null,
        $statusToFilter
    );

    foreach ($users as $user) {
        if ((int) ($user['user_id'] ?? 0) === $userId) {
            return true;
        }
    }

    return false;
}

function isExerciseInCourseContext(int $courseId, int $sessionId, int $exerciseId): bool
{
    if ($exerciseId <= 0) {
        return false;
    }

    $entityManager = Database::getManager();
    $course = $entityManager->find(\Chamilo\CoreBundle\Entity\Course::class, $courseId);
    if (!$course instanceof \Chamilo\CoreBundle\Entity\Course) {
        return false;
    }

    $session = null;
    if ($sessionId > 0) {
        $session = $entityManager->find(\Chamilo\CoreBundle\Entity\Session::class, $sessionId);
        if (!$session instanceof \Chamilo\CoreBundle\Entity\Session) {
            return false;
        }
    }

    $repository = $entityManager->getRepository(\Chamilo\CourseBundle\Entity\CQuiz::class);
    if (!$repository instanceof \Chamilo\CourseBundle\Repository\CQuizRepository) {
        return false;
    }

    $queryBuilder = $repository->findAllByCourse(
        $course,
        $session,
        null,
        null,
        false,
        null,
        false
    );

    $exercise = $queryBuilder
        ->andWhere('resource.iid = :reportExerciseId')
        ->setParameter('reportExerciseId', $exerciseId)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    return $exercise instanceof \Chamilo\CourseBundle\Entity\CQuiz;
}

function isLearningPathInCourseContext(int $courseId, int $sessionId, int $learningPathId): bool
{
    if ($learningPathId <= 0) {
        return false;
    }

    $entityManager = Database::getManager();
    $course = $entityManager->find(\Chamilo\CoreBundle\Entity\Course::class, $courseId);
    if (!$course instanceof \Chamilo\CoreBundle\Entity\Course) {
        return false;
    }

    $session = null;
    if ($sessionId > 0) {
        $session = $entityManager->find(\Chamilo\CoreBundle\Entity\Session::class, $sessionId);
        if (!$session instanceof \Chamilo\CoreBundle\Entity\Session) {
            return false;
        }
    }

    $repository = $entityManager->getRepository(\Chamilo\CourseBundle\Entity\CLp::class);
    if (!$repository instanceof \Chamilo\CourseBundle\Repository\CLpRepository) {
        return false;
    }

    $learningPath = $repository
        ->findAllByCourse($course, $session, null, null, false)
        ->andWhere('resource.iid = :reportLearningPathId')
        ->setParameter('reportLearningPathId', $learningPathId)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    return $learningPath instanceof \Chamilo\CourseBundle\Entity\CLp;
}

function isAttemptInExerciseContext(
    int $courseId,
    int $sessionId,
    int $exerciseId,
    int $attemptId
): bool {
    if ($exerciseId <= 0 || $attemptId <= 0) {
        return false;
    }

    $attempt = Database::getManager()
        ->getRepository(\Chamilo\CoreBundle\Entity\TrackEExercise::class)
        ->find($attemptId);

    if (!$attempt instanceof \Chamilo\CoreBundle\Entity\TrackEExercise) {
        return false;
    }

    if ((int) $attempt->getCourse()->getId() !== $courseId) {
        return false;
    }

    if ((int) ($attempt->getQuiz()?->getIid() ?? 0) !== $exerciseId) {
        return false;
    }

    $attemptSessionId = (int) ($attempt->getSession()?->getId() ?? 0);

    return $attemptSessionId === $sessionId;
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

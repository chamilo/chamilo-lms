<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use ChamiloSession as Session;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$allowAnonymousUsers = 'true' === $plugin->get('unregistered_users_enable');
$userIsAnonymous = api_is_anonymous();
$registrationUrl = api_get_path(WEB_CODE_PATH).'auth/registration.php';

$normalizeRelativePath = static function (string $url): string {
    $path = (string) parse_url($url, PHP_URL_PATH);
    $query = (string) parse_url($url, PHP_URL_QUERY);

    if ('' === $path) {
        $path = '/plugin/BuyCourses/src/course_information.php';
    }

    return '' !== $query ? $path.'?'.$query : $path;
};

$renderDialogMessage = static function (
    string $title,
    string $message,
    int $statusCode = 200,
    ?string $actionUrl = null,
    ?string $actionLabel = null
): void {
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=UTF-8');

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $actionHtml = '';
    if (!empty($actionUrl) && !empty($actionLabel)) {
        $safeActionUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
        $safeActionLabel = htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8');

        $actionHtml = <<<HTML
<div class="pt-2">
    <a
        href="{$safeActionUrl}"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
    >
        {$safeActionLabel}
    </a>
</div>
HTML;
    }

    echo <<<HTML
<div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
    <div class="space-y-3">
        <h2 class="text-xl font-semibold text-gray-90">{$safeTitle}</h2>
        <p class="text-sm leading-6 text-gray-50">{$safeMessage}</p>
        {$actionHtml}
    </div>
</div>
HTML;
    exit;
};

if ($userIsAnonymous && !$allowAnonymousUsers) {
    $currentPath = $normalizeRelativePath(
        (string) ($_SERVER['REQUEST_URI'] ?? ('/plugin/BuyCourses/src/course_information.php?course_id='.$courseId))
    );

    Session::write('buy_course_redirect', $currentPath);

    $renderDialogMessage(
        'Authentication required',
        'Please sign in to view the course details.',
        403,
        $registrationUrl,
        'Go to registration'
    );
}

if ($courseId <= 0) {
    $renderDialogMessage(
        'Course not found',
        'The selected course could not be found.',
        404
    );
}

$course = $plugin->getCourseInfo($courseId);

if (empty($course)) {
    $renderDialogMessage(
        'Course not available',
        'This course is not currently available in the catalog.',
        404
    );
}

$template = new Template(false);
$template->assign('course', $course);

header('Content-Type: text/html; charset=UTF-8');
echo $template->fetch('BuyCourses/view/course_information.tpl');
exit;

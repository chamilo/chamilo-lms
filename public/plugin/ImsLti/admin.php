<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\LtiBundle\Entity\ExternalTool;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$pluginEntity = Container::getPluginRepository()->findOneByTitle('ImsLti');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

if (!$isPluginEnabled) {
    api_not_allowed(true);
}

$em = Database::getManager();

/** @var CShortcutRepository $shortcutRepository */
$shortcutRepository = $em->getRepository(CShortcut::class);

/** @var ExternalTool[] $allTools */
$allTools = $em->getRepository(ExternalTool::class)->findAll();

$normalizeLaunchUrl = static function (?string $url): string {
    $url = trim((string) $url);

    if ('' === $url) {
        return '';
    }

    $parts = parse_url($url);

    if (false === $parts) {
        return strtolower(rtrim($url, '/'));
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    $port = isset($parts['port']) ? ':'.$parts['port'] : '';
    $path = rtrim((string) ($parts['path'] ?? ''), '/');

    return $scheme.'://'.$host.$port.$path;
};

$buildLogicalToolKey = static function (ExternalTool $tool) use ($normalizeLaunchUrl): string {
    $version = trim((string) $tool->getVersion());
    $launchUrl = $normalizeLaunchUrl($tool->getLaunchUrl());

    if (ImsLti::V_1P3 === $version) {
        $clientId = strtolower(trim((string) $tool->getClientId()));
        $loginUrl = $normalizeLaunchUrl($tool->getLoginUrl());

        return implode('|', [
            'version='.$version,
            'launch='.$launchUrl,
            'client='.$clientId,
            'login='.$loginUrl,
        ]);
    }

    $consumerKey = strtolower(trim((string) $tool->getConsumerKey()));

    return implode('|', [
        'version='.$version,
        'launch='.$launchUrl,
        'consumer='.$consumerKey,
    ]);
};

$getToolCourseIds = static function (ExternalTool $tool) use ($shortcutRepository): array {
    $courseIds = array_map(
        'intval',
        $shortcutRepository->getAssignedCourseIdsFromResource($tool)
    );

    if ($tool->hasResourceNode()) {
        foreach ($tool->getResourceNode()->getResourceLinks() as $link) {
            $course = $link->getCourse();

            if (null !== $course) {
                $courseIds[] = (int) $course->getId();
            }
        }
    }

    $courseIds = array_values(array_unique(array_filter($courseIds)));
    sort($courseIds);

    return $courseIds;
};

$buildAssignedCoursesData = static function (array $courseIds): array {
    $items = [];

    foreach ($courseIds as $courseId) {
        $courseId = (int) $courseId;

        if ($courseId <= 0) {
            continue;
        }

        $courseInfo = api_get_course_info_by_id($courseId);

        if (empty($courseInfo)) {
            continue;
        }

        $title = trim((string) ($courseInfo['title'] ?? ''));
        $code = trim((string) ($courseInfo['code'] ?? ''));

        $label = $title;

        if ('' !== $code) {
            $label .= ' ('.$code.')';
        }

        if ('' === $label) {
            $label = 'Course #'.$courseId;
        }

        $items[] = [
            'id' => (string) $courseId,
            'text' => $label,
        ];
    }

    return $items;
};

$chooseRepresentativeTool = static function (array $tools): ExternalTool {
    usort(
        $tools,
        static function (ExternalTool $a, ExternalTool $b): int {
            $aHasParentTool = $a->getToolParent() instanceof ExternalTool;
            $bHasParentTool = $b->getToolParent() instanceof ExternalTool;

            $aHasCourseParent = $a->getParent() instanceof Course;
            $bHasCourseParent = $b->getParent() instanceof Course;

            $aScore = 0;
            $bScore = 0;

            if ($aHasParentTool) {
                $aScore += 20;
            }

            if ($bHasParentTool) {
                $bScore += 20;
            }

            if ($aHasCourseParent) {
                $aScore += 10;
            }

            if ($bHasCourseParent) {
                $bScore += 10;
            }

            if ($aScore === $bScore) {
                return ((int) $a->getId()) <=> ((int) $b->getId());
            }

            return $aScore <=> $bScore;
        }
    );

    return $tools[0];
};

$buildIncompleteToolMessage = static function (ExternalTool $tool) use ($plugin): string {
    $missingFields = [];

    if (ImsLti::V_1P3 === $tool->getVersion()) {
        if ('' === trim((string) $tool->getLaunchUrl())) {
            $missingFields[] = $plugin->get_lang('LaunchUrl');
        }

        if ('' === trim((string) $tool->getLoginUrl())) {
            $missingFields[] = $plugin->get_lang('LoginUrl');
        }

        if ('' === trim((string) $tool->getRedirectUrl())) {
            $missingFields[] = $plugin->get_lang('RedirectUrl');
        }

        if (
            '' === trim((string) $tool->getJwksUrl()) &&
            '' === trim((string) $tool->publicKey)
        ) {
            $missingFields[] = $plugin->get_lang('JwksUrlOrRsaKey');
        }

        if ('' === trim((string) $tool->getClientId())) {
            $missingFields[] = $plugin->get_lang('ClientId');
        }
    } else {
        if ('' === trim((string) $tool->getLaunchUrl())) {
            $missingFields[] = $plugin->get_lang('LaunchUrl');
        }
    }

    if (empty($missingFields)) {
        return '';
    }

    return sprintf(
        $plugin->get_lang('CompleteParamsLti'),
        implode(', ', $missingFields)
    );
};

$groupedTools = [];

foreach ($allTools as $tool) {
    $logicalKey = $buildLogicalToolKey($tool);
    $groupedTools[$logicalKey][] = $tool;
}

$toolRows = [];

foreach ($groupedTools as $groupTools) {
    $representativeTool = $chooseRepresentativeTool($groupTools);

    $courseIds = [];
    $groupIds = [];

    foreach ($groupTools as $groupTool) {
        $groupIds[] = (int) $groupTool->getId();
        $courseIds = array_merge($courseIds, $getToolCourseIds($groupTool));
    }

    $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
    sort($courseIds);
    sort($groupIds);

    $launchUrl = trim((string) $representativeTool->getLaunchUrl());
    $clientId = trim((string) $representativeTool->getClientId());

    $incompleteMessage = $buildIncompleteToolMessage($representativeTool);
    $isReadyForCourses = '' === $incompleteMessage;

    $groupCount = count($groupTools);

    $toolRows[] = [
        'id' => $representativeTool->getId(),
        'title' => (string) $representativeTool->getTitle(),
        'version' => (string) $representativeTool->getVersion(),
        'client_id' => $clientId,
        'launch_url' => $launchUrl,
        'is_lti13' => ImsLti::V_1P3 === $representativeTool->getVersion(),
        'is_ready_for_courses' => $isReadyForCourses,
        'incomplete_message' => $incompleteMessage,
        'group_count' => $groupCount,
        'has_duplicates' => $groupCount > 1,
        'duplicate_count' => max(0, $groupCount - 1),
        'duplicate_ids_label' => implode(', ', $groupIds),
        'assigned_courses' => $buildAssignedCoursesData($courseIds),
        'assigned_courses_count' => count($courseIds),
        'can_delete' => 1 === $groupCount,
        'delete_disabled_reason' => $groupCount > 1
            ? 'This tool has internal course copies. Remove course assignments first.'
            : '',
    ];
}

usort(
    $toolRows,
    static function (array $a, array $b): int {
        $titleComparison = strcmp(
            mb_strtolower((string) ($a['title'] ?? '')),
            mb_strtolower((string) ($b['title'] ?? ''))
        );

        if (0 !== $titleComparison) {
            return $titleComparison;
        }

        return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
    }
);

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('PlatformAdmin'),
];

$pageTitle = $plugin->get_title();
$pageDescription = $plugin->get_lang('ImsLtiDescription');

$template = new Template($pageTitle);
$template->assign('tools', $toolRows);
$template->assign('page_title', $pageTitle);
$template->assign('page_description', $pageDescription);

$content = $template->fetch('ImsLti/view/admin.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();

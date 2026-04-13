<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Doctrine\Common\Collections\ArrayCollection;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();

$showDebug = false;
$adminUrl = api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php';
$isAjax = false;

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

        $items[] = [
            'id' => (string) $courseId,
            'text' => $label,
        ];
    }

    return $items;
};

/**
 * Build a normalized list of assigned courses for the modal.
 *
 * Each item is shaped as:
 * [
 *   'id' => '123',
 *   'text' => 'Course title (COURSECODE)'
 * ]
 */
$renderFormContent = static function (
    FormValidator $form,
    ExternalTool $tool,
    array $assignedCourses = [],
    string $message = '',
    string $messageType = 'error'
) use ($plugin): string {
    $toolTitle = htmlspecialchars((string) $tool->getTitle(), ENT_QUOTES, 'UTF-8');
    $assignedCoursesJson = htmlspecialchars(
        json_encode(array_values($assignedCourses), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ENT_QUOTES,
        'UTF-8'
    );

    $html = '';
    $html .= '<div id="imslti-multiply-form-wrapper" class="space-y-6">';
    $html .= '    <div class="rounded-2xl border border-gray-25 bg-support-2 p-5">';
    $html .= '        <div class="mb-2 text-caption font-semibold uppercase tracking-wide text-gray-50">'.$plugin->get_lang('Tool').'</div>';
    $html .= '        <div class="text-body-1 font-semibold text-gray-90">'.$toolTitle.'</div>';
    $html .= '        <p class="mt-3 text-body-2 text-gray-50">'.$plugin->get_lang('SelectCoursesForExternalTool').'</p>';
    $html .= '    </div>';

    $html .= '    <div class="js-imslti-assigned-courses hidden" data-assigned="'.$assignedCoursesJson.'"></div>';

    if (!empty($assignedCourses)) {
        $html .= Display::return_message(
            'Removing an activity from a course can have an impact on the assessments of that course. Are you sure you want to remove it?',
            'warning',
            false
        );

        $html .= '    <div class="rounded-2xl border border-gray-25 bg-white p-5">';
        $html .= '        <div class="mb-3 text-caption font-semibold uppercase tracking-wide text-gray-50">'.$plugin->get_lang('Courses').'</div>';
        $html .= '        <div class="flex flex-wrap gap-2">';

        foreach ($assignedCourses as $courseItem) {
            $courseLabel = htmlspecialchars((string) ($courseItem['text'] ?? ''), ENT_QUOTES, 'UTF-8');
            if ('' === $courseLabel) {
                continue;
            }

            $html .= '<span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-caption font-semibold text-primary">'
                .$courseLabel
                .'</span>';
        }

        $html .= '        </div>';
        $html .= '    </div>';
    }

    if (!empty($message)) {
        $html .= Display::return_message($message, $messageType, false);
    }

    $html .= '    <div class="rounded-2xl border border-gray-25 bg-white p-5">';
    $html .=          $form->returnForm();
    $html .= '    </div>';
    $html .= '</div>';

    return $html;
};

/**
 * Return a JSON success payload for AJAX mode.
 */
$sendJsonSuccess = static function (string $message): void {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
    ]);
    exit;
};

/**
 * Return HTML content for AJAX mode.
 */
$sendAjaxHtml = static function (string $html, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
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

try {
    $pluginEntity = Container::getPluginRepository()->findOneByTitle('ImsLti');
    $currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
    $pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

    $isPluginEnabled = $pluginEntity
        && $pluginEntity->isInstalled()
        && $pluginConfiguration
        && $pluginConfiguration->isActive();

    if (!$isPluginEnabled) {
        throw new Exception(get_lang('NotAllowed'));
    }

    $request = Container::getRequest();
    $isAjax = $request->isXmlHttpRequest()
        || 1 === (int) $request->query->get('ajax', 0)
        || 1 === (int) $request->request->get('ajax', 0);

    /** @var ExternalTool|null $requestedTool */
    $requestedTool = $em->find(ExternalTool::class, $request->query->getInt('id'));

    if (!$requestedTool) {
        throw new Exception($plugin->get_lang('NoTool'));
    }

    /** @var ExternalTool $baseTool */
    $baseTool = $requestedTool;
    $toolParent = $requestedTool->getToolParent();
    if ($toolParent instanceof ExternalTool) {
        $baseTool = $toolParent;
    }

    $incompleteToolMessage = $buildIncompleteToolMessage($baseTool);

    if ('' !== $incompleteToolMessage) {
        throw new Exception($incompleteToolMessage);
    }

    if (!$baseTool->hasResourceNode()) {
        throw new Exception($plugin->get_lang('ExternalToolWithoutResourceNode'));
    }

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

    $getToolCourseLinks = static function (ExternalTool $tool, $course): array {
        $links = [];

        if (!$tool->hasResourceNode()) {
            return $links;
        }

        foreach ($tool->getResourceNode()->getResourceLinks() as $link) {
            $linkCourse = $link->getCourse();

            if (null === $linkCourse || (int) $linkCourse->getId() !== (int) $course->getId()) {
                continue;
            }

            $links[] = $link;
        }

        return $links;
    };

    $isToolAssignedToCourse = static function (ExternalTool $tool, $course) use ($shortcutRepository, $getToolCourseLinks): bool {
        if (!empty($getToolCourseLinks($tool, $course))) {
            return true;
        }

        if (!$tool->hasResourceNode()) {
            return false;
        }

        $assignedCourseIds = array_map(
            'intval',
            $shortcutRepository->getAssignedCourseIdsFromResource($tool)
        );

        return in_array((int) $course->getId(), $assignedCourseIds, true);
    };

    $getCourseSpecificTools = static function (ExternalTool $baseTool, $course) use (
        &$allTools,
        $normalizeLaunchUrl,
        $isToolAssignedToCourse
    ): array {
        $matches = [];
        $baseId = (int) $baseTool->getId();
        $baseLaunchUrl = $normalizeLaunchUrl($baseTool->getLaunchUrl());

        foreach ($allTools as $candidate) {
            if (!$candidate instanceof ExternalTool) {
                continue;
            }

            $score = null;
            $candidateId = (int) $candidate->getId();
            $candidateParent = $candidate->getToolParent();

            if (
                $candidateParent instanceof ExternalTool
                && (int) $candidateParent->getId() === $baseId
            ) {
                if (null !== $candidate->getFirstResourceLinkFromCourseSession($course)) {
                    $score = 10;
                } elseif ($isToolAssignedToCourse($candidate, $course)) {
                    $score = 20;
                }
            } elseif (
                $candidateId !== $baseId
                && '' !== $baseLaunchUrl
                && $baseLaunchUrl === $normalizeLaunchUrl($candidate->getLaunchUrl())
                && $isToolAssignedToCourse($candidate, $course)
            ) {
                if (null !== $candidate->getFirstResourceLinkFromCourseSession($course)) {
                    $score = 30;
                } else {
                    $score = 40;
                }
            } elseif (
                $candidateId === $baseId
                && $isToolAssignedToCourse($candidate, $course)
            ) {
                $score = 50;
            }

            if (null === $score) {
                continue;
            }

            $matches[] = [
                'score' => $score,
                'tool' => $candidate,
            ];
        }

        usort(
            $matches,
            static function (array $a, array $b): int {
                if ($a['score'] === $b['score']) {
                    return ((int) $a['tool']->getId()) <=> ((int) $b['tool']->getId());
                }

                return $a['score'] <=> $b['score'];
            }
        );

        return array_map(
            static fn (array $row): ExternalTool => $row['tool'],
            $matches
        );
    };

    $getAssignedCourseIdsForBaseTool = static function (ExternalTool $baseTool) use (
        &$allTools,
        $getToolCourseIds,
        $normalizeLaunchUrl
    ): array {
        $courseIds = $getToolCourseIds($baseTool);
        $baseId = (int) $baseTool->getId();
        $baseLaunchUrl = $normalizeLaunchUrl($baseTool->getLaunchUrl());

        foreach ($allTools as $candidate) {
            if (!$candidate instanceof ExternalTool) {
                continue;
            }

            if ((int) $candidate->getId() === $baseId) {
                continue;
            }

            $candidateParent = $candidate->getToolParent();
            $isChildOfBase = $candidateParent instanceof ExternalTool
                && (int) $candidateParent->getId() === $baseId;

            $sameLaunchUrl = '' !== $baseLaunchUrl
                && $baseLaunchUrl === $normalizeLaunchUrl($candidate->getLaunchUrl());

            if (!$isChildOfBase && !$sameLaunchUrl) {
                continue;
            }

            $courseIds = array_merge($courseIds, $getToolCourseIds($candidate));
        }

        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
        sort($courseIds);

        return $courseIds;
    };

    $createLocalToolForCourse = static function (ExternalTool $baseTool, $course) use (&$allTools, $em): ExternalTool {
        $localTool = clone $baseTool;
        $localTool->setToolParent($baseTool);
        $localTool->setResourceNode(null);
        $localTool->setGradebookEval(null);
        $localTool->setLineItems(new ArrayCollection());
        $localTool->addCourseLink($course);

        $em->persist($localTool);
        $em->flush();

        $allTools[] = $localTool;

        return $localTool;
    };

    $ensureCourseLink = static function (ExternalTool $tool, $course) use ($em): void {
        if (null !== $tool->getFirstResourceLinkFromCourseSession($course)) {
            return;
        }

        $tool->addCourseLink($course);
        $em->persist($tool);
    };

    $ensureShortcut = static function (ExternalTool $tool, $course, $currentUser) use ($shortcutRepository, $em): void {
        if ($tool->isActiveDeepLinking()) {
            return;
        }

        $shortcut = $shortcutRepository->addShortCut($tool, $currentUser, $course, null);
        $shortcut->setTitle($tool->getTitle());
        $shortcut->setShortCutNode($tool->getResourceNode());
        $shortcut->target = 'iframe' === $tool->getDocumentTarget() ? '_self' : '_blank';

        $em->persist($shortcut);
        $shortcutRepository->setVisibilityPublished($shortcut, $course, null);
    };

    $removeToolAssignmentFromCourse = static function (ExternalTool $tool, $course) use ($shortcutRepository, $em, $getToolCourseLinks): void {
        $shortcutRepository->removeShortCutFromCourse($tool, $course);

        foreach ($getToolCourseLinks($tool, $course) as $link) {
            $em->remove($link);
        }
    };

    $hasProtectedAssociations = static function (ExternalTool $tool): bool {
        if (null !== $tool->getGradebookEval()) {
            return true;
        }

        return $tool->getLineItems()->count() > 0;
    };

    $hasRemainingLinks = static function (ExternalTool $tool): bool {
        if (!$tool->hasResourceNode()) {
            return false;
        }

        return $tool->getResourceNode()->getResourceLinks()->count() > 0;
    };

    $canDeleteLocalTool = static function (ExternalTool $tool) use ($hasProtectedAssociations, $hasRemainingLinks): bool {
        if (!$tool->getToolParent() instanceof ExternalTool) {
            return false;
        }

        if ($hasProtectedAssociations($tool)) {
            return false;
        }

        if ($hasRemainingLinks($tool)) {
            return false;
        }

        return true;
    };

    $formatCourseLabel = static function (int $courseId): string {
        $courseInfo = api_get_course_info_by_id($courseId);

        if (!empty($courseInfo)) {
            $label = trim((string) ($courseInfo['title'] ?? ''));
            $code = trim((string) ($courseInfo['code'] ?? ''));

            if ('' !== $code) {
                $label .= ' ('.$code.')';
            }

            if ('' !== $label) {
                return $label;
            }
        }

        return (string) $courseId;
    };

    $assignedCourseIds = $getAssignedCourseIdsForBaseTool($baseTool);
    $assignedCoursesData = $buildAssignedCoursesData($assignedCourseIds);

    $formAction = api_get_self().'?id='.$baseTool->getId();
    if ($isAjax) {
        $formAction .= '&ajax=1';
    }

    $form = new FormValidator('frm_multiply', 'post', $formAction);
    $form->addHidden('ajax', $isAjax ? '1' : '0');
    $form->addSelectAjax(
        'courses',
        get_lang('Courses'),
        [],
        [
            'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
            'multiple' => true,
        ]
    );
    $form->addButtonExport(get_lang('Save'));

    if ($form->validate()) {
        $formValues = $form->exportValues();
        $selectedCourseIds = empty($formValues['courses']) ? [] : $formValues['courses'];
        $selectedCourseIds = array_values(array_unique(array_filter(array_map('intval', $selectedCourseIds))));

        if (empty($selectedCourseIds) && empty($assignedCourseIds)) {
            $content = $renderFormContent(
                $form,
                $baseTool,
                $assignedCoursesData,
                get_lang('You must select at least one course'),
                'error'
            );

            if ($isAjax) {
                $sendAjaxHtml($content, 200);
            }

            $interbreadcrumb[] = [
                'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
                'name' => get_lang('PlatformAdmin'),
            ];
            $interbreadcrumb[] = [
                'url' => $adminUrl,
                'name' => $plugin->get_title(),
            ];

            $backButton = Display::toolbarButton(
                get_lang('Back'),
                $adminUrl,
                'arrow-left',
                'plain'
            );

            $actions = Display::toolbarAction(
                'ims_lti_multiply_actions',
                [
                    $backButton,
                ]
            );

            $template = new Template($plugin->get_lang('AddInCourses'));
            $template->assign('actions', $actions);
            $template->assign('header', $plugin->get_lang('AddInCourses'));
            $template->assign('content', $content);
            $template->display_one_col_template();
            exit;
        }

        $currentUser = api_get_user_entity(api_get_user_id());
        if (!$currentUser) {
            throw new Exception(get_lang('UserNotFound'));
        }

        $selectedLookup = array_fill_keys($selectedCourseIds, true);
        $coursesWithProtectedAssignments = [];

        foreach ($selectedCourseIds as $courseId) {
            $course = api_get_course_entity($courseId);

            if (!$course || !$course->getResourceNode()) {
                continue;
            }

            $courseTools = $getCourseSpecificTools($baseTool, $course);

            $localTool = null;
            foreach ($courseTools as $candidateTool) {
                if ((int) $candidateTool->getId() === (int) $baseTool->getId()) {
                    continue;
                }

                $localTool = $candidateTool;
                break;
            }

            if (!$localTool instanceof ExternalTool) {
                $localTool = $createLocalToolForCourse($baseTool, $course);
            } else {
                $ensureCourseLink($localTool, $course);
            }

            $removeToolAssignmentFromCourse($baseTool, $course);
            $ensureShortcut($localTool, $course, $currentUser);

            foreach ($courseTools as $duplicateTool) {
                if ((int) $duplicateTool->getId() === (int) $localTool->getId()) {
                    continue;
                }

                $removeToolAssignmentFromCourse($duplicateTool, $course);

                if ($canDeleteLocalTool($duplicateTool)) {
                    $em->remove($duplicateTool);
                }
            }
        }

        foreach ($assignedCourseIds as $assignedCourseId) {
            $assignedCourseId = (int) $assignedCourseId;

            if (isset($selectedLookup[$assignedCourseId])) {
                continue;
            }

            $course = api_get_course_entity($assignedCourseId);
            if (!$course) {
                continue;
            }

            $courseTools = $getCourseSpecificTools($baseTool, $course);

            $protectedTools = array_values(array_filter(
                $courseTools,
                static fn (ExternalTool $tool): bool => $tool->getToolParent() instanceof ExternalTool
                    && (null !== $tool->getGradebookEval() || $tool->getLineItems()->count() > 0)
            ));

            if (!empty($protectedTools)) {
                $coursesWithProtectedAssignments[] = $formatCourseLabel($assignedCourseId);
                continue;
            }

            $removeToolAssignmentFromCourse($baseTool, $course);

            foreach ($courseTools as $courseTool) {
                if ((int) $courseTool->getId() === (int) $baseTool->getId()) {
                    continue;
                }

                $removeToolAssignmentFromCourse($courseTool, $course);

                if ($canDeleteLocalTool($courseTool)) {
                    $em->remove($courseTool);
                }
            }
        }

        $em->flush();

        $successMessage = get_lang('ItemUpdated');

        if (!empty($coursesWithProtectedAssignments)) {
            $coursesWithProtectedAssignments = array_values(array_unique($coursesWithProtectedAssignments));

            Display::addFlash(
                Display::return_message($successMessage)
            );

            Display::addFlash(
                Display::return_message(
                    'Some course-specific copies were kept because they are already linked to gradebook evaluations or line items: '.implode(', ', $coursesWithProtectedAssignments),
                    'warning'
                )
            );

            if ($isAjax) {
                $sendJsonSuccess(
                    $successMessage.' Some course-specific copies were kept because they are already linked to gradebook evaluations or line items: '.implode(', ', $coursesWithProtectedAssignments)
                );
            }

            header('Location: '.$adminUrl);
            exit;
        }

        Display::addFlash(
            Display::return_message($successMessage)
        );

        if ($isAjax) {
            $sendJsonSuccess($successMessage);
        }

        header('Location: '.$adminUrl);
        exit;
    }

    $form->protect();
    $content = $renderFormContent($form, $baseTool, $assignedCoursesData);

    if ($isAjax) {
        $sendAjaxHtml($content, 200);
    }

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('PlatformAdmin'),
    ];
    $interbreadcrumb[] = [
        'url' => $adminUrl,
        'name' => $plugin->get_title(),
    ];

    $backButton = Display::toolbarButton(
        get_lang('Back'),
        $adminUrl,
        'arrow-left',
        'plain'
    );

    $actions = Display::toolbarAction(
        'ims_lti_multiply_actions',
        [
            $backButton,
        ]
    );

    $template = new Template($plugin->get_lang('AddInCourses'));
    $template->assign('actions', $actions);
    $template->assign('header', $plugin->get_lang('AddInCourses'));
    $template->assign('content', $content);
    $template->display_one_col_template();
} catch (\Throwable $exception) {
    $message = $showDebug
        ? $exception->getMessage().'<br><pre>'.$exception->getTraceAsString().'</pre>'
        : $exception->getMessage();

    if ($isAjax) {
        $sendAjaxHtml(
            Display::return_message($message, 'error', false),
            500
        );
    }

    Display::addFlash(
        Display::return_message($message, 'error')
    );

    header('Location: '.$adminUrl);
    exit;
}

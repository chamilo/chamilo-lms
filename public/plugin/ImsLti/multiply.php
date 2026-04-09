<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\LtiBundle\Entity\ExternalTool;

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

    /** @var ExternalTool|null $tool */
    $tool = $em->find(ExternalTool::class, $request->query->getInt('id'));

    if (!$tool) {
        throw new Exception($plugin->get_lang('NoTool'));
    }

    $incompleteToolMessage = $buildIncompleteToolMessage($tool);

    if ('' !== $incompleteToolMessage) {
        throw new Exception($incompleteToolMessage);
    }

    if (!$tool->hasResourceNode()) {
        throw new Exception($plugin->get_lang('ExternalToolWithoutResourceNode'));
    }

    /** @var CShortcutRepository $shortcutRepository */
    $shortcutRepository = $em->getRepository(CShortcut::class);

    $assignedCourseIds = $shortcutRepository->getAssignedCourseIdsFromResource($tool);
    $assignedCoursesData = $buildAssignedCoursesData($assignedCourseIds);

    $formAction = api_get_self().'?id='.$tool->getId();
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
            'placeholder' => get_lang('Select'),
        ]
    );
    $form->addButtonSave(get_lang('Save'));

    if ($form->validate()) {
        $formValues = $form->exportValues();
        $selectedCourseIds = empty($formValues['courses']) ? [] : $formValues['courses'];
        $selectedCourseIds = array_values(array_unique(array_filter(array_map('intval', $selectedCourseIds))));

        // Allow empty selection when the tool is already assigned:
        // saving with no courses means "remove all course shortcuts".
        if (empty($selectedCourseIds) && empty($assignedCourseIds)) {
            $content = $renderFormContent(
                $form,
                $tool,
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

        // Create or keep selected course shortcuts.
        foreach ($selectedCourseIds as $courseId) {
            $course = api_get_course_entity($courseId);

            if (!$course || !$course->getResourceNode()) {
                continue;
            }

            /** @var CShortcut $shortcut */
            $shortcut = $shortcutRepository->addShortCut($tool, $currentUser, $course, null);

            $shortcut->setTitle($tool->getTitle());
            $shortcut->setShortCutNode($tool->getResourceNode());
            $shortcut->target = 'iframe' === $tool->getDocumentTarget() ? '_self' : '_blank';

            $em->persist($shortcut);
            $shortcutRepository->setVisibilityPublished($shortcut, $course, null);
        }

        // Remove shortcuts for courses that are no longer selected.
        foreach ($assignedCourseIds as $assignedCourseId) {
            if (isset($selectedLookup[(int) $assignedCourseId])) {
                continue;
            }

            $course = api_get_course_entity((int) $assignedCourseId);
            if (!$course) {
                continue;
            }

            $shortcutRepository->removeShortCutFromCourse($tool, $course);
        }

        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('ItemUpdated'))
        );

        if ($isAjax) {
            $sendJsonSuccess(get_lang('ItemUpdated'));
        }

        header('Location: '.$adminUrl);
        exit;
    }

    $form->protect();
    $content = $renderFormContent($form, $tool, $assignedCoursesData);

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
} catch (Exception $exception) {
    if ($showDebug) {
        Display::display_header($plugin->get_lang('AddInCourses'));
        echo Display::return_message($exception->getMessage(), 'error', false);
        echo '<pre style="white-space: pre-wrap; font-size: 12px;">'
            .htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8')
            .'</pre>';
        Display::display_footer();
        exit;
    }

    if ($isAjax) {
        $errorHtml = Display::return_message($exception->getMessage(), 'error', false);
        $sendAjaxHtml($errorHtml, 400);
    }

    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );

    header('Location: '.$adminUrl);
    exit;
}

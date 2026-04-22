<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SearchEngineField;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsValueTemplate;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Search\Xapian\SearchIndexPathResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Collator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const DIRECTORY_SEPARATOR;
use const SORT_REGULAR;

#[Route('/admin')]
class SettingsController extends BaseController
{
    use ControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly SearchIndexPathResolver $searchIndexPathResolver
    ) {}

    #[Route('/settings', name: 'admin_settings')]
    public function index(): Response
    {
        return $this->redirectToRoute('chamilo_platform_settings', ['namespace' => 'platform']);
    }

    /**
     * Toggle access_url_changeable for a given setting variable.
     * Only platform admins on the main URL (ID = 1) are allowed to change it.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/settings/toggle_changeable', name: 'settings_toggle_changeable', methods: ['POST'])]
    public function toggleChangeable(Request $request, AccessUrlHelper $accessUrlHelper): JsonResponse
    {
        // Security: only admins (defense-in-depth; attribute already protects this route).
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Only platform admins can modify this flag.',
            ], 403);
        }

        $currentUrl = $accessUrlHelper->getCurrent();
        if (!$currentUrl) {
            return $this->json([
                'error' => 'Access URL not resolved.',
            ], 500);
        }

        $currentUrlId = (int) $currentUrl->getId();
        if (1 !== $currentUrlId) {
            return $this->json([
                'error' => 'Only the main URL (ID 1) can toggle this setting.',
            ], 403);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json([
                'error' => 'Invalid JSON payload.',
            ], 400);
        }

        $variable = isset($payload['variable']) ? trim((string) $payload['variable']) : '';
        $statusRaw = $payload['status'] ?? null;

        // Optional: category/namespace helps avoid collisions when the same variable exists in multiple schemas.
        $category = null;
        if (isset($payload['category'])) {
            $category = trim((string) $payload['category']);
        } elseif (isset($payload['namespace'])) {
            $category = trim((string) $payload['namespace']);
        }

        if ('' === $variable) {
            return $this->json([
                'error' => 'Missing "variable".',
            ], 400);
        }

        // Basic hardening: setting variable names are typically snake_case.
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $variable)) {
            return $this->json([
                'error' => 'Invalid variable name.',
            ], 400);
        }

        $status = ((int) $statusRaw) === 1 ? 1 : 0;

        $repo = $this->entityManager->getRepository(SettingsCurrent::class);

        // Ensure we always update canonical rows on main URL (ID=1).
        $mainUrl = $this->entityManager->getRepository(AccessUrl::class)->find(1);
        if (!$mainUrl instanceof AccessUrl) {
            return $this->json([
                'error' => 'Main URL (ID 1) not found.',
            ], 500);
        }

        // Find rows: either a specific category, or all rows matching the variable on main URL.
        $rows = [];
        if (null !== $category && '' !== $category) {
            $rows = array_merge(
                $repo->findBy(['variable' => $variable, 'url' => $mainUrl, 'category' => $category]),
                $repo->findBy(['variable' => $variable, 'url' => $mainUrl, 'category' => ucfirst($category)])
            );
            // Remove duplicates
            $rows = array_values(array_unique($rows, SORT_REGULAR));
        } else {
            $rows = $repo->findBy(['variable' => $variable, 'url' => $mainUrl]);
        }

        if (empty($rows)) {
            return $this->json([
                'error' => 'Setting not found on main URL.',
            ], 404);
        }

        try {
            $updated = 0;

            foreach ($rows as $setting) {
                if (!$setting instanceof SettingsCurrent) {
                    continue;
                }

                // Locked settings must not be toggled (even on main URL).
                if (method_exists($setting, 'getAccessUrlLocked') && 1 === (int) $setting->getAccessUrlLocked()) {
                    return $this->json([
                        'error' => 'This setting is locked and cannot be toggled.',
                    ], 403);
                }

                $setting->setAccessUrlChangeable($status);
                $this->entityManager->persist($setting);
                $updated++;
            }

            $this->entityManager->flush();

            // Clear session schema caches so admin UI reflects the change immediately.
            if ($request->hasSession()) {
                $session = $request->getSession();
                foreach (array_keys((array) $session->all()) as $key) {
                    if ('schemas' === $key || str_starts_with((string) $key, 'schemas_url_')) {
                        $session->remove($key);
                    }
                }
            }

            return $this->json([
                'result' => 1,
                'variable' => $variable,
                'status' => $status,
                'updated_rows' => $updated,
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'error' => 'Unable to update setting.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Edit configuration with given namespace (search page).
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/settings/search_settings', name: 'chamilo_platform_settings_search')]
    public function searchSetting(Request $request, AccessUrlHelper $accessUrlHelper): Response
    {
        $manager = $this->getSettingsManager();

        $url = $accessUrlHelper->getCurrent();
        $manager->setUrl($url);

        $formList = [];
        $templateMap = [];
        $templateMapByCategory = [];
        $settings = [];

        $keyword = trim((string) $request->query->get('keyword', ''));

        $searchForm = $this->getSearchForm();
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $values = $searchForm->getData();
            $keyword = trim((string) ($values['keyword'] ?? ''));
        }

        $schemas = $manager->getSchemas();
        [$ordered, $labelMap] = $this->computeOrderedNamespacesByTranslatedLabel($schemas, $request);

        $settingsRepo = $this->entityManager->getRepository(SettingsCurrent::class);

        $currentUrlId = (int) $url->getId();
        $mainUrl = $this->entityManager->getRepository(AccessUrl::class)->find(1);

        // Build template map: current URL overrides main URL when missing.
        if ($mainUrl instanceof AccessUrl && 1 !== $currentUrlId) {
            $mainRows = $settingsRepo->findBy(['url' => $mainUrl]);
            foreach ($mainRows as $s) {
                if ($s->getValueTemplate()) {
                    $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
                }
            }
        }

        $currentRows = $settingsRepo->findBy(['url' => $url]);
        foreach ($currentRows as $s) {
            if ($s->getValueTemplate()) {
                $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
            }
        }

        // MultiURL flags: read from main URL (ID = 1) only
        $changeableMap = [];
        $lockedMap = [];

        $mainUrlRows = $settingsRepo->createQueryBuilder('sc')
            ->join('sc.url', 'u')
            ->andWhere('u.id = :mainId')
            ->setParameter('mainId', 1)
            ->getQuery()
            ->getResult()
        ;

        foreach ($mainUrlRows as $row) {
            if ($row instanceof SettingsCurrent) {
                $changeableMap[$row->getVariable()] = (int) $row->getAccessUrlChangeable();
                $lockedMap[$row->getVariable()] = method_exists($row, 'getAccessUrlLocked')
                    ? (int) $row->getAccessUrlLocked()
                    : 0;
            }
        }

        $hideDisabledOnSubUrl = false;
        if (1 !== $currentUrlId) {
            foreach ($mainUrlRows as $row) {
                if ($row instanceof SettingsCurrent && 'multiple_url_hide_disabled_settings' === $row->getVariable()) {
                    $hideDisabledOnSubUrl = 'true' === $row->getSelectedValue();

                    break;
                }
            }
        }

        // Only platform admins on the main URL can toggle the MultiURL flag.
        $canToggleMultiUrlSetting = $this->isGranted('ROLE_ADMIN') && 1 === $currentUrlId;

        if ('' === $keyword) {
            return $this->render('@ChamiloCore/Admin/Settings/search.html.twig', [
                'keyword' => $keyword,
                'schemas' => $schemas,
                'settings' => $settings,
                'form_list' => $formList,
                'search_form' => $searchForm->createView(),
                'template_map' => $templateMap,
                'template_map_by_category' => $templateMapByCategory,
                'ordered_namespaces' => $ordered,
                'namespace_labels' => $labelMap,
                'changeable_map' => $changeableMap,
                'locked_map' => $lockedMap,
                'current_url_id' => $currentUrlId,
                'can_toggle_multiurl_setting' => $canToggleMultiUrlSetting,
            ]);
        }

        $settingsFromKeyword = $manager->getParametersFromKeywordOrderedByCategory($keyword);
        if (!empty($settingsFromKeyword)) {
            foreach ($settingsFromKeyword as $category => $parameterList) {
                if (empty($category)) {
                    continue;
                }

                $variablesInCategory = [];
                foreach ($parameterList as $parameter) {
                    $var = $parameter->getVariable();

                    // Hide locked settings from child URLs (do not show them at all).
                    $isLocked = 1 === (int) ($lockedMap[$var] ?? 0);
                    $isNonChangeableHidden = $hideDisabledOnSubUrl && 0 === (int) ($changeableMap[$var] ?? 1);
                    if (1 !== $currentUrlId && ($isLocked || $isNonChangeableHidden)) {
                        continue;
                    }

                    $variablesInCategory[] = $var;

                    if (isset($templateMap[$var])) {
                        $templateMapByCategory[$category][$var] = $templateMap[$var];
                    }
                }

                $schemaAlias = $manager->convertNameSpaceToService($category);

                // Skip unknown/legacy categories (e.g., "tools")
                if (!isset($schemas[$schemaAlias])) {
                    continue;
                }

                $settings = $manager->load($category);
                $form = $this->getSettingsFormFactory()->create($schemaAlias);

                // Keep only keyword-matching variables, and also remove locked/hidden-disabled ones for child URLs.
                foreach (array_keys($settings->getParameters()) as $name) {
                    $isLockedForChild = (1 !== $currentUrlId) && (1 === (int) ($lockedMap[$name] ?? 0));
                    $isNonChangeableHiddenForChild = (1 !== $currentUrlId) && $hideDisabledOnSubUrl && (0 === (int) ($changeableMap[$name] ?? 1));

                    if ($isLockedForChild || $isNonChangeableHiddenForChild || !\in_array($name, $variablesInCategory, true)) {
                        if ($form->has($name)) {
                            $form->remove($name);
                        }
                        $settings->remove($name);
                    }
                }

                $form->setData($settings);
                $formList[$category] = $form->createView();
            }
        }

        return $this->render('@ChamiloCore/Admin/Settings/search.html.twig', [
            'keyword' => $keyword,
            'schemas' => $schemas,
            'settings' => $settings,
            'form_list' => $formList,
            'search_form' => $searchForm->createView(),
            'template_map' => $templateMap,
            'template_map_by_category' => $templateMapByCategory,
            'ordered_namespaces' => $ordered,
            'namespace_labels' => $labelMap,
            'changeable_map' => $changeableMap,
            'locked_map' => $lockedMap,
            'current_url_id' => $currentUrlId,
            'can_toggle_multiurl_setting' => $canToggleMultiUrlSetting,
            'unsupported_settings' => $this->getUnsupportedSettings(),
        ]);
    }

    /**
     * Edit configuration with given namespace.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/settings/{namespace}', name: 'chamilo_platform_settings')]
    public function updateSetting(Request $request, AccessUrlHelper $accessUrlHelper, string $namespace): Response
    {
        $manager = $this->getSettingsManager();
        $url = $accessUrlHelper->getCurrent();
        $manager->setUrl($url);

        $schemaAlias = $manager->convertNameSpaceToService($namespace);

        $keyword = (string) $request->query->get('keyword', '');
        $searchDiagnostics = null;

        // Validate schema BEFORE load/create to avoid NonExistingServiceException
        $schemas = $manager->getSchemas();
        if (!isset($schemas[$schemaAlias])) {
            $this->addFlash('warning', \sprintf('Unknown settings category "%s". Showing Platform settings.', $namespace));

            return $this->redirectToRoute('chamilo_platform_settings', [
                'namespace' => 'platform',
            ]);
        }

        $settingsRepo = $this->entityManager->getRepository(SettingsCurrent::class);

        $currentUrlId = (int) $url->getId();
        $mainUrl = $this->entityManager->getRepository(AccessUrl::class)->find(1);

        // MultiURL flags: read from main URL (ID = 1) only
        $changeableMap = [];
        $lockedMap = [];

        $mainUrlRows = $settingsRepo->createQueryBuilder('sc')
            ->join('sc.url', 'u')
            ->andWhere('u.id = :mainId')
            ->setParameter('mainId', 1)
            ->getQuery()
            ->getResult()
        ;

        foreach ($mainUrlRows as $row) {
            if ($row instanceof SettingsCurrent) {
                $changeableMap[$row->getVariable()] = (int) $row->getAccessUrlChangeable();
                $lockedMap[$row->getVariable()] = method_exists($row, 'getAccessUrlLocked')
                    ? (int) $row->getAccessUrlLocked()
                    : 0;
            }
        }

        $hideDisabledOnSubUrl = false;
        if (1 !== $currentUrlId) {
            foreach ($mainUrlRows as $row) {
                if ($row instanceof SettingsCurrent && 'multiple_url_hide_disabled_settings' === $row->getVariable()) {
                    $hideDisabledOnSubUrl = 'true' === $row->getSelectedValue();

                    break;
                }
            }
        }

        $settings = $manager->load($namespace);

        $form = $this->getSettingsFormFactory()->create(
            $schemaAlias,
            null,
            ['allow_extra_fields' => true]
        );

        // Hide locked and (optionally) non-changeable settings from child URLs.
        if (1 !== $currentUrlId) {
            foreach (array_keys($settings->getParameters()) as $name) {
                $isLocked = 1 === (int) ($lockedMap[$name] ?? 0);
                $isNonChangeableHidden = $hideDisabledOnSubUrl && 0 === (int) ($changeableMap[$name] ?? 1);
                if ($isLocked || $isNonChangeableHidden) {
                    if ($form->has($name)) {
                        $form->remove($name);
                    }
                    $settings->remove($name);
                }
            }
        }

        $form->setData($settings);

        // Build extra diagnostics for Xapian and converters when editing "search" settings
        if ('search' === $namespace) {
            $searchDiagnostics = $this->buildSearchDiagnostics($manager);
        }

        $isPartial =
            $request->isMethod('PATCH')
            || 'PATCH' === strtoupper((string) $request->request->get('_method'))
            || $request->request->getBoolean('_partial', false);

        if ($isPartial) {
            $payload = $request->request->all($form->getName());
            $form->submit($payload, false);
        } else {
            $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $messageType = 'success';

            try {
                $manager->save($form->getData());
                $message = $this->trans('The settings have been stored');
            } catch (ValidatorException $validatorException) {
                $message = $this->trans($validatorException->getMessage());
                $messageType = 'error';
            }

            $this->addFlash($messageType, $message);

            if ('' !== $keyword) {
                return $this->redirectToRoute('chamilo_platform_settings_search', [
                    'keyword' => $keyword,
                ]);
            }

            return $this->redirectToRoute('chamilo_platform_settings', [
                'namespace' => $namespace,
            ]);
        }

        [$ordered, $labelMap] = $this->computeOrderedNamespacesByTranslatedLabel($schemas, $request);

        $templateMap = [];

        // Build template map: fallback to main URL templates when sub-URL has no row for a locked setting.
        if ($mainUrl instanceof AccessUrl && 1 !== $currentUrlId) {
            $mainRows = $settingsRepo->findBy(['url' => $mainUrl]);
            foreach ($mainRows as $s) {
                if ($s->getValueTemplate()) {
                    $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
                }
            }
        }

        $settingsWithTemplate = $settingsRepo->findBy(['url' => $url]);
        foreach ($settingsWithTemplate as $s) {
            if ($s->getValueTemplate()) {
                $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
            }
        }

        $platform = [
            'server_type' => (string) $manager->getSetting('platform.server_type', true),
        ];

        // Only platform admins on the main URL can toggle the MultiURL flag.
        $canToggleMultiUrlSetting = $this->isGranted('ROLE_ADMIN') && 1 === $currentUrlId;

        return $this->render('@ChamiloCore/Admin/Settings/default.html.twig', [
            'schemas' => $schemas,
            'settings' => $settings,
            'form' => $form->createView(),
            'keyword' => $keyword,
            'search_form' => $this->getSearchForm()->createView(),
            'template_map' => $templateMap,
            'ordered_namespaces' => $ordered,
            'namespace_labels' => $labelMap,
            'platform' => $platform,
            'changeable_map' => $changeableMap,
            'locked_map' => $lockedMap,
            'current_url_id' => $currentUrlId,
            'can_toggle_multiurl_setting' => $canToggleMultiUrlSetting,
            'search_diagnostics' => $searchDiagnostics,
            'unsupported_settings' => $this->getUnsupportedSettings(),
        ]);
    }

    /**
     * Sync settings from classes with the database.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/settings_sync', name: 'sync_settings')]
    public function syncSettings(AccessUrlHelper $accessUrlHelper): Response
    {
        $manager = $this->getSettingsManager();
        $url = $accessUrlHelper->getCurrent();
        $manager->setUrl($url);
        $manager->installSchemas($url);

        return new Response('Updated');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/settings/template/{id}', name: 'chamilo_platform_settings_template')]
    public function getTemplateExample(int $id): JsonResponse
    {
        $repo = $this->entityManager->getRepository(SettingsValueTemplate::class);
        $template = $repo->find($id);

        if (!$template) {
            return $this->json([
                'error' => $this->translator->trans('Template not found.'),
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'variable' => $template->getVariable(),
            'json_example' => $template->getJsonExample(),
            'description' => $template->getDescription(),
        ]);
    }

    /**
     * @return FormInterface
     */
    private function getSearchForm()
    {
        $builder = $this->container->get('form.factory')->createNamedBuilder('search');
        $builder->add('keyword', TextType::class);
        $builder->add('search', SubmitType::class, ['attr' => ['class' => 'btn btn--primary']]);

        return $builder->getForm();
    }

    private function computeOrderedNamespacesByTranslatedLabel(array $schemas, Request $request): array
    {
        // Extract raw namespaces from schema service ids
        $namespaces = array_map(
            static fn ($k) => str_replace('chamilo_core.settings.', '', $k),
            array_keys($schemas)
        );

        $transform = [
            'announcement' => 'Announcements',
            'attendance' => 'Attendances',
            'cas' => 'CAS',
            'certificate' => 'Certificates',
            'course' => 'Courses',
            'document' => 'Documents',
            'exercise' => 'Tests',
            'forum' => 'Forums',
            'group' => 'Groups',
            'language' => 'Internationalization',
            'lp' => 'Learning paths',
            'mail' => 'E-mail',
            'message' => 'Messages',
            'profile' => 'User profiles',
            'session' => 'Sessions',
            'skill' => 'Skills',
            'social' => 'Social network',
            'survey' => 'Surveys',
            'work' => 'Assignments',
            'ticket' => 'Support tickets',
            'tracking' => 'Reporting',
            'webservice' => 'Webservices',
            'catalog' => 'Catalogue',
            'catalogue' => 'Catalogue',
            'ai_helpers' => 'AI helpers',
        ];

        // Build label map (translated). For keys not in $transform, use Title Case of ns.
        $labelMap = [];
        foreach ($namespaces as $ns) {
            if (isset($transform[$ns])) {
                $labelMap[$ns] = $this->translator->trans($transform[$ns]);
            } else {
                $key = ucfirst(str_replace('_', ' ', $ns));
                $labelMap[$ns] = $this->translator->trans($key);
            }
        }

        // Sort by translated label (locale-aware)
        $collator = class_exists(Collator::class) ? new Collator($request->getLocale()) : null;
        usort($namespaces, function ($a, $b) use ($labelMap, $collator) {
            return $collator
                ? $collator->compare($labelMap[$a], $labelMap[$b])
                : strcasecmp($labelMap[$a], $labelMap[$b]);
        });

        // Optional: keep AI helpers near the top (second position)
        $idx = array_search('ai_helpers', $namespaces, true);
        if (false !== $idx) {
            array_splice($namespaces, $idx, 1);
            array_splice($namespaces, 1, 0, ['ai_helpers']);
        }

        return [$namespaces, $labelMap];
    }

    /**
     * Build environment diagnostics for the "search" settings page.
     */
    private function buildSearchDiagnostics(SettingsManager $manager): array
    {
        $searchEnabled = (string) $manager->getSetting('search.search_enabled');

        // Base status rows (Xapian extension + directory checks + custom fields)
        $indexDir = $this->searchIndexPathResolver->getIndexDir();

        $xapianLoaded = \extension_loaded('xapian');
        $dirExists = is_dir($indexDir);
        $dirWritable = is_writable($indexDir);
        $fieldsCount = $this->entityManager
            ->getRepository(SearchEngineField::class)
            ->count([])
        ;

        $statusRows = [
            [
                'label' => $this->translator->trans('Xapian module installed'),
                'ok' => $xapianLoaded,
            ],
            [
                'label' => $this->translator->trans('The directory exists').' - '.$indexDir,
                'ok' => $dirExists,
            ],
            [
                'label' => $this->translator->trans('Is writable').' - '.$indexDir,
                'ok' => $dirWritable,
            ],
            [
                'label' => $this->translator->trans('Available custom search fields'),
                'ok' => $fieldsCount > 0,
            ],
        ];

        // External converters (ps2pdf, pdftotext, ...)
        $tools = [];
        $toolsWarning = null;

        $isWindows = DIRECTORY_SEPARATOR === '\\';

        if ($isWindows) {
            $toolsWarning = $this->translator->trans(
                'You are using Chamilo on a Windows platform. Document conversion helpers are not available for full-text indexing.'
            );
        } else {
            $programs = ['ps2pdf', 'pdftotext', 'catdoc', 'html2text', 'unrtf', 'catppt', 'xls2csv'];

            foreach ($programs as $program) {
                $output = [];
                $returnVar = null;

                // Same behaviour as "which $program" in Chamilo 1
                @exec('which '.escapeshellarg($program), $output, $returnVar);
                $path = $output[0] ?? '';
                $installed = '' !== $path;

                $tools[] = [
                    'name' => $program,
                    'path' => $path,
                    'ok' => $installed,
                ];
            }
        }

        return [
            // Whether full-text search is enabled at all
            'enabled' => ('true' === $searchEnabled),
            // Xapian + directory + custom fields
            'status_rows' => $statusRows,
            // External converters
            'tools' => $tools,
            'tools_warning' => $toolsWarning,
        ];
    }

    /**
     * Settings that exist in the database but are not yet implemented in Chamilo 2.x.
     * Rendered as disabled with a "not yet supported" notice in the settings UI.
     * Format: 'category.variable'.
     *
     * @return string[]
     */
    private function getUnsupportedSettings(): array
    {
        return [
            'ai_helpers.course_analyser',
            'course.enable_tool_introduction',
            'course.show_toolshortcuts',
            'document.default_group_quotum',
            'dropbox.dropbox_allow_mailing',
            'editor.video_context_menu_hidden',
            'exercise.exercise_result_end_text_html_strict_filtering',
            'exercise.my_courses_show_pending_exercise_attempts',
            'exercise.quiz_discard_orphan_in_course_export',
            'exercise.quiz_hide_attempts_table_on_start_page',
            'exercise.quiz_keep_alive_ping_interval',
            'exercise.quiz_question_delete_automatically_when_deleting_exercise',
            'exercise.show_exercise_attempts_in_all_user_sessions',
            'exercise.tracking_my_progress_show_deleted_exercises',
            'forum.allow_forum_category_language_filter',
            'forum.subscribe_users_to_forum_notifications_also_in_base_course',
            'gradebook.gradebook_enable_subcategory_skills_independant_assignement',
            'gradebook.gradebook_hide_table',
            'gradebook.my_display_coloring',
            'language.auto_detect_language_custom_pages',
            'language.template_activate_language_filter',
            'lp.allow_import_scorm_package_in_course_builder',
            'lp.lp_allow_export_to_students',
            'lp.lp_enable_flow',
            'lp.lp_item_prerequisite_dates',
            'lp.lp_prerequisite_on_quiz_unblock_if_max_attempt_reached',
            'lp.lp_prerequisite_use_last_attempt_only',
            'lp.lp_start_and_end_date_visible_in_student_view',
            'lp.scorm_api_username_as_student_id',
            'lp.scorm_lms_update_sco_status_all_time',
            'lp.scorm_upload_from_cache',
            'lp.show_invisible_exercise_in_lp_list',
            'lp.show_invisible_lp_in_course_home',
            'lp.student_follow_page_hide_lp_tests_average',
            'lp.student_follow_page_include_not_subscribed_lp_students',
            'mail.mailer_debug_enable',
            'message.filter_interactivity_messages',
            'platform.disable_copy_paste',
            'platform.institution_address',
            'platform.platform_logo_url',
            'platform.use_career_external_id_as_identifier_in_diagrams',
            'platform.use_custom_pages',
            'platform.use_virtual_keyboard',
            'profile.enable_profile_user_address_geolocalization',
            'profile.hide_username_in_course_chat',
            'profile.pass_reminder_custom_link',
            'registration.drh_autosubscribe',
            'registration.sessionadmin_autosubscribe',
            'registration.student_autosubscribe',
            'registration.teacher_autosubscribe',
            'registration.user_hide_never_expire_option',
            'security.admins_can_set_users_pass',
            'security.hide_breadcrumb_if_not_allowed',
            'security.security_session_cookie_samesite_none',
            'session.assignment_base_course_teacher_access_to_all_session',
            'session.career_diagram_disclaimer',
            'session.career_diagram_legend',
            'session.email_template_subscription_to_session_confirmation_lost_password',
            'session.email_template_subscription_to_session_confirmation_username',
            'session.hide_session_graph_in_my_progress',
            'session.my_progress_session_show_all_courses',
            'session.session_course_users_subscription_limited_to_session_users',
            'session.session_days_after_coach_access',
            'session.session_days_before_coach_access',
            'session.show_session_data',
            'skill.badge_assignation_notification',
            'webservice.allow_download_documents_by_api_key',
            'webservice.messaging_gdc_project_number',
            'webservice.webservice_enable_adminonly_api',
            'webservice.webservice_return_user_field',
            'work.my_courses_show_pending_work',
            'workflows.disable_user_conditions_sender_id',
            'workflows.drh_allow_access_to_all_students',
            'workflows.usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe',
            'workflows.usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe',
            'workflows.usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe',
        ];
    }
}

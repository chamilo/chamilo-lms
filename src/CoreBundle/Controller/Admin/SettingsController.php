<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsValueTemplate;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
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

#[Route('/admin')]
class SettingsController extends BaseController
{
    use ControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator
    ) {}

    #[Route('/settings', name: 'admin_settings')]
    public function index(): Response
    {
        return $this->redirectToRoute('chamilo_platform_settings', ['namespace' => 'platform']);
    }

    /**
     * Toggle access_url_changeable for a given setting variable.
     * Only platform admins on the main URL (ID = 1) are allowed to change it,
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/settings/toggle_changeable', name: 'settings_toggle_changeable', methods: ['POST'])]
    public function toggleChangeable(Request $request, AccessUrlHelper $accessUrlHelper): JsonResponse
    {
        // Security: only admins.
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Only platform admins can modify this flag.',
            ], 403);
        }

        $currentUrl = $accessUrlHelper->getCurrent();
        $currentUrlId = $currentUrl->getId();

        // Only main URL (ID = 1) can toggle the flag.
        if (1 !== $currentUrlId) {
            return $this->json([
                'error' => 'Only the main URL (ID 1) can toggle this setting.',
            ], 403);
        }

        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload) || !isset($payload['variable'], $payload['status'])) {
            return $this->json([
                'error' => 'Invalid payload.',
            ], 400);
        }

        $variable = (string) $payload['variable'];
        $status = (int) $payload['status'];

        $repo = $this->entityManager->getRepository(SettingsCurrent::class);

        // We search by variable + current main AccessUrl entity.
        $setting = $repo->findOneBy([
            'variable' => $variable,
            'url' => $currentUrl,
        ]);

        if (!$setting) {
            return $this->json([
                'error' => 'Setting not found.',
            ], 404);
        }

        try {
            $setting->setAccessUrlChangeable($status);
            $this->entityManager->flush();

            return $this->json([
                'result' => 1,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
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

        // Template map for current URL (existing behavior – JSON helper)
        $settingsRepo = $this->entityManager->getRepository(SettingsCurrent::class);
        $settingsWithTemplate = $settingsRepo->findBy(['url' => $url]);

        foreach ($settingsWithTemplate as $s) {
            if ($s->getValueTemplate()) {
                $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
            }
        }

        // MultiURL changeable flags: read from main URL (ID = 1) only
        $changeableMap = [];
        $mainUrlRows = $settingsRepo->createQueryBuilder('sc')
            ->join('sc.url', 'u')
            ->andWhere('u.id = :mainId')
            ->setParameter('mainId', 1)
            ->getQuery()
            ->getResult();

        foreach ($mainUrlRows as $row) {
            if ($row instanceof SettingsCurrent) {
                $changeableMap[$row->getVariable()] = $row->getAccessUrlChangeable();
            }
        }

        $currentUrlId = $url->getId();
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
                    $variablesInCategory[] = $var;
                    if (isset($templateMap[$var])) {
                        $templateMapByCategory[$category][$var] = $templateMap[$var];
                    }
                }

                // Convert category to schema alias and validate it BEFORE loading/creating the form
                $schemaAlias = $manager->convertNameSpaceToService($category);

                // Skip unknown/legacy categories (e.g., "tools")
                if (!isset($schemas[$schemaAlias])) {
                    continue;
                }

                $settings = $manager->load($category);
                $form = $this->getSettingsFormFactory()->create($schemaAlias);

                foreach (array_keys($settings->getParameters()) as $name) {
                    if (!\in_array($name, $variablesInCategory, true)) {
                        $form->remove($name);
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
            'current_url_id' => $currentUrlId,
            'can_toggle_multiurl_setting' => $canToggleMultiUrlSetting,
        ]);
    }

    /**
     * Edit configuration with given namespace (main settings page).
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

        // Validate schema BEFORE load/create to avoid NonExistingServiceException
        $schemas = $manager->getSchemas();
        if (!isset($schemas[$schemaAlias])) {
            $this->addFlash('warning', \sprintf('Unknown settings category "%s". Showing Platform settings.', $namespace));

            return $this->redirectToRoute('chamilo_platform_settings', [
                'namespace' => 'platform',
            ]);
        }

        $settings = $manager->load($namespace);

        $form = $this->getSettingsFormFactory()->create(
            $schemaAlias,
            null,
            ['allow_extra_fields' => true]
        );

        $form->setData($settings);

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
        $settingsRepo = $this->entityManager->getRepository(SettingsCurrent::class);

        // Template map for current URL (existing behavior – JSON helper)
        $settingsWithTemplate = $settingsRepo->findBy(['url' => $url]);

        foreach ($settingsWithTemplate as $s) {
            if ($s->getValueTemplate()) {
                $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
            }
        }

        // MultiURL changeable flags: read from main URL (ID = 1) only
        $changeableMap = [];
        $mainUrlRows = $settingsRepo->createQueryBuilder('sc')
            ->join('sc.url', 'u')
            ->andWhere('u.id = :mainId')
            ->setParameter('mainId', 1)
            ->getQuery()
            ->getResult();

        foreach ($mainUrlRows as $row) {
            if ($row instanceof SettingsCurrent) {
                $changeableMap[$row->getVariable()] = $row->getAccessUrlChangeable();
            }
        }

        $platform = [
            'server_type' => (string) $manager->getSetting('platform.server_type', true),
        ];

        $currentUrlId = $url->getId();
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
            'current_url_id' => $currentUrlId,
            'can_toggle_multiurl_setting' => $canToggleMultiUrlSetting,
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
}

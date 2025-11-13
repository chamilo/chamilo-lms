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
     * Edit configuration with given namespace.
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
            ]);
        }

        $settingsRepo = $this->entityManager->getRepository(SettingsCurrent::class);
        $settingsWithTemplate = $settingsRepo->findBy(['url' => $url]);
        foreach ($settingsWithTemplate as $s) {
            if ($s->getValueTemplate()) {
                $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
            }
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

                // skip unknown/legacy categories (e.g., "tools")
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

        $settingsWithTemplate = $settingsRepo->findBy(['url' => $url]);

        foreach ($settingsWithTemplate as $s) {
            if ($s->getValueTemplate()) {
                $templateMap[$s->getVariable()] = $s->getValueTemplate()->getId();
            }
        }
        $platform = [
            'server_type' => (string) $manager->getSetting('platform.server_type', true),
        ];

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

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\SettingsBundle\Registry\ServiceRegistryInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const ARRAY_FILTER_USE_KEY;

#[IsGranted('ROLE_ADMIN')]
final class CourseSettingPolicyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseSettingPolicyManager $policyManager,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SettingsManager $settingsManager,
        #[Autowire(service: 'chamilo_course.registry.settings_schema')]
        private readonly ServiceRegistryInterface $courseSchemas,
    ) {}

    #[Route('/admin/settings/course-settings', name: 'chamilo_course_settings_policies', methods: ['GET', 'POST'], priority: 10)]
    public function __invoke(Request $request): Response
    {
        // These policies apply to every URL; only administrators of the main URL may change them.
        if (1 !== (int) $this->accessUrlHelper->getCurrent()?->getId()) {
            throw $this->createAccessDeniedException('This page is only available on the main access URL.');
        }

        $definitions = CourseSettingsManager::getPolicyDefaults();
        $allowedValues = CourseSettingsManager::getPolicyAllowedValues();
        $schemaDefaults = [];
        $namedCategories = [];

        foreach ($this->courseSchemas->all() as $namespace => $schema) {
            $category = str_replace('chamilo_course.settings.', '', (string) $namespace);
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $category)) {
                continue;
            }
            $builder = new SettingsBuilder();
            $schema->buildSettings($builder);
            foreach ($builder->resolve([]) as $variable => $value) {
                if (!\is_string($variable) || !preg_match('/^[a-z][a-z0-9_]*$/', $variable)
                    || !\is_scalar($value)) {
                    continue;
                }
                $namedCategories[$variable][$category] = true;
                $schemaDefaults[$variable][$category] = (string) $value;
                $definitions[$variable] ??= (string) $value;
            }
        }

        $known = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT DISTINCT variable, category FROM c_course_setting ORDER BY variable, category',
        );
        foreach ($known as $item) {
            $variable = $item['variable'] ?? null;
            if (!\is_string($variable) || !preg_match('/^[a-z][a-z0-9_]*$/', $variable)) {
                continue;
            }
            $category = trim((string) ($item['category'] ?? ''));
            if ('' !== $category && preg_match('/^[a-z][a-z0-9_]*$/', $category)) {
                $namedCategories[$variable][$category] = true;
            }
            $definitions[$variable] ??= '';
        }

        // Blank categories in older courses represent legacy aliases. Distinct
        // named categories require separate keys to avoid cross-tool overrides.
        $ambiguous = [];
        foreach ($namedCategories as $variable => $categories) {
            if (\count($categories) <= 1) {
                continue;
            }
            $ambiguous[$variable] = true;
            $baseDefault = $definitions[$variable] ?? '';
            unset($definitions[$variable]);
            foreach (array_keys($categories) as $category) {
                $key = $category.'.'.$variable;
                $definitions[$key] = $schemaDefaults[$variable][$category] ?? $baseDefault;
                if ('enabled' === $variable) {
                    $allowedValues[$key] = ['0', '1'];
                    if ('' === $definitions[$key]) {
                        array_unshift($allowedValues[$key], '');
                    }
                } elseif (isset($allowedValues[$variable])) {
                    $allowedValues[$key] = $allowedValues[$variable];
                }
            }
        }
        ksort($definitions);

        $search = trim((string) $request->query->get('q', ''));
        $search = mb_substr($search, 0, 120);
        $visibleDefinitions = '' === $search ? $definitions : array_filter(
            $definitions,
            static fn (string $variable): bool => str_contains(mb_strtolower($variable), mb_strtolower($search)),
            ARRAY_FILTER_USE_KEY,
        );

        $policies = $this->policyManager->getPolicies();
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('course_settings_policies', (string) $request->request->get('_token', ''))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $editable = $request->request->all('editable');
            $defaults = $request->request->all('defaults');
            if (array_diff(array_keys($defaults), array_keys($definitions))
                || array_diff(array_keys($editable), array_keys($definitions))) {
                throw $this->createNotFoundException('Unknown course setting.');
            }

            if ([] === $defaults) {
                throw $this->createNotFoundException('No course settings submitted.');
            }

            $updated = $policies;
            // Only update explicitly submitted rows. A filtered form must not
            // turn other course settings into non-editable settings.
            foreach ($defaults as $variable => $value) {
                $originalDefault = $definitions[$variable];
                if (!\is_string($value) || mb_strlen($value) > 2048
                    || (isset($allowedValues[$variable]) && !\in_array($value, $allowedValues[$variable], true))) {
                    throw $this->createNotFoundException('Invalid course setting value.');
                }
                $canEdit = '1' === ($editable[$variable] ?? null);
                // Unchanged controls must not alter any existing course's legacy defaults.
                if ($canEdit && !isset($policies[$variable]) && $value === $originalDefault) {
                    continue;
                }
                $updated[$variable] = [
                    'editable' => $canEdit,
                    'default' => $value,
                ];
            }
            $this->policyManager->savePolicies($updated);
            $this->addFlash('success', 'Course settings policies updated.');

            return $this->redirectToRoute('chamilo_course_settings_policies', ['q' => $search]);
        }

        return $this->render('@ChamiloCore/Admin/Settings/course_policies.html.twig', [
            'schemas' => $this->settingsManager->getSchemas(),
            'definitions' => $visibleDefinitions,
            'search' => $search,
            'total_count' => \count($definitions),
            'ambiguous_count' => \count($ambiguous),
            'policies' => $policies,
            'allowed_values' => $allowedValues,
        ]);
    }
}

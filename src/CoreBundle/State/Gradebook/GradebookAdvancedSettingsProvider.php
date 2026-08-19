<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookAdvancedSettings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradeComponents;
use Chamilo\CoreBundle\Entity\GradeModel;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<GradebookAdvancedSettings>
 */
final readonly class GradebookAdvancedSettingsProvider implements ProviderInterface
{
    private const SKILL_RESULT_LIMIT = 50;

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookAdvancedSettings
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request);
        if (!$resolved['canManage']) {
            throw new AccessDeniedHttpException('You are not allowed to manage advanced Gradebook settings in this context.');
        }

        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $this->contextResolver->getSelectedCategory(
            $request,
            $resolved['course'],
            $resolved['session'],
            $rootCategory,
        );
        $isRoot = (int) $category->getId() === (int) $rootCategory->getId();

        $gradeModelEnabled = $this->contextResolver->isSettingEnabled('gradebook.gradebook_enable_grade_model');
        $teachersCanChangeGradeModel = $this->contextResolver->isSettingEnabled(
            'gradebook.teachers_can_change_grade_model_settings'
        );
        $gradeModelFrozen = $isRoot && ($rootCategory->getSubCategories()->count() > 0 || $rootCategory->getLinks()->count() > 0);
        $canChangeGradeModel = $isRoot
            && $gradeModelEnabled
            && $resolved['canManage']
            && ($this->security->isGranted('ROLE_ADMIN') || $teachersCanChangeGradeModel)
            && !$gradeModelFrozen;

        $skillToolEnabled = $this->contextResolver->isSettingEnabled('skill.allow_skills_tool');
        $teachersCanAssignSkills = $this->contextResolver->isSettingEnabled('skill.skills_teachers_can_assign_skills');
        $canManageSkills = $resolved['canManage']
            && $skillToolEnabled
            && (
                $this->security->isGranted('ROLE_ADMIN')
                || $this->security->isGranted('ROLE_HR')
                || $teachersCanAssignSkills
            );

        $selectedSkillIds = $this->getSelectedSkillIds($category);
        $skillQuery = trim((string) $request->query->get('skillQuery', ''));

        $resource = new GradebookAdvancedSettings();
        $resource->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resource->category = [
            'id' => (int) $category->getId(),
            'parentId' => (int) ($category->getParent()?->getId() ?? 0),
            'isRoot' => $isRoot,
            'title' => $category->getTitle(),
        ];
        $resource->canManage = $resolved['canManage'];
        $resource->canManageSkills = $canManageSkills;
        $resource->gradeModelEnabled = $gradeModelEnabled;
        $resource->canChangeGradeModel = $canChangeGradeModel;
        $resource->gradeModelFrozen = $gradeModelFrozen;
        $resource->gradeModelId = null !== $category->getGradeModel() ? (int) $category->getGradeModel()->getId() : null;
        $resource->defaultGradeModelId = $this->getDefaultGradeModelId();
        $resource->gradeModels = $gradeModelEnabled ? $this->getGradeModels() : [];
        $resource->skillIds = $selectedSkillIds;
        $resource->skills = $canManageSkills ? $this->getSkillOptions($skillQuery, $selectedSkillIds) : [];
        $resource->skillToolEnabled = $skillToolEnabled;
        $resource->allowSubcategorySkillsSetting = $this->contextResolver->isSettingEnabled(
            'gradebook.gradebook_enable_subcategory_skills_independant_assignement'
        );
        $resource->parentAllowsSkillsBySubcategory = $this->parentAllowsSkillsBySubcategory(
            $request,
            $category,
            $rootCategory,
            $resolved['course'],
            $resolved['session'],
        );

        return $resource;
    }

    /**
     * @return list<int>
     */
    private function getSelectedSkillIds(GradebookCategory $category): array
    {
        $ids = [];
        foreach ($category->getSkills() as $relation) {
            if (!$relation instanceof SkillRelGradebook) {
                continue;
            }
            $skillId = $relation->getSkill()->getId();
            if (null !== $skillId) {
                $ids[] = (int) $skillId;
            }
        }

        sort($ids);

        return array_values(array_unique($ids));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getGradeModels(): array
    {
        $models = $this->entityManager->getRepository(GradeModel::class)->findBy([], ['title' => 'ASC']);
        $result = [];

        foreach ($models as $model) {
            if (!$model instanceof GradeModel || null === $model->getId()) {
                continue;
            }

            $components = $this->entityManager->getRepository(GradeComponents::class)->findBy(
                ['gradeModel' => $model],
                ['id' => 'ASC'],
            );
            $normalizedComponents = [];
            foreach ($components as $component) {
                if (!$component instanceof GradeComponents) {
                    continue;
                }
                $normalizedComponents[] = [
                    'id' => (int) $component->getId(),
                    'title' => (string) $component->getTitle(),
                    'acronym' => (string) $component->getAcronym(),
                    'percentage' => (float) $component->getPercentage(),
                ];
            }

            $result[] = [
                'id' => (int) $model->getId(),
                'title' => (string) $model->getTitle(),
                'description' => trim(strip_tags((string) $model->getDescription())),
                'components' => $normalizedComponents,
            ];
        }

        return $result;
    }

    private function getDefaultGradeModelId(): ?int
    {
        $value = $this->settingsManager->getSetting('gradebook.gradebook_default_grade_model_id', true);
        if (!is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    /**
     * @param list<int> $selectedSkillIds
     *
     * @return list<array{id: int, title: string}>
     */
    private function getSkillOptions(string $query, array $selectedSkillIds): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('skill')
            ->from(Skill::class, 'skill')
            ->orderBy('skill.title', 'ASC')
            ->setMaxResults(self::SKILL_RESULT_LIMIT)
        ;

        if ('' !== $query) {
            $qb
                ->andWhere('LOWER(skill.title) LIKE :skillQuery')
                ->setParameter('skillQuery', '%'.mb_strtolower($query).'%', Types::STRING)
            ;
        }

        $skills = $qb->getQuery()->getResult();
        $byId = [];
        foreach ($skills as $skill) {
            if (!$skill instanceof Skill || null === $skill->getId()) {
                continue;
            }
            $byId[(int) $skill->getId()] = [
                'id' => (int) $skill->getId(),
                'title' => $skill->getTitle(),
            ];
        }

        if ([] !== $selectedSkillIds) {
            $selected = $this->entityManager->createQueryBuilder()
                ->select('skill')
                ->from(Skill::class, 'skill')
                ->where('skill.id IN (:skillIds)')
                ->setParameter('skillIds', $selectedSkillIds, ArrayParameterType::INTEGER)
                ->getQuery()
                ->getResult()
            ;
            foreach ($selected as $skill) {
                if (!$skill instanceof Skill || null === $skill->getId()) {
                    continue;
                }
                $byId[(int) $skill->getId()] = [
                    'id' => (int) $skill->getId(),
                    'title' => $skill->getTitle(),
                ];
            }
        }

        uasort($byId, static fn (array $a, array $b): int => strcasecmp($a['title'], $b['title']));

        return array_values($byId);
    }

    private function parentAllowsSkillsBySubcategory(
        Request $request,
        GradebookCategory $category,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): bool {
        $parentCategoryId = $request->query->getInt('parentCategoryId');
        if ($parentCategoryId > 0) {
            $parent = $this->contextResolver->getCategoryInGradebook(
                $parentCategoryId,
                $rootCategory,
                $course,
                $session,
            );

            return 1 === (int) $parent->getAllowSkillsBySubcategory();
        }

        $parent = $category->getParent();
        if (!$parent instanceof GradebookCategory) {
            return 1 === (int) $category->getAllowSkillsBySubcategory();
        }

        return 1 === (int) $parent->getAllowSkillsBySubcategory();
    }
}

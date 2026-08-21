<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCategoryAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradeComponents;
use Chamilo\CoreBundle\Entity\GradeModel;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\GradebookCalculationMode;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\EventLoggerHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookCategoryAction, GradebookCategoryAction>
 */
final readonly class GradebookCategoryActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_category_action';

    private const ACTION_INITIALIZE = 'initialize';
    private const ACTION_CREATE = 'create';
    private const ACTION_UPDATE = 'update';
    private const ACTION_DELETE = 'delete';
    private const ACTION_MOVE = 'move';
    private const ACTION_SET_VISIBILITY = 'set_visibility';
    private const ACTION_LOCK = 'lock';
    private const ACTION_UNLOCK = 'unlock';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private EventLoggerHelper $eventLoggerHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookCategoryAction
    {
        if (!$data instanceof GradebookCategoryAction) {
            throw new BadRequestHttpException('Invalid Gradebook category action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
        $this->validateCourseResourceNode($request, $course);
        $this->validateGroupContext($operation, $course);
        $user = $this->getCurrentUser();

        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage Gradebook categories in this context.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $action = strtolower(trim($data->action));
        $rootCategory = $this->findRootCategory($course, $session);
        $affectedCategory = match ($action) {
            self::ACTION_INITIALIZE => $this->initializeGradebook($rootCategory, $course, $session, $user),
            self::ACTION_CREATE => $this->createCategory($data, $rootCategory, $course, $session, $user),
            self::ACTION_UPDATE => $this->updateCategory($data, $rootCategory, $course, $session, $user),
            self::ACTION_DELETE => $this->deleteCategory($data, $rootCategory, $course, $session),
            self::ACTION_MOVE => $this->moveCategory($data, $rootCategory, $course, $session),
            self::ACTION_SET_VISIBILITY => $this->setCategoryVisibility($data, $rootCategory, $course, $session),
            self::ACTION_LOCK => $this->setCategoryLock($data, $rootCategory, $course, $session, $user, true),
            self::ACTION_UNLOCK => $this->setCategoryLock($data, $rootCategory, $course, $session, $user, false),
            default => throw new BadRequestHttpException('Unsupported Gradebook category action.'),
        };

        $this->entityManager->flush();

        $response = new GradebookCategoryAction();
        $response->action = $action;
        $response->categoryId = $affectedCategory instanceof GradebookCategory
            ? (int) $affectedCategory->getId()
            : $data->categoryId;
        $response->success = true;

        return $response;
    }

    private function initializeGradebook(
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookCategory {
        if ($rootCategory instanceof GradebookCategory) {
            return $rootCategory;
        }

        $title = $course->getCode();
        if ($session instanceof Session) {
            $title .= ' - Session '.$session->getTitle();
        }

        $rootCategory = new GradebookCategory();
        $rootCategory
            ->setTitle($title)
            ->setDescription(null)
            ->setUser($user)
            ->setCourse($course)
            ->setSession($session)
            ->setParent(null)
            ->setWeight(100.0)
            ->setCalculationMode(GradebookCalculationMode::WEIGHTED_AVERAGE)
            ->setVisible(false)
            ->setCertifMinScore(75)
            ->setLocked(0)
            ->setGenerateCertificates(false)
            ->setIsRequirement(null === $session)
            ->setAllowSkillsBySubcategory(1)
        ;

        $this->entityManager->persist($rootCategory);

        return $rootCategory;
    }

    private function createCategory(
        GradebookCategoryAction $data,
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookCategory {
        if (!$rootCategory instanceof GradebookCategory) {
            throw new BadRequestHttpException('The Gradebook must be initialized before creating a category.');
        }

        $parentId = (int) ($data->parentCategoryId ?? 0);
        $parent = $parentId > 0
            ? $this->getCategoryInGradebook($parentId, $rootCategory, $course, $session)
            : $rootCategory;

        if ($this->isLockedForCurrentUser($parent)) {
            throw new AccessDeniedHttpException('The parent Gradebook category is locked.');
        }

        if (null !== $parent->getGradeModel()) {
            throw new AccessDeniedHttpException('Categories managed by a Grade Model cannot receive manual subcategories.');
        }

        $category = new GradebookCategory();
        $category
            ->setTitle($this->normalizeTitle($data->title))
            ->setDescription($this->normalizeDescription($data->description))
            ->setUser($user)
            ->setCourse($course)
            ->setSession($session)
            ->setParent($parent)
            ->setWeight($this->normalizeWeight($data->weight))
            ->setCalculationMode($this->normalizeCalculationMode($data->calculationMode))
            ->setVisible($data->visible ?? $this->isGradebookVisibleByDefault())
            ->setLocked(0)
            ->setGenerateCertificates(false)
            ->setIsRequirement($data->isRequirement ?? false)
            ->setAllowSkillsBySubcategory(1)
        ;

        if (null !== $data->certificateMinScore) {
            if (!$this->canSetSubcategoryMinimumScore($parent)) {
                throw new AccessDeniedHttpException('A minimum score for skills is not enabled for this Gradebook category.');
            }

            $category->setCertifMinScore($this->normalizeMinimumScore($data->certificateMinScore));
        }

        $this->entityManager->persist($category);
        $this->updateCategorySkills($category, $data->skillIds);

        return $category;
    }

    private function updateCategory(
        GradebookCategoryAction $data,
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookCategory {
        $category = $this->requireCategory($data, $rootCategory, $course, $session);
        if ($this->isLockedForCurrentUser($category)) {
            throw new AccessDeniedHttpException('The Gradebook category is locked.');
        }

        $isRoot = $category === $rootCategory;
        if (!$isRoot) {
            $category->setTitle($this->normalizeTitle($data->title));
        }

        $category
            ->setDescription($this->normalizeDescription($data->description))
            ->setWeight($this->normalizeWeight($data->weight))
            ->setCalculationMode($this->normalizeCalculationMode($data->calculationMode))
            ->setIsRequirement($data->isRequirement ?? false)
        ;

        if (!$isRoot && null !== $data->visible) {
            $category->setVisible($data->visible);
        }

        if (null !== $data->certificateMinScore) {
            if ($isRoot) {
                $category->setCertifMinScore($this->normalizeMinimumScore($data->certificateMinScore));
            } elseif ($category->getParent() instanceof GradebookCategory
                && $this->canSetSubcategoryMinimumScore($category->getParent())
            ) {
                $category->setCertifMinScore($this->normalizeMinimumScore($data->certificateMinScore));
            } else {
                throw new AccessDeniedHttpException('A minimum score for skills is not enabled for this Gradebook category.');
            }
        }

        if ($isRoot) {
            if (null !== $data->generateCertificates) {
                if (!$this->canChangeGradeModelSettings()) {
                    throw new AccessDeniedHttpException('You are not allowed to change Gradebook certificate generation.');
                }

                $category->setGenerateCertificates($data->generateCertificates);
            }

            if ($this->isSettingEnabled('gradebook.gradebook_enable_subcategory_skills_independant_assignement')
                && null !== $data->allowSkillsBySubcategory
            ) {
                $category->setAllowSkillsBySubcategory($data->allowSkillsBySubcategory ? 1 : 0);
            }

            if (null !== $data->gradeModelId) {
                $this->updateGradeModel($category, $data->gradeModelId, $course, $session, $user);
            }
        } elseif (null !== $data->gradeModelId) {
            throw new AccessDeniedHttpException('A Grade Model can only be assigned to the root Gradebook category.');
        }

        $this->updateCategorySkills($category, $data->skillIds);

        return $category;
    }

    private function deleteCategory(
        GradebookCategoryAction $data,
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): ?GradebookCategory {
        $category = $this->requireCategory($data, $rootCategory, $course, $session);
        if ($category === $rootCategory) {
            throw new AccessDeniedHttpException('The root Gradebook category cannot be deleted.');
        }

        if ($this->isLockedForCurrentUser($category)) {
            throw new AccessDeniedHttpException('The Gradebook category is locked.');
        }

        $this->entityManager->remove($category);

        return null;
    }

    private function moveCategory(
        GradebookCategoryAction $data,
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookCategory {
        $category = $this->requireCategory($data, $rootCategory, $course, $session);
        if ($category === $rootCategory) {
            throw new AccessDeniedHttpException('The root Gradebook category cannot be moved.');
        }

        if ($this->isLockedForCurrentUser($category)) {
            throw new AccessDeniedHttpException('The Gradebook category is locked.');
        }

        $targetId = (int) ($data->targetCategoryId ?? 0);
        if ($targetId <= 0) {
            throw new BadRequestHttpException('A target Gradebook category is required.');
        }

        $target = $this->getCategoryInGradebook($targetId, $rootCategory, $course, $session);
        if ($target === $category || $this->isCategoryDescendantOf($target, $category)) {
            throw new BadRequestHttpException('A Gradebook category cannot be moved into itself or one of its descendants.');
        }

        if ($this->isLockedForCurrentUser($target)) {
            throw new AccessDeniedHttpException('The target Gradebook category is locked.');
        }

        $category->setParent($target);

        return $category;
    }

    private function setCategoryVisibility(
        GradebookCategoryAction $data,
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookCategory {
        $category = $this->requireCategory($data, $rootCategory, $course, $session);
        if ($category === $rootCategory) {
            throw new AccessDeniedHttpException('The root Gradebook category visibility cannot be changed here.');
        }

        if ($this->isLockedForCurrentUser($category)) {
            throw new AccessDeniedHttpException('The Gradebook category is locked.');
        }

        if (null === $data->visible) {
            throw new BadRequestHttpException('A visibility value is required.');
        }

        $this->applyVisibilityToChildren($category, $data->visible);

        return $category;
    }

    private function setCategoryLock(
        GradebookCategoryAction $data,
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
        bool $locked,
    ): GradebookCategory {
        if (!$this->isSettingEnabled('gradebook.gradebook_locking_enabled')) {
            throw new AccessDeniedHttpException('Gradebook locking is disabled on this platform.');
        }

        if (!$locked && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only a platform administrator can unlock a Gradebook category.');
        }

        $category = $this->requireCategory($data, $rootCategory, $course, $session);
        $value = $locked ? 1 : 0;
        $category->setLocked($value);

        foreach ($category->getEvaluations() as $evaluation) {
            if ($evaluation instanceof GradebookEvaluation) {
                $evaluation->setLocked($value);
            }
        }

        foreach ($category->getLinks() as $link) {
            if ($link instanceof GradebookLink) {
                $link->setLocked($value);
            }
        }

        $this->eventLoggerHelper->addEvent(
            $locked ? 'gradebook_locked' : 'gradebook_unlocked',
            'gradebook_id',
            (int) $category->getId(),
            null,
            (int) $user->getId(),
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
        );

        return $category;
    }

    private function requireCategory(
        GradebookCategoryAction $data,
        ?GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookCategory {
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $categoryId = (int) ($data->categoryId ?? 0);
        if ($categoryId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook category id is required.');
        }

        return $this->getCategoryInGradebook($categoryId, $rootCategory, $course, $session);
    }

    private function getCategoryInGradebook(
        int $categoryId,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookCategory {
        $category = $this->entityManager->getRepository(GradebookCategory::class)->find($categoryId);
        if (!$category instanceof GradebookCategory) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }

        $this->assertCategoryContext($category, $course, $session);
        if (!$this->isCategoryDescendantOf($category, $rootCategory)) {
            throw new AccessDeniedHttpException('The requested Gradebook category is outside the current Gradebook.');
        }

        return $category;
    }

    private function applyVisibilityToChildren(GradebookCategory $category, bool $visible): void
    {
        $category->setVisible($visible);
        $value = $visible ? 1 : 0;

        foreach ($category->getEvaluations() as $evaluation) {
            if ($evaluation instanceof GradebookEvaluation) {
                $evaluation->setVisible($value);
            }
        }

        foreach ($category->getLinks() as $link) {
            if ($link instanceof GradebookLink) {
                $link->setVisible($value);
            }
        }

        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory instanceof GradebookCategory) {
                $this->applyVisibilityToChildren($subCategory, $visible);
            }
        }
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function validateCourseResourceNode(Request $request, Course $course): void
    {
        $nodeId = $request->query->getInt('node');
        if ($nodeId <= 0) {
            throw new BadRequestHttpException('A valid course resource node id is required.');
        }

        $resourceNode = $course->getResourceNode();
        if (null === $resourceNode || (int) $resourceNode->getId() !== $nodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to the current course.');
        }
    }

    private function validateGroupContext(Operation $operation, Course $course): void
    {
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        if (!$group instanceof CGroup) {
            return;
        }

        $groupNode = $group->getResourceNode();
        $courseNode = $course->getResourceNode();
        if (null === $groupNode || null === $courseNode
            || (int) ($groupNode->getParent()?->getId() ?? 0) !== (int) $courseNode->getId()
        ) {
            throw new AccessDeniedHttpException('The requested group does not belong to the current course.');
        }
    }

    private function findRootCategory(Course $course, ?Session $session): ?GradebookCategory
    {
        return $this->entityManager->getRepository(GradebookCategory::class)->findOneBy(
            [
                'course' => $course,
                'session' => $session,
                'parent' => null,
            ],
            ['id' => 'ASC'],
        );
    }

    private function assertCategoryContext(GradebookCategory $category, Course $course, ?Session $session): void
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another course.');
        }

        $categorySessionId = null !== $category->getSession() ? (int) $category->getSession()->getId() : 0;
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        if ($categorySessionId !== $sessionId) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another session context.');
        }
    }

    private function isCategoryDescendantOf(GradebookCategory $category, GradebookCategory $rootCategory): bool
    {
        $visited = [];
        $current = $category;

        while (null !== $current) {
            $currentId = (int) $current->getId();
            if ($currentId === (int) $rootCategory->getId()) {
                return true;
            }

            if (isset($visited[$currentId])) {
                return false;
            }

            $visited[$currentId] = true;
            $current = $current->getParent();
        }

        return false;
    }

    private function updateGradeModel(
        GradebookCategory $rootCategory,
        int $gradeModelId,
        Course $course,
        ?Session $session,
        User $user,
    ): void {
        if (!$this->isSettingEnabled('gradebook.gradebook_enable_grade_model')) {
            throw new AccessDeniedHttpException('Grade Models are disabled on this platform.');
        }
        if (!$this->canChangeGradeModelSettings()) {
            throw new AccessDeniedHttpException('You are not allowed to change the Grade Model.');
        }

        $currentGradeModelId = (int) ($rootCategory->getGradeModel()?->getId() ?? 0);
        $targetGradeModelId = $gradeModelId > 0 ? $gradeModelId : 0;
        if ($currentGradeModelId === $targetGradeModelId) {
            return;
        }

        if ($rootCategory->getSubCategories()->count() > 0 || $rootCategory->getLinks()->count() > 0) {
            throw new AccessDeniedHttpException('The Grade Model cannot be changed after categories or online activities have been added.');
        }

        if (0 === $targetGradeModelId) {
            $rootCategory->setGradeModel(null);

            return;
        }

        $gradeModel = $this->entityManager->getRepository(GradeModel::class)->find($targetGradeModelId);
        if (!$gradeModel instanceof GradeModel) {
            throw new BadRequestHttpException('The requested Grade Model was not found.');
        }

        $rootCategory->setGradeModel($gradeModel);
        $components = $this->entityManager->getRepository(GradeComponents::class)->findBy(
            ['gradeModel' => $gradeModel],
            ['id' => 'ASC'],
        );

        foreach ($components as $component) {
            if (!$component instanceof GradeComponents) {
                continue;
            }

            $title = trim(strip_tags((string) $component->getAcronym()));
            if ('' === $title) {
                $title = trim(strip_tags((string) $component->getTitle()));
            }
            if ('' === $title) {
                continue;
            }

            $child = new GradebookCategory();
            $child
                ->setTitle($title)
                ->setDescription($this->normalizeDescription((string) $component->getTitle()))
                ->setUser($user)
                ->setCourse($course)
                ->setSession($session)
                ->setParent($rootCategory)
                ->setWeight((float) $component->getPercentage() / 100 * (float) $rootCategory->getWeight())
                ->setCalculationMode(GradebookCalculationMode::WEIGHTED_AVERAGE)
                ->setVisible(false)
                ->setCertifMinScore(0)
                ->setLocked(0)
                ->setGenerateCertificates(false)
                ->setIsRequirement(false)
                ->setAllowSkillsBySubcategory(1)
            ;
            $this->entityManager->persist($child);
        }
    }

    /**
     * @param list<int>|null $skillIds
     */
    private function updateCategorySkills(GradebookCategory $category, ?array $skillIds): void
    {
        if (null === $skillIds) {
            return;
        }
        if (!$this->canManageSkills()) {
            throw new AccessDeniedHttpException('You are not allowed to assign skills to Gradebook categories.');
        }

        $normalizedIds = [];
        foreach ($skillIds as $skillId) {
            $skillId = (int) $skillId;
            if ($skillId > 0) {
                $normalizedIds[$skillId] = $skillId;
            }
        }

        $existingBySkillId = [];
        foreach ($category->getSkills() as $relation) {
            if (!$relation instanceof SkillRelGradebook || null === $relation->getSkill()->getId()) {
                continue;
            }
            $existingBySkillId[(int) $relation->getSkill()->getId()] = $relation;
        }

        foreach ($existingBySkillId as $skillId => $relation) {
            if (!isset($normalizedIds[$skillId])) {
                $this->entityManager->remove($relation);
            }
        }

        foreach ($normalizedIds as $skillId) {
            if (isset($existingBySkillId[$skillId])) {
                continue;
            }

            $skill = $this->entityManager->getRepository(Skill::class)->find($skillId);
            if (!$skill instanceof Skill) {
                throw new BadRequestHttpException('One of the selected skills was not found.');
            }

            $relation = new SkillRelGradebook();
            $relation
                ->setGradeBookCategory($category)
                ->setSkill($skill)
                ->setType('')
            ;
            $this->entityManager->persist($relation);
        }
    }

    private function canManageSkills(): bool
    {
        if (!$this->isSettingEnabled('skill.allow_skills_tool')) {
            return false;
        }

        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_HR')
            || $this->isSettingEnabled('skill.skills_teachers_can_assign_skills');
    }

    private function canSetSubcategoryMinimumScore(GradebookCategory $parent): bool
    {
        return $this->isSettingEnabled('gradebook.gradebook_enable_subcategory_skills_independant_assignement')
            && 1 === (int) $parent->getAllowSkillsBySubcategory();
    }

    private function isLockedForCurrentUser(GradebookCategory $category): bool
    {
        return 1 === (int) $category->getLocked() && !$this->security->isGranted('ROLE_ADMIN');
    }

    private function normalizeTitle(string $title): string
    {
        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new BadRequestHttpException('The Gradebook category title is required.');
        }

        if (mb_strlen($title) > 50) {
            throw new BadRequestHttpException('The Gradebook category title cannot exceed 50 characters.');
        }

        return $title;
    }

    private function normalizeDescription(string $description): string
    {
        return trim(strip_tags($description));
    }

    private function normalizeWeight(?float $weight): float
    {
        if (null === $weight) {
            $configuredWeight = $this->settingsManager->getSetting('gradebook.gradebook_default_weight', true);
            $weight = is_numeric($configuredWeight) ? (float) $configuredWeight : 100.0;
        }

        if ($weight < 0) {
            throw new BadRequestHttpException('The Gradebook category weight cannot be negative.');
        }

        return $weight;
    }

    private function normalizeMinimumScore(int $minimumScore): int
    {
        if ($minimumScore < 0) {
            throw new BadRequestHttpException('The minimum score cannot be negative.');
        }

        return $minimumScore;
    }

    private function normalizeCalculationMode(string $calculationMode): GradebookCalculationMode
    {
        if ('' === trim($calculationMode)) {
            return GradebookCalculationMode::WEIGHTED_AVERAGE;
        }

        return GradebookCalculationMode::tryFrom($calculationMode)
            ?? throw new BadRequestHttpException('Unsupported Gradebook calculation mode.');
    }

    private function canChangeGradeModelSettings(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->isSettingEnabled('gradebook.teachers_can_change_grade_model_settings');
    }

    private function isGradebookVisibleByDefault(): bool
    {
        $setting = $this->settingsManager->getSetting('course.active_tools_on_create', true);
        if (!\is_array($setting)) {
            if (!\is_scalar($setting)) {
                return false;
            }

            $raw = trim((string) $setting);
            if ('' === $raw) {
                return false;
            }

            $decoded = json_decode($raw, true);
            $setting = \is_array($decoded) ? $decoded : (preg_split('/[,;|]+/', $raw) ?: []);
        }

        foreach ($setting as $key => $value) {
            if ((true === $value || 'true' === $value || '1' === $value) && \is_string($key)) {
                $value = $key;
            }

            if (\is_scalar($value) && 'gradebook' === strtolower(trim((string) $value))) {
                return true;
            }
        }

        return false;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function validateCsrfToken(string $submittedCsrfToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedCsrfToken))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }
}

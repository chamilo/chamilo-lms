<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookOverview;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Read-only course Gradebook overview.
 *
 * @implements ProviderInterface<GradebookOverview>
 */
final readonly class GradebookOverviewProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private GradebookLinkResourceResolver $linkResourceResolver,
        private GradebookScoreCalculator $scoreCalculator,
        private GradebookContextResolver $contextResolver,
        private GradebookLearnerStatisticsCalculator $statisticsCalculator,
        private PluginHelper $pluginHelper,
        private StudentViewHelper $studentViewHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookOverview
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
        $user = $this->getCurrentUser();
        $this->validateCourseResourceNode($request, $course);
        $groupId = $this->validateGroupContext($operation, $course);

        $isStudentView = $this->studentViewHelper->isActive();
        $canViewAll = !$isStudentView && $this->canViewAllGradebookItems();
        $canManage = $this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session);
        if (!$canViewAll && !$this->canViewGradebook()) {
            throw new AccessDeniedHttpException('You are not allowed to view the Gradebook in this context.');
        }

        $rootCategory = $this->findRootCategory($course, $session);
        $overview = new GradebookOverview();
        $overview->canManage = $canManage;
        $overview->canViewAll = $canViewAll;
        $overview->canUnlock = $canManage && $this->security->isGranted('ROLE_ADMIN');
        $overview->canSyncAchievements = !$canViewAll
            && !$this->security->isGranted('ROLE_ADMIN')
            && !$this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            && !$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            && ($this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
                || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT'));
        $scoreModelMax = $this->getCourseScoreModelMax($course);
        $overview->settings = [
            ...$this->getSettings(),
            'studentView' => $isStudentView,
            'scoreModelMax' => $scoreModelMax,
        ];
        $overview->context = [
            'cid' => (int) $course->getId(),
            'sid' => null !== $session ? (int) $session->getId() : 0,
            'gid' => $groupId,
            'node' => $request->query->getInt('node'),
        ];

        if ($overview->canSyncAchievements) {
            $overview->achievementCsrfToken = $this->csrfTokenManager
                ->getToken(GradebookAchievementActionProcessor::CSRF_TOKEN_ID)
                ->getValue()
            ;
        }

        if ($canManage) {
            $overview->csrfToken = $this->csrfTokenManager
                ->getToken(GradebookCategoryActionProcessor::CSRF_TOKEN_ID)
                ->getValue()
            ;
            $overview->evaluationCsrfToken = $this->csrfTokenManager
                ->getToken(GradebookEvaluationActionProcessor::CSRF_TOKEN_ID)
                ->getValue()
            ;
            $overview->linkCsrfToken = $this->csrfTokenManager
                ->getToken(GradebookLinkActionProcessor::CSRF_TOKEN_ID)
                ->getValue()
            ;
        }

        if (!$rootCategory instanceof GradebookCategory) {
            return $overview;
        }

        $overview->hasGradebook = true;
        $overview->rootCategoryId = (int) $rootCategory->getId();

        $currentCategory = $this->getSelectedCategory($request, $course, $session, $rootCategory, $canViewAll);
        $overview->currentCategory = $this->normalizeCategory($currentCategory);
        if (!$canViewAll) {
            $overview->scoreSummary = $this->scoreCalculator->calculateCategory(
                $currentCategory,
                $user,
                $course,
                $session,
            );
            if (true === ($overview->settings['useExerciseScoreSettingsInTotal'] ?? false)) {
                $overview->scoreSummary = $this->convertScoreToExercisePlatformScale(
                    $overview->scoreSummary,
                    $overview->settings,
                );
            }
        }
        $overview->categoryTrail = $this->buildCategoryTrail($currentCategory, $rootCategory);
        $overview->categoryOptions = $this->buildCategoryOptions($rootCategory, $canViewAll);
        $detailedStatsEnabled = !$canViewAll
            && null === $scoreModelMax
            && $this->isSettingEnabled('gradebook.gradebook_detailed_admin_view');
        $students = $detailedStatsEnabled ? $this->contextResolver->getStudents($course, $session) : [];
        $overview->items = $this->getItems(
            $currentCategory,
            $course,
            $session,
            $user,
            $canViewAll,
            $canManage,
            $groupId,
            $detailedStatsEnabled,
            $students,
        );
        $overview->totalItems = \count($overview->items);
        if ($canManage && $this->pluginHelper->isPluginEnabled('GradingElectronic')) {
            $overview->controlledFallbacks['gradingElectronic'] = '/main/gradebook/index.php?'.http_build_query([
                'cid' => $overview->context['cid'],
                'sid' => $overview->context['sid'],
                'gid' => $overview->context['gid'],
                'selectcat' => (int) $currentCategory->getId(),
            ]);
        }

        return $overview;
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

    private function canViewGradebook(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
            || $this->canViewAllGradebookItems();
    }

    private function canViewAllGradebookItems(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || ($this->security->isGranted('ROLE_SESSION_MANAGER')
                && $this->isSettingEnabled('session.session_admins_edit_courses_content'));
    }

    private function validateGroupContext(Operation $operation, Course $course): int
    {
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        if (!$group instanceof CGroup) {
            return 0;
        }

        $groupId = (int) $group->getIid();

        $groupNode = $group->getResourceNode();
        $courseNode = $course->getResourceNode();
        if (null === $groupNode || null === $courseNode
            || (int) ($groupNode->getParent()?->getId() ?? 0) !== (int) $courseNode->getId()
        ) {
            throw new AccessDeniedHttpException('The requested group does not belong to the current course.');
        }

        return $groupId;
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

    private function getSelectedCategory(
        Request $request,
        Course $course,
        ?Session $session,
        GradebookCategory $rootCategory,
        bool $canViewAll,
    ): GradebookCategory {
        $categoryId = $request->query->getInt('categoryId');
        if ($categoryId <= 0 || $categoryId === (int) $rootCategory->getId()) {
            return $rootCategory;
        }

        $category = $this->entityManager->getRepository(GradebookCategory::class)->find($categoryId);
        if (!$category instanceof GradebookCategory) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }

        $this->assertCategoryContext($category, $course, $session);
        if (!$this->isCategoryDescendantOf($category, $rootCategory)) {
            throw new AccessDeniedHttpException('The requested Gradebook category is outside the current Gradebook.');
        }

        if (!$canViewAll && !$category->getVisible()) {
            throw new AccessDeniedHttpException('The requested Gradebook category is not visible.');
        }

        return $category;
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

    /**
     * @param list<User> $students
     *
     * @return list<array<string, mixed>>
     */
    private function getItems(
        GradebookCategory $category,
        Course $course,
        ?Session $session,
        User $user,
        bool $canViewAll,
        bool $canManage,
        int $groupId,
        bool $detailedStatsEnabled,
        array $students,
    ): array {
        $items = [];

        foreach ($category->getSubCategories() as $subCategory) {
            if (!$subCategory instanceof GradebookCategory) {
                continue;
            }

            $this->assertCategoryContext($subCategory, $course, $session);
            if (!$canViewAll && !$subCategory->getVisible()) {
                continue;
            }

            $categoryItem = [
                ...$this->normalizeCategory($subCategory),
                'kind' => 'category',
                'maxScore' => null,
                'score' => null,
                'percentage' => null,
                'attempts' => 0,
                'date' => null,
                'weightedScore' => null,
                'hasResult' => false,
                'refId' => null,
                'linkType' => null,
                'linkTypeLabel' => null,
            ];
            if (!$canViewAll) {
                $categoryResult = $this->scoreCalculator->calculateCategory(
                    $subCategory,
                    $user,
                    $course,
                    $session,
                );
                if ($this->isSettingEnabled('gradebook.gradebook_use_exercise_score_settings_in_total')) {
                    $categoryResult = $this->convertScoreToExercisePlatformScale(
                        $categoryResult,
                        $this->getExerciseScaleSettings(),
                    );
                }
                $categoryItem = [
                    ...$categoryItem,
                    ...$categoryResult,
                ];
                if ($detailedStatsEnabled) {
                    $categoryItem['stats'] = $this->convertStatisticsToExercisePlatformScale(
                        $this->statisticsCalculator->calculate(
                            $subCategory,
                            $user,
                            $students,
                            $course,
                            $session,
                        ),
                        $this->isSettingEnabled('gradebook.gradebook_use_exercise_score_settings_in_categories'),
                    );
                }
            }
            $items[] = $categoryItem;
        }

        foreach ($category->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof GradebookEvaluation) {
                continue;
            }

            if ((int) $evaluation->getCourse()->getId() !== (int) $course->getId()) {
                continue;
            }

            if (!$canViewAll && 1 !== (int) $evaluation->getVisible()) {
                continue;
            }

            $evaluationItem = $this->normalizeEvaluation($evaluation, $user, $canViewAll);
            if (!$canViewAll) {
                $configuredResult = $this->scoreCalculator->calculateConfiguredItem(
                    $evaluation,
                    $category,
                    $user,
                    $course,
                    $session,
                );
                if (null !== $configuredResult) {
                    $evaluationItem = [
                        ...$evaluationItem,
                        ...$configuredResult,
                    ];
                }
                if ($detailedStatsEnabled) {
                    $evaluationItem['stats'] = $this->statisticsCalculator->calculate(
                        $evaluation,
                        $user,
                        $students,
                        $course,
                        $session,
                    );
                }
            }
            $items[] = $evaluationItem;
        }

        foreach ($category->getLinks() as $link) {
            if (!$link instanceof GradebookLink) {
                continue;
            }

            if ((int) $link->getCourse()->getId() !== (int) $course->getId()) {
                continue;
            }

            if (!$canViewAll && 1 !== (int) $link->getVisible()) {
                continue;
            }

            $normalizedLink = $this->linkResourceResolver->normalizeLink(
                $link,
                $course,
                $session,
                $groupId,
                $canManage,
            );
            if (!$canViewAll && $this->isSettingEnabled('gradebook.gradebook_hide_link_to_item_for_student')) {
                $normalizedLink['url'] = null;
            }
            if (!$canViewAll) {
                $linkResult = $this->scoreCalculator->calculateLink($link, $user, $course, $session);
                if (GradebookLinkResourceResolver::LINK_EXERCISE === (int) $link->getType()) {
                    $linkResult = $this->convertScoreToExercisePlatformScale(
                        $linkResult,
                        $this->getExerciseScaleSettings(),
                    );
                }
                $normalizedLink = [
                    ...$normalizedLink,
                    ...$linkResult,
                ];
                $configuredResult = $this->scoreCalculator->calculateConfiguredItem(
                    $link,
                    $category,
                    $user,
                    $course,
                    $session,
                );
                if (null !== $configuredResult) {
                    if (GradebookLinkResourceResolver::LINK_EXERCISE === (int) $link->getType()) {
                        $configuredResult = $this->convertScoreToExercisePlatformScale(
                            $configuredResult,
                            $this->getExerciseScaleSettings(),
                        );
                    }
                    $normalizedLink = [
                        ...$normalizedLink,
                        ...$configuredResult,
                    ];
                }
                if ($detailedStatsEnabled) {
                    $normalizedLink['stats'] = $this->convertStatisticsToExercisePlatformScale(
                        $this->statisticsCalculator->calculate(
                            $link,
                            $user,
                            $students,
                            $course,
                            $session,
                        ),
                        GradebookLinkResourceResolver::LINK_EXERCISE === (int) $link->getType(),
                    );
                }
            }
            $items[] = $normalizedLink;
        }

        usort(
            $items,
            static fn (array $left, array $right): int => strnatcasecmp(
                (string) $left['title'],
                (string) $right['title'],
            ),
        );

        return array_values($items);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeEvaluation(GradebookEvaluation $evaluation, User $user, bool $canViewAll): array
    {
        $item = [
            'id' => (int) $evaluation->getId(),
            'kind' => 'evaluation',
            'title' => (string) $evaluation->getTitle(),
            'description' => $this->normalizeDescription($evaluation->getDescription()),
            'weight' => (float) $evaluation->getWeight(),
            'visible' => 1 === (int) $evaluation->getVisible(),
            'locked' => 1 === (int) $evaluation->getLocked(),
            'maxScore' => (float) $evaluation->getMax(),
            'minScore' => $evaluation->getMinScore(),
            'hasResults' => $this->evaluationHasScoredResults($evaluation),
            'score' => null,
            'percentage' => null,
            'attempts' => 0,
            'date' => null,
            'weightedScore' => null,
            'hasResult' => false,
            'refId' => null,
            'linkType' => null,
            'linkTypeLabel' => null,
        ];

        if ($canViewAll) {
            return $item;
        }

        return [
            ...$item,
            ...$this->scoreCalculator->calculateEvaluation($evaluation, $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCategory(GradebookCategory $category): array
    {
        $parent = $category->getParent();
        $skillIds = [];

        foreach ($category->getSkills() as $skillRelation) {
            if ($skillRelation instanceof SkillRelGradebook && null !== $skillRelation->getSkill()->getId()) {
                $skillIds[] = (int) $skillRelation->getSkill()->getId();
            }
        }

        return [
            'id' => (int) $category->getId(),
            'parentId' => null !== $parent ? (int) $parent->getId() : null,
            'title' => $category->getTitle(),
            'description' => $this->normalizeDescription($category->getDescription()),
            'weight' => (float) $category->getWeight(),
            'visible' => (bool) $category->getVisible(),
            'locked' => 1 === (int) $category->getLocked(),
            'calculationMode' => $category->getCalculationMode()->value,
            'certificateMinScore' => null !== $category->getCertifMinScore()
                ? (int) $category->getCertifMinScore()
                : null,
            'generateCertificates' => (bool) $category->getGenerateCertificates(),
            'isRequirement' => (bool) $category->getIsRequirement(),
            'allowSkillsBySubcategory' => 1 === (int) $category->getAllowSkillsBySubcategory(),
            'hasGradeModel' => null !== $category->getGradeModel(),
            'skillIds' => $skillIds,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildCategoryTrail(GradebookCategory $category, GradebookCategory $rootCategory): array
    {
        $trail = [];
        $visited = [];
        $current = $category;

        while (null !== $current) {
            $currentId = (int) $current->getId();
            if (isset($visited[$currentId])) {
                break;
            }

            $visited[$currentId] = true;
            $trail[] = [
                'id' => $currentId,
                'title' => $current->getTitle(),
            ];

            if ($currentId === (int) $rootCategory->getId()) {
                break;
            }

            $current = $current->getParent();
        }

        return array_reverse($trail);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildCategoryOptions(GradebookCategory $rootCategory, bool $canViewAll): array
    {
        $options = [];
        $this->appendCategoryOptions($options, $rootCategory, 0, $canViewAll);

        return $options;
    }

    /**
     * @param list<array<string, mixed>> $options
     */
    private function appendCategoryOptions(
        array &$options,
        GradebookCategory $category,
        int $depth,
        bool $canViewAll,
    ): void {
        if ($canViewAll || $category->getVisible() || 0 === $depth) {
            $options[] = [
                ...$this->normalizeCategory($category),
                'depth' => $depth,
            ];
        }

        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory instanceof GradebookCategory) {
                $this->appendCategoryOptions($options, $subCategory, $depth + 1, $canViewAll);
            }
        }
    }

    private function normalizeDescription(?string $description): string
    {
        return trim(strip_tags((string) $description));
    }

    private function evaluationHasScoredResults(GradebookEvaluation $evaluation): bool
    {
        $count = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(result.id)')
            ->from(GradebookResult::class, 'result')
            ->where('IDENTITY(result.evaluation) = :evaluationId')
            ->andWhere('result.score IS NOT NULL')
            ->setParameter('evaluationId', (int) $evaluation->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    private function getCourseScoreModelMax(Course $course): ?float
    {
        $setting = $this->settingsManager->getSetting('exercise.score_grade_model', true);
        if (!\is_array($setting) || !isset($setting['models']) || !\is_array($setting['models']) || [] === $setting['models']) {
            return null;
        }

        $modelId = -1;
        $courseSettings = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            ['cId' => (int) $course->getId(), 'variable' => 'score_model_id'],
            ['iid' => 'ASC'],
        );
        foreach ($courseSettings as $courseSetting) {
            if (!$courseSetting instanceof CCourseSetting || null === $courseSetting->getValue()) {
                continue;
            }
            $modelId = (int) $courseSetting->getValue();

            break;
        }

        $selectedModel = $setting['models'][0];
        if (-1 !== $modelId) {
            foreach ($setting['models'] as $model) {
                if (\is_array($model) && (int) ($model['id'] ?? 0) === $modelId) {
                    $selectedModel = $model;

                    break;
                }
            }
        }

        if (!\is_array($selectedModel) || !isset($selectedModel['score_list']) || !\is_array($selectedModel['score_list'])) {
            return null;
        }

        $max = null;
        foreach ($selectedModel['score_list'] as $item) {
            if (!\is_array($item) || !isset($item['max']) || !is_numeric($item['max'])) {
                continue;
            }

            // Legacy EvalForm uses the last score model item as the evaluation maximum.
            $max = (float) $item['max'];
        }

        return $max;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        $defaultWeight = $this->settingsManager->getSetting('gradebook.gradebook_default_weight', true);

        return [
            'hideGraph' => $this->isSettingEnabled('gradebook.gradebook_hide_graph'),
            'hideTable' => $this->isSettingEnabled('gradebook.gradebook_hide_table'),
            'hidePdfReportButton' => $this->isSettingEnabled('gradebook.gradebook_hide_pdf_report_button'),
            'hideLinkToItemForStudent' => $this->isSettingEnabled('gradebook.gradebook_hide_link_to_item_for_student'),
            'multipleEvaluationAttempts' => $this->isSettingEnabled('gradebook.gradebook_multiple_evaluation_attempts'),
            'allowComments' => $this->isSettingEnabled('gradebook.allow_gradebook_comments'),
            'allowStats' => $this->isSettingEnabled('gradebook.allow_gradebook_stats'),
            'detailedAdminView' => $this->isSettingEnabled('gradebook.gradebook_detailed_admin_view'),
            'hidePercentageUserResult' => $this->isSettingEnabled('gradebook.hide_gradebook_percentage_user_result'),
            'lockingEnabled' => $this->isSettingEnabled('gradebook.gradebook_locking_enabled'),
            'gradeModelEnabled' => $this->isSettingEnabled('gradebook.gradebook_enable_grade_model'),
            'teachersCanChangeGradeModelSettings' => $this->isSettingEnabled('gradebook.teachers_can_change_grade_model_settings'),
            'allowSubcategorySkills' => $this->isSettingEnabled('gradebook.gradebook_enable_subcategory_skills_independant_assignement'),
            'skillsTeachersCanAssign' => $this->isSettingEnabled('skill.skills_teachers_can_assign_skills'),
            'teachersCanChangeScoreSettings' => $this->isSettingEnabled('gradebook.teachers_can_change_score_settings'),
            'scoreDisplayCustom' => $this->isSettingEnabled('gradebook.gradebook_score_display_custom'),
            'useExerciseScoreSettingsInCategories' => $this->isSettingEnabled(
                'gradebook.gradebook_use_exercise_score_settings_in_categories',
            ),
            'useExerciseScoreSettingsInTotal' => $this->isSettingEnabled(
                'gradebook.gradebook_use_exercise_score_settings_in_total',
            ),
            ...$this->getExerciseScaleSettings(),
            'defaultWeight' => is_numeric($defaultWeight) ? (float) $defaultWeight : 100.0,
            'defaultCategoryVisible' => $this->isGradebookVisibleByDefault(),
            'numberDecimals' => max(
                0,
                (int) ($this->settingsManager->getSetting('gradebook.gradebook_number_decimals', true) ?: 0),
            ),
        ];
    }

    /**
     * @return array{exerciseMinScore: float|null, exerciseMaxScore: float|null}
     */
    private function getExerciseScaleSettings(): array
    {
        return [
            'exerciseMinScore' => $this->getNumericSetting('exercise.exercise_min_score'),
            'exerciseMaxScore' => $this->getNumericSetting('exercise.exercise_max_score'),
        ];
    }

    /** @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function convertScoreToExercisePlatformScale(array $result, array $settings): array
    {
        $minimum = $settings['exerciseMinScore'] ?? null;
        $maximum = $settings['exerciseMaxScore'] ?? null;
        $score = $result['score'] ?? null;
        $weight = $result['maxScore'] ?? null;
        if (!is_numeric($minimum) || !is_numeric($maximum) || !is_numeric($score) || !is_numeric($weight)) {
            return $result;
        }

        $minimum = (float) $minimum;
        $maximum = (float) $maximum;
        $weight = (float) $weight;
        if ($maximum <= $minimum) {
            return $result;
        }

        $score = 0.0 !== $weight
            ? $minimum + (($maximum - $minimum) * (float) $score / $weight)
            : $minimum;
        $result['score'] = $score;
        $result['maxScore'] = $maximum;
        $result['percentage'] = 0.0 !== $maximum ? ($score / $maximum) * 100.0 : null;

        return $result;
    }

    /**
     * @param array<string, mixed> $statistics
     *
     * @return array<string, mixed>
     */
    private function convertStatisticsToExercisePlatformScale(array $statistics, bool $convert): array
    {
        if (!$convert) {
            return $statistics;
        }

        $settings = $this->getExerciseScaleSettings();
        foreach (['best', 'average'] as $key) {
            if (\is_array($statistics[$key] ?? null)) {
                $statistics[$key] = $this->convertScoreToExercisePlatformScale($statistics[$key], $settings);
            }
        }

        return $statistics;
    }

    private function getNumericSetting(string $name): ?float
    {
        $value = $this->settingsManager->getSetting($name, true);
        if (!\is_scalar($value) || '' === trim((string) $value) || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
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
}

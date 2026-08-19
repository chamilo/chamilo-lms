<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookReport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookComment;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookScoreDisplay;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Read-only score matrix used by the Vue Gradebook reports.
 *
 * @implements ProviderInterface<GradebookReport>
 */
final readonly class GradebookReportProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private GradebookScoreCalculator $scoreCalculator,
        private GradebookLinkResourceResolver $linkResourceResolver,
        private ExtraFieldRepository $extraFieldRepository,
        private Connection $connection,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookReport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        return $this->buildReport(
            $request,
            $this->getBooleanQuery($request, 'all', false),
        );
    }

    public function buildReport(
        Request $request,
        bool $exportAll = false,
        ?bool $includeScoresOverride = null,
    ): GradebookReport {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
        $this->validateCourseResourceNode($request, $course);
        $groupId = $this->validateGroupContext($course);
        $this->assertCanViewReport();

        $rootCategory = $this->findRootCategory($course, $session);
        if (!$rootCategory instanceof GradebookCategory) {
            return $this->emptyReport($request, $course, $session, $groupId);
        }

        $category = $this->getSelectedCategory($request, $course, $session, $rootCategory);
        $report = new GradebookReport();
        $includeScores = $includeScoresOverride ?? $this->getBooleanQuery($request, 'includeScores', true);
        $report->context = $this->buildContext($request, $course, $session, $groupId);
        $report->category = $this->normalizeCategory($category);
        $report->columns = $includeScores ? $this->buildColumns($category, $course, $session, $groupId) : [];
        $report->extraFieldColumns = $includeScores ? $this->getExtraFieldDefinitions() : [];
        $report->settings = $this->buildSettings($category);
        $scoreDisplayRanges = $this->getScoreDisplayRanges($category);
        $upperLimitIncluded = $this->isSettingEnabled('gradebook.gradebook_score_display_upperlimit');
        if ($report->settings['allowComments']) {
            $report->commentCsrfToken = (string) $this->csrfTokenManager->getToken(
                GradebookCommentActionProcessor::CSRF_TOKEN_ID,
            );
        }

        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = min(100, max(1, $request->query->getInt('itemsPerPage', 20)));
        $search = mb_strtolower(trim((string) $request->query->get('search', '')));
        $sortBy = $this->normalizeSortBy((string) $request->query->get('sortBy', 'fullName'));
        $sortDirection = 'desc' === strtolower((string) $request->query->get('sortDirection', 'asc')) ? 'desc' : 'asc';

        $students = $this->getStudents($course, $session);
        if ('' !== $search) {
            $students = array_values(array_filter(
                $students,
                static function (User $student) use ($search): bool {
                    $haystack = mb_strtolower(implode(' ', [
                        $student->getUsername(),
                        (string) $student->getFirstname(),
                        (string) $student->getLastname(),
                        (string) $student->getOfficialCode(),
                    ]));

                    return str_contains($haystack, $search);
                },
            ));
        }

        $this->sortStudents($students, $sortBy, $sortDirection);

        $report->page = $exportAll ? 1 : $page;
        $report->itemsPerPage = $exportAll ? max(1, \count($students)) : $itemsPerPage;
        $report->totalItems = \count($students);
        $report->sortBy = $sortBy;
        $report->sortDirection = $sortDirection;

        if (!$exportAll) {
            $students = \array_slice($students, ($page - 1) * $itemsPerPage, $itemsPerPage);
        }
        $extraFieldValues = $this->getExtraFieldValues(
            array_map(static fn (User $student): int => (int) $student->getId(), $students),
            $report->extraFieldColumns,
        );
        $reportItems = $includeScores ? $this->getReportItems($category, $course, $session) : [];
        $commentsByUserId = $report->settings['allowComments']
            ? $this->getCommentsByUserId($category, $students)
            : [];

        foreach ($students as $student) {
            $scores = [];
            if ($includeScores) {
                foreach ($reportItems as $item) {
                    $itemResult = $this->calculateItem(
                        $item,
                        $category,
                        $student,
                        $course,
                        $session,
                    );
                    $itemResult = $this->applyExerciseDisplaySettings($item, $itemResult, $report->settings);
                    $scores[$this->getItemKey($item)] = $this->decorateScoreResult(
                        $itemResult,
                        $report->settings,
                        $scoreDisplayRanges,
                        $upperLimitIncluded,
                    );
                }
            }

            $userId = (int) $student->getId();
            $total = $includeScores
                ? $this->scoreCalculator->calculateCategory($category, $student, $course, $session, true)
                : null;
            if (\is_array($total)) {
                if (true === ($report->settings['useExerciseScoreSettingsInTotal'] ?? false)) {
                    $total = $this->convertScoreToExercisePlatformScale($total, $report->settings);
                }
                $total = $this->decorateScoreResult(
                    $total,
                    $report->settings,
                    $scoreDisplayRanges,
                    $upperLimitIncluded,
                );
            }
            $customScore = null;
            if ($includeScores
                && true === ($report->settings['customScoreStandalone'] ?? false)
                && \is_array($total)
                && is_numeric($total['percentage'] ?? null)
            ) {
                $customScore = $this->resolveScoreDisplay(
                    (float) $total['percentage'],
                    $scoreDisplayRanges,
                    $upperLimitIncluded,
                );
            }

            $report->rows[] = [
                'user' => [
                    'id' => $userId,
                    'fullName' => $student->getFullName(),
                    'firstName' => (string) $student->getFirstname(),
                    'lastName' => (string) $student->getLastname(),
                    'username' => $student->getUsername(),
                    'officialCode' => (string) ($student->getOfficialCode() ?? ''),
                ],
                'extraFields' => $extraFieldValues[$userId] ?? [],
                'comment' => $commentsByUserId[$userId] ?? '',
                'scores' => $scores,
                'total' => $total,
                'customScore' => $customScore,
            ];
        }

        return $report;
    }

    private function validateCourseResourceNode(Request $request, Course $course): void
    {
        $nodeId = $request->query->getInt('node');
        $courseNode = $course->getResourceNode();
        if ($nodeId <= 0 || null === $courseNode || (int) $courseNode->getId() !== $nodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to the current course.');
        }
    }

    private function validateGroupContext(Course $course): int
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

    private function assertCanViewReport(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            return;
        }

        if ($this->security->isGranted('ROLE_SESSION_MANAGER')
            && $this->isSettingEnabled('session.session_admins_edit_courses_content')
        ) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to view the Gradebook learner report.');
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
    ): GradebookCategory {
        $categoryId = $request->query->getInt('categoryId');
        if ($categoryId <= 0 || $categoryId === (int) $rootCategory->getId()) {
            return $rootCategory;
        }

        $category = $this->entityManager->getRepository(GradebookCategory::class)->find($categoryId);
        if (!$category instanceof GradebookCategory) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }
        if (!$this->sameCategoryContext($category, $course, $session) || !$this->isCategoryDescendantOf($category, $rootCategory)) {
            throw new AccessDeniedHttpException('The requested Gradebook category is outside the current Gradebook.');
        }

        return $category;
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
     * @return list<User>
     */
    private function getStudents(Course $course, ?Session $session): array
    {
        $students = [];
        if ($session instanceof Session) {
            $subscriptions = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findBy([
                'course' => $course,
                'session' => $session,
                'status' => Session::STUDENT,
            ]);
            foreach ($subscriptions as $subscription) {
                if ($subscription instanceof SessionRelCourseRelUser) {
                    $student = $subscription->getUser();
                    if (User::SOFT_DELETED !== $student->getStatus()) {
                        $students[(int) $student->getId()] = $student;
                    }
                }
            }
        } else {
            $subscriptions = $this->entityManager->getRepository(CourseRelUser::class)->findBy([
                'course' => $course,
                'status' => CourseRelUser::STUDENT,
            ]);
            foreach ($subscriptions as $subscription) {
                if ($subscription instanceof CourseRelUser) {
                    $student = $subscription->getUser();
                    if (User::SOFT_DELETED !== $student->getStatus()) {
                        $students[(int) $student->getId()] = $student;
                    }
                }
            }
        }

        return array_values($students);
    }

    /**
     * Reproduce the current legacy flat-view column model:
     * root categories show visible weighted subcategories plus direct root items;
     * lower categories show evaluations/links recursively.
     *
     * @return list<GradebookCategory|GradebookEvaluation|GradebookLink>
     */
    private function getReportItems(GradebookCategory $category, Course $course, ?Session $session): array
    {
        $items = [];
        $subCategories = $this->getDirectSubCategories($category, $course, $session);

        if (null === $category->getParent() && [] !== $subCategories) {
            foreach ($subCategories as $subCategory) {
                if (!$subCategory->getVisible() || (float) $subCategory->getWeight() <= 0.0) {
                    continue;
                }
                $items[] = $subCategory;
            }

            foreach ($this->getDirectEvaluations($category, $course) as $evaluation) {
                $items[] = $evaluation;
            }
            foreach ($this->getDirectLinks($category, $course) as $link) {
                $items[] = $link;
            }

            return $items;
        }

        foreach ($this->getEvaluationsRecursive($category, $course, $session) as $evaluation) {
            $items[] = $evaluation;
        }
        foreach ($this->getLinksRecursive($category, $course, $session) as $link) {
            $items[] = $link;
        }

        return $items;
    }

    /**
     * @return list<GradebookCategory>
     */
    private function getDirectSubCategories(GradebookCategory $category, Course $course, ?Session $session): array
    {
        $categories = [];
        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory instanceof GradebookCategory && $this->sameCategoryContext($subCategory, $course, $session)) {
                $categories[] = $subCategory;
            }
        }

        usort($categories, static fn (GradebookCategory $left, GradebookCategory $right): int => (int) $left->getId() <=> (int) $right->getId());

        return $categories;
    }

    /**
     * @return list<GradebookEvaluation>
     */
    private function getDirectEvaluations(GradebookCategory $category, Course $course): array
    {
        $evaluations = [];
        foreach ($category->getEvaluations() as $evaluation) {
            if ($evaluation instanceof GradebookEvaluation && (int) $evaluation->getCourse()->getId() === (int) $course->getId()) {
                $evaluations[] = $evaluation;
            }
        }

        usort($evaluations, static fn (GradebookEvaluation $left, GradebookEvaluation $right): int => (int) $left->getId() <=> (int) $right->getId());

        return $evaluations;
    }

    /**
     * @return list<GradebookLink>
     */
    private function getDirectLinks(GradebookCategory $category, Course $course): array
    {
        $links = [];
        foreach ($category->getLinks() as $link) {
            if ($link instanceof GradebookLink && (int) $link->getCourse()->getId() === (int) $course->getId()) {
                $links[] = $link;
            }
        }

        usort($links, static fn (GradebookLink $left, GradebookLink $right): int => (int) $left->getId() <=> (int) $right->getId());

        return $links;
    }

    /**
     * @return list<GradebookEvaluation>
     */
    private function getEvaluationsRecursive(GradebookCategory $category, Course $course, ?Session $session): array
    {
        $evaluations = $this->getDirectEvaluations($category, $course);
        foreach ($this->getDirectSubCategories($category, $course, $session) as $subCategory) {
            foreach ($this->getEvaluationsRecursive($subCategory, $course, $session) as $evaluation) {
                $evaluations[] = $evaluation;
            }
        }

        return $evaluations;
    }

    /**
     * @return list<GradebookLink>
     */
    private function getLinksRecursive(GradebookCategory $category, Course $course, ?Session $session): array
    {
        $links = $this->getDirectLinks($category, $course);
        foreach ($this->getDirectSubCategories($category, $course, $session) as $subCategory) {
            foreach ($this->getLinksRecursive($subCategory, $course, $session) as $link) {
                $links[] = $link;
            }
        }

        return $links;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildColumns(GradebookCategory $category, Course $course, ?Session $session, int $groupId): array
    {
        $items = $this->getReportItems($category, $course, $session);
        $categoryWeights = [];
        $itemWeights = [];
        foreach ($items as $item) {
            if ($item instanceof GradebookCategory) {
                $categoryWeights[] = (float) $item->getWeight();

                continue;
            }

            $itemWeights[] = (float) $item->getWeight();
        }

        $mainWeight = (float) $category->getWeight();
        $categoryWeightScale = $this->normalizeMainWeightForItems($mainWeight, $categoryWeights);
        $itemWeightScale = $this->normalizeMainWeightForItems($mainWeight, $itemWeights);

        $columns = [];
        foreach ($items as $item) {
            $kind = $this->getItemKind($item);
            $rawWeight = (float) $item->getWeight();
            $weightScale = $item instanceof GradebookCategory ? $categoryWeightScale : $itemWeightScale;
            $relativeWeight = 0.0 !== $weightScale ? round(100 * $rawWeight / $weightScale, 1) : 0.0;

            $columns[] = [
                'key' => $this->getItemKey($item),
                'id' => (int) $item->getId(),
                'kind' => $kind,
                'title' => $this->getItemTitle($item, $course, $session, $groupId),
                'weight' => $item instanceof GradebookLink
                    && GradebookLinkResourceResolver::LINK_FORUM_PARTICIPATION === (int) $item->getType()
                    ? max(
                        (float) ($item->getPointsOne() ?? 0),
                        (float) (($item->getPointsMany() ?? 0) ?: ($item->getPointsOne() ?? 0)),
                    )
                    : $rawWeight,
                'relativeWeight' => $relativeWeight,
                'visible' => $item instanceof GradebookCategory
                    ? (bool) $item->getVisible()
                    : 1 === (int) $item->getVisible(),
            ];
        }

        return $columns;
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateItem(
        GradebookCategory|GradebookEvaluation|GradebookLink $item,
        GradebookCategory $currentCategory,
        User $student,
        Course $course,
        ?Session $session,
    ): array {
        if ($item instanceof GradebookCategory) {
            return $this->scoreCalculator->calculateCategory($item, $student, $course, $session, true);
        }

        $configuredResult = $this->scoreCalculator->calculateConfiguredItem(
            $item,
            $currentCategory,
            $student,
            $course,
            $session,
        );
        if (null !== $configuredResult) {
            return $configuredResult;
        }

        if ($item instanceof GradebookEvaluation) {
            return $this->scoreCalculator->calculateEvaluation($item, $student);
        }

        return $this->scoreCalculator->calculateLink($item, $student, $course, $session);
    }

    private function getItemKind(GradebookCategory|GradebookEvaluation|GradebookLink $item): string
    {
        return match (true) {
            $item instanceof GradebookCategory => 'category',
            $item instanceof GradebookEvaluation => 'evaluation',
            default => 'link',
        };
    }

    private function getItemKey(GradebookCategory|GradebookEvaluation|GradebookLink $item): string
    {
        return $this->getItemKind($item).':'.(int) $item->getId();
    }

    private function getItemTitle(
        GradebookCategory|GradebookEvaluation|GradebookLink $item,
        Course $course,
        ?Session $session,
        int $groupId = 0,
    ): string {
        if ($item instanceof GradebookCategory || $item instanceof GradebookEvaluation) {
            return (string) $item->getTitle();
        }

        $normalized = $this->linkResourceResolver->normalizeLink($item, $course, $session, $groupId, false);

        return (string) ($normalized['title'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCategory(GradebookCategory $category): array
    {
        return [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
            'weight' => (float) $category->getWeight(),
            'calculationMode' => $category->getCalculationMode()->value,
            'parentId' => null !== $category->getParent() ? (int) $category->getParent()->getId() : 0,
            'locked' => (bool) $category->getLocked(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSettings(GradebookCategory $category): array
    {
        return [
            'numberDecimals' => max(
                0,
                (int) ($this->settingsManager->getSetting('gradebook.gradebook_number_decimals', true) ?: 0),
            ),
            'calculationMode' => $category->getCalculationMode()->value,
            'allowComments' => $this->isSettingEnabled('gradebook.allow_gradebook_comments'),
            'allowSkillRelItems' => $this->isSettingEnabled('skill.allow_skill_rel_items'),
            'hideGraph' => $this->isSettingEnabled('gradebook.gradebook_hide_graph'),
            'hideTable' => $this->isSettingEnabled('gradebook.gradebook_hide_table'),
            'detailedAdminView' => $this->isSettingEnabled('gradebook.gradebook_detailed_admin_view'),
            'hidePdfReportButton' => $this->isSettingEnabled('gradebook.gradebook_hide_pdf_report_button'),
            'reportScoreStyle' => $this->getReportScoreStyle(),
            'customScoreStandalone' => $this->isSettingEnabled('gradebook.gradebook_score_display_custom_standalone')
                && $this->isSettingEnabled('gradebook.gradebook_score_display_custom'),
            'useExerciseScoreSettingsInCategories' => $this->isSettingEnabled(
                'gradebook.gradebook_use_exercise_score_settings_in_categories',
            ),
            'useExerciseScoreSettingsInTotal' => $this->isSettingEnabled(
                'gradebook.gradebook_use_exercise_score_settings_in_total',
            ),
            'exerciseMinScore' => $this->getNumericSetting('exercise.exercise_min_score'),
            'exerciseMaxScore' => $this->getNumericSetting('exercise.exercise_max_score'),
        ];
    }

    /**
     * @return list<array{id: int, variable: string, label: string}>
     */
    private function getExtraFieldDefinitions(): array
    {
        $setting = $this->settingsManager->getSetting('gradebook.gradebook_flatview_extrafields_columns', true);
        if (!\is_array($setting) || !isset($setting['variables']) || !\is_array($setting['variables'])) {
            return [];
        }

        $variables = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $setting['variables'],
        ))));
        if ([] === $variables) {
            return [];
        }

        $definitions = [];
        foreach ($this->extraFieldRepository->getExtraFields(ExtraField::USER_FIELD_TYPE) as $field) {
            $variable = (string) $field->getVariable();
            if (!\in_array($variable, $variables, true)) {
                continue;
            }

            $definitions[$variable] = [
                'id' => (int) $field->getId(),
                'variable' => $variable,
                'label' => (string) ($field->getDisplayText() ?: $variable),
            ];
        }

        $ordered = [];
        foreach ($variables as $variable) {
            if (isset($definitions[$variable])) {
                $ordered[] = $definitions[$variable];
            }
        }

        return $ordered;
    }

    /**
     * @param list<int>                                             $userIds
     * @param list<array{id: int, variable: string, label: string}> $fields
     *
     * @return array<int, array<string, string>>
     */
    private function getExtraFieldValues(array $userIds, array $fields): array
    {
        if ([] === $userIds || [] === $fields) {
            return [];
        }

        $fieldIds = array_map(static fn (array $field): int => $field['id'], $fields);
        $rows = $this->connection->fetchAllAssociative(
            'SELECT
                field_values.item_id AS user_id,
                field.variable,
                field_values.value
               FROM extra_field_values field_values
               INNER JOIN extra_field field
                   ON field.id = field_values.field_id
              WHERE field.item_type = :itemType
                AND field.id IN (:fieldIds)
                AND field_values.item_id IN (:userIds)
              ORDER BY field_values.id ASC',
            [
                'itemType' => ExtraField::USER_FIELD_TYPE,
                'fieldIds' => $fieldIds,
                'userIds' => $userIds,
            ],
            [
                'fieldIds' => ArrayParameterType::INTEGER,
                'userIds' => ArrayParameterType::INTEGER,
            ],
        );

        $result = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $variable = (string) $row['variable'];
            $value = trim((string) ($row['value'] ?? ''));
            if (isset($result[$userId][$variable]) && '' !== $value) {
                $result[$userId][$variable] .= ', '.$value;
            } else {
                $result[$userId][$variable] = $value;
            }
        }

        return $result;
    }

    /**
     * @param list<User> $students
     */
    private function sortStudents(array &$students, string $sortBy, string $sortDirection): void
    {
        $factor = 'desc' === $sortDirection ? -1 : 1;
        usort(
            $students,
            static function (User $left, User $right) use ($sortBy, $factor): int {
                $leftValue = match ($sortBy) {
                    'firstName' => (string) $left->getFirstname(),
                    'lastName' => (string) $left->getLastname(),
                    'username' => $left->getUsername(),
                    default => $left->getFullName(),
                };
                $rightValue = match ($sortBy) {
                    'firstName' => (string) $right->getFirstname(),
                    'lastName' => (string) $right->getLastname(),
                    'username' => $right->getUsername(),
                    default => $right->getFullName(),
                };

                return $factor * strnatcasecmp($leftValue, $rightValue);
            },
        );
    }

    private function normalizeSortBy(string $sortBy): string
    {
        return \in_array($sortBy, ['fullName', 'firstName', 'lastName', 'username'], true) ? $sortBy : 'fullName';
    }

    /**
     * @return array<string, int>
     */
    private function buildContext(Request $request, Course $course, ?Session $session, int $groupId): array
    {
        return [
            'cid' => (int) $course->getId(),
            'sid' => null !== $session ? (int) $session->getId() : 0,
            'gid' => $groupId,
            'node' => $request->query->getInt('node'),
        ];
    }

    /**
     * @param list<User> $students
     *
     * @return array<int, string>
     */
    private function getCommentsByUserId(GradebookCategory $category, array $students): array
    {
        $studentIds = array_fill_keys(
            array_map(static fn (User $student): int => (int) $student->getId(), $students),
            true,
        );
        if ([] === $studentIds) {
            return [];
        }

        $comments = [];
        foreach ($this->entityManager->getRepository(GradebookComment::class)->findBy(['gradeBook' => $category]) as $comment) {
            if (!$comment instanceof GradebookComment || null === $comment->getUser()->getId()) {
                continue;
            }
            $userId = (int) $comment->getUser()->getId();
            if (isset($studentIds[$userId])) {
                $comments[$userId] = (string) ($comment->getComment() ?? '');
            }
        }

        return $comments;
    }

    private function getBooleanQuery(Request $request, string $name, bool $default): bool
    {
        $value = $request->query->get($name);
        if (null === $value || '' === $value) {
            return $default;
        }

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function sameCategoryContext(GradebookCategory $category, Course $course, ?Session $session): bool
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            return false;
        }

        $categorySessionId = null !== $category->getSession() ? (int) $category->getSession()->getId() : 0;
        $sessionId = null !== $session ? (int) $session->getId() : 0;

        return $categorySessionId === $sessionId;
    }

    /**
     * Exercise results are displayed through the platform exercise score scale in legacy Gradebook.
     * Category/total conversion is controlled by the Gradebook compatibility settings.
     *
     * @param array<string, mixed> $result
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function applyExerciseDisplaySettings(
        GradebookCategory|GradebookEvaluation|GradebookLink $item,
        array $result,
        array $settings,
    ): array {
        if ($item instanceof GradebookLink
            && GradebookLinkResourceResolver::LINK_EXERCISE === (int) $item->getType()
        ) {
            return $this->convertScoreToExercisePlatformScale($result, $settings);
        }

        if ($item instanceof GradebookCategory
            && true === ($settings['useExerciseScoreSettingsInTotal'] ?? false)
        ) {
            return $this->convertScoreToExercisePlatformScale($result, $settings);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $settings
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

        $convertedScore = 0.0 !== $weight
            ? $minimum + (($maximum - $minimum) * (float) $score / $weight)
            : $minimum;
        $result['score'] = $convertedScore;
        $result['maxScore'] = $maximum;
        $result['percentage'] = 0.0 !== $maximum ? ($convertedScore / $maximum) * 100.0 : null;

        return $result;
    }

    private function getNumericSetting(string $name): ?float
    {
        $value = $this->settingsManager->getSetting($name, true);
        if (!\is_scalar($value) || '' === trim((string) $value) || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function getReportScoreStyle(): int
    {
        $value = (int) ($this->settingsManager->getSetting('gradebook.gradebook_report_score_style', true) ?: 0);

        return $value >= 1 && $value <= 14 ? $value : 7;
    }

    /**
     * @return list<array{score: float, display: string}>
     */
    private function getScoreDisplayRanges(GradebookCategory $category): array
    {
        if (!$this->isSettingEnabled('gradebook.gradebook_score_display_custom')) {
            return [];
        }

        $rows = $this->entityManager->getRepository(GradebookScoreDisplay::class)->findBy(
            ['category' => $category],
            ['score' => 'ASC'],
        );
        $ranges = [];
        foreach ($rows as $row) {
            if ($row instanceof GradebookScoreDisplay) {
                $ranges[] = [
                    'score' => (float) $row->getScore(),
                    'display' => (string) $row->getDisplay(),
                ];
            }
        }

        return $ranges;
    }

    /**
     * @param array<string, mixed>                       $result
     * @param array<string, mixed>                       $settings
     * @param list<array{score: float, display: string}> $ranges
     *
     * @return array<string, mixed>
     */
    private function decorateScoreResult(
        array $result,
        array $settings,
        array $ranges,
        bool $upperLimitIncluded,
    ): array {
        if (true !== ($result['hasResult'] ?? false)) {
            $result['display'] = '';
            $result['customScore'] = null;

            return $result;
        }

        $percentage = is_numeric($result['percentage'] ?? null) ? (float) $result['percentage'] : null;
        $customScore = null !== $percentage
            ? $this->resolveScoreDisplay($percentage, $ranges, $upperLimitIncluded)
            : null;
        $result['customScore'] = $customScore;
        $result['display'] = $this->formatReportScore(
            $result['score'] ?? null,
            $result['maxScore'] ?? null,
            $percentage,
            (int) ($settings['reportScoreStyle'] ?? 7),
            $customScore,
            max(0, min(6, (int) ($settings['numberDecimals'] ?? 2))),
        );

        return $result;
    }

    /**
     * @param list<array{score: float, display: string}> $ranges
     */
    private function resolveScoreDisplay(float $percentage, array $ranges, bool $upperLimitIncluded): ?string
    {
        if ([] === $ranges) {
            return null;
        }

        $score = max(0.0, min(100.0, $percentage));
        foreach ($ranges as $range) {
            $limit = $range['score'];
            if (($upperLimitIncluded && $score <= $limit)
                || (!$upperLimitIncluded && ($score < $limit || 100.0 === $limit))
            ) {
                return $range['display'];
            }
        }

        return $ranges[array_key_last($ranges)]['display'] ?? null;
    }

    private function formatReportScore(
        mixed $score,
        mixed $maxScore,
        ?float $percentage,
        int $style,
        ?string $customScore,
        int $decimals,
    ): string {
        if (null === $score && null === $percentage) {
            return '';
        }

        $scoreText = is_numeric($score) ? number_format((float) $score, $decimals, '.', '') : '';
        $maxText = is_numeric($maxScore) ? number_format((float) $maxScore, $decimals, '.', '') : '';
        $percentText = null !== $percentage ? number_format($percentage, $decimals, '.', '').'%' : '';
        $division = '' !== $scoreText && '' !== $maxText ? $scoreText.' / '.$maxText : $scoreText;
        $customSuffix = null !== $customScore && '' !== trim($customScore) ? ' - '.$customScore : '';

        return match ($style) {
            1 => $division,
            2, 4 => $percentText,
            3 => '' !== $division && '' !== $percentText ? $percentText.' ('.$division.')' : $division.$percentText,
            5 => is_numeric($score) && is_numeric($maxScore) && 0.0 !== (float) $maxScore
                ? number_format((float) $score / (float) $maxScore, $decimals, '.', '')
                : '',
            6 => $percentText,
            7, 13 => $scoreText,
            8 => '',
            9 => ('' !== $division && '' !== $percentText ? $percentText.' ('.$division.')' : $division.$percentText).$customSuffix,
            10 => $customScore ?? '',
            11, 12 => $scoreText.$customSuffix,
            14 => null !== $percentage ? (string) round($percentage) : '',
            default => $scoreText,
        };
    }

    /**
     * @param list<float> $itemWeights
     */
    private function normalizeMainWeightForItems(float $mainWeight, array $itemWeights): float
    {
        if ($mainWeight <= 0.0) {
            return 1.0;
        }

        $maximum = 0.0;
        $hasFractionalRatio = false;
        foreach ($itemWeights as $itemWeight) {
            $maximum = max($maximum, $itemWeight);
            if ($itemWeight > 0.0 && ($itemWeight < 1.0 || abs($itemWeight - floor($itemWeight)) > 0.00001)) {
                $hasFractionalRatio = true;
            }
        }

        if ($mainWeight <= 1.0 && $maximum > 1.0) {
            return $mainWeight * 100.0;
        }

        if ($mainWeight > 1.0 && $maximum <= 1.0 && $hasFractionalRatio) {
            return $mainWeight / 100.0;
        }

        return $mainWeight;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function emptyReport(Request $request, Course $course, ?Session $session, int $groupId): GradebookReport
    {
        $report = new GradebookReport();
        $report->context = $this->buildContext($request, $course, $session, $groupId);
        $report->settings = [
            'allowComments' => $this->isSettingEnabled('gradebook.allow_gradebook_comments'),
            'allowSkillRelItems' => $this->isSettingEnabled('skill.allow_skill_rel_items'),
            'hideGraph' => $this->isSettingEnabled('gradebook.gradebook_hide_graph'),
            'hideTable' => $this->isSettingEnabled('gradebook.gradebook_hide_table'),
            'reportScoreStyle' => $this->getReportScoreStyle(),
            'customScoreStandalone' => false,
            'useExerciseScoreSettingsInCategories' => $this->isSettingEnabled(
                'gradebook.gradebook_use_exercise_score_settings_in_categories',
            ),
            'useExerciseScoreSettingsInTotal' => $this->isSettingEnabled(
                'gradebook.gradebook_use_exercise_score_settings_in_total',
            ),
            'exerciseMinScore' => $this->getNumericSetting('exercise.exercise_min_score'),
            'exerciseMaxScore' => $this->getNumericSetting('exercise.exercise_max_score'),
        ];

        return $report;
    }
}

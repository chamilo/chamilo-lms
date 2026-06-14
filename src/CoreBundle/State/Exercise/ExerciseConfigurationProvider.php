<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeInterface;
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
 * @implements ProviderInterface<ExerciseConfiguration>
 */
final readonly class ExerciseConfigurationProvider implements ProviderInterface
{
    private const CSRF_TOKEN_ID = 'exercise_configuration';
    private const MEDIA_QUESTION = 15;
    private const PAGE_BREAK = 31;
    private const SKILL_ITEM_TYPE_EXERCISE = 1;


    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseConfiguration
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercises in this context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 < $exerciseId) {
            $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);

            return $this->buildEditConfiguration($quiz, $course, $session);
        }

        return $this->buildCreateConfiguration($course, $session);
    }

    private function buildCreateConfiguration(Course $course, ?Session $session): ExerciseConfiguration
    {
        $configuration = new ExerciseConfiguration();
        $configuration->mode = 'create';
        $configuration->canCreate = true;
        $configuration->canEdit = false;
        $configuration->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $configuration->settings = $this->getSettings();
        $configuration->options = $this->getOptions($course, null);
        $configuration->listUrl = '';
        $configuration->questionsUrl = '';
        $configuration->maxAttempt = 0;
        $configuration->displayCategoryName = true;
        $configuration->hideAttemptsTable = $this->isSettingEnabled('exercise.quiz_hide_attempts_table_on_start_page');
        $configuration->feedbackType = 0;
        $configuration->resultsDisabled = 0;
        $configuration->questionSelectionType = 1;
        $configuration->randomByCategory = 0;
        $configuration->categoryMatrix = [];
        $configuration->language = '';
        $configuration->updateTitleInLearningPaths = false;
        $configuration->skillIds = [];
        $configuration->extraFieldValues = $this->getDefaultExtraFieldValues();
        $configuration->extraNotification = '';
        $configuration->lockedFields = [];
        $configuration->displayCategoryName = true;
        $configuration->pageResultConfiguration = $this->getDefaultPageResultConfiguration();
        $configuration->notifications = [];
        $configuration->accessCondition = '';
        $configuration->sound = '';

        return $configuration;
    }

    private function buildEditConfiguration(CQuiz $quiz, Course $course, ?Session $session): ExerciseConfiguration
    {
        $category = $quiz->getQuizCategory();
        $configuration = new ExerciseConfiguration();
        $configuration->exerciseId = (int) $quiz->getIid();
        $configuration->mode = 'edit';
        $configuration->title = $quiz->getTitle();
        $configuration->description = (string) $quiz->getDescription();
        $configuration->type = $quiz->getType();
        $configuration->categoryId = null !== $category && null !== $category->getId() ? (int) $category->getId() : null;
        $configuration->language = $this->getResourceLanguageIsoCode($quiz);
        $configuration->updateTitleInLearningPaths = false;
        $configuration->skillIds = $this->getSelectedSkillIds($quiz);
        $configuration->extraFieldValues = $this->getExerciseExtraFieldValues($quiz);
        $configuration->extraNotification = '';
        $configuration->lockedFields = $this->getLockedFieldsForEdit();
        $configuration->startTime = $this->formatDateForInput($quiz->getStartTime());
        $configuration->endTime = $this->formatDateForInput($quiz->getEndTime());
        $configuration->duration = $quiz->getDuration();
        $configuration->maxAttempt = $quiz->getMaxAttempt();
        $configuration->passPercentage = (int) ($quiz->getPassPercentage() ?? 0);
        $configuration->random = (int) $quiz->getRandom();
        $configuration->randomByCategory = (int) $quiz->getRandomByCategory();
        $configuration->categoryMatrix = $this->getCategoryMatrix($quiz);
        $configuration->randomAnswers = $quiz->getRandomAnswers();
        $configuration->showPreviousButton = $quiz->isShowPreviousButton();
        $configuration->preventBackwards = 1 === $quiz->getPreventBackwards();
        $configuration->hideAttemptsTable = $quiz->isHideAttemptsTable();
        $configuration->autoLaunch = $quiz->isAutoLaunch();
        $configuration->notifications = $this->normalizeNotifications($quiz->getNotifications());
        $configuration->accessCondition = (string) $quiz->getAccessCondition();
        $configuration->sound = (string) ($quiz->getSound() ?? '');
        $configuration->feedbackType = $quiz->getFeedbackType();
        $configuration->resultsDisabled = $quiz->getResultsDisabled();
        $configuration->questionSelectionType = (int) ($quiz->getQuestionSelectionType() ?? 1);
        $configuration->displayCategoryName = 1 === $quiz->getDisplayCategoryName();
        $configuration->hideQuestionTitle = $quiz->isHideQuestionTitle();
        $configuration->hideQuestionNumber = 1 === (int) $quiz->getHideQuestionNumber();
        $configuration->propagateNeg = 1 === $quiz->getPropagateNeg();
        $configuration->saveCorrectAnswers = (int) ($quiz->getSaveCorrectAnswers() ?? 0);
        $configuration->reviewAnswers = 1 === $quiz->getReviewAnswers();
        $configuration->expiredTime = $quiz->getExpiredTime();
        $configuration->displayChartDegreeCertainty = (int) $quiz->getDisplayChartDegreeCertainty();
        $configuration->sendEmailChartDegreeCertainty = (int) $quiz->getSendEmailChartDegreeCertainty();
        $configuration->notDisplayBalancePercentageCategorieQuestion = (int) $quiz->getNotDisplayBalancePercentageCategorieQuestion();
        $configuration->displayChartDegreeCertaintyCategory = (int) $quiz->getDisplayChartDegreeCertaintyCategory();
        $configuration->gatherQuestionsCategories = (int) $quiz->getGatherQuestionsCategories();
        $configuration->pageResultConfiguration = $this->normalizePageResultConfiguration($quiz->getPageResultConfiguration());
        $configuration->textWhenFinished = (string) $quiz->getTextWhenFinished();
        $configuration->textWhenFinishedFailure = (string) $quiz->getTextWhenFinishedFailure();
        $configuration->canCreate = true;
        $configuration->canEdit = true;
        $configuration->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $configuration->settings = $this->getSettings();
        $configuration->options = $this->getOptions($course, $quiz);
        $configuration->listUrl = '';
        $configuration->questionsUrl = $this->buildLegacyQuestionsUrl($quiz, $course, $session);

        return $configuration;
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        if ($this->isExerciseInContext($exerciseId, $course, $session)) {
            return $quiz;
        }

        throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
    }

    private function isExerciseInContext(int $exerciseId, Course $course, ?Session $session): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'allowExerciseCategories' => $this->isSettingEnabled('exercise.allow_exercise_categories'),
            'allowShowPreviousButtonSetting' => $this->isSettingEnabled('exercise.allow_quiz_show_previous_button_setting'),
            'allowQuizResultsPageConfig' => $this->isSettingEnabled('exercise.allow_quiz_results_page_config'),
            'disableNewAttempts' => $this->isSettingEnabled('exercise.exercises_disable_new_attempts'),
            'hideAttemptsTableOnStartPage' => $this->isSettingEnabled('exercise.quiz_hide_attempts_table_on_start_page'),
            'limitTeacherAccess' => $this->isSettingEnabled('exercise.limit_exercise_teacher_access'),
            'allowNotificationSettingPerExercise' => $this->isSettingEnabled('exercise.allow_notification_setting_per_exercise'),
            'allowHideQuestionNumberSetting' => $this->isSettingEnabled('exercise.quiz_hide_question_number'),
            'enableQuizScenario' => $this->isSettingEnabled('enable_quiz_scenario'),
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getOptions(Course $course, ?CQuiz $quiz): array
    {
        return [
            'typeOptions' => [
                ['value' => CQuiz::ALL_ON_ONE_PAGE, 'label' => 'All questions on one page'],
                ['value' => CQuiz::ONE_PER_PAGE, 'label' => 'One question per page'],
            ],
            'categoryOptions' => $this->getCategoryOptions($course),
            'languageOptions' => $this->getResourceLanguageOptions(),
            'skillOptions' => $this->getSkillOptions(),
            'extraFieldDefinitions' => $this->getExtraFieldDefinitions(),
            'extraNotificationOptions' => [],
            'feedbackOptions' => $this->getFeedbackOptions($quiz),
            'resultOptions' => [
                ['value' => 0, 'label' => 'Auto-evaluation mode: show score and expected answers'],
                ['value' => 1, 'label' => 'Exam mode: Do not show score nor answers'],
                ['value' => 2, 'label' => 'Practice mode: Show score only, by category if at least one is used'],
                ['value' => 4, 'label' => 'Show score on every attempt, show correct answers only on last attempt (only works with an attempts limit)'],
                ['value' => 5, 'label' => 'Do not show the score (only when user finishes all attempts) but show feedback for each attempt.'],
                ['value' => 6, 'label' => 'Ranking mode: Do not show results details question by question and show a table with the ranking of all other users.'],
                ['value' => 7, 'label' => 'Show only global score (not question score) and show only the correct answers, do not show incorrect answers at all'],
                ['value' => 8, 'label' => 'Auto-evaluation mode and ranking'],
                ['value' => 9, 'label' => 'Show score by category on a radar/spiderweb chart'],
                ['value' => 10, 'label' => 'Show the result to the learner: Show the score, the learner choice and his feedback on each attempt, add the correct answer and his feedback when the chosen limit of attempts is reached.'],
            ],
            'questionSelectionTypeOptions' => [
                ['value' => 1, 'label' => 'Ordered by user'],
                ['value' => 2, 'label' => 'Random'],
                ['value' => 3, 'label' => 'Ordered categories alphabetically with questions ordered'],
                ['value' => 4, 'label' => 'Random categories with questions ordered'],
                ['value' => 5, 'label' => 'Ordered categories alphabetically with random questions'],
                ['value' => 6, 'label' => 'Random categories with random questions'],
            ],
            'randomByCategoryOptions' => [
                ['value' => 0, 'label' => 'No'],
                ['value' => 1, 'label' => 'Random shuffled categories'],
                ['value' => 2, 'label' => 'Random ordered categories'],
            ],
            'notificationOptions' => [
                ['value' => 2, 'label' => 'Paranoid: E-mail teacher when a student starts an exercise'],
                ['value' => 1, 'label' => 'Aware: E-mail teacher when a student ends an exercise'],
                ['value' => 3, 'label' => 'Relaxed open: E-mail teacher when a student ends an exercise, only if an open question is answered'],
                ['value' => 4, 'label' => 'Relaxed audio: E-mail teacher when a student ends an exercise, only if an oral question is answered'],
            ],
            'saveCorrectAnswerOptions' => [
                ['value' => 0, 'label' => 'Please select an option'],
                ['value' => 1, 'label' => 'Save the correct answer for the next attempt'],
                ['value' => 2, 'label' => 'Pre-fill with answers from previous attempt'],
            ],
        ];
    }


    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFeedbackOptions(?CQuiz $quiz): array
    {
        $options = [
            ['value' => 0, 'label' => 'At end of test'],
            ['value' => 2, 'label' => 'Exam (no feedback)'],
        ];

        if ($this->isSettingEnabled('enable_quiz_scenario') || \in_array($quiz?->getFeedbackType(), [1, 3], true)) {
            $options[] = ['value' => 1, 'label' => 'Adaptative test with immediate feedback'];
            $options[] = ['value' => 3, 'label' => 'Direct pop-up mode'];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategoryMatrix(CQuiz $quiz): array
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return [];
        }

        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $categories = [];
        $generalQuestionCount = 0;
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $questionType = (int) $question->getType();
            if (self::MEDIA_QUESTION === $questionType || self::PAGE_BREAK === $questionType) {
                continue;
            }

            $questionCategories = $question->getCategories();
            if (0 === $questionCategories->count()) {
                $generalQuestionCount++;
                continue;
            }

            foreach ($questionCategories as $category) {
                if (!$category instanceof CQuizQuestionCategory || null === $category->getIid()) {
                    continue;
                }

                $categoryId = (int) $category->getIid();
                if (!isset($categories[$categoryId])) {
                    $categories[$categoryId] = [
                        'categoryId' => $categoryId,
                        'title' => $category->getTitle(),
                        'availableQuestions' => 0,
                    ];
                }

                $categories[$categoryId]['availableQuestions']++;
            }
        }

        if ([] === $categories) {
            return [];
        }

        uasort(
            $categories,
            static fn (array $left, array $right): int => strcasecmp((string) $left['title'], (string) $right['title'])
        );

        $savedCounts = $this->getSavedCategoryQuestionCounts($exerciseId);
        $matrix = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category['categoryId'];
            $matrix[] = [
                'categoryId' => $categoryId,
                'title' => (string) $category['title'],
                'availableQuestions' => (int) $category['availableQuestions'],
                'countQuestions' => $savedCounts[$categoryId] ?? -1,
            ];
        }

        $matrix[] = [
            'categoryId' => 0,
            'title' => 'General',
            'availableQuestions' => $generalQuestionCount,
            'countQuestions' => $savedCounts[0] ?? -1,
        ];

        return $matrix;
    }

    /**
     * @return array<int, int>
     */
    private function getSavedCategoryQuestionCounts(int $exerciseId): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT category_id, count_questions FROM c_quiz_rel_category WHERE exercise_id = :exerciseId',
            ['exerciseId' => $exerciseId],
            ['exerciseId' => Types::INTEGER]
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['category_id']] = (int) $row['count_questions'];
        }

        return $counts;
    }

    /**
     * @return array<string, bool>
     */
    private function getDefaultPageResultConfiguration(): array
    {
        return [
            'hideExpectedAnswers' => false,
            'hideTotalScore' => false,
            'hideQuestionScore' => false,
            'hideCategoryTable' => false,
            'hideCorrectAnsweredQuestions' => false,
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<string, bool>
     */
    private function normalizePageResultConfiguration(array $configuration): array
    {
        return [
            'hideExpectedAnswers' => $this->isEnabledPageResultFlag($configuration, 'hideExpectedAnswers', 'hide_expected_answer'),
            'hideTotalScore' => $this->isEnabledPageResultFlag($configuration, 'hideTotalScore', 'hide_total_score'),
            'hideQuestionScore' => $this->isEnabledPageResultFlag($configuration, 'hideQuestionScore', 'hide_question_score'),
            'hideCategoryTable' => $this->isEnabledPageResultFlag($configuration, 'hideCategoryTable', 'hide_category_table'),
            'hideCorrectAnsweredQuestions' => $this->isEnabledPageResultFlag($configuration, 'hideCorrectAnsweredQuestions', 'hide_correct_answered_questions'),
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function isEnabledPageResultFlag(array $configuration, string $camelKey, string $legacyKey): bool
    {
        $value = $configuration[$camelKey] ?? $configuration[$legacyKey] ?? false;

        return true === $value || 1 === $value || '1' === (string) $value || 'on' === strtolower((string) $value);
    }


    /**
     * Legacy freezes these fields after an exercise has been created.
     *
     * @return array<int, string>
     */
    private function getLockedFieldsForEdit(): array
    {
        return [
            'random',
            'maxAttempt',
            'propagateNeg',
            'enableTimeControl',
            'expiredTime',
            'reviewAnswers',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getResourceLanguageOptions(): array
    {
        $languages = $this->entityManager->getRepository(Language::class)->findBy(
            ['available' => true],
            ['englishName' => 'ASC']
        );

        $items = [
            ['value' => '', 'label' => 'No specific language'],
        ];

        foreach ($languages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $items[] = [
                'value' => $language->getIsocode(),
                'label' => $language->getOriginalName() ?: $language->getEnglishName(),
            ];
        }

        return $items;
    }

    private function getResourceLanguageIsoCode(CQuiz $quiz): string
    {
        $resourceNode = $quiz->getResourceNode();
        if (null === $resourceNode) {
            return '';
        }

        $language = $resourceNode->getLanguage();
        if (!$language instanceof Language) {
            return '';
        }

        return $language->getIsocode();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSkillOptions(): array
    {
        $skills = $this->entityManager->createQueryBuilder()
            ->select('skill')
            ->from(Skill::class, 'skill')
            ->andWhere('skill.status = :status')
            ->setParameter('status', Skill::STATUS_ENABLED, Types::INTEGER)
            ->orderBy('skill.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($skills as $skill) {
            if (!$skill instanceof Skill || null === $skill->getId()) {
                continue;
            }

            $label = $skill->getTitle();
            if ('' !== trim($skill->getShortCode())) {
                $label .= ' ('.$skill->getShortCode().')';
            }

            $items[] = [
                'value' => (int) $skill->getId(),
                'label' => $label,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, int>
     */
    private function getSelectedSkillIds(CQuiz $quiz): array
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return [];
        }

        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation', 'skill')
            ->from(SkillRelItem::class, 'relation')
            ->innerJoin('relation.skill', 'skill')
            ->andWhere('relation.itemType = :itemType')
            ->andWhere('relation.itemId = :itemId')
            ->andWhere('skill.status = :status')
            ->setParameter('itemType', self::SKILL_ITEM_TYPE_EXERCISE, Types::INTEGER)
            ->setParameter('itemId', $exerciseId, Types::INTEGER)
            ->setParameter('status', Skill::STATUS_ENABLED, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $ids = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof SkillRelItem || null === $relation->getSkill()->getId()) {
                continue;
            }

            $ids[] = (int) $relation->getSkill()->getId();
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, ExtraField>
     */
    private function getExerciseExtraFields(bool $includeNotifications = false): array
    {
        $fields = $this->entityManager->createQueryBuilder()
            ->select('field', 'options')
            ->from(ExtraField::class, 'field')
            ->leftJoin('field.options', 'options')
            ->andWhere('field.itemType = :itemType')
            ->setParameter('itemType', ExtraField::EXERCISE_FIELD_TYPE, Types::INTEGER)
            ->orderBy('field.fieldOrder', 'ASC')
            ->addOrderBy('field.displayText', 'ASC')
            ->addOrderBy('options.optionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($fields as $field) {
            if (!$field instanceof ExtraField) {
                continue;
            }

            if (!$includeNotifications && 'notifications' === $field->getVariable()) {
                continue;
            }

            $items[] = $field;
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExtraFieldDefinitions(): array
    {
        $items = [];
        foreach ($this->getExerciseExtraFields() as $field) {
            if (null === $field->getId() || !$this->isSupportedExtraFieldType((int) $field->getValueType())) {
                continue;
            }

            $items[] = [
                'id' => (int) $field->getId(),
                'variable' => $field->getVariable(),
                'label' => $field->getDisplayText() ?: $field->getVariable(),
                'type' => (int) $field->getValueType(),
                'defaultValue' => (string) ($field->getDefaultValue() ?? ''),
                'changeable' => true === $field->isChangeable(),
                'options' => $this->getExtraFieldOptionItems($field),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getExtraFieldOptionItems(ExtraField $field): array
    {
        $items = [];
        foreach ($field->getOptions() as $option) {
            if (!$option instanceof ExtraFieldOptions) {
                continue;
            }

            $value = (string) ($option->getValue() ?? '');
            if ('' === $value) {
                continue;
            }

            $items[] = [
                'value' => $value,
                'label' => $option->getDisplayText() ?: $value,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultExtraFieldValues(): array
    {
        $values = [];
        foreach ($this->getExerciseExtraFields() as $field) {
            if (!$this->isSupportedExtraFieldType((int) $field->getValueType())) {
                continue;
            }

            $values[$field->getVariable()] = $this->normalizeExtraFieldValueForFrontend(
                (int) $field->getValueType(),
                (string) ($field->getDefaultValue() ?? ''),
                0 < $field->getOptions()->count()
            );
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function getExerciseExtraFieldValues(CQuiz $quiz): array
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        $values = $this->getDefaultExtraFieldValues();
        if (0 >= $exerciseId) {
            return $values;
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('value', 'field')
            ->from(ExtraFieldValues::class, 'value')
            ->innerJoin('value.field', 'field')
            ->andWhere('value.itemId = :itemId')
            ->andWhere('field.itemType = :itemType')
            ->setParameter('itemId', $exerciseId, Types::INTEGER)
            ->setParameter('itemType', ExtraField::EXERCISE_FIELD_TYPE, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        foreach ($rows as $row) {
            if (!$row instanceof ExtraFieldValues) {
                continue;
            }

            $field = $row->getField();
            if ('notifications' === $field->getVariable() || !$this->isSupportedExtraFieldType((int) $field->getValueType())) {
                continue;
            }

            $values[$field->getVariable()] = $this->normalizeExtraFieldValueForFrontend(
                (int) $field->getValueType(),
                (string) ($row->getFieldValue() ?? ''),
                0 < $field->getOptions()->count()
            );
        }

        return $values;
    }

    private function getExerciseExtraNotification(CQuiz $quiz): string
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return '';
        }

        $row = $this->entityManager->createQueryBuilder()
            ->select('value', 'field')
            ->from(ExtraFieldValues::class, 'value')
            ->innerJoin('value.field', 'field')
            ->andWhere('value.itemId = :itemId')
            ->andWhere('field.itemType = :itemType')
            ->andWhere('field.variable = :variable')
            ->setParameter('itemId', $exerciseId, Types::INTEGER)
            ->setParameter('itemType', ExtraField::EXERCISE_FIELD_TYPE, Types::INTEGER)
            ->setParameter('variable', 'notifications', Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $row instanceof ExtraFieldValues ? (string) ($row->getFieldValue() ?? '') : '';
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getExtraNotificationOptions(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeExtraNotificationSettings(mixed $rawValue): array
    {
        if (
            \is_array($rawValue)
        ) {
            return $rawValue;
        }

        if (!\is_string($rawValue) || '' === trim($rawValue)) {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (\is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    private function normalizeExtraFieldValueForFrontend(int $type, string $value, bool $hasOptions): mixed
    {
        if (ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $type || (ExtraField::FIELD_TYPE_CHECKBOX === $type && $hasOptions)) {
            if ('' === trim($value)) {
                return [];
            }

            return array_values(array_filter(explode(';', $value), static fn (string $item): bool => '' !== trim($item)));
        }

        if (ExtraField::FIELD_TYPE_CHECKBOX === $type) {
            return true === $value || '1' === $value || 'on' === strtolower($value);
        }

        return $value;
    }

    private function isSupportedExtraFieldType(int $type): bool
    {
        return \in_array($type, [
            ExtraField::FIELD_TYPE_TEXT,
            ExtraField::FIELD_TYPE_TEXTAREA,
            ExtraField::FIELD_TYPE_RADIO,
            ExtraField::FIELD_TYPE_SELECT,
            ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
            ExtraField::FIELD_TYPE_DATE,
            ExtraField::FIELD_TYPE_DATETIME,
            ExtraField::FIELD_TYPE_CHECKBOX,
            ExtraField::FIELD_TYPE_INTEGER,
            ExtraField::FIELD_TYPE_FLOAT,
        ], true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategoryOptions(Course $course): array
    {
        if (!$this->isSettingEnabled('exercise.allow_exercise_categories')) {
            return [];
        }

        $categories = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(CQuizCategory::class, 'category')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('category.position', 'ASC')
            ->addOrderBy('category.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($categories as $category) {
            if (!$category instanceof CQuizCategory || null === $category->getId()) {
                continue;
            }

            $items[] = [
                'value' => (int) $category->getId(),
                'label' => $category->getTitle(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, int>
     */
    private function normalizeNotifications(?string $notifications): array
    {
        if (null === $notifications || '' === trim($notifications)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn (string $value): int => (int) trim($value),
                explode(',', $notifications)
            ),
            static fn (int $value): bool => 0 < $value
        ));
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function formatDateForInput(?DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $date->format('Y-m-d\\TH:i');
    }

    private function buildLegacyQuestionsUrl(CQuiz $quiz, Course $course, ?Session $session): string
    {
        return '/main/exercise/admin.php?'.http_build_query([
            'exerciseId' => (int) $quiz->getIid(),
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
        ]);
    }
}

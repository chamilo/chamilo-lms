<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
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
        $configuration->options = $this->getOptions($course);
        $configuration->listUrl = '';
        $configuration->questionsUrl = '';
        $configuration->maxAttempt = 0;
        $configuration->displayCategoryName = true;
        $configuration->hideAttemptsTable = $this->isSettingEnabled('exercise.quiz_hide_attempts_table_on_start_page');
        $configuration->feedbackType = 0;
        $configuration->resultsDisabled = 0;
        $configuration->questionSelectionType = 1;
        $configuration->randomByCategory = 0;
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
        $configuration->startTime = $this->formatDateForInput($quiz->getStartTime());
        $configuration->endTime = $this->formatDateForInput($quiz->getEndTime());
        $configuration->duration = $quiz->getDuration();
        $configuration->maxAttempt = $quiz->getMaxAttempt();
        $configuration->passPercentage = (int) ($quiz->getPassPercentage() ?? 0);
        $configuration->random = (int) $quiz->getRandom();
        $configuration->randomByCategory = (int) $quiz->getRandomByCategory();
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
        $configuration->options = $this->getOptions($course);
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
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getOptions(Course $course): array
    {
        return [
            'typeOptions' => [
                ['value' => CQuiz::ALL_ON_ONE_PAGE, 'label' => 'All questions on one page'],
                ['value' => CQuiz::ONE_PER_PAGE, 'label' => 'One question per page'],
            ],
            'categoryOptions' => $this->getCategoryOptions($course),
            'feedbackOptions' => [
                ['value' => 0, 'label' => 'At end of test'],
                ['value' => 1, 'label' => 'Adaptative test with immediate feedback'],
                ['value' => 2, 'label' => 'Exam (no feedback)'],
                ['value' => 3, 'label' => 'Direct pop-up mode'],
            ],
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

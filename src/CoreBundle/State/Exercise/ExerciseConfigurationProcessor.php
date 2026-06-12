<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
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
 * @implements ProcessorInterface<ExerciseConfiguration, ExerciseConfiguration>
 */
final readonly class ExerciseConfigurationProcessor implements ProcessorInterface
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseConfiguration
    {
        if (!$data instanceof ExerciseConfiguration) {
            throw new BadRequestHttpException('Invalid exercise configuration payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->csrfToken);

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercises in this context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if (0 < $exerciseId) {
            $quiz = $this->updateExercise($exerciseId, $data, $course, $session);
        } else {
            $quiz = $this->createExercise($data, $course, $session);
        }

        $this->entityManager->flush();

        return $this->buildResponse($quiz, $course, $session);
    }

    private function createExercise(ExerciseConfiguration $data, Course $course, ?Session $session): CQuiz
    {
        $this->validatePayload($data);

        $quiz = new CQuiz();
        $this->applyCommonFields($quiz, $data, $course);
        $quiz
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->quizRepository->create($quiz);

        return $quiz;
    }

    private function updateExercise(int $exerciseId, ExerciseConfiguration $data, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $this->validatePayload($data);
        $this->applyCommonFields($quiz, $data, $course);
        $this->entityManager->persist($quiz);

        return $quiz;
    }

    private function applyCommonFields(CQuiz $quiz, ExerciseConfiguration $data, Course $course): void
    {
        $startTime = $this->parseOptionalDate($data->startTime, 'startTime');
        $endTime = $this->parseOptionalDate($data->endTime, 'endTime');

        if (null !== $startTime && null !== $endTime && $startTime > $endTime) {
            throw new BadRequestHttpException('The first date should be before the end date.');
        }

        $feedbackType = $this->normalizeFeedbackType($data->feedbackType);
        $resultsDisabled = $this->normalizeResultsDisabled($data->resultsDisabled);
        if (\in_array($feedbackType, [1, 3], true)) {
            $resultsDisabled = 0;
        }

        $type = $data->type;
        if (!\in_array($type, [CQuiz::ALL_ON_ONE_PAGE, CQuiz::ONE_PER_PAGE], true)) {
            $type = CQuiz::ONE_PER_PAGE;
        }

        if (1 === $feedbackType) {
            $type = CQuiz::ONE_PER_PAGE;
        }

        $quiz
            ->setTitle(trim($data->title))
            ->setDescription((string) $data->description)
            ->setType($type)
            ->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setDuration($this->normalizeNullablePositiveInteger($data->duration))
            ->setMaxAttempt(max(0, $data->maxAttempt))
            ->setPassPercentage(max(0, min(100, $data->passPercentage)))
            ->setRandom($this->normalizeRandomQuestionCount($data->random))
            ->setRandomByCategory($this->normalizeRandomByCategory($data->randomByCategory))
            ->setRandomAnswers($data->randomAnswers)
            ->setShowPreviousButton($data->showPreviousButton)
            ->setPreventBackwards($data->preventBackwards ? 1 : 0)
            ->setHideAttemptsTable($data->hideAttemptsTable)
            ->setAutoLaunch($data->autoLaunch)
            ->setNotifications($this->normalizeNotifications($data->notifications))
            ->setAccessCondition((string) $data->accessCondition)
            ->setSound((string) $data->sound)
            ->setFeedbackType($feedbackType)
            ->setResultsDisabled($resultsDisabled)
            ->setQuestionSelectionType($this->normalizeQuestionSelectionType($data->questionSelectionType))
            ->setDisplayCategoryName($data->displayCategoryName ? 1 : 0)
            ->setHideQuestionTitle($data->hideQuestionTitle)
            ->setHideQuestionNumber($data->hideQuestionNumber ? 1 : 0)
            ->setPropagateNeg($data->propagateNeg ? 1 : 0)
            ->setSaveCorrectAnswers(max(0, $data->saveCorrectAnswers))
            ->setReviewAnswers($data->reviewAnswers ? 1 : 0)
            ->setExpiredTime(max(0, $data->expiredTime))
            ->setDisplayChartDegreeCertainty($this->normalizeBinaryInteger($data->displayChartDegreeCertainty))
            ->setSendEmailChartDegreeCertainty($this->normalizeBinaryInteger($data->sendEmailChartDegreeCertainty))
            ->setNotDisplayBalancePercentageCategorieQuestion($this->normalizeBinaryInteger($data->notDisplayBalancePercentageCategorieQuestion))
            ->setDisplayChartDegreeCertaintyCategory($this->normalizeBinaryInteger($data->displayChartDegreeCertaintyCategory))
            ->setGatherQuestionsCategories($this->normalizeBinaryInteger($data->gatherQuestionsCategories))
            ->setTextWhenFinished((string) $data->textWhenFinished)
            ->setTextWhenFinishedFailure((string) $data->textWhenFinishedFailure)
        ;

        if ($this->isSettingEnabled('exercise.allow_quiz_results_page_config')) {
            $quiz->setPageResultConfiguration($this->normalizePageResultConfigurationForStorage($data->pageResultConfiguration));
        }

        $category = $this->getCategory($data->categoryId, $course);
        if ($category instanceof CQuizCategory) {
            $quiz->setQuizCategory($category);
        }
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
     * Stores the same keys as legacy Exercise::setPageResultConfiguration().
     *
     * @param array<string, mixed> $configuration
     *
     * @return array<string, string>
     */
    private function normalizePageResultConfigurationForStorage(array $configuration): array
    {
        return [
            'hide_expected_answer' => $this->isEnabledPageResultFlag($configuration, 'hideExpectedAnswers', 'hide_expected_answer') ? 'on' : '',
            'hide_question_score' => $this->isEnabledPageResultFlag($configuration, 'hideQuestionScore', 'hide_question_score') ? 'on' : '',
            'hide_total_score' => $this->isEnabledPageResultFlag($configuration, 'hideTotalScore', 'hide_total_score') ? 'on' : '',
            'hide_category_table' => $this->isEnabledPageResultFlag($configuration, 'hideCategoryTable', 'hide_category_table') ? 'on' : '',
            'hide_correct_answered_questions' => $this->isEnabledPageResultFlag($configuration, 'hideCorrectAnsweredQuestions', 'hide_correct_answered_questions') ? 'on' : '',
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

    private function normalizeFeedbackType(int $feedbackType): int
    {
        return \in_array($feedbackType, [0, 1, 2, 3], true) ? $feedbackType : 0;
    }

    private function normalizeResultsDisabled(int $resultsDisabled): int
    {
        return 0 <= $resultsDisabled && 10 >= $resultsDisabled ? $resultsDisabled : 0;
    }

    private function normalizeQuestionSelectionType(int $questionSelectionType): int
    {
        return 1 <= $questionSelectionType && 10 >= $questionSelectionType ? $questionSelectionType : 1;
    }

    private function normalizeRandomByCategory(int $randomByCategory): int
    {
        return \in_array($randomByCategory, [0, 1, 2], true) ? $randomByCategory : 0;
    }

    private function normalizeRandomQuestionCount(int $random): int
    {
        if (-1 === $random) {
            return -1;
        }

        return max(0, $random);
    }

    private function normalizeBinaryInteger(int $value): int
    {
        return 1 === $value ? 1 : 0;
    }

    /**
     * @param array<int, mixed> $notifications
     */
    private function normalizeNotifications(array $notifications): string
    {
        $allowed = [1, 2, 3, 4];
        $normalized = [];
        foreach ($notifications as $notification) {
            $value = (int) $notification;
            if (\in_array($value, $allowed, true)) {
                $normalized[$value] = $value;
            }
        }

        return implode(',', array_values($normalized));
    }

    private function validatePayload(ExerciseConfiguration $data): void
    {
        if ('' === trim(strip_tags($data->title))) {
            throw new BadRequestHttpException('The exercise title is required.');
        }
    }

    private function getCategory(?int $categoryId, Course $course): ?CQuizCategory
    {
        if (null === $categoryId || 0 >= $categoryId) {
            return null;
        }

        $category = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(CQuizCategory::class, 'category')
            ->andWhere('category.id = :categoryId')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->setParameter('categoryId', $categoryId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$category instanceof CQuizCategory) {
            throw new BadRequestHttpException('The selected exercise category is invalid.');
        }

        return $category;
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

    private function parseOptionalDate(?string $value, string $field): ?DateTime
    {
        if (null === $value || '' === trim($value)) {
            return null;
        }

        try {
            $date = new DateTime($value);
        } catch (\Throwable) {
            throw new BadRequestHttpException('The '.$field.' field contains an invalid date.');
        }

        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }

    private function normalizeNullablePositiveInteger(?int $value): ?int
    {
        if (null === $value || 0 >= $value) {
            return null;
        }

        return $value;
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

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function buildResponse(CQuiz $quiz, Course $course, ?Session $session): ExerciseConfiguration
    {
        $configuration = new ExerciseConfiguration();
        $configuration->exerciseId = (int) $quiz->getIid();
        $configuration->mode = 'edit';
        $configuration->title = $quiz->getTitle();
        $configuration->description = (string) $quiz->getDescription();
        $configuration->type = $quiz->getType();
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
        $configuration->notifications = $this->parseNotifications($quiz->getNotifications());
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
        $configuration->questionsUrl = $this->buildLegacyQuestionsUrl($quiz, $course, $session);
        $configuration->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);

        return $configuration;
    }


    /**
     * @return array<int, int>
     */
    private function parseNotifications(?string $notifications): array
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

    private function formatDateForInput(?DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $date->format('Y-m-d\TH:i');
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

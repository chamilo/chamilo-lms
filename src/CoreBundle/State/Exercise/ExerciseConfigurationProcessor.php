<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
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
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
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
        $this->getLanguageFromCode($data->language);

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if (0 < $exerciseId) {
            $quiz = $this->updateExercise($exerciseId, $data, $course, $session);
        } else {
            $quiz = $this->createExercise($data, $course, $session);
        }

        $this->entityManager->flush();
        $this->applyResourceLanguage($quiz, $data->language);
        $this->updateLearningPathTitles($quiz, $data);
        $this->saveCategoryMatrix($quiz, $data);
        $this->saveExerciseSkills($quiz, $data, $course, $session);
        $this->saveExerciseExtraFields($quiz, $data);
        $this->saveExerciseGradebookLink($quiz, $data, $course);
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
        if (!$this->isSettingEnabled('enable_quiz_scenario') && \in_array($feedbackType, [1, 3], true)) {
            $feedbackType = \in_array($quiz->getFeedbackType(), [1, 3], true) ? $quiz->getFeedbackType() : 0;
        }

        $resultsDisabled = $this->normalizeResultsDisabled($data->resultsDisabled);
        if (\in_array($feedbackType, [1, 3], true)) {
            $resultsDisabled = 0;
        }

        $type = $data->type;
        if (!\in_array($type, [CQuiz::ALL_ON_ONE_PAGE, CQuiz::ONE_PER_PAGE], true)) {
            $type = CQuiz::ONE_PER_PAGE;
        }

        if (\in_array($feedbackType, [1, 3], true)) {
            $type = CQuiz::ONE_PER_PAGE;
        }

        $isExistingExercise = null !== $quiz->getIid();
        $random = $isExistingExercise ? (int) $quiz->getRandom() : $this->normalizeRandomQuestionCount($data->random);
        $maxAttempt = $isExistingExercise ? $quiz->getMaxAttempt() : max(0, $data->maxAttempt);
        $propagateNeg = $isExistingExercise ? $quiz->getPropagateNeg() : ($data->propagateNeg ? 1 : 0);
        $reviewAnswers = $isExistingExercise ? $quiz->getReviewAnswers() : ($data->reviewAnswers ? 1 : 0);
        $expiredTime = $isExistingExercise ? $quiz->getExpiredTime() : max(0, $data->expiredTime);

        $quiz
            ->setTitle(trim($data->title))
            ->setDescription((string) $data->description)
            ->setType($type)
            ->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setDuration($this->normalizeNullablePositiveInteger($data->duration))
            ->setMaxAttempt($maxAttempt)
            ->setPassPercentage(max(0, min(100, $data->passPercentage)))
            ->setRandom($random)
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
            ->setPropagateNeg($propagateNeg)
            ->setSaveCorrectAnswers(max(0, $data->saveCorrectAnswers))
            ->setReviewAnswers($reviewAnswers)
            ->setExpiredTime($expiredTime)
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
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
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
        $category = $quiz->getQuizCategory();
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
        $configuration->settings = $this->getSettings();
        $configuration->options = $this->getOptions($course, $quiz);
        $configuration->canCreate = true;
        $configuration->canEdit = true;

        return $configuration;
    }



    private function applyResourceLanguage(CQuiz $quiz, string $rawLanguage): void
    {
        $resourceNode = $quiz->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $language = $this->getLanguageFromCode($rawLanguage);

        $resourceNode->setLanguage($language);
        $this->entityManager->persist($resourceNode);
    }


    private function getLanguageFromCode(string $rawLanguage): ?Language
    {
        $languageCode = trim($rawLanguage);
        if ('' === $languageCode) {
            return null;
        }

        $language = $this->entityManager->getRepository(Language::class)->findOneBy([
            'isocode' => $languageCode,
            'available' => true,
        ]);

        if (!$language instanceof Language) {
            throw new BadRequestHttpException('The selected resource language is invalid.');
        }

        return $language;
    }

    private function saveExerciseGradebookLink(CQuiz $quiz, ExerciseConfiguration $data, Course $course): void
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return;
        }

        $link = $this->getExerciseGradebookLink($exerciseId, $course);
        if (!$data->addToGradebook) {
            if ($link instanceof GradebookLink && 1 !== (int) $link->getLocked()) {
                $this->entityManager->remove($link);
            }

            return;
        }

        if (null === $data->gradebookCategoryId || 0 >= $data->gradebookCategoryId) {
            return;
        }

        $category = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(GradebookCategory::class, 'category')
            ->andWhere('category.id = :categoryId')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->setParameter('categoryId', (int) $data->gradebookCategoryId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$category instanceof GradebookCategory) {
            return;
        }

        if (!$link instanceof GradebookLink) {
            $link = new GradebookLink();
            $link->setType(1);
            $link->setRefId($exerciseId);
            $link->setUserScoreList([]);
            $link->setCourse($course);
            $link->setCreatedAt(new DateTime());
            $link->setLocked(0);
        }

        if (1 === (int) $link->getLocked()) {
            return;
        }

        $link
            ->setCategory($category)
            ->setWeight((float) max(0, $data->gradebookWeight))
            ->setVisible($data->gradebookVisible ? 1 : 0)
        ;

        $this->entityManager->persist($link);
    }

    private function getExerciseGradebookLink(int $exerciseId, Course $course): ?GradebookLink
    {
        $link = $this->entityManager->createQueryBuilder()
            ->select('link')
            ->from(GradebookLink::class, 'link')
            ->andWhere('link.type = :type')
            ->andWhere('link.refId = :exerciseId')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->setParameter('type', 1, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $link instanceof GradebookLink ? $link : null;
    }

    private function updateLearningPathTitles(CQuiz $quiz, ExerciseConfiguration $data): void
    {
        if (!$data->updateTitleInLearningPaths || null === $quiz->getIid()) {
            return;
        }

        $items = $this->entityManager->createQueryBuilder()
            ->select('item')
            ->from(CLpItem::class, 'item')
            ->andWhere('item.itemType = :itemType')
            ->andWhere('item.path = :path')
            ->setParameter('itemType', 'quiz', Types::STRING)
            ->setParameter('path', (string) $quiz->getIid(), Types::STRING)
            ->getQuery()
            ->getResult()
        ;

        foreach ($items as $item) {
            if (!$item instanceof CLpItem) {
                continue;
            }

            $item->setTitle($quiz->getTitle());
            $this->entityManager->persist($item);
        }
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

    private function saveCategoryMatrix(CQuiz $quiz, ExerciseConfiguration $data): void
    {
        if ([] === $data->categoryMatrix) {
            return;
        }

        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return;
        }

        $matrix = $this->normalizeCategoryMatrix($quiz, $data->categoryMatrix);
        if ([] === $matrix) {
            return;
        }

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            'DELETE FROM c_quiz_rel_category WHERE exercise_id = :exerciseId',
            ['exerciseId' => $exerciseId],
            ['exerciseId' => Types::INTEGER]
        );

        foreach ($matrix as $categoryId => $countQuestions) {
            if (0 === (int) $categoryId) {
                continue;
            }

            $connection->insert('c_quiz_rel_category', [
                'exercise_id' => $exerciseId,
                'category_id' => $categoryId,
                'count_questions' => $countQuestions,
            ], [
                'exercise_id' => Types::INTEGER,
                'category_id' => Types::INTEGER,
                'count_questions' => Types::INTEGER,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $matrix
     *
     * @return array<int, int>
     */
    private function normalizeCategoryMatrix(CQuiz $quiz, array $matrix): array
    {
        $allowedCategoryIds = $this->getAllowedCategoryMatrixIds($quiz);
        if ([] === $allowedCategoryIds) {
            return [];
        }

        $normalized = [];
        foreach ($matrix as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $categoryId = (int) ($row['categoryId'] ?? 0);
            if (!\in_array($categoryId, $allowedCategoryIds, true)) {
                continue;
            }

            $countQuestions = (int) ($row['countQuestions'] ?? -1);
            $normalized[$categoryId] = max(-1, $countQuestions);
        }

        return $normalized;
    }

    /**
     * @return array<int, int>
     */
    private function getAllowedCategoryMatrixIds(CQuiz $quiz): array
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
            ->getQuery()
            ->getResult()
        ;

        $categoryIds = [];
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

            foreach ($question->getCategories() as $category) {
                if (!$category instanceof CQuizQuestionCategory || null === $category->getIid()) {
                    continue;
                }

                $categoryIds[(int) $category->getIid()] = (int) $category->getIid();
            }
        }

        if ([] === $categoryIds) {
            return [];
        }

        $categoryIds[0] = 0;

        return array_values($categoryIds);
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
    private function getSettings(): array
    {
        return [
            'allowExerciseCategories' => $this->isSettingEnabled('exercise.allow_exercise_categories'),
            'allowQuizResultsPageConfig' => $this->isSettingEnabled('exercise.allow_quiz_results_page_config'),
            'allowExerciseAutoLaunch' => $this->isSettingEnabled('exercise.allow_exercise_auto_launch'),
            'enableExerciseAutoLaunch' => $this->isSettingEnabled('exercise.enable_exercise_auto_launch'),
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

    private function saveExerciseSkills(CQuiz $quiz, ExerciseConfiguration $data, Course $course, ?Session $session): void
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return;
        }

        $skillIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): int => (int) $value, $data->skillIds),
            static fn (int $value): bool => 0 < $value
        )));

        $this->entityManager->createQueryBuilder()
            ->delete(SkillRelItem::class, 'relation')
            ->andWhere('relation.itemType = :itemType')
            ->andWhere('relation.itemId = :itemId')
            ->setParameter('itemType', self::SKILL_ITEM_TYPE_EXERCISE, Types::INTEGER)
            ->setParameter('itemId', $exerciseId, Types::INTEGER)
            ->getQuery()
            ->execute()
        ;

        if ([] === $skillIds) {
            return;
        }

        $skills = $this->entityManager->createQueryBuilder()
            ->select('skill')
            ->from(Skill::class, 'skill')
            ->andWhere('skill.id IN (:skillIds)')
            ->andWhere('skill.status = :status')
            ->setParameter('skillIds', $skillIds, ArrayParameterType::INTEGER)
            ->setParameter('status', Skill::STATUS_ENABLED, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $userId = $this->getCurrentUserId();
        foreach ($skills as $skill) {
            if (!$skill instanceof Skill) {
                continue;
            }

            $relation = new SkillRelItem();
            $relation
                ->setSkill($skill)
                ->setItemType(self::SKILL_ITEM_TYPE_EXERCISE)
                ->setItemId($exerciseId)
                ->setIsReal(true)
                ->setRequiresValidation(false)
                ->setCreatedBy($userId)
                ->setUpdatedBy($userId)
                ->setCourseId((int) $course->getId())
            ;

            if (null !== $session && null !== $session->getId()) {
                $relation->setSessionId((int) $session->getId());
            }

            $this->entityManager->persist($relation);
        }
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
    private function getExerciseExtraFieldValues(CQuiz $quiz): array
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
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

    private function saveExerciseExtraFields(CQuiz $quiz, ExerciseConfiguration $data): void
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return;
        }

        foreach ($this->getExerciseExtraFields() as $field) {
            if (null === $field->getId() || !$this->isSupportedExtraFieldType((int) $field->getValueType())) {
                continue;
            }

            $rawValue = $data->extraFieldValues[$field->getVariable()] ?? null;
            $value = $this->normalizeExtraFieldValueForStorage($field, $rawValue);
            $this->saveExtraFieldValue($field, $exerciseId, $value);
        }
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

    private function saveExerciseExtraNotification(CQuiz $quiz, ExerciseConfiguration $data): void
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if (0 >= $exerciseId) {
            return;
        }

        $notification = trim($data->extraNotification);
        $options = $this->getExtraNotificationOptions();
        if ('' !== $notification && [] !== $options && !\in_array($notification, array_column($options, 'value'), true)) {
            throw new BadRequestHttpException('The selected finished notification is invalid.');
        }

        $field = $this->getExerciseExtraFieldByVariable('notifications');
        if (!$field instanceof ExtraField) {
            return;
        }

        $this->saveExtraFieldValue($field, $exerciseId, $notification);
    }

    private function getExerciseExtraFieldByVariable(string $variable): ?ExtraField
    {
        $field = $this->entityManager->createQueryBuilder()
            ->select('field')
            ->from(ExtraField::class, 'field')
            ->andWhere('field.itemType = :itemType')
            ->andWhere('field.variable = :variable')
            ->setParameter('itemType', ExtraField::EXERCISE_FIELD_TYPE, Types::INTEGER)
            ->setParameter('variable', $variable, Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $field instanceof ExtraField ? $field : null;
    }

    private function saveExtraFieldValue(ExtraField $field, int $exerciseId, string $value): void
    {
        $extraValue = $this->entityManager->createQueryBuilder()
            ->select('value')
            ->from(ExtraFieldValues::class, 'value')
            ->andWhere('value.itemId = :itemId')
            ->andWhere('IDENTITY(value.field) = :fieldId')
            ->setParameter('itemId', $exerciseId, Types::INTEGER)
            ->setParameter('fieldId', (int) $field->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$extraValue instanceof ExtraFieldValues) {
            $extraValue = new ExtraFieldValues();
            $extraValue->setField($field);
            $extraValue->setItemId($exerciseId);
            $extraValue->setComment('');
        }

        $extraValue->setFieldValue($value);
        $this->entityManager->persist($extraValue);
    }

    private function normalizeExtraFieldValueForStorage(ExtraField $field, mixed $rawValue): string
    {
        $type = (int) $field->getValueType();
        $hasOptions = 0 < $field->getOptions()->count();

        if (ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $type || (ExtraField::FIELD_TYPE_CHECKBOX === $type && $hasOptions)) {
            if (!\is_array($rawValue)) {
                return '';
            }

            return implode(';', array_values(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), $rawValue),
                static fn (string $value): bool => '' !== $value
            )));
        }

        if (ExtraField::FIELD_TYPE_CHECKBOX === $type) {
            return true === $rawValue || '1' === (string) $rawValue || 'on' === strtolower((string) $rawValue) ? '1' : '0';
        }

        return trim((string) $rawValue);
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
        if (\is_array($rawValue)) {
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

    private function getCurrentUserId(): int
    {
        $user = $this->security->getUser();
        if (!\is_object($user) || !method_exists($user, 'getId')) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return (int) $user->getId();
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

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionEditor;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
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
 * @implements ProviderInterface<ExerciseQuestionEditor>
 */
final readonly class ExerciseQuestionEditorProvider implements ProviderInterface
{
    private const CSRF_TOKEN_ID = 'exercise_question_editor';
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;
    private const FILL_IN_BLANKS = 3;
    private const MATCHING = 4;
    private const FREE_ANSWER = 5;
    private const HOT_SPOT = 6;
    private const HOT_SPOT_DELINEATION = 8;
    private const MULTIPLE_ANSWER_COMBINATION = 9;
    private const UNIQUE_ANSWER_NO_OPTION = 10;
    private const MULTIPLE_ANSWER_TRUE_FALSE = 11;
    private const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;
    private const ORAL_EXPRESSION = 13;
    private const GLOBAL_MULTIPLE_ANSWER = 14;
    private const MEDIA_QUESTION = 15;
    private const CALCULATED_ANSWER = 16;
    private const UNIQUE_ANSWER_IMAGE = 17;
    private const ANNOTATION = 20;
    private const READING_COMPREHENSION = 21;
    private const MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY = 22;
    private const UPLOAD_ANSWER = 23;
    private const PAGE_BREAK = 31;
    private const DRAGGABLE = 18;
    private const MATCHING_DRAGGABLE = 19;
    private const MATCHING_COMBINATION = 24;
    private const MATCHING_DRAGGABLE_COMBINATION = 25;
    private const HOT_SPOT_COMBINATION = 26;
    private const FILL_IN_BLANKS_COMBINATION = 27;
    private const MULTIPLE_ANSWER_DROPDOWN_COMBINATION = 28;
    private const MULTIPLE_ANSWER_DROPDOWN = 29;
    private const UNKNOWN_ANSWER_POSITION = 666;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizQuestionRepository $questionRepository,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionEditor
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercise questions in this context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $questionId = isset($uriVariables['questionId']) ? (int) $uriVariables['questionId'] : 0;

        if (0 < $questionId) {
            return $this->buildExistingQuestionResponse($quiz, $questionId, $course, $session);
        }

        $type = $request->query->getInt('type', self::UNIQUE_ANSWER);
        if (!$this->isVueSupportedQuestionType($type)) {
            throw new BadRequestHttpException('This question type is still managed by the legacy exercise tool.');
        }

        return $this->buildNewQuestionResponse($quiz, $type, $course, $session);
    }

    private function buildNewQuestionResponse(CQuiz $quiz, int $type, Course $course, ?Session $session): ExerciseQuestionEditor
    {
        $response = new ExerciseQuestionEditor();
        $response->exerciseId = (int) $quiz->getIid();
        $response->questionId = null;
        $response->type = $type;
        $response->typeLabel = $this->getQuestionTypeLabel($type);
        $response->score = $this->usesGlobalScore($type) ? 0.0 : 1.0;
        $response->globalScore = $this->usesGlobalScore($type) ? 10.0 : 0.0;
        if (self::CALCULATED_ANSWER === $type) {
            $response->score = 10.0;
            $response->globalScore = 0.0;
            $response->calculatedText = $this->getDefaultCalculatedText();
            $response->calculatedFormula = '';
            $response->calculatedRanges = $this->buildCalculatedRanges($response->calculatedText, []);
            $response->calculatedVariations = 1;
            $response->calculatedComment = '';
            $response->answers = [];
        }
        if ($this->usesFillBlanks($type)) {
            $response->fillBlanksText = 'The capital of Peru is [Lima].';
            $response->fillBlankItems = $this->buildFillBlankItems($response->fillBlanksText, [1.0], [200], 0);
            $response->fillBlanksSeparator = 0;
            $response->fillBlanksSwitchable = false;
            $response->fillBlanksCaseInsensitive = false;
            $response->score = self::FILL_IN_BLANKS_COMBINATION === $type ? 0.0 : 1.0;
            $response->globalScore = self::FILL_IN_BLANKS_COMBINATION === $type ? 10.0 : 0.0;
        }
        if ($this->usesHotspot($type)) {
            $response->score = self::HOT_SPOT_COMBINATION === $type ? 0.0 : 10.0;
            $response->globalScore = self::HOT_SPOT_COMBINATION === $type ? 10.0 : 0.0;
            $response->hotspotItems = $this->getDefaultHotspotItems($type);
            if (self::HOT_SPOT_DELINEATION === $type) {
                $this->addHotspotScenarioOptions($response, $quiz, null);
            }
            $response->answers = [];
        }
        if ($this->usesMatching($type)) {
            $response->score = $this->usesGlobalScore($type) ? 0.0 : 20.0;
            $response->globalScore = $this->usesGlobalScore($type) ? 10.0 : 0.0;
            $response->matchingOptions = $this->getDefaultMatchingOptions();
            $response->matchingPairs = $this->getDefaultMatchingPairs($type);
            $response->answers = [];
        }
        if ($this->usesDraggableOrdering($type)) {
            $response->score = 20.0;
            $response->globalScore = 0.0;
            $response->draggableItems = $this->getDefaultDraggableItems();
            $response->matchingOrientation = 'h';
            $response->answers = [];
        }
        if (\in_array($type, [self::ORAL_EXPRESSION, self::ANNOTATION, self::UPLOAD_ANSWER], true)) {
            $response->score = 10.0;
            $response->globalScore = 0.0;
            $response->answers = [];
        }
        if ($this->isStructuralQuestionType($type)) {
            $response->score = 0.0;
            $response->globalScore = 0.0;
            $response->mandatory = false;
            $response->duration = null;
            $response->parentMediaId = 0;
            $response->answers = [];
        }
        $response->correctScore = 1.0;
        $response->wrongScore = -0.5;
        $response->unknownScore = 0.0;
        $response->noNegativeScore = self::GLOBAL_MULTIPLE_ANSWER === $type;
        $response->usesGlobalScore = $this->usesGlobalScore($type);
        $response->hasFixedUnknownAnswer = self::UNIQUE_ANSWER_NO_OPTION === $type;
        $response->difficulty = 1;
        $response->categoryId = 0;
        $response->parentMediaId = 0;
        $response->answers = $this->getDefaultAnswers($type);
        if ($this->usesMatching($type) || $this->usesDraggableOrdering($type) || $this->usesHotspot($type) || $this->isStructuralQuestionType($type)) {
            $response->answers = [];
        }
        $this->addSharedEditorData($response, $quiz, null, $course, $session);

        return $response;
    }

    private function buildExistingQuestionResponse(CQuiz $quiz, int $questionId, Course $course, ?Session $session): ExerciseQuestionEditor
    {
        $question = $this->getQuestionFromExercise($quiz, $questionId);
        $type = (int) $question->getType();
        if (!$this->isVueSupportedQuestionType($type)) {
            throw new BadRequestHttpException('This question type is still managed by the legacy exercise tool.');
        }

        $response = new ExerciseQuestionEditor();
        $response->exerciseId = (int) $quiz->getIid();
        $response->questionId = $questionId;
        $response->type = $type;
        $response->typeLabel = $this->getQuestionTypeLabel($type);
        $response->title = $question->getQuestion();
        $response->description = (string) $question->getDescription();
        $response->feedback = (string) $question->getFeedback();
        $response->dropdownListText = $this->buildDropdownListText($question);
        $response->score = (float) $question->getPonderation();
        $response->globalScore = $this->usesGlobalScore($type) ? (float) $question->getPonderation() : 0.0;
        [$response->correctScore, $response->wrongScore, $response->unknownScore] = $this->getTrueFalseScores($question);
        $response->usesGlobalScore = $this->usesGlobalScore($type);
        $response->hasFixedUnknownAnswer = self::UNIQUE_ANSWER_NO_OPTION === $type;
        $response->mandatory = 1 === (int) $question->getMandatory();
        $response->duration = $question->getDuration();
        $response->difficulty = max(1, (int) $question->getLevel());
        $response->categoryId = $this->getFirstCategoryId($question);
        $response->parentMediaId = (int) ($question->getParentMediaId() ?? 0);
        $response->answers = $this->getAnswers($question);
        $this->addAnnotationData($response, $question, $course, $session);
        $this->addHotspotData($response, $quiz, $question, $course, $session);
        $this->addCalculatedData($response, $question);
        $this->addFillBlanksData($response, $question);
        $this->addMatchingData($response, $question);
        $this->addDraggableData($response, $question);
        $response->noNegativeScore = self::GLOBAL_MULTIPLE_ANSWER === $type && $this->hasNoNegativeScore($response->answers);
        $this->addSharedEditorData($response, $quiz, $question, $course, $session);

        return $response;
    }

    private function addSharedEditorData(
        ExerciseQuestionEditor $response,
        CQuiz $quiz,
        ?CQuizQuestion $question,
        Course $course,
        ?Session $session,
    ): void {
        $summary = $this->getQuestionSummary($quiz);
        $response->questionCount = (int) $summary['questionCount'];
        $response->totalScore = (float) $summary['totalScore'];
        $response->categoryOptions = $this->getCategoryOptions($course, $session);
        $response->mediaOptions = $this->getMediaOptions($quiz, $question);
        $response->legacyUrls = $this->getLegacyUrls($quiz, $course, $session);
        $response->csrfToken = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();
        $response->allowQuestionFeedback = $this->isQuestionFeedbackEnabled();
        $response->imageZoomEnabled = $this->isImageZoomEnabled();
        $response->allowMandatoryQuestion = $this->isMandatoryQuestionInCategoryEnabled($quiz);
    }

    private function isMandatoryQuestionInCategoryEnabled(CQuiz $quiz): bool
    {
        return 5 === (int) ($quiz->getQuestionSelectionType() ?? 0)
            && 'true' === $this->settingsManager->getSetting('exercise.allow_mandatory_question_in_category', true)
            && $this->hasMandatoryQuestionCategoryColumn();
    }

    private function hasMandatoryQuestionCategoryColumn(): bool
    {
        try {
            return $this->entityManager
                ->getConnection()
                ->createSchemaManager()
                ->introspectTable('c_quiz_question_rel_category')
                ->hasColumn('mandatory');
        } catch (\Throwable) {
            return false;
        }
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

    private function getQuestionFromExercise(CQuiz $quiz, int $questionId): CQuizQuestion
    {
        $relation = $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$relation instanceof CQuizRelQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        return $relation->getQuestion();
    }


    private function buildDropdownListText(CQuizQuestion $question): string
    {
        $type = (int) $question->getType();
        if (!\in_array($type, [self::MULTIPLE_ANSWER_DROPDOWN, self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION], true)) {
            return '';
        }

        $lines = [];
        foreach ($this->getExistingAnswers($question) as $answer) {
            $lines[] = trim(strip_tags(html_entity_decode((string) $answer->getAnswer(), ENT_QUOTES, 'UTF-8')));
        }

        return implode(PHP_EOL, array_values(array_filter($lines, static fn (string $line): bool => '' !== $line)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultAnswers(int $type): array
    {
        if (\in_array($type, [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::ANNOTATION, self::UPLOAD_ANSWER, self::CALCULATED_ANSWER], true) || $this->isStructuralQuestionType($type) || $this->usesFillBlanks($type) || $this->usesMatching($type) || $this->usesDraggableOrdering($type) || \in_array($type, [self::MULTIPLE_ANSWER_DROPDOWN, self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION], true)) {
            return [];
        }

        $answers = [];
        $maxPosition = self::UNIQUE_ANSWER_NO_OPTION === $type ? 3 : 4;
        for ($position = 1; $position <= $maxPosition; $position++) {
            $answers[] = [
                'id' => null,
                'answer' => '',
                'correct' => $this->usesSingleCorrectAnswer($type) ? 1 === $position : false,
                'correctChoice' => $this->usesTrueFalseOptions($type) ? 1 : null,
                'comment' => '',
                'score' => $this->usesGlobalScore($type) || $this->usesTrueFalseOptions($type) ? 0.0 : (1 === $position ? 1.0 : 0.0),
                'position' => $position,
                'isUnknown' => false,
            ];
        }

        if (self::UNIQUE_ANSWER_NO_OPTION === $type) {
            $answers[] = [
                'id' => null,
                'answer' => 'Don\'t know',
                'correct' => false,
                'correctChoice' => null,
                'comment' => '',
                'score' => 0.0,
                'position' => self::UNKNOWN_ANSWER_POSITION,
                'isUnknown' => true,
            ];
        }

        return $answers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAnswers(CQuizQuestion $question): array
    {
        $answers = $this->getExistingAnswers($question);

        $type = (int) $question->getType();
        if (self::CALCULATED_ANSWER === $type || $this->usesFillBlanks($type) || $this->usesMatching($type) || $this->usesDraggableOrdering($type)) {
            return [];
        }

        $optionPositionsByIid = $this->getQuestionOptionPositionsByIid($question);

        $items = [];
        foreach ($answers as $answer) {
            if (!$answer instanceof CQuizAnswer) {
                continue;
            }

            $correctValue = (int) $answer->getCorrect();
            $correctChoice = $this->usesTrueFalseOptions($type)
                ? (int) ($optionPositionsByIid[$correctValue] ?? $correctValue)
                : null;

            $items[] = [
                'id' => $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'correct' => $this->usesTrueFalseOptions($type) ? 1 === $correctChoice : 1 === $correctValue,
                'correctChoice' => $this->usesTrueFalseOptions($type) ? $correctChoice : null,
                'comment' => (string) $answer->getComment(),
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
                'isUnknown' => self::UNKNOWN_ANSWER_POSITION === (int) $answer->getPosition(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getExistingAnswers(CQuizQuestion $question): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array{questionCount: int, totalScore: float}
     */
    private function getQuestionSummary(CQuiz $quiz): array
    {
        $questions = $this->entityManager->createQueryBuilder()
            ->select('question.ponderation')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;

        $totalScore = 0.0;
        foreach ($questions as $question) {
            $totalScore += (float) ($question['ponderation'] ?? 0.0);
        }

        return [
            'questionCount' => \count($questions),
            'totalScore' => $totalScore,
        ];
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function getCategoryOptions(Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(CQuizQuestionCategory::class, 'category')
            ->innerJoin('category.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('category.title', 'ASC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $category) {
            if (!$category instanceof CQuizQuestionCategory || null === $category->getIid()) {
                continue;
            }

            $items[] = [
                'label' => $category->getTitle(),
                'value' => (int) $category->getIid(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function getMediaOptions(CQuiz $quiz, ?CQuizQuestion $currentQuestion): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('question.type = :mediaType')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('mediaType', self::MEDIA_QUESTION, Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
        ;

        if (null !== $currentQuestion && null !== $currentQuestion->getIid()) {
            $queryBuilder
                ->andWhere('question.iid != :questionId')
                ->setParameter('questionId', (int) $currentQuestion->getIid(), Types::INTEGER)
            ;
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
                continue;
            }

            $items[] = [
                'label' => strip_tags($question->getQuestion()),
                'value' => (int) $question->getIid(),
            ];
        }

        return $items;
    }

    private function getFirstCategoryId(CQuizQuestion $question): int
    {
        foreach ($question->getCategories() as $category) {
            if ($category instanceof CQuizQuestionCategory && null !== $category->getIid()) {
                return (int) $category->getIid();
            }
        }

        return 0;
    }

    private function isVueSupportedQuestionType(int $type): bool
    {
        return \in_array($type, [
            self::UNIQUE_ANSWER,
            self::MULTIPLE_ANSWER,
            self::FILL_IN_BLANKS,
            self::MATCHING,
            self::FREE_ANSWER,
            self::HOT_SPOT,
            self::ORAL_EXPRESSION,
            self::MULTIPLE_ANSWER_COMBINATION,
            self::UNIQUE_ANSWER_NO_OPTION,
            self::MULTIPLE_ANSWER_TRUE_FALSE,
            self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE,
            self::GLOBAL_MULTIPLE_ANSWER,
            self::MEDIA_QUESTION,
            self::CALCULATED_ANSWER,
            self::UNIQUE_ANSWER_IMAGE,
            self::ANNOTATION,
            self::READING_COMPREHENSION,
            self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY,
            self::UPLOAD_ANSWER,
            self::DRAGGABLE,
            self::MATCHING_DRAGGABLE,
            self::MATCHING_COMBINATION,
            self::MATCHING_DRAGGABLE_COMBINATION,
            self::HOT_SPOT_COMBINATION,
            self::FILL_IN_BLANKS_COMBINATION,
            self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION,
            self::MULTIPLE_ANSWER_DROPDOWN,
            self::PAGE_BREAK,
        ], true);
    }

    private function getQuestionTypeLabel(int $type): string
    {
        return match ($type) {
            self::UNIQUE_ANSWER => 'Unique answer',
            self::MULTIPLE_ANSWER => 'Multiple answer',
            self::FILL_IN_BLANKS => 'Fill in blanks',
            self::MATCHING => 'Matching',
            self::FREE_ANSWER => 'Open question',
            self::HOT_SPOT => 'Hotspot',
            self::HOT_SPOT_DELINEATION => 'Hotspot delineation',
            self::ORAL_EXPRESSION => 'Oral expression',
            self::MULTIPLE_ANSWER_COMBINATION => 'Exact Selection',
            self::UNIQUE_ANSWER_NO_OPTION => 'Unique answer with unknown',
            self::MULTIPLE_ANSWER_TRUE_FALSE => 'Multiple answer true/false',
            self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => 'Multiple answer combination true/false',
            self::GLOBAL_MULTIPLE_ANSWER => 'Global multiple answer',
            self::MEDIA_QUESTION => 'Media question',
            self::CALCULATED_ANSWER => 'Calculated answer',
            self::UNIQUE_ANSWER_IMAGE => 'Unique answer with images',
            self::ANNOTATION => 'Annotation',
            self::READING_COMPREHENSION => 'Reading comprehension',
            self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => 'Multiple answer true/false with degree of certainty',
            self::UPLOAD_ANSWER => 'Upload answer',
            self::DRAGGABLE => 'Draggable',
            self::MATCHING_DRAGGABLE => 'Matching draggable',
            self::MATCHING_COMBINATION => 'Matching combination',
            self::MATCHING_DRAGGABLE_COMBINATION => 'Matching draggable combination',
            self::HOT_SPOT_COMBINATION => 'Hotspot combination',
            self::FILL_IN_BLANKS_COMBINATION => 'Fill in blanks combination',
            self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => 'Multiple answer dropdown combination',
            self::MULTIPLE_ANSWER_DROPDOWN => 'Multiple answer dropdown',
            self::PAGE_BREAK => 'Page break',
            default => 'Question',
        };
    }

    private function usesSingleCorrectAnswer(int $type): bool
    {
        return \in_array($type, [self::UNIQUE_ANSWER, self::UNIQUE_ANSWER_NO_OPTION, self::UNIQUE_ANSWER_IMAGE], true);
    }

    private function usesGlobalScore(int $type): bool
    {
        return \in_array($type, [self::MULTIPLE_ANSWER_COMBINATION, self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE, self::GLOBAL_MULTIPLE_ANSWER, self::FILL_IN_BLANKS_COMBINATION, self::MATCHING_COMBINATION, self::MATCHING_DRAGGABLE_COMBINATION, self::HOT_SPOT_COMBINATION, self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION], true);
    }

    private function usesTrueFalseOptions(int $type): bool
    {
        return \in_array($type, [self::MULTIPLE_ANSWER_TRUE_FALSE, self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE, self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY], true);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function getTrueFalseScores(CQuizQuestion $question): array
    {
        $scores = explode(':', (string) $question->getExtra());

        return [
            (float) ($scores[0] ?? 1.0),
            (float) ($scores[1] ?? -0.5),
            (float) ($scores[2] ?? 0.0),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function getQuestionOptionPositionsByIid(CQuizQuestion $question): array
    {
        $items = [];
        foreach ($question->getOptions() as $option) {
            if (!$option instanceof CQuizQuestionOption || null === $option->getIid()) {
                continue;
            }

            $items[(int) $option->getIid()] = (int) $option->getPosition();
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $answers
     */
    private function hasNoNegativeScore(array $answers): bool
    {
        foreach ($answers as $answer) {
            if (true === ($answer['correct'] ?? false)) {
                continue;
            }

            if (0.0 === (float) ($answer['score'] ?? 0.0)) {
                return true;
            }
        }

        return false;
    }

    private function isQuestionFeedbackEnabled(): bool
    {
        $value = $this->settingsManager->getSetting('exercise.allow_quiz_question_feedback', true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function isImageZoomEnabled(): bool
    {
        $value = $this->settingsManager->getSetting('exercise.quiz_image_zoom', true);
        if (\is_array($value)) {
            return isset($value['options']) || true === ($value['value'] ?? false) || '1' === (string) ($value['value'] ?? '');
        }

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }


    private function addHotspotData(ExerciseQuestionEditor $response, CQuiz $quiz, CQuizQuestion $question, Course $course, ?Session $session): void
    {
        if (!$this->usesHotspot((int) $question->getType())) {
            return;
        }

        $resourceNode = $question->getResourceNode();
        if (null !== $resourceNode) {
            $resourceFile = $resourceNode->getResourceFiles()->first();
            if ($resourceFile instanceof ResourceFile) {
                $response->hotspotImageName = (string) $resourceFile->getOriginalName();
                $response->hotspotImageUrl = $this->appendCourseContextToUrl(
                    $this->questionRepository->getHotSpotImageUrl($question),
                    $course,
                    $session
                );
            }
        }

        $response->hotspotItems = $this->getHotspotItems($question);
        if (self::HOT_SPOT_DELINEATION === (int) $question->getType()) {
            $this->addHotspotScenarioOptions($response, $quiz, $question);
        }
        $response->answers = [];
    }

    private function addHotspotScenarioOptions(ExerciseQuestionEditor $response, CQuiz $quiz, ?CQuizQuestion $question): void
    {
        $response->hotspotScenarioOptions = [
            ['label' => 'Select destination', 'value' => ''],
            ['label' => 'Repeat question', 'value' => 'repeat'],
            ['label' => 'End of test', 'value' => '-1'],
            ['label' => 'Other (custom URL)', 'value' => 'url'],
        ];

        foreach ($this->getScenarioQuestionOptions($quiz) as $option) {
            $response->hotspotScenarioOptions[] = $option;
        }

        if (null === $question) {
            return;
        }

        $relation = $this->entityManager->getRepository(CQuizRelQuestion::class)->findOneBy([
            'quiz' => $quiz,
            'question' => $question,
        ]);

        if (!$relation instanceof CQuizRelQuestion || '' === (string) $relation->getDestination()) {
            return;
        }

        $destination = json_decode((string) $relation->getDestination(), true);
        if (!\is_array($destination)) {
            return;
        }

        $success = \is_array($destination['success'] ?? null) ? $destination['success'] : [];
        $failure = \is_array($destination['failure'] ?? null) ? $destination['failure'] : [];

        $response->hotspotScenarioSuccessType = (string) ($success['type'] ?? '');
        $response->hotspotScenarioSuccessUrl = (string) ($success['url'] ?? '');
        $response->hotspotScenarioFailureType = (string) ($failure['type'] ?? '');
        $response->hotspotScenarioFailureUrl = (string) ($failure['url'] ?? '');
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function getScenarioQuestionOptions(CQuiz $quiz): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation', 'question')
            ->from(CQuizRelQuestion::class, 'relation')
            ->innerJoin('relation.question', 'question')
            ->andWhere('IDENTITY(relation.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relation.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $options = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (null === $question->getIid()) {
                continue;
            }

            $options[] = [
                'label' => 'Q'.(int) $relation->getQuestionOrder().': '.$this->plainText($question->getQuestion()),
                'value' => (string) $question->getIid(),
            ];
        }

        return $options;
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getHotspotItems(CQuizQuestion $question): array
    {
        $answers = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($answers as $answer) {
            if (!$answer instanceof CQuizAnswer) {
                continue;
            }

            if ('noerror' === (string) $answer->getHotspotType()) {
                continue;
            }

            $items[] = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'comment' => (string) $answer->getComment(),
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
                'hotspotType' => (string) ($answer->getHotspotType() ?: 'square'),
                'coordinates' => (string) ($answer->getHotspotCoordinates() ?: '0;0|0|0'),
            ];
        }

        return [] !== $items ? $items : $this->getDefaultHotspotItems((int) $question->getType());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultHotspotItems(int $type): array
    {
        if (self::HOT_SPOT_DELINEATION === $type) {
            return [
                [
                    'id' => null,
                    'answer' => 'delineation',
                    'comment' => '',
                    'score' => 10.0,
                    'position' => 1,
                    'hotspotType' => 'delineation',
                    'coordinates' => '0;0|0|0',
                ],
            ];
        }

        return [
            [
                'id' => null,
                'answer' => '',
                'comment' => '',
                'score' => self::HOT_SPOT_COMBINATION === $type ? 0.0 : 10.0,
                'position' => 1,
                'hotspotType' => 'square',
                'coordinates' => '0;0|0|0',
            ],
        ];
    }

    private function usesHotspot(int $type): bool
    {
        return \in_array($type, [self::HOT_SPOT, self::HOT_SPOT_DELINEATION, self::HOT_SPOT_COMBINATION], true);
    }

    private function addAnnotationData(ExerciseQuestionEditor $response, CQuizQuestion $question, Course $course, ?Session $session): void
    {
        if (self::ANNOTATION !== (int) $question->getType()) {
            return;
        }

        $resourceNode = $question->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $resourceFile = $resourceNode->getResourceFiles()->first();
        if (!$resourceFile instanceof ResourceFile) {
            return;
        }

        $response->annotationImageName = (string) $resourceFile->getOriginalName();
        $response->annotationImageUrl = $this->appendCourseContextToUrl(
            $this->questionRepository->getHotSpotImageUrl($question),
            $course,
            $session
        );
    }

    private function appendCourseContextToUrl(string $url, Course $course, ?Session $session): string
    {
        if ('' === $url) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();
        $params = [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? $request?->query->getInt('sid', 0) ?? 0),
            'gid' => (int) ($request?->query->getInt('gid', 0) ?? 0),
        ];

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($params);
    }

    private function addMatchingData(ExerciseQuestionEditor $response, CQuizQuestion $question): void
    {
        $type = (int) $question->getType();
        if (!$this->usesMatching($type)) {
            return;
        }

        $data = $this->buildMatchingData($question);
        $response->matchingOptions = $data['options'];
        $response->matchingPairs = $data['pairs'];
        $response->answers = [];
        $response->score = (float) $question->getPonderation();
    }

    /**
     * @return array{options: array<int, array<string, mixed>>, pairs: array<int, array<string, mixed>>}
     */
    private function buildMatchingData(CQuizQuestion $question): array
    {
        $answers = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $options = [];
        $optionsByIid = [];
        $pairs = [];

        foreach ($answers as $answer) {
            if (!$answer instanceof CQuizAnswer) {
                continue;
            }

            $correct = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $correct) {
                $option = [
                    'id' => (int) $answer->getIid(),
                    'localId' => 'option-'.(int) $answer->getPosition(),
                    'label' => $this->getMatchingOptionLabel(\count($options) + 1),
                    'answer' => $answer->getAnswer(),
                    'position' => (int) $answer->getPosition(),
                ];
                $options[] = $option;
                $optionsByIid[(int) $answer->getIid()] = $option;

                continue;
            }

            $pairs[] = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'optionId' => $correct,
                'optionLocalId' => '',
                'comment' => (string) $answer->getComment(),
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        foreach ($pairs as &$pair) {
            $optionId = (int) ($pair['optionId'] ?? 0);
            if (isset($optionsByIid[$optionId])) {
                $pair['optionLocalId'] = (string) $optionsByIid[$optionId]['localId'];
            }
        }
        unset($pair);

        if (empty($options)) {
            $options = $this->getDefaultMatchingOptions();
        }

        if (empty($pairs)) {
            $pairs = $this->getDefaultMatchingPairs($type);
        }

        return ['options' => $options, 'pairs' => $pairs];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultMatchingOptions(): array
    {
        return [
            ['id' => null, 'localId' => 'option-1', 'label' => 'A', 'answer' => '', 'position' => 1],
            ['id' => null, 'localId' => 'option-2', 'label' => 'B', 'answer' => '', 'position' => 2],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultMatchingPairs(int $type = self::MATCHING): array
    {
        $score = \in_array($type, [self::MATCHING_COMBINATION, self::MATCHING_DRAGGABLE_COMBINATION], true) ? 0.0 : 10.0;

        return [
            ['id' => null, 'answer' => '', 'optionId' => null, 'optionLocalId' => 'option-1', 'comment' => '', 'score' => $score, 'position' => 3],
            ['id' => null, 'answer' => '', 'optionId' => null, 'optionLocalId' => 'option-2', 'comment' => '', 'score' => $score, 'position' => 4],
        ];
    }

    private function getMatchingOptionLabel(int $position): string
    {
        if (1 <= $position && 26 >= $position) {
            return chr(64 + $position);
        }

        return (string) $position;
    }


    private function addDraggableData(ExerciseQuestionEditor $response, CQuizQuestion $question): void
    {
        if (!$this->usesDraggableOrdering((int) $question->getType())) {
            return;
        }

        $response->draggableItems = $this->buildDraggableItems($question);
        $response->matchingOrientation = \in_array((string) $question->getExtra(), ['h', 'v'], true) ? (string) $question->getExtra() : 'h';
        $response->answers = [];
        $response->score = (float) $question->getPonderation();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDraggableItems(CQuizQuestion $question): array
    {
        $items = [];
        foreach ($this->getExistingAnswers($question) as $answer) {
            $correct = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $correct) {
                continue;
            }

            $items[] = [
                'id' => (int) $answer->getIid(),
                'localId' => 'draggable-'.(int) $answer->getPosition(),
                'answer' => $answer->getAnswer(),
                'targetPosition' => $correct,
                'score' => (float) $answer->getPonderation(),
                'position' => \count($items) + 1,
            ];
        }

        return [] !== $items ? $items : $this->getDefaultDraggableItems();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultDraggableItems(): array
    {
        return [
            ['id' => null, 'localId' => 'draggable-1', 'answer' => '', 'targetPosition' => 1, 'score' => 10.0, 'position' => 1],
            ['id' => null, 'localId' => 'draggable-2', 'answer' => '', 'targetPosition' => 2, 'score' => 10.0, 'position' => 2],
        ];
    }

    private function addCalculatedData(ExerciseQuestionEditor $response, CQuizQuestion $question): void
    {
        if (self::CALCULATED_ANSWER !== (int) $question->getType()) {
            return;
        }

        $answers = $this->getExistingAnswers($question);
        $firstAnswer = $answers[0] ?? null;
        if (!$firstAnswer instanceof CQuizAnswer) {
            $response->calculatedText = $this->getDefaultCalculatedText();
            $response->calculatedFormula = '';
            $response->calculatedRanges = $this->buildCalculatedRanges($response->calculatedText, []);
            $response->calculatedVariations = 1;
            $response->calculatedComment = '';
            $response->answers = [];

            return;
        }

        $parsed = $this->parseCalculatedAnswer((string) $firstAnswer->getAnswer());
        $response->calculatedText = $parsed['text'];
        $response->calculatedFormula = $parsed['formula'];
        $response->calculatedRanges = $this->buildCalculatedRanges($parsed['text'], []);
        $response->calculatedVariations = max(1, \count($answers));
        $response->calculatedComment = (string) $firstAnswer->getComment();
        $response->score = (float) $question->getPonderation();
        $response->answers = [];
    }

    /**
     * @return array{text: string, formula: string}
     */
    private function parseCalculatedAnswer(string $encodedAnswer): array
    {
        $parts = explode('@@', $encodedAnswer);
        $formula = \count($parts) > 1 ? (string) array_pop($parts) : '';
        $text = (string) array_shift($parts);
        $text = preg_replace('/\[[^\]]*\]/', '[]', $text) ?? $text;

        return [
            'text' => $text,
            'formula' => $formula,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $submittedRanges
     *
     * @return array<int, array{token: string, low: string, high: string, random: string, position: int}>
     */
    private function buildCalculatedRanges(string $text, array $submittedRanges): array
    {
        $previousByToken = [];
        foreach ($submittedRanges as $range) {
            if (!\is_array($range)) {
                continue;
            }

            $token = (string) ($range['token'] ?? '');
            if ('' === $token) {
                continue;
            }

            $previousByToken[$token] = $range;
        }

        $ranges = [];
        foreach ($this->extractCalculatedTokens($text) as $index => $token) {
            $previous = $previousByToken[$token] ?? [];
            $low = (string) ($previous['low'] ?? '1');
            $high = (string) ($previous['high'] ?? '20');

            $ranges[] = [
                'token' => $token,
                'low' => $low,
                'high' => $high,
                'random' => $this->buildCalculatedRandomPreview($low, $high),
                'position' => $index + 1,
            ];
        }

        return $ranges;
    }

    /**
     * @return array<int, string>
     */
    private function extractCalculatedTokens(string $text): array
    {
        preg_match_all('/\[[^\]]+\]/', $text, $matches);
        $tokens = [];
        foreach ($matches[0] ?? [] as $token) {
            $token = trim((string) $token);
            if ('' !== $token && !\in_array($token, $tokens, true)) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    private function buildCalculatedRandomPreview(string $low, string $high): string
    {
        $minimum = (float) $low;
        $maximum = (float) $high;
        if ($maximum < $minimum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        $hasDecimal = str_contains($low, '.') || str_contains($high, '.');
        $value = random_int((int) round($minimum * 100), (int) round($maximum * 100)) / 100;

        if (!$hasDecimal) {
            return (string) random_int((int) $minimum, (int) $maximum);
        }

        return number_format($value, 2, '.', '');
    }

    private function getDefaultCalculatedText(): string
    {
        return '<p>Calculate the Body Mass Index for a person with weight [95] Kg and height [1.81] m.</p><p>Body Mass Index: []</p>';
    }

    private function addFillBlanksData(ExerciseQuestionEditor $response, CQuizQuestion $question): void
    {
        $type = (int) $question->getType();
        if (!$this->usesFillBlanks($type)) {
            return;
        }

        $answer = $this->getFirstAnswer($question);
        $parsed = $this->parseFillBlanksAnswer($answer?->getAnswer() ?? '');

        $response->fillBlanksText = $parsed['text'];
        $response->fillBlankItems = $this->buildFillBlankItems(
            $parsed['text'],
            $parsed['weights'],
            $parsed['sizes'],
            $parsed['separator']
        );
        $response->fillBlanksSeparator = $parsed['separator'];
        $response->fillBlanksSwitchable = $parsed['switchable'];
        $response->fillBlanksCaseInsensitive = 'case:false' === (string) $question->getExtra();
        $response->fillBlanksComment = (string) ($answer?->getComment() ?? '');
        if (self::FILL_IN_BLANKS_COMBINATION === $type) {
            $response->globalScore = (float) $question->getPonderation();
            $response->score = 0.0;
        } else {
            $response->score = (float) $question->getPonderation();
        }
    }

    private function getFirstAnswer(CQuizQuestion $question): ?CQuizAnswer
    {
        $answer = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $answer instanceof CQuizAnswer ? $answer : null;
    }

    /**
     * @return array{text: string, weights: array<int, float>, sizes: array<int, int>, separator: int, switchable: bool}
     */
    private function parseFillBlanksAnswer(string $encodedAnswer): array
    {
        $parts = explode('::', $encodedAnswer, 2);
        $text = (string) ($parts[0] ?? '');
        $systemString = (string) ($parts[1] ?? '');
        $switchableParts = explode('@', $systemString, 2);
        $details = explode(':', (string) ($switchableParts[0] ?? ''));

        $weights = $this->parseFloatList((string) ($details[0] ?? ''));
        $sizes = [];
        $separator = 0;

        if (\count($details) >= 3) {
            $sizes = $this->parseIntegerList((string) ($details[1] ?? ''));
            $separator = max(0, (int) ($details[2] ?? 0));
        } else {
            foreach ($weights as $_weight) {
                $sizes[] = 200;
            }
        }

        return [
            'text' => $text,
            'weights' => $weights,
            'sizes' => $sizes,
            'separator' => $separator,
            'switchable' => '1' === (string) ($switchableParts[1] ?? ''),
        ];
    }

    /**
     * @return array<int, array{answer: string, score: float, inputSize: int, position: int}>
     */
    private function buildFillBlankItems(string $text, array $weights, array $sizes, int $separator): array
    {
        $blanks = $this->extractFillBlankAnswers($text, $separator);
        $items = [];
        foreach ($blanks as $index => $blank) {
            $items[] = [
                'answer' => $blank,
                'score' => (float) ($weights[$index] ?? 1.0),
                'inputSize' => (int) ($sizes[$index] ?? 200),
                'position' => $index + 1,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function extractFillBlankAnswers(string $text, int $separator): array
    {
        [$start, $end] = $this->getFillBlankSeparators($separator);
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
        preg_match_all($pattern, $text, $matches);

        return array_map(
            static fn (string $value): string => trim($value),
            $matches[1] ?? []
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getFillBlankSeparators(int $separator): array
    {
        return match ($separator) {
            1 => ['{', '}'],
            2 => ['(', ')'],
            3 => ['*', '*'],
            4 => ['#', '#'],
            5 => ['%', '%'],
            6 => ['$', '$'],
            default => ['[', ']'],
        };
    }

    /**
     * @return array<int, float>
     */
    private function parseFloatList(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_map(
            static fn (string $item): float => (float) trim($item),
            explode(',', $value)
        );
    }

    /**
     * @return array<int, int>
     */
    private function parseIntegerList(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_map(
            static fn (string $item): int => max(40, (int) trim($item)),
            explode(',', $value)
        );
    }

    private function usesDraggableOrdering(int $type): bool
    {
        return self::DRAGGABLE === $type;
    }

    private function isStructuralQuestionType(int $type): bool
    {
        return \in_array($type, [self::MEDIA_QUESTION, self::READING_COMPREHENSION, self::PAGE_BREAK], true);
    }

    private function usesMatching(int $type): bool
    {
        return \in_array($type, [self::MATCHING, self::MATCHING_COMBINATION, self::MATCHING_DRAGGABLE, self::MATCHING_DRAGGABLE_COMBINATION], true);
    }

    private function usesFillBlanks(int $type): bool
    {
        return \in_array($type, [self::FILL_IN_BLANKS, self::FILL_IN_BLANKS_COMBINATION], true);
    }

    /**
     * @return array<string, string>
     */
    private function getLegacyUrls(CQuiz $quiz, Course $course, ?Session $session): array
    {
        $baseParams = [
            'exerciseId' => (int) $quiz->getIid(),
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
        ];

        return [
            'questions' => '/main/exercise/admin.php?'.http_build_query($baseParams),
            'questionPool' => '/main/exercise/question_pool.php?'.http_build_query($baseParams),
        ];
    }
}

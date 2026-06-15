<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionEditor;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLpItem;
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
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Webit\Util\EvalMath\EvalMath;

/**
 * @implements ProcessorInterface<ExerciseQuestionEditor, ExerciseQuestionEditor>
 */
final readonly class ExerciseQuestionEditorProcessor implements ProcessorInterface
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
    private const LP_ITEM_TYPE_QUIZ = 'quiz';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private CQuizQuestionRepository $questionRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionEditor
    {
        if (!$data instanceof ExerciseQuestionEditor) {
            throw new BadRequestHttpException('Invalid exercise question payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercise questions in this context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $quiz = 0 < $exerciseId ? $this->getExerciseFromCurrentContext($exerciseId, $course, $session) : null;
        if ($quiz instanceof CQuiz && $this->isExerciseReadOnlyFromLearningPath((int) $quiz->getIid())) {
            throw new AccessDeniedHttpException('This exercise is read-only because it is included in a learning path.');
        }

        $questionId = isset($uriVariables['questionId']) ? (int) $uriVariables['questionId'] : (int) ($data->questionId ?? 0);
        $this->validatePayload($data);
        $this->validateAnnotationImageOnCreate($data, 0 >= $questionId);
        $this->validateHotspotImageOnCreate($data, 0 >= $questionId);

        if (0 < $questionId) {
            $question = $quiz instanceof CQuiz
                ? $this->updateQuestion($quiz, $questionId, $data, $course, $session)
                : $this->updateGlobalQuestion($questionId, $data, $course, $session);
        } elseif ($quiz instanceof CQuiz) {
            $question = $this->createQuestion($quiz, $data, $course, $session);
        } else {
            $question = $this->createGlobalQuestion($data, $course, $session);
        }

        $this->entityManager->flush();
        if ($quiz instanceof CQuiz) {
            $this->syncQuestionCategoryMandatory($quiz, $question, $data);

            return $this->buildResponse($quiz, $question, $course, $session);
        }

        return $this->buildGlobalResponse($question, $course, $session);
    }

    private function isExerciseReadOnlyFromLearningPath(int $exerciseId): bool
    {
        if ($this->isSettingEnabled('lp.force_edit_exercise_in_lp')) {
            return false;
        }

        return null !== $this->entityManager->createQueryBuilder()
            ->select('lpItem.iid')
            ->from(CLpItem::class, 'lpItem')
            ->andWhere('lpItem.itemType = :itemType')
            ->andWhere('lpItem.path = :exerciseId OR lpItem.ref = :exerciseId')
            ->setParameter('itemType', self::LP_ITEM_TYPE_QUIZ, Types::STRING)
            ->setParameter('exerciseId', (string) $exerciseId, Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function isSettingEnabled(string $settingName): bool
    {
        $value = $this->settingsManager->getSetting($settingName, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function createQuestion(CQuiz $quiz, ExerciseQuestionEditor $data, Course $course, ?Session $session): CQuizQuestion
    {
        $question = new CQuizQuestion();
        $this->applyQuestionFields($quiz, $question, $data, $this->getNextQuestionOrder($quiz));
        $question
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->questionRepository->create($question);
        $this->addAnnotationImageOnCreate($question, $data);
        $this->addHotspotImageOnCreate($question, $data);
        $this->replaceAnswers($question, $data);
        $this->updateQuestionCategory($question, $data, $course, $session);

        $relation = new CQuizRelQuestion();
        $relation
            ->setQuiz($quiz)
            ->setQuestion($question)
            ->setQuestionOrder($this->getNextQuestionOrder($quiz))
        ;
        $this->applyHotspotDelineationDestination($relation, $data);
        $this->entityManager->persist($relation);

        return $question;
    }

    private function createGlobalQuestion(ExerciseQuestionEditor $data, Course $course, ?Session $session): CQuizQuestion
    {
        $question = new CQuizQuestion();
        $this->applyQuestionFields(null, $question, $data, 0);
        $question
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->questionRepository->create($question);
        $this->addAnnotationImageOnCreate($question, $data);
        $this->addHotspotImageOnCreate($question, $data);
        $this->replaceAnswers($question, $data);
        $this->updateQuestionCategory($question, $data, $course, $session);

        return $question;
    }

    private function updateGlobalQuestion(int $questionId, ExerciseQuestionEditor $data, Course $course, ?Session $session): CQuizQuestion
    {
        $question = $this->getQuestionFromCurrentContext($questionId, $course, $session);
        $this->applyQuestionFields(null, $question, $data, (int) $question->getPosition());
        $this->replaceAnswers($question, $data);
        $this->updateQuestionCategory($question, $data, $course, $session);
        $this->entityManager->persist($question);

        return $question;
    }

    private function updateQuestion(CQuiz $quiz, int $questionId, ExerciseQuestionEditor $data, Course $course, ?Session $session): CQuizQuestion
    {
        $question = $this->getQuestionFromExercise($quiz, $questionId);
        $this->applyQuestionFields($quiz, $question, $data, (int) $question->getPosition());
        $this->replaceAnswers($question, $data);
        $this->updateQuestionCategory($question, $data, $course, $session);
        $relation = $this->getQuestionRelation($quiz, $question);
        if ($relation instanceof CQuizRelQuestion) {
            $this->applyHotspotDelineationDestination($relation, $data);
        }
        $this->entityManager->persist($question);

        return $question;
    }

    private function applyQuestionFields(?CQuiz $quiz, CQuizQuestion $question, ExerciseQuestionEditor $data, int $position): void
    {
        $type = (int) $data->type;
        if (!$this->isVueSupportedQuestionType($type)) {
            throw new BadRequestHttpException('This question type is still managed by the legacy exercise tool.');
        }

        $score = $this->calculateQuestionScore($data);
        $question
            ->setQuestion(trim($data->title))
            ->setDescription((string) $data->description)
            ->setFeedback($this->isQuestionFeedbackEnabled() ? (string) $data->feedback : $question->getFeedback())
            ->setExtra($this->buildQuestionExtra($type, $data, $question))
            ->setType($type)
            ->setLevel($this->normalizeDifficulty($data->difficulty))
            ->setPosition($position)
            ->setPonderation($score)
            ->setMandatory($this->isStructuralQuestionType($type) || null === $quiz ? 0 : ($this->isMandatoryQuestionInCategoryEnabled($quiz) && $data->mandatory ? 1 : 0))
            ->setDuration($this->isStructuralQuestionType($type) ? null : $this->normalizeNullablePositiveInteger($data->duration))
            ->setParentMediaId($this->isStructuralQuestionType($type) || null === $quiz ? null : $this->normalizeParentMediaId($quiz, $data, $question))
        ;
    }

    private function replaceAnswers(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        foreach ($this->getExistingAnswers($question) as $answer) {
            $this->entityManager->remove($answer);
        }

        $type = (int) $data->type;
        if (\in_array($type, [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::ANNOTATION, self::UPLOAD_ANSWER], true) || $this->isStructuralQuestionType($type)) {
            return;
        }

        if ($this->usesHotspot($type)) {
            $this->persistHotspotAnswers($question, $data);

            return;
        }

        if ($this->usesFillBlanks($type)) {
            $this->persistFillBlanksAnswer($question, $data);

            return;
        }

        if (self::CALCULATED_ANSWER === $type) {
            $this->persistCalculatedAnswers($question, $data);

            return;
        }

        if ($this->usesDraggableOrdering($type)) {
            $this->persistDraggableAnswers($question, $data);

            return;
        }

        if ($this->usesMatching($type)) {
            $this->persistMatchingAnswers($question, $data);

            return;
        }

        $optionIidsByPosition = $this->usesTrueFalseOptions($type)
            ? $this->ensureTrueFalseOptions($question, $type)
            : [];

        $answers = $this->normalizeAnswersForType($data, $optionIidsByPosition);
        $position = 1;
        foreach ($answers as $answerData) {
            $answerPosition = true === ($answerData['isUnknown'] ?? false) ? self::UNKNOWN_ANSWER_POSITION : $position;
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) $answerData['answer'])
                ->setCorrect((int) $answerData['correct'])
                ->setComment((string) $answerData['comment'])
                ->setPonderation((float) $answerData['score'])
                ->setPosition($answerPosition)
            ;
            $this->entityManager->persist($answer);

            if (self::UNKNOWN_ANSWER_POSITION !== $answerPosition) {
                ++$position;
            }
        }
    }

    private function validatePayload(ExerciseQuestionEditor $data): void
    {
        if ('' === trim(strip_tags($data->title))) {
            throw new BadRequestHttpException('The question title is required.');
        }

        if (!$this->isVueSupportedQuestionType((int) $data->type)) {
            throw new BadRequestHttpException('This question type is still managed by the legacy exercise tool.');
        }

        $type = (int) $data->type;
        if ($this->usesHotspot($type)) {
            $this->validateHotspotPayload($data);

            return;
        }

        if (\in_array($type, [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::ANNOTATION, self::UPLOAD_ANSWER], true)) {
            if (0 >= (float) $data->score) {
                throw new BadRequestHttpException('Required field.');
            }

            return;
        }

        if (self::CALCULATED_ANSWER === $type) {
            $this->validateCalculatedPayload($data);

            return;
        }

        if ($this->isStructuralQuestionType($type)) {
            return;
        }

        if ($this->usesFillBlanks($type)) {
            $this->validateFillBlanksPayload($data);

            return;
        }

        if ($this->usesDraggableOrdering($type)) {
            $this->validateDraggablePayload($data);

            return;
        }

        if ($this->usesMatching($type)) {
            $this->validateMatchingPayload($data);

            return;
        }

        $answers = $this->getCleanAnswers($data);
        $regularAnswers = array_filter($answers, static fn (array $answer): bool => true !== ($answer['isUnknown'] ?? false));
        if (2 > \count($regularAnswers)) {
            throw new BadRequestHttpException('At least two answers are required.');
        }

        if ($this->usesTrueFalseOptions($type)) {
            foreach ($regularAnswers as $answer) {
                if (!\in_array((int) ($answer['correctChoice'] ?? 0), [1, 2], true)) {
                    throw new BadRequestHttpException('Each true/false answer must have True or False selected.');
                }
            }
        } else {
            $correctAnswers = array_filter($regularAnswers, static fn (array $answer): bool => true === $answer['correct']);
            if ($this->usesSingleCorrectAnswer($type) && 1 !== \count($correctAnswers)) {
                throw new BadRequestHttpException('A unique answer question must have exactly one correct answer.');
            }

            if (!$this->usesSingleCorrectAnswer($type) && 0 === \count($correctAnswers)) {
                throw new BadRequestHttpException('At least one correct answer is required.');
            }
        }

        if (self::MULTIPLE_ANSWER_DROPDOWN === $type) {
            $dropdownScore = 0.0;
            foreach ($regularAnswers as $answer) {
                if (true === ($answer['correct'] ?? false)) {
                    $dropdownScore += max(0.0, (float) ($answer['score'] ?? 0.0));
                }
            }

            if (0 >= $dropdownScore) {
                throw new BadRequestHttpException('Required field.');
            }
        }

        if ($this->usesGlobalScore($type) && 0 >= (float) $data->globalScore) {
            throw new BadRequestHttpException('Required field.');
        }

        if ($this->usesTrueFalseScoreOptions($type) && 0 >= (float) $data->correctScore) {
            throw new BadRequestHttpException('Required field.');
        }
    }

    private function validateAnnotationImageOnCreate(ExerciseQuestionEditor $data, bool $isCreate): void
    {
        if (self::ANNOTATION !== (int) $data->type || !$isCreate) {
            return;
        }

        if ('' === trim($data->annotationImageData)) {
            throw new BadRequestHttpException('Please select an image');
        }

        $this->decodeAnnotationImage($data);
    }

    private function addAnnotationImageOnCreate(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        if (self::ANNOTATION !== (int) $data->type) {
            return;
        }

        $image = $this->decodeAnnotationImage($data);
        $this->questionRepository->addFileFromString(
            $question,
            $image['fileName'],
            $image['mimeType'],
            $image['content'],
            true
        );
    }

    private function validateHotspotImageOnCreate(ExerciseQuestionEditor $data, bool $isCreate): void
    {
        if (!$this->usesHotspot((int) $data->type) || !$isCreate) {
            return;
        }

        if ('' === trim($data->hotspotImageData)) {
            throw new BadRequestHttpException('Please select an image');
        }

        $this->decodeHotspotImage($data);
    }

    private function addHotspotImageOnCreate(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        if (!$this->usesHotspot((int) $data->type)) {
            return;
        }

        $image = $this->decodeHotspotImage($data);
        $this->questionRepository->addFileFromString(
            $question,
            $image['fileName'],
            $image['mimeType'],
            $image['content'],
            true
        );
    }

    /**
     * @return array{fileName: string, mimeType: string, content: string}
     */
    private function decodeAnnotationImage(ExerciseQuestionEditor $data): array
    {
        return $this->decodeQuestionImage(
            $data->annotationImageData,
            $data->annotationImageMimeType,
            $data->annotationImageName,
            'annotation_image'
        );
    }

    /**
     * @return array{fileName: string, mimeType: string, content: string}
     */
    private function decodeHotspotImage(ExerciseQuestionEditor $data): array
    {
        return $this->decodeQuestionImage(
            $data->hotspotImageData,
            $data->hotspotImageMimeType,
            $data->hotspotImageName,
            'hotspot_image'
        );
    }

    /**
     * @return array{fileName: string, mimeType: string, content: string}
     */
    private function decodeQuestionImage(string $rawData, string $submittedMimeType, string $submittedFileName, string $defaultBaseName): array
    {
        $rawData = trim($rawData);
        $mimeType = strtolower(trim($submittedMimeType));
        $encodedContent = $rawData;

        if (preg_match('/^data:(image\/(?:jpeg|png|gif));base64,(.+)$/i', $rawData, $matches)) {
            $mimeType = strtolower($matches[1]);
            $encodedContent = $matches[2];
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!\in_array($mimeType, $allowedMimeTypes, true)) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images allowed');
        }

        $content = base64_decode($encodedContent, true);
        if (false === $content || '' === $content) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images allowed');
        }

        if (false === getimagesizefromstring($content)) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images allowed');
        }

        $fileName = basename(trim($submittedFileName));
        if ('' === $fileName) {
            $fileName = $defaultBaseName.'.'.$this->getExtensionFromMimeType($mimeType);
        }

        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        if ('jpg' === $extension) {
            $extension = 'jpeg';
        }

        if (!\in_array($extension, ['jpeg', 'png', 'gif'], true)) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images allowed');
        }

        return [
            'fileName' => $fileName,
            'mimeType' => $mimeType,
            'content' => $content,
        ];
    }

    private function getExtensionFromMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            default => 'png',
        };
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
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
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

    private function getQuestionFromCurrentContext(int $questionId, Course $course, ?Session $session): CQuizQuestion
    {
        $question = $this->entityManager->getRepository(CQuizQuestion::class)->find($questionId);
        if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
            throw new NotFoundHttpException('The requested question was not found.');
        }

        if ($this->isQuestionInContext($question, $course, $session)) {
            return $question;
        }

        throw new AccessDeniedHttpException('The requested question does not belong to the current course context.');
    }

    private function isQuestionInContext(CQuizQuestion $question, Course $course, ?Session $session): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('question.iid')
            ->from(CQuizQuestion::class, 'question')
            ->leftJoin('question.resourceNode', 'questionNode')
            ->andWhere('question = :question')
            ->setParameter('question', $question)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        $existsQuestionLink = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(ResourceLink::class, 'questionLink')
            ->where('questionLink.resourceNode = questionNode')
            ->andWhere('IDENTITY(questionLink.course) = :courseId')
        ;
        $this->applyActiveLinkConstraints($existsQuestionLink, 'questionLink', $session, true);

        $existsViaQuiz = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(CQuizRelQuestion::class, 'scopeRelation')
            ->innerJoin('scopeRelation.quiz', 'scopeQuiz')
            ->innerJoin('scopeQuiz.resourceNode', 'scopeNode')
            ->innerJoin('scopeNode.resourceLinks', 'scopeLinks')
            ->where('scopeRelation.question = question')
            ->andWhere('IDENTITY(scopeLinks.course) = :courseId')
        ;
        $this->applyActiveLinkConstraints($existsViaQuiz, 'scopeLinks', $session, true);

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->exists($existsQuestionLink->getDQL()),
                $queryBuilder->expr()->exists($existsViaQuiz->getDQL())
            )
        );

        if (null !== $session) {
            $queryBuilder->setParameter('sessionId', (int) $session->getId(), Types::INTEGER);
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
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

    private function hasAnswerContent(string $answer): bool
    {
        if ('' === trim($answer)) {
            return false;
        }

        if (1 === preg_match('/<img\b[^>]*>/i', $answer)) {
            return true;
        }

        return '' !== trim(strip_tags(html_entity_decode($answer, ENT_QUOTES, 'UTF-8')));
    }

    /**
     * @return array<int, array{answer: string, correct: bool, comment: string, score: float, position: int, isUnknown: bool}>
     */
    private function getCleanAnswers(ExerciseQuestionEditor $data): array
    {
        $answers = [];
        foreach ($data->answers as $answer) {
            if (!\is_array($answer)) {
                continue;
            }

            $position = (int) ($answer['position'] ?? 0);
            $isUnknown = true === ($answer['isUnknown'] ?? false) || self::UNKNOWN_ANSWER_POSITION === $position;
            $answerText = trim((string) ($answer['answer'] ?? ''));
            if (!$this->hasAnswerContent($answerText) && !$isUnknown) {
                continue;
            }

            if ($isUnknown && '' === $answerText) {
                $answerText = 'Don\'t know';
            }

            $score = (float) ($answer['score'] ?? 0.0);
            $answers[] = [
                'answer' => $answerText,
                'correct' => !$isUnknown && true === ($answer['correct'] ?? false),
                'correctChoice' => (int) ($answer['correctChoice'] ?? 0),
                'comment' => (string) ($answer['comment'] ?? ''),
                'score' => $isUnknown ? 0.0 : $score,
                'position' => $isUnknown ? self::UNKNOWN_ANSWER_POSITION : $position,
                'isUnknown' => $isUnknown,
            ];
        }

        return $answers;
    }

    private function updateQuestionCategory(
        CQuizQuestion $question,
        ExerciseQuestionEditor $data,
        ?Course $course,
        ?Session $session,
    ): void {
        $categories = $question->getCategories();
        foreach ($categories as $category) {
            if ($category instanceof CQuizQuestionCategory) {
                $question->removeCategory($category);
            }
        }

        $categoryId = (int) $data->categoryId;
        if (0 >= $categoryId) {
            return;
        }

        $category = $this->getQuestionCategory($categoryId, $course, $session);
        $question->addCategory($category);
    }

    private function syncQuestionCategoryMandatory(CQuiz $quiz, CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        $questionId = (int) ($question->getIid() ?? 0);
        if (0 >= $questionId || !$this->hasMandatoryQuestionCategoryColumn()) {
            return;
        }

        $mandatory = $this->isMandatoryQuestionInCategoryEnabled($quiz) && $data->mandatory ? 1 : 0;
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE c_quiz_question_rel_category SET mandatory = :mandatory WHERE question_id = :questionId',
            [
                'mandatory' => $mandatory,
                'questionId' => $questionId,
            ],
            [
                'mandatory' => Types::INTEGER,
                'questionId' => Types::INTEGER,
            ]
        );
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

    private function getQuestionCategory(int $categoryId, ?Course $course, ?Session $session): CQuizQuestionCategory
    {
        $category = $this->entityManager->getRepository(CQuizQuestionCategory::class)->find($categoryId);
        if (!$category instanceof CQuizQuestionCategory) {
            throw new BadRequestHttpException('The requested question category was not found.');
        }

        if (null === $course) {
            return $category;
        }

        if ($this->isQuestionCategoryInContext($categoryId, $course, $session)) {
            return $category;
        }

        throw new AccessDeniedHttpException('The requested question category does not belong to the current course context.');
    }

    private function isQuestionCategoryInContext(int $categoryId, Course $course, ?Session $session): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category.iid')
            ->from(CQuizQuestionCategory::class, 'category')
            ->innerJoin('category.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('category.iid = :categoryId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('categoryId', $categoryId, Types::INTEGER)
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

    private function normalizeDifficulty(int $difficulty): int
    {
        if (1 > $difficulty) {
            return 1;
        }

        if (5 < $difficulty) {
            return 5;
        }

        return $difficulty;
    }

    private function normalizeParentMediaId(CQuiz $quiz, ExerciseQuestionEditor $data, CQuizQuestion $question): ?int
    {
        $mediaId = (int) $data->parentMediaId;
        if (0 >= $mediaId) {
            return null;
        }

        if (null !== $question->getIid() && $mediaId === (int) $question->getIid()) {
            throw new BadRequestHttpException('A question cannot be attached to itself as media.');
        }

        $mediaQuestion = $this->questionRepository->find($mediaId);
        if (!$mediaQuestion instanceof CQuizQuestion || self::MEDIA_QUESTION !== (int) $mediaQuestion->getType()) {
            return null;
        }

        if (!$this->isQuestionInExercise($quiz, $mediaId)) {
            throw new AccessDeniedHttpException('The selected media question does not belong to this exercise.');
        }

        return $mediaId;
    }

    private function isQuestionInExercise(CQuiz $quiz, int $questionId): bool
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('relQuestion.iid')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $result;
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getResponseAnswers(CQuizQuestion $question): array
    {
        $items = [];
        $type = (int) $question->getType();
        if ($this->usesFillBlanks($type)) {
            return [];
        }

        $optionPositionsByIid = $this->getQuestionOptionPositionsByIid($question);
        foreach ($this->getExistingAnswers($question) as $answer) {
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

    private function calculateQuestionScore(ExerciseQuestionEditor $data): float
    {
        $type = (int) $data->type;
        if ($this->isStructuralQuestionType($type)) {
            return 0.0;
        }

        if ($this->usesHotspot($type)) {
            if (self::HOT_SPOT_COMBINATION === $type) {
                return max(0.0, (float) $data->globalScore);
            }

            $score = 0.0;
            foreach ($this->getCleanHotspotItems($data) as $item) {
                $score += max(0.0, (float) ($item['score'] ?? 0.0));
            }

            return $score;
        }

        if (\in_array($type, [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::ANNOTATION, self::UPLOAD_ANSWER, self::CALCULATED_ANSWER], true)) {
            return max(0.0, (float) $data->score);
        }

        if (self::FILL_IN_BLANKS === $type) {
            return $this->calculateFillBlanksScore($data);
        }

        if (self::FILL_IN_BLANKS_COMBINATION === $type) {
            return max(0.0, (float) $data->globalScore);
        }

        if ($this->usesDraggableOrdering($type)) {
            $score = 0.0;
            foreach ($this->getCleanDraggableItems($data) as $item) {
                $score += max(0.0, (float) ($item['score'] ?? 0.0));
            }

            return $score;
        }

        if ($this->usesMatching($type)) {
            if (\in_array($type, [self::MATCHING_COMBINATION, self::MATCHING_DRAGGABLE_COMBINATION], true)) {
                return max(0.0, (float) $data->globalScore);
            }

            $score = 0.0;
            foreach ($this->getCleanMatchingPairs($data) as $pair) {
                $score += max(0.0, (float) ($pair['score'] ?? 0.0));
            }

            return $score;
        }

        if (\in_array($type, [self::MULTIPLE_ANSWER_TRUE_FALSE, self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY], true)) {
            $regularAnswerCount = \count(array_filter(
                $this->getCleanAnswers($data),
                static fn (array $answer): bool => true !== ($answer['isUnknown'] ?? false)
            ));

            return max(0.0, (float) $data->correctScore) * max(1, $regularAnswerCount);
        }

        if ($this->usesGlobalScore($type)) {
            return max(0.0, (float) $data->globalScore);
        }

        $score = 0.0;
        foreach ($this->getCleanAnswers($data) as $answer) {
            if (true === $answer['correct'] && 0 < $answer['score']) {
                $score += $answer['score'];
            }
        }

        if (0 < $score) {
            return $score;
        }

        return max(0.0, (float) $data->score);
    }

    private function getNextQuestionOrder(CQuiz $quiz): int
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('MAX(relQuestion.questionOrder)')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result + 1;
    }

    private function normalizeNullablePositiveInteger(?int $value): ?int
    {
        if (null === $value || 0 >= $value) {
            return null;
        }

        return $value;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
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

    private function usesTrueFalseScoreOptions(int $type): bool
    {
        return \in_array($type, [self::MULTIPLE_ANSWER_TRUE_FALSE, self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY], true);
    }

    private function buildTrueFalseExtra(ExerciseQuestionEditor $data): string
    {
        return implode(':', [
            (string) (float) $data->correctScore,
            (string) (float) $data->wrongScore,
            (string) (float) $data->unknownScore,
        ]);
    }

    /**
     * @return array<int, array{answer: string, correct: bool, comment: string, score: float, position: int, isUnknown: bool}>
     */
    private function normalizeAnswersForType(ExerciseQuestionEditor $data, array $optionIidsByPosition = []): array
    {
        $type = (int) $data->type;
        if (\in_array($type, [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::ANNOTATION, self::UPLOAD_ANSWER, self::CALCULATED_ANSWER], true) || $this->isStructuralQuestionType($type) || $this->usesHotspot($type) || $this->usesFillBlanks($type) || $this->usesMatching($type) || $this->usesDraggableOrdering($type)) {
            return [];
        }

        $answers = $this->getCleanAnswers($data);

        if (self::UNIQUE_ANSWER_NO_OPTION === $type) {
            $hasUnknownAnswer = false;
            foreach ($answers as &$answer) {
                if (true === $answer['isUnknown']) {
                    $answer['correct'] = false;
                    $answer['score'] = 0.0;
                    $answer['position'] = self::UNKNOWN_ANSWER_POSITION;
                    $hasUnknownAnswer = true;
                }
            }
            unset($answer);

            if (!$hasUnknownAnswer) {
                $answers[] = [
                    'answer' => 'Don\'t know',
                    'correct' => false,
                    'correctChoice' => 0,
                    'comment' => '',
                    'score' => 0.0,
                    'position' => self::UNKNOWN_ANSWER_POSITION,
                    'isUnknown' => true,
                ];
            }
        }

        if ($this->usesTrueFalseOptions($type)) {
            foreach ($answers as $index => &$answer) {
                $choicePosition = (int) ($answer['correctChoice'] ?? 1);
                if (!\in_array($choicePosition, [1, 2], true)) {
                    $choicePosition = 1;
                }

                $answer['correct'] = (int) ($optionIidsByPosition[$choicePosition] ?? $choicePosition);
                $answer['score'] = self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE === $type && 0 === $index
                    ? max(0.0, (float) $data->globalScore)
                    : 0.0;
            }
            unset($answer);
        }

        if (self::MULTIPLE_ANSWER_COMBINATION === $type) {
            $globalScore = max(0.0, (float) $data->globalScore);
            foreach ($answers as $index => &$answer) {
                $answer['score'] = 0 === $index ? $globalScore : 0.0;
            }
            unset($answer);
        }

        if (self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION === $type) {
            foreach ($answers as &$answer) {
                $answer['score'] = 0.0;
            }
            unset($answer);
        }

        if (self::MULTIPLE_ANSWER_DROPDOWN === $type) {
            foreach ($answers as &$answer) {
                if (true !== $answer['correct']) {
                    $answer['score'] = 0.0;
                }
            }
            unset($answer);
        }

        if (self::GLOBAL_MULTIPLE_ANSWER === $type) {
            $correctCount = 0;
            foreach ($answers as $answer) {
                if (true === $answer['correct']) {
                    ++$correctCount;
                }
            }

            $correctScore = 0 < $correctCount ? max(0.0, (float) $data->globalScore) / $correctCount : 0.0;
            foreach ($answers as &$answer) {
                $answer['score'] = true === $answer['correct']
                    ? abs($correctScore)
                    : (true === $data->noNegativeScore ? 0.0 : -abs($correctScore));
            }
            unset($answer);
        }

        return $answers;
    }

    /**
     * @return array<int, int>
     */
    private function ensureTrueFalseOptions(CQuizQuestion $question, int $type): array
    {
        $expectedTitles = self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY === $type
            ? [1 => 'True', 2 => 'False', 3 => '50%', 4 => '60%', 5 => '70%', 6 => '80%', 7 => '90%', 8 => '100%']
            : [1 => 'True', 2 => 'False', 3 => 'Don\'t know'];

        $optionIidsByPosition = [];
        foreach ($question->getOptions() as $option) {
            if (!$option instanceof CQuizQuestionOption || null === $option->getIid()) {
                continue;
            }

            $optionIidsByPosition[(int) $option->getPosition()] = (int) $option->getIid();
        }

        foreach ($expectedTitles as $position => $title) {
            if (isset($optionIidsByPosition[$position])) {
                continue;
            }

            $option = new CQuizQuestionOption();
            $option
                ->setQuestion($question)
                ->setTitle($title)
                ->setPosition((int) $position)
            ;
            $this->entityManager->persist($option);
            $this->entityManager->flush();
            $optionIidsByPosition[$position] = (int) $option->getIid();
        }

        ksort($optionIidsByPosition);

        return $optionIidsByPosition;
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


    private function buildQuestionExtra(int $type, ExerciseQuestionEditor $data, CQuizQuestion $question): ?string
    {
        if ($this->usesTrueFalseScoreOptions($type)) {
            if (self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY === $type) {
                return implode(':', [
                    (string) (float) $data->correctScore,
                    (string) (float) $data->wrongScore,
                    '0',
                ]);
            }

            return $this->buildTrueFalseExtra($data);
        }

        if ($this->usesFillBlanks($type)) {
            return $data->fillBlanksCaseInsensitive ? 'case:false' : null;
        }

        if ($this->usesDraggableOrdering($type)) {
            return \in_array($data->matchingOrientation, ['h', 'v'], true) ? $data->matchingOrientation : 'h';
        }

        return $question->getExtra();
    }


    private function validateDraggablePayload(ExerciseQuestionEditor $data): void
    {
        $items = $this->getCleanDraggableItems($data);

        if (2 > \count($items)) {
            throw new BadRequestHttpException('At least two draggable items are required.');
        }

        $validPositions = range(1, \count($items));
        foreach ($items as $item) {
            if (!\in_array((int) ($item['targetPosition'] ?? 0), $validPositions, true)) {
                throw new BadRequestHttpException('Each draggable item must be linked to a valid target position.');
            }
        }

        if (0 >= $this->calculateQuestionScore($data)) {
            throw new BadRequestHttpException('Required field.');
        }
    }

    private function persistDraggableAnswers(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        $items = $this->getCleanDraggableItems($data);
        $position = 1;

        foreach ($items as $index => $_item) {
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) ($index + 1))
                ->setCorrect(0)
                ->setComment('')
                ->setPonderation(0.0)
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);
            ++$position;
        }

        foreach ($items as $item) {
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) $item['answer'])
                ->setCorrect((int) $item['targetPosition'])
                ->setComment('')
                ->setPonderation((float) $item['score'])
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);
            ++$position;
        }
    }

    /**
     * @return array<int, array{answer: string, targetPosition: int, score: float, position: int}>
     */
    private function getCleanDraggableItems(ExerciseQuestionEditor $data): array
    {
        $items = [];
        foreach ($data->draggableItems as $index => $item) {
            if (!\is_array($item)) {
                continue;
            }

            $answer = trim((string) ($item['answer'] ?? ''));
            if ('' === $answer) {
                continue;
            }

            $items[] = [
                'answer' => $answer,
                'targetPosition' => max(1, (int) ($item['targetPosition'] ?? ($index + 1))),
                'score' => max(0.0, (float) ($item['score'] ?? 0.0)),
                'position' => \count($items) + 1,
            ];
        }

        return $items;
    }

    private function validateMatchingPayload(ExerciseQuestionEditor $data): void
    {
        $options = $this->getCleanMatchingOptions($data);
        $pairs = $this->getCleanMatchingPairs($data);

        if (2 > \count($options)) {
            throw new BadRequestHttpException('At least two matching options are required.');
        }

        if (2 > \count($pairs)) {
            throw new BadRequestHttpException('At least two matching pairs are required.');
        }

        $optionLocalIds = array_column($options, 'localId');
        foreach ($pairs as $pair) {
            if (!\in_array((string) ($pair['optionLocalId'] ?? ''), $optionLocalIds, true)) {
                throw new BadRequestHttpException('Each matching pair must be linked to a valid option.');
            }
        }

        if (0 >= $this->calculateQuestionScore($data)) {
            throw new BadRequestHttpException('Required field.');
        }
    }

    private function persistMatchingAnswers(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        $options = $this->getCleanMatchingOptions($data);
        $pairs = $this->getCleanMatchingPairs($data);
        $optionIidsByLocalId = [];
        $position = 1;

        foreach ($options as $index => $optionData) {
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) $optionData['answer'])
                ->setCorrect(0)
                ->setComment('')
                ->setPonderation(0.0)
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);
            $optionIidsByLocalId[(string) $optionData['localId']] = $answer;
            ++$position;
        }

        $this->entityManager->flush();

        $optionIids = [];
        foreach ($optionIidsByLocalId as $localId => $answer) {
            if ($answer instanceof CQuizAnswer && null !== $answer->getIid()) {
                $optionIids[$localId] = (int) $answer->getIid();
            }
        }

        foreach ($pairs as $pairData) {
            $optionLocalId = (string) ($pairData['optionLocalId'] ?? '');
            $correctOptionIid = (int) ($optionIids[$optionLocalId] ?? 0);
            if (0 >= $correctOptionIid) {
                throw new BadRequestHttpException('Each matching pair must be linked to a valid option.');
            }

            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) $pairData['answer'])
                ->setCorrect($correctOptionIid)
                ->setComment((string) $pairData['comment'])
                ->setPonderation(\in_array((int) $data->type, [self::MATCHING_COMBINATION, self::MATCHING_DRAGGABLE_COMBINATION], true) ? 0.0 : (float) $pairData['score'])
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);
            ++$position;
        }
    }

    /**
     * @return array<int, array{localId: string, answer: string, position: int}>
     */
    private function getCleanMatchingOptions(ExerciseQuestionEditor $data): array
    {
        $options = [];
        foreach ($data->matchingOptions as $index => $option) {
            if (!\is_array($option)) {
                continue;
            }

            $answer = trim((string) ($option['answer'] ?? ''));
            if ('' === $answer) {
                continue;
            }

            $localId = trim((string) ($option['localId'] ?? ''));
            if ('' === $localId) {
                $localId = 'option-'.($index + 1);
            }

            $options[] = [
                'localId' => $localId,
                'answer' => $answer,
                'position' => \count($options) + 1,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array{answer: string, optionLocalId: string, comment: string, score: float, position: int}>
     */
    private function getCleanMatchingPairs(ExerciseQuestionEditor $data): array
    {
        $pairs = [];
        foreach ($data->matchingPairs as $index => $pair) {
            if (!\is_array($pair)) {
                continue;
            }

            $answer = trim((string) ($pair['answer'] ?? ''));
            if ('' === $answer) {
                continue;
            }

            $pairs[] = [
                'answer' => $answer,
                'optionLocalId' => (string) ($pair['optionLocalId'] ?? ''),
                'comment' => (string) ($pair['comment'] ?? ''),
                'score' => max(0.0, (float) ($pair['score'] ?? 0.0)),
                'position' => \count($pairs) + 1,
            ];
        }

        return $pairs;
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
        $options = [];
        $optionsByIid = [];
        $pairs = [];

        foreach ($this->getExistingAnswers($question) as $answer) {
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

        return ['options' => $options, 'pairs' => $pairs];
    }

    private function getMatchingOptionLabel(int $position): string
    {
        if (1 <= $position && 26 >= $position) {
            return chr(64 + $position);
        }

        return (string) $position;
    }


    private function validateCalculatedPayload(ExerciseQuestionEditor $data): void
    {
        if ('' === trim(strip_tags((string) $data->calculatedText))) {
            throw new BadRequestHttpException('Please type the text.');
        }

        if (0 === \count($this->extractCalculatedTokens((string) $data->calculatedText))) {
            throw new BadRequestHttpException('Please define at least one blank with the selected marker.');
        }

        if ('' === trim((string) $data->calculatedFormula)) {
            throw new BadRequestHttpException('Please, write the formula.');
        }

        if (0 >= (float) $data->score) {
            throw new BadRequestHttpException('Required field.');
        }

        if (1 > (int) $data->calculatedVariations) {
            throw new BadRequestHttpException('Question variations.');
        }
    }

    private function persistCalculatedAnswers(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        $variations = max(1, (int) $data->calculatedVariations);
        $ranges = $this->normalizeCalculatedRanges($data);

        for ($position = 1; $position <= $variations; ++$position) {
            $answerText = (string) $data->calculatedText;
            $formula = (string) $data->calculatedFormula;

            foreach ($this->extractCalculatedTokens($answerText) as $token) {
                $range = $ranges[$token] ?? ['low' => '1', 'high' => '20'];
                $value = $this->generateCalculatedValue((string) $range['low'], (string) $range['high']);
                $answerText = str_replace($token, (string) $value, $answerText);
                $formula = str_replace($token, (string) $value, $formula);
            }

            $result = $this->evaluateCalculatedFormula($formula);
            $encodedAnswer = $answerText.' ['.$result.']@@'.(string) $data->calculatedFormula;

            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer($encodedAnswer)
                ->setCorrect(1)
                ->setComment((string) $data->calculatedComment)
                ->setPonderation((float) $data->score)
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);
        }
    }

    /**
     * @return array<string, array{low: string, high: string}>
     */
    private function normalizeCalculatedRanges(ExerciseQuestionEditor $data): array
    {
        $submittedRanges = [];
        foreach ($data->calculatedRanges as $range) {
            if (!\is_array($range)) {
                continue;
            }

            $token = (string) ($range['token'] ?? '');
            if ('' === $token) {
                continue;
            }

            $submittedRanges[$token] = [
                'low' => (string) ($range['low'] ?? '1'),
                'high' => (string) ($range['high'] ?? '20'),
            ];
        }

        $ranges = [];
        foreach ($this->extractCalculatedTokens((string) $data->calculatedText) as $token) {
            $ranges[$token] = $submittedRanges[$token] ?? ['low' => '1', 'high' => '20'];
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

    private function generateCalculatedValue(string $low, string $high): string
    {
        $minimum = (float) $low;
        $maximum = (float) $high;
        if ($maximum < $minimum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        $hasDecimal = str_contains($low, '.') || str_contains($high, '.');
        if (!$hasDecimal) {
            return (string) random_int((int) $minimum, (int) $maximum);
        }

        $value = random_int((int) round($minimum * 100), (int) round($maximum * 100)) / 100;

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function evaluateCalculatedFormula(string $formula): string
    {
        $math = new EvalMath();
        $result = (float) $math->evaluate($formula);

        return rtrim(rtrim(number_format($result, 2, '.', ''), '0'), '.');
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

        $items = [];
        foreach ($this->getExistingAnswers($question) as $answer) {
            if ('noerror' === (string) $answer->getHotspotType()) {
                continue;
            }

            $items[] = [
                'id' => $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'comment' => (string) $answer->getComment(),
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
                'hotspotType' => (string) ($answer->getHotspotType() ?: 'square'),
                'coordinates' => (string) ($answer->getHotspotCoordinates() ?: '0;0|0|0'),
            ];
        }

        $response->hotspotItems = [] !== $items ? $items : $this->getDefaultHotspotItems((int) $question->getType());
        if (self::HOT_SPOT_DELINEATION === (int) $question->getType()) {
            $this->addHotspotScenarioData($response, $quiz, $question);
        }
        $response->answers = [];
    }

    private function addHotspotScenarioData(ExerciseQuestionEditor $response, CQuiz $quiz, ?CQuizQuestion $question): void
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

        $relation = $this->getQuestionRelation($quiz, $question);
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

    private function getQuestionRelation(CQuiz $quiz, CQuizQuestion $question): ?CQuizRelQuestion
    {
        $relation = $this->entityManager->getRepository(CQuizRelQuestion::class)->findOneBy([
            'quiz' => $quiz,
            'question' => $question,
        ]);

        return $relation instanceof CQuizRelQuestion ? $relation : null;
    }

    private function applyHotspotDelineationDestination(CQuizRelQuestion $relation, ExerciseQuestionEditor $data): void
    {
        if (self::HOT_SPOT_DELINEATION !== (int) $data->type) {
            $relation->setDestination(null);

            return;
        }

        $destination = json_encode([
            'success' => [
                'type' => $this->normalizeScenarioDestinationType($data->hotspotScenarioSuccessType),
                'url' => trim($data->hotspotScenarioSuccessUrl),
            ],
            'failure' => [
                'type' => $this->normalizeScenarioDestinationType($data->hotspotScenarioFailureType),
                'url' => trim($data->hotspotScenarioFailureUrl),
            ],
        ]);

        $relation->setDestination(false === $destination ? null : $destination);
    }

    private function normalizeScenarioDestinationType(string $value): string
    {
        $value = trim($value);
        if ('' === $value || 'repeat' === $value || '-1' === $value || 'url' === $value || ctype_digit($value)) {
            return $value;
        }

        return '';
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCleanHotspotItems(ExerciseQuestionEditor $data): array
    {
        $items = [];
        foreach ($data->hotspotItems as $index => $item) {
            if (!\is_array($item)) {
                continue;
            }

            $hotspotType = $this->normalizeHotspotType((string) ($item['hotspotType'] ?? 'square'));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ('delineation' === $hotspotType && '' === $answer) {
                $answer = 'delineation';
            }
            if ('oar' === $hotspotType && '' === $answer) {
                $answer = 'Area to avoid';
            }

            $coordinates = $this->normalizeHotspotCoordinates((string) ($item['coordinates'] ?? ''));
            if ('' === $answer || '' === $coordinates) {
                continue;
            }

            $items[] = [
                'answer' => $answer,
                'comment' => (string) ($item['comment'] ?? ''),
                'score' => 'oar' === $hotspotType ? 0.0 : max(0.0, (float) ($item['score'] ?? 0.0)),
                'position' => $index + 1,
                'hotspotType' => $hotspotType,
                'coordinates' => $coordinates,
            ];
        }

        return $items;
    }

    private function validateHotspotPayload(ExerciseQuestionEditor $data): void
    {
        $items = $this->getCleanHotspotItems($data);
        if (0 === \count($items)) {
            throw new BadRequestHttpException('Please draw at least one hotspot.');
        }

        if (self::HOT_SPOT_COMBINATION === (int) $data->type) {
            if (0 >= (float) $data->globalScore) {
                throw new BadRequestHttpException('Required field.');
            }

            return;
        }

        if (self::HOT_SPOT_DELINEATION === (int) $data->type) {
            $hasDelineation = false;
            foreach ($items as $item) {
                if ('delineation' === (string) ($item['hotspotType'] ?? '')) {
                    $hasDelineation = true;
                }

                if ('oar' !== (string) ($item['hotspotType'] ?? '') && 0 >= (float) ($item['score'] ?? 0.0)) {
                    throw new BadRequestHttpException('You must give a positive score for each hotspots');
                }
            }

            if (!$hasDelineation) {
                throw new BadRequestHttpException('Please draw at least one delineation.');
            }

            return;
        }

        foreach ($items as $item) {
            if (0 >= (float) ($item['score'] ?? 0.0)) {
                throw new BadRequestHttpException('You must give a positive score for each hotspots');
            }
        }
    }

    private function persistHotspotAnswers(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        $position = 1;
        foreach ($this->getCleanHotspotItems($data) as $item) {
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) $item['answer'])
                ->setCorrect(0)
                ->setComment((string) $item['comment'])
                ->setPonderation(self::HOT_SPOT_COMBINATION === (int) $data->type ? 0.0 : (float) $item['score'])
                ->setPosition($position)
                ->setHotspotCoordinates((string) $item['coordinates'])
                ->setHotspotType((string) $item['hotspotType'])
            ;
            $this->entityManager->persist($answer);
            ++$position;
        }

        if (self::HOT_SPOT_DELINEATION === (int) $data->type) {
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer('noerror')
                ->setCorrect(0)
                ->setComment('')
                ->setPonderation(0.0)
                ->setPosition($position)
                ->setHotspotCoordinates(null)
                ->setHotspotType('noerror')
            ;
            $this->entityManager->persist($answer);
        }
    }

    private function normalizeHotspotType(string $type): string
    {
        return \in_array($type, ['square', 'circle', 'poly', 'delineation', 'oar'], true) ? $type : 'square';
    }

    private function normalizeHotspotCoordinates(string $coordinates): string
    {
        $coordinates = trim($coordinates);
        if ('' === $coordinates || '0;0|0|0' === $coordinates) {
            return '';
        }

        return $coordinates;
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
            $response->calculatedRanges = [];
            $response->calculatedVariations = 1;
            $response->calculatedComment = '';
            $response->answers = [];

            return;
        }

        $parsed = $this->parseCalculatedAnswer((string) $firstAnswer->getAnswer());
        $response->calculatedText = $parsed['text'];
        $response->calculatedFormula = $parsed['formula'];
        $response->calculatedRanges = $this->buildCalculatedRanges($parsed['text']);
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
     * @return array<int, array{token: string, low: string, high: string, random: string, position: int}>
     */
    private function buildCalculatedRanges(string $text): array
    {
        $ranges = [];
        foreach ($this->extractCalculatedTokens($text) as $index => $token) {
            $ranges[] = [
                'token' => $token,
                'low' => '1',
                'high' => '20',
                'random' => $this->generateCalculatedValue('1', '20'),
                'position' => $index + 1,
            ];
        }

        return $ranges;
    }

    private function getDefaultCalculatedText(): string
    {
        return '<p>Calculate the Body Mass Index for a person with weight [95] Kg and height [1.81] m.</p><p>Body Mass Index: []</p>';
    }

    private function validateFillBlanksPayload(ExerciseQuestionEditor $data): void
    {
        $type = (int) $data->type;
        $text = trim((string) $data->fillBlanksText);
        if ('' === trim(strip_tags($text))) {
            throw new BadRequestHttpException('The fill in blanks text is required.');
        }

        $blanks = $this->extractFillBlankAnswers($text, (int) $data->fillBlanksSeparator);
        if (0 === \count($blanks)) {
            throw new BadRequestHttpException('Please define at least one blank with the selected marker.');
        }

        if (self::FILL_IN_BLANKS_COMBINATION === $type) {
            if (0 >= (float) $data->globalScore) {
                throw new BadRequestHttpException('Required field.');
            }

            return;
        }

        if (0 >= $this->calculateFillBlanksScore($data)) {
            throw new BadRequestHttpException('Required field.');
        }
    }

    private function persistFillBlanksAnswer(CQuizQuestion $question, ExerciseQuestionEditor $data): void
    {
        $answer = new CQuizAnswer();
        $answer
            ->setQuestion($question)
            ->setAnswer($this->buildFillBlanksAnswerString($data))
            ->setCorrect(0)
            ->setComment((string) $data->fillBlanksComment)
            ->setPonderation($this->calculateQuestionScore($data))
            ->setPosition(1)
        ;
        $this->entityManager->persist($answer);
    }

    private function buildFillBlanksAnswerString(ExerciseQuestionEditor $data): string
    {
        $separator = $this->normalizeFillBlanksSeparator((int) $data->fillBlanksSeparator);
        $text = $this->normalizeFillBlanksText((string) $data->fillBlanksText, $separator);
        $blanks = $this->extractFillBlankAnswers($text, $separator);
        $items = \is_array($data->fillBlankItems) ? array_values($data->fillBlankItems) : [];

        $weights = [];
        $sizes = [];
        foreach ($blanks as $index => $_blank) {
            $item = $items[$index] ?? [];
            $weights[] = self::FILL_IN_BLANKS_COMBINATION === (int) $data->type
                ? 0.0
                : max(0.0, (float) ($item['score'] ?? 1.0));
            $sizes[] = $this->normalizeFillBlanksInputSize((int) ($item['inputSize'] ?? 200));
        }

        return sprintf(
            '%s::%s:%s:%d@%s',
            $text,
            implode(',', $weights),
            implode(',', $sizes),
            $separator,
            $data->fillBlanksSwitchable ? '1' : ''
        );
    }

    private function normalizeFillBlanksText(string $text, int $separator): string
    {
        [$start, $end] = $this->getFillBlankSeparators($separator);
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';

        return preg_replace_callback(
            $pattern,
            static fn (array $matches): string => $start.trim((string) $matches[1]).$end,
            $text
        ) ?? $text;
    }

    private function calculateFillBlanksScore(ExerciseQuestionEditor $data): float
    {
        $score = 0.0;
        foreach ($this->normalizeFillBlankItems($data) as $item) {
            $score += max(0.0, (float) ($item['score'] ?? 0.0));
        }

        return $score;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFillBlankItems(ExerciseQuestionEditor $data): array
    {
        $text = (string) $data->fillBlanksText;
        $separator = $this->normalizeFillBlanksSeparator((int) $data->fillBlanksSeparator);
        $blanks = $this->extractFillBlankAnswers($text, $separator);
        $submittedItems = \is_array($data->fillBlankItems) ? array_values($data->fillBlankItems) : [];
        $items = [];

        foreach ($blanks as $index => $blank) {
            $submitted = $submittedItems[$index] ?? [];
            $items[] = [
                'answer' => $blank,
                'score' => max(0.0, (float) ($submitted['score'] ?? 1.0)),
                'inputSize' => $this->normalizeFillBlanksInputSize((int) ($submitted['inputSize'] ?? 200)),
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
        [$start, $end] = $this->getFillBlankSeparators($this->normalizeFillBlanksSeparator($separator));
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
        preg_match_all($pattern, $text, $matches);

        return array_map(
            static fn (string $value): string => trim($value),
            $matches[1] ?? []
        );
    }

    private function normalizeFillBlanksSeparator(int $separator): int
    {
        return \in_array($separator, [0, 1, 2, 3, 4, 5, 6], true) ? $separator : 0;
    }

    private function normalizeFillBlanksInputSize(int $inputSize): int
    {
        if (40 > $inputSize) {
            return 40;
        }

        if (800 < $inputSize) {
            return 800;
        }

        return $inputSize;
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
            $separator = $this->normalizeFillBlanksSeparator((int) ($details[2] ?? 0));
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

        if ([] === $items) {
            return [
                ['id' => null, 'localId' => 'draggable-1', 'answer' => '', 'targetPosition' => 1, 'score' => 10.0, 'position' => 1],
                ['id' => null, 'localId' => 'draggable-2', 'answer' => '', 'targetPosition' => 2, 'score' => 10.0, 'position' => 2],
            ];
        }

        return $items;
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

    private function buildGlobalResponse(CQuizQuestion $question, Course $course, ?Session $session): ExerciseQuestionEditor
    {
        $response = new ExerciseQuestionEditor();
        $response->exerciseId = 0;
        $response->questionId = (int) $question->getIid();
        $response->type = (int) $question->getType();
        $response->typeLabel = $this->getQuestionTypeLabel((int) $question->getType());
        $response->title = $question->getQuestion();
        $response->description = (string) $question->getDescription();
        $response->feedback = (string) $question->getFeedback();
        $response->dropdownListText = $this->buildDropdownListText($question);
        $response->score = (float) $question->getPonderation();
        $response->globalScore = $this->usesGlobalScore((int) $question->getType()) ? (float) $question->getPonderation() : 0.0;
        [$response->correctScore, $response->wrongScore, $response->unknownScore] = $this->getTrueFalseScores($question);
        $response->usesGlobalScore = $this->usesGlobalScore((int) $question->getType());
        $response->hasFixedUnknownAnswer = self::UNIQUE_ANSWER_NO_OPTION === (int) $question->getType();
        $response->mandatory = false;
        $response->duration = $question->getDuration();
        $response->difficulty = max(1, (int) $question->getLevel());
        $response->categoryId = $this->getFirstCategoryId($question);
        $response->parentMediaId = 0;
        $response->answers = $this->getResponseAnswers($question);
        $this->addAnnotationData($response, $question, $course, $session);
        $this->addCalculatedData($response, $question);
        $this->addFillBlanksData($response, $question);
        $this->addMatchingData($response, $question);
        $this->addDraggableData($response, $question);
        $response->noNegativeScore = self::GLOBAL_MULTIPLE_ANSWER === (int) $question->getType() && $this->hasNoNegativeScore($response->answers);
        $response->questionCount = 0;
        $response->totalScore = 0.0;
        $response->categoryOptions = $this->getCategoryOptions($course, $session);
        $response->mediaOptions = [];
        $response->legacyUrls = [];
        $response->csrfToken = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();
        $response->allowQuestionFeedback = $this->isQuestionFeedbackEnabled();
        $response->imageZoomEnabled = $this->isImageZoomEnabled();
        $response->allowMandatoryQuestion = false;

        return $response;
    }

    private function buildResponse(CQuiz $quiz, CQuizQuestion $question, Course $course, ?Session $session): ExerciseQuestionEditor
    {
        $response = new ExerciseQuestionEditor();
        $response->exerciseId = (int) $quiz->getIid();
        $response->questionId = (int) $question->getIid();
        $response->type = (int) $question->getType();
        $response->typeLabel = $this->getQuestionTypeLabel((int) $question->getType());
        $response->title = $question->getQuestion();
        $response->description = (string) $question->getDescription();
        $response->feedback = (string) $question->getFeedback();
        $response->dropdownListText = $this->buildDropdownListText($question);
        $response->score = (float) $question->getPonderation();
        $response->globalScore = $this->usesGlobalScore((int) $question->getType()) ? (float) $question->getPonderation() : 0.0;
        [$response->correctScore, $response->wrongScore, $response->unknownScore] = $this->getTrueFalseScores($question);
        $response->usesGlobalScore = $this->usesGlobalScore((int) $question->getType());
        $response->hasFixedUnknownAnswer = self::UNIQUE_ANSWER_NO_OPTION === (int) $question->getType();
        $response->mandatory = $this->isQuestionMandatoryInCategory($question);
        $response->duration = $question->getDuration();
        $response->difficulty = max(1, (int) $question->getLevel());
        $response->categoryId = $this->getFirstCategoryId($question);
        $response->parentMediaId = (int) ($question->getParentMediaId() ?? 0);
        $response->answers = $this->getResponseAnswers($question);
        $this->addAnnotationData($response, $question, $course, $session);
        $this->addHotspotData($response, $quiz, $question, $course, $session);
        $this->addCalculatedData($response, $question);
        $this->addFillBlanksData($response, $question);
        $this->addMatchingData($response, $question);
        $this->addDraggableData($response, $question);
        $response->noNegativeScore = self::GLOBAL_MULTIPLE_ANSWER === (int) $question->getType() && $this->hasNoNegativeScore($response->answers);
        $summary = $this->getQuestionSummary($quiz);
        $response->questionCount = (int) $summary['questionCount'];
        $response->totalScore = (float) $summary['totalScore'];
        $response->legacyUrls = [
            'questions' => '/main/exercise/admin.php?'.http_build_query([
                'exerciseId' => (int) $quiz->getIid(),
                'cid' => (int) $course->getId(),
                'sid' => (int) ($session?->getId() ?? 0),
            ]),
        ];
        $response->csrfToken = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();
        $response->allowQuestionFeedback = $this->isQuestionFeedbackEnabled();
        $response->allowMandatoryQuestion = $this->isMandatoryQuestionInCategoryEnabled($quiz);

        return $response;
    }

    private function applyActiveLinkConstraints(QueryBuilder $queryBuilder, string $alias, ?Session $session, bool $includeCourseWhenSessionSelected): void
    {
        $queryBuilder
            ->andWhere($alias.'.deletedAt IS NULL')
            ->andWhere($alias.'.endVisibilityAt IS NULL')
            ->andWhere($alias.'.visibility IN (0,2)')
        ;

        $this->applySessionFilter($queryBuilder, $alias, $session, $includeCourseWhenSessionSelected);
    }

    private function applySessionFilter(QueryBuilder $queryBuilder, string $alias, ?Session $session, bool $includeCourseWhenSessionSelected = false): void
    {
        if (null !== $session) {
            if ($includeCourseWhenSessionSelected) {
                $queryBuilder->andWhere('(IDENTITY('.$alias.'.session) = :sessionId OR '.$alias.'.session IS NULL)');

                return;
            }

            $queryBuilder->andWhere('IDENTITY('.$alias.'.session) = :sessionId');

            return;
        }

        $queryBuilder->andWhere($alias.'.session IS NULL');
    }

    private function isQuestionMandatoryInCategory(CQuizQuestion $question): bool
    {
        $questionId = (int) ($question->getIid() ?? 0);
        if (0 >= $questionId || !$this->hasMandatoryQuestionCategoryColumn()) {
            return 1 === (int) $question->getMandatory();
        }

        $value = $this->entityManager->getConnection()->fetchOne(
            'SELECT mandatory FROM c_quiz_question_rel_category WHERE question_id = :questionId ORDER BY iid ASC LIMIT 1',
            ['questionId' => $questionId],
            ['questionId' => Types::INTEGER]
        );

        if (false === $value) {
            return 1 === (int) $question->getMandatory();
        }

        return 1 === (int) $value;
    }
}

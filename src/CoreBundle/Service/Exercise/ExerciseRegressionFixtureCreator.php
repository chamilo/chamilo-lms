<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionEditor;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionEditorProcessor;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class ExerciseRegressionFixtureCreator
{
    private const int MAX_TITLE_PREFIX_LENGTH = 120;
    private const int MAX_TOPIC_LENGTH = 300;
    private const int MAX_LANGUAGE_LENGTH = 32;
    private const int MAX_PROVIDER_LENGTH = 64;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExerciseQuestionEditorProcessor $questionEditorProcessor,
        private ExerciseRegressionFixtureQuestionFactory $questionFactory,
        private ExerciseRegressionTopicContentGenerator $topicContentGenerator,
        private ExerciseRegressionTopicContentApplicator $topicContentApplicator,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * Create the complete current Exercises question-type regression suite.
     *
     * Without a topic, the historical deterministic QA fixtures are preserved.
     * With a topic, the same 30 question-type structures and deterministic
     * binary assets are kept, while the visible pedagogical content is generated
     * through Chamilo's configured AI text provider and validated before writes.
     *
     * @return array{
     *     mode: string,
     *     topic: string|null,
     *     language: string,
     *     ai_provider: string|null,
     *     distinct_question_type_count: int,
     *     total_question_count: int,
     *     unsupported_legacy_types: list<array{type: int, reason: string}>,
     *     exercises: list<array{
     *         quiz_id: int,
     *         resource_node_id: int,
     *         title: string,
     *         feedback_type: int,
     *         published: bool,
     *         question_count: int,
     *         question_types: list<int>,
     *         questions: list<array{question_id: int, type: int, label: string, title: string, score: float}>,
     *         content_url: string
     *     }>
     * }
     */
    public function create(
        Course $course,
        User $user,
        string $titlePrefix = 'Exercise question type regression',
        bool $publish = false,
        ?string $topic = null,
        string $language = 'en',
        ?string $aiProvider = null,
    ): array {
        $titlePrefix = trim(strip_tags($titlePrefix));
        if ('' === $titlePrefix) {
            throw new InvalidArgumentException('The regression suite title prefix is required.');
        }
        if (mb_strlen($titlePrefix) > self::MAX_TITLE_PREFIX_LENGTH) {
            throw new InvalidArgumentException('The regression suite title prefix cannot be longer than 120 characters.');
        }

        $topic = null !== $topic ? trim(strip_tags($topic)) : null;
        if ('' === $topic) {
            $topic = null;
        }
        if (null !== $topic && mb_strlen($topic) > self::MAX_TOPIC_LENGTH) {
            throw new InvalidArgumentException('The regression suite topic cannot be longer than 300 characters.');
        }

        $language = trim(strip_tags($language));
        if ('' === $language) {
            $language = 'en';
        }
        if (mb_strlen($language) > self::MAX_LANGUAGE_LENGTH || 1 !== preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $language)) {
            throw new InvalidArgumentException('The regression suite language is invalid.');
        }

        $aiProvider = null !== $aiProvider ? trim(strip_tags($aiProvider)) : null;
        if ('' === $aiProvider) {
            $aiProvider = null;
        }
        if (null !== $aiProvider && mb_strlen($aiProvider) > self::MAX_PROVIDER_LENGTH) {
            throw new InvalidArgumentException('The AI provider name is too long.');
        }

        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;

        $standardQuiz = $this->buildQuiz(
            $course,
            $user,
            $titlePrefix.' - Standard question types',
            0,
            $visibility,
            CQuiz::ALL_ON_ONE_PAGE,
            $topic,
        );
        $adaptiveQuiz = $this->buildQuiz(
            $course,
            $user,
            $titlePrefix.' - Adaptive hotspot delineation',
            1,
            $visibility,
            CQuiz::ONE_PER_PAGE,
            $topic,
        );

        $standardPayloads = $this->buildPayloads($this->questionFactory->standardTypes());
        $adaptivePayloads = $this->buildPayloads($this->questionFactory->adaptiveTypes());

        // Fail before any AI call or write when a platform prerequisite is missing.
        // This catches enable_quiz_scenario and OnlyOffice availability using
        // exactly the same validation as the Vue editor.
        $this->validatePayloads($standardQuiz, $standardPayloads);
        $this->validatePayloads($adaptiveQuiz, $adaptivePayloads);

        $resolvedProvider = null;
        if (null !== $topic) {
            $generated = $this->topicContentGenerator->generate(
                $course,
                $topic,
                $language,
                $aiProvider,
            );
            $resolvedProvider = $generated['provider'];

            $this->topicContentApplicator->apply($standardPayloads, $generated['content']);
            $this->topicContentApplicator->apply($adaptivePayloads, $generated['content']);

            // Revalidate the fully enriched payloads before any persistence.
            $this->validatePayloads($standardQuiz, $standardPayloads);
            $this->validatePayloads($adaptiveQuiz, $adaptivePayloads);
        }

        $result = $this->entityManager->wrapInTransaction(function () use (
            $course,
            $standardQuiz,
            $adaptiveQuiz,
            $standardPayloads,
            $adaptivePayloads,
            $publish,
            $topic,
            $language,
            $resolvedProvider,
        ): array {
            $this->entityManager->persist($standardQuiz);
            $this->entityManager->persist($adaptiveQuiz);
            $this->entityManager->flush();

            $standardQuestions = $this->createQuestions($course, $standardQuiz, $standardPayloads);
            $adaptiveQuestions = $this->createQuestions($course, $adaptiveQuiz, $adaptivePayloads);

            $this->entityManager->flush();

            return [
                'mode' => null === $topic ? 'deterministic' : 'topic',
                'topic' => $topic,
                'language' => $language,
                'ai_provider' => $resolvedProvider,
                'distinct_question_type_count' => \count($this->questionFactory->supportedTypes()),
                'total_question_count' => \count($standardQuestions) + \count($adaptiveQuestions),
                'unsupported_legacy_types' => [
                    [
                        'type' => 7,
                        'reason' => 'Legacy HOT_SPOT_ORDER is not exposed by the current Vue question selector and is not supported by ExerciseQuestionEditorProcessor.',
                    ],
                ],
                'exercises' => [
                    $this->normalizeExercise($course, $standardQuiz, $standardQuestions, $publish),
                    $this->normalizeExercise($course, $adaptiveQuiz, $adaptiveQuestions, $publish),
                ],
            ];
        });

        if (null !== $topic && null !== $resolvedProvider) {
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.(int) $course->getId().':exercise_regression_topic:'.sha1($topic.'|'.$language),
                userId: (int) $user->getId(),
                meta: [
                    'feature' => 'exercise_regression_topic_suite',
                    'provider' => $resolvedProvider,
                    'language' => $language,
                    'question_type_count' => \count($this->questionFactory->supportedTypes()),
                ],
                courseId: (int) $course->getId(),
            );
        }

        return $result;
    }

    /**
     * @param list<int> $types
     *
     * @return list<ExerciseQuestionEditor>
     */
    private function buildPayloads(array $types): array
    {
        return array_map(
            fn (int $type): ExerciseQuestionEditor => $this->questionFactory->create($type),
            $types,
        );
    }

    /**
     * @param list<ExerciseQuestionEditor> $payloads
     */
    private function validatePayloads(CQuiz $quiz, array $payloads): void
    {
        foreach ($payloads as $payload) {
            $this->questionEditorProcessor->validateProgrammaticCreate($quiz, $payload);
        }
    }

    private function buildQuiz(
        Course $course,
        User $user,
        string $title,
        int $feedbackType,
        int $visibility,
        int $displayType,
        ?string $topic,
    ): CQuiz {
        $description = null === $topic
            ? '<p>Deterministic MCP regression fixture for Chamilo Exercises.</p>'
            : '<p>Topic-aware MCP regression suite covering every current Chamilo Exercises question type.</p>';

        return (new CQuiz())
            ->setTitle($title)
            ->setDescription($description)
            ->setSound('')
            ->setAccessCondition('')
            ->setTextWhenFinished('')
            ->setTextWhenFinishedFailure('')
            ->setNotifications('')
            ->setType($displayType)
            ->setRandom(0)
            ->setRandomAnswers(false)
            ->setResultsDisabled(0)
            ->setMaxAttempt(1)
            ->setFeedbackType($feedbackType)
            ->setExpiredTime(0)
            ->setPropagateNeg(0)
            ->setSaveCorrectAnswers(0)
            ->setReviewAnswers(1)
            ->setQuestionSelectionType(1)
            ->setRandomByCategory(0)
            ->setDisplayCategoryName(0)
            ->setPassPercentage(0)
            ->setPreventBackwards(0)
            ->setHideQuestionTitle(false)
            ->setHideQuestionNumber(0)
            ->setShowPreviousButton(true)
            ->setAutoLaunch(false)
            ->setHideAttemptsTable(false)
            ->setPageResultConfiguration([])
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course, null, null, $visibility)
        ;
    }

    /**
     * @param list<ExerciseQuestionEditor> $payloads
     *
     * @return list<array{entity: CQuizQuestion, type: int, label: string}>
     */
    private function createQuestions(Course $course, CQuiz $quiz, array $payloads): array
    {
        $created = [];
        $mediaQuestionId = 0;

        foreach ($payloads as $payload) {
            if (ExerciseRegressionFixtureQuestionFactory::CALCULATED_ANSWER === (int) $payload->type && $mediaQuestionId > 0) {
                $payload->parentMediaId = $mediaQuestionId;
            }

            $question = $this->questionEditorProcessor->createProgrammaticQuestion(
                $quiz,
                $payload,
                $course,
                null,
            );

            $questionId = (int) ($question->getIid() ?? 0);
            if ($questionId <= 0) {
                throw new RuntimeException('Chamilo created an incomplete regression fixture question.');
            }

            if (ExerciseRegressionFixtureQuestionFactory::MEDIA_QUESTION === (int) $payload->type) {
                $mediaQuestionId = $questionId;
            }

            $created[] = [
                'entity' => $question,
                'type' => (int) $payload->type,
                'label' => $this->questionFactory->label((int) $payload->type),
            ];
        }

        return $created;
    }

    /**
     * @param list<array{entity: CQuizQuestion, type: int, label: string}> $questions
     *
     * @return array{
     *     quiz_id: int,
     *     resource_node_id: int,
     *     title: string,
     *     feedback_type: int,
     *     published: bool,
     *     question_count: int,
     *     question_types: list<int>,
     *     questions: list<array{question_id: int, type: int, label: string, title: string, score: float}>,
     *     content_url: string
     * }
     */
    private function normalizeExercise(Course $course, CQuiz $quiz, array $questions, bool $publish): array
    {
        $quizId = (int) ($quiz->getIid() ?? 0);
        $resourceNodeId = (int) ($quiz->getResourceNode()?->getId() ?? 0);
        if ($quizId <= 0 || $resourceNodeId <= 0) {
            throw new RuntimeException('Chamilo created an incomplete regression fixture exercise.');
        }

        $questionRows = [];
        $types = [];
        foreach ($questions as $item) {
            $question = $item['entity'];
            $types[] = (int) $item['type'];
            $questionRows[] = [
                'question_id' => (int) $question->getIid(),
                'type' => (int) $item['type'],
                'label' => (string) $item['label'],
                'title' => $question->getQuestion(),
                'score' => (float) $question->getPonderation(),
            ];
        }

        return [
            'quiz_id' => $quizId,
            'resource_node_id' => $resourceNodeId,
            'title' => $quiz->getTitle(),
            'feedback_type' => (int) $quiz->getFeedbackType(),
            'published' => $publish,
            'question_count' => \count($questionRows),
            'question_types' => $types,
            'questions' => $questionRows,
            'content_url' => '/resources/exercise/'.$resourceNodeId.'/'.$quizId.'/edit?cid='.(int) $course->getId(),
        ];
    }
}

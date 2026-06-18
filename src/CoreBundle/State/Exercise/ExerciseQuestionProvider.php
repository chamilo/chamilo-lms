<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestion;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\Session;
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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<ExerciseQuestion>
 */
final readonly class ExerciseQuestionProvider implements ProviderInterface
{
    private const CSRF_TOKEN_ID = 'exercise_question_action';
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const LP_READ_ONLY_MESSAGE = 'This exercise has been included in a learning path, so it cannot be accessed by students directly from here. If you want to put the same exercise available through the exercises tool, please make a copy of the current exercise using the copy icon.';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizQuestionRepository $questionRepository,
        private CQuizRepository $quizRepository,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestion
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
        $questions = $this->getQuestions($quiz, $course, $session);
        $isLinkedToLearningPath = $this->isExerciseLinkedToLearningPath((int) $quiz->getIid());
        $isReadOnlyFromLearningPath = $isLinkedToLearningPath && !$this->isSettingEnabled('lp.force_edit_exercise_in_lp');

        $response = new ExerciseQuestion();
        $response->exerciseId = $exerciseId;
        $response->title = $quiz->getTitle();
        $response->questions = $questions;
        $response->questionTypes = $this->getQuestionTypes($quiz);
        $response->questionCount = \count($questions);
        $response->totalScore = $this->getTotalScore($questions);
        $response->legacyUrls = [];
        $response->canManage = true;
        $response->isAdaptiveFeedback = 1 === (int) $quiz->getFeedbackType();
        $response->canRecycleQuestions = !$response->isAdaptiveFeedback;
        $response->isLinkedToLearningPath = $isLinkedToLearningPath;
        $response->isReadOnlyFromLearningPath = $isReadOnlyFromLearningPath;
        $response->learningPathReadOnlyMessage = $isLinkedToLearningPath ? self::LP_READ_ONLY_MESSAGE : '';
        $response->csrfToken = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();

        return $response;
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

    private function isExerciseLinkedToLearningPath(int $exerciseId): bool
    {
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
        $value = api_get_setting($settingName);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestions(CQuiz $quiz, Course $course, ?Session $session): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion')
            ->addSelect('question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
                continue;
            }

            $type = (int) $question->getType();
            $items[] = [
                'id' => (int) $question->getIid(),
                'title' => $question->getQuestion(),
                'description' => (string) $question->getDescription(),
                'type' => $type,
                'typeLabel' => $this->getQuestionTypeLabel($type),
                'typeIcon' => $this->getQuestionTypeIcon($type),
                'score' => (float) $question->getPonderation(),
                'position' => (int) $relation->getQuestionOrder(),
                'mandatory' => 1 === (int) $question->getMandatory(),
                'duration' => $question->getDuration(),
                'difficulty' => max(1, (int) $question->getLevel()),
                'categoryLabel' => $this->getFirstCategoryTitle($question),
                'answers' => $this->getAnswers($question),
                'fillBlanks' => $this->getFillBlanksPreview($question),
                'matching' => $this->getMatchingPreview($question),
                'draggable' => $this->getDraggablePreview($question),
                'annotation' => $this->getAnnotationPreview($question, $course, $session),
                'hotspot' => $this->getHotspotPreview($question, $course, $session),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    private function getAnnotationPreview(CQuizQuestion $question, Course $course, ?Session $session): array
    {
        if (20 !== (int) $question->getType()) {
            return [];
        }

        $resourceNode = $question->getResourceNode();
        if (null === $resourceNode) {
            return [];
        }

        $resourceFile = $resourceNode->getResourceFiles()->first();
        if (!$resourceFile instanceof ResourceFile) {
            return [];
        }

        return [
            'imageName' => (string) $resourceFile->getOriginalName(),
            'imageUrl' => $this->appendCourseContextToUrl(
                $this->questionRepository->getHotSpotImageUrl($question),
                $course,
                $session
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getHotspotPreview(CQuizQuestion $question, Course $course, ?Session $session): array
    {
        if (!$this->usesHotspot((int) $question->getType())) {
            return [];
        }

        $imageName = '';
        $imageUrl = '';
        $resourceNode = $question->getResourceNode();
        if (null !== $resourceNode) {
            $resourceFile = $resourceNode->getResourceFiles()->first();
            if ($resourceFile instanceof ResourceFile) {
                $imageName = (string) $resourceFile->getOriginalName();
                $imageUrl = $this->appendCourseContextToUrl(
                    $this->questionRepository->getHotSpotImageUrl($question),
                    $course,
                    $session
                );
            }
        }

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
            if (!$answer instanceof CQuizAnswer || 'noerror' === (string) $answer->getHotspotType()) {
                continue;
            }

            $items[] = [
                'answer' => $answer->getAnswer(),
                'comment' => (string) $answer->getComment(),
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
                'hotspotType' => (string) ($answer->getHotspotType() ?: 'square'),
                'coordinates' => (string) ($answer->getHotspotCoordinates() ?: ''),
            ];
        }

        return [
            'imageName' => $imageName,
            'imageUrl' => $imageUrl,
            'items' => $items,
        ];
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAnswers(CQuizQuestion $question): array
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

        $type = (int) $question->getType();
        if ($this->usesFillBlanks($type) || $this->usesMatching($type) || $this->usesHotspot($type) || 18 === $type) {
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
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'correct' => $this->usesTrueFalseOptions($type) ? 1 === $correctChoice : 1 === $correctValue,
                'correctChoice' => $this->usesTrueFalseOptions($type) ? $correctChoice : null,
                'comment' => (string) $answer->getComment(),
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        return $items;
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

    private function usesTrueFalseOptions(int $type): bool
    {
        return \in_array($type, [11, 12, 22], true);
    }

    private function getFirstCategoryTitle(CQuizQuestion $question): string
    {
        foreach ($question->getCategories() as $category) {
            if ($category instanceof CQuizQuestionCategory) {
                return $category->getTitle();
            }
        }

        return '';
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function getTotalScore(array $questions): float
    {
        $total = 0.0;
        foreach ($questions as $question) {
            $total += (float) ($question['score'] ?? 0.0);
        }

        return $total;
    }


    /**
     * @return array<string, mixed>|null
     */
    private function getDraggablePreview(CQuizQuestion $question): ?array
    {
        if (18 !== (int) $question->getType()) {
            return null;
        }

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

            $targetPosition = (int) ($answer->getCorrect() ?? 0);
            if (0 >= $targetPosition) {
                continue;
            }

            $items[] = [
                'id' => (int) $answer->getIid(),
                'answer' => $answer->getAnswer(),
                'targetPosition' => $targetPosition,
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        return [
            'orientation' => \in_array((string) $question->getExtra(), ['h', 'v'], true) ? (string) $question->getExtra() : 'h',
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getMatchingPreview(CQuizQuestion $question): ?array
    {
        $type = (int) $question->getType();
        if (!$this->usesMatching($type)) {
            return null;
        }

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
                'optionLabel' => isset($optionsByIid[$correct]) ? (string) $optionsByIid[$correct]['label'] : '',
                'optionAnswer' => isset($optionsByIid[$correct]) ? (string) $optionsByIid[$correct]['answer'] : '',
                'comment' => (string) $answer->getComment(),
                'score' => (float) $answer->getPonderation(),
                'position' => (int) $answer->getPosition(),
            ];
        }

        return [
            'options' => $options,
            'pairs' => $pairs,
        ];
    }

    private function getMatchingOptionLabel(int $position): string
    {
        if (1 <= $position && 26 >= $position) {
            return chr(64 + $position);
        }

        return (string) $position;
    }


    /**
     * @return array<string, mixed>|null
     */
    private function getFillBlanksPreview(CQuizQuestion $question): ?array
    {
        $type = (int) $question->getType();
        if (!$this->usesFillBlanks($type)) {
            return null;
        }

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

        if (!$answer instanceof CQuizAnswer) {
            return null;
        }

        $parsed = $this->parseFillBlanksAnswer($answer->getAnswer());

        return [
            'text' => $parsed['text'],
            'items' => $this->buildFillBlankItems($parsed['text'], $parsed['weights'], $parsed['sizes'], $parsed['separator']),
            'separator' => $parsed['separator'],
            'switchable' => $parsed['switchable'],
            'caseInsensitive' => 'case:false' === (string) $question->getExtra(),
            'comment' => (string) $answer->getComment(),
        ];
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
        $items = [];
        foreach ($this->extractFillBlankAnswers($text, $separator) as $index => $blank) {
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

    private function usesHotspot(int $type): bool
    {
        return \in_array($type, [6, 8, 26], true);
    }

    private function usesMatching(int $type): bool
    {
        return \in_array($type, [4, 19, 24, 25], true);
    }

    private function usesFillBlanks(int $type): bool
    {
        return \in_array($type, [3, 27], true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestionTypes(CQuiz $quiz): array
    {
        $feedbackType = (int) $quiz->getFeedbackType();
        $types = [];

        foreach ($this->getLegacyQuestionTypeDefinitions() as $definition) {
            $type = (int) $definition['type'];
            if (!$this->isQuestionTypeVisibleInSelector($type, $feedbackType)) {
                continue;
            }

            if (!$this->isQuestionTypeAllowedByFeedback($type, $feedbackType)) {
                continue;
            }

            $isVueEditor = $this->isVueQuestionEditorType($type);
            $types[] = [
                'type' => $type,
                'label' => $definition['label'],
                'icon' => $definition['icon'],
                'enabled' => true,
                'requiresImmediateFeedback' => false,
                'migratedToVue' => $isVueEditor,
                'editor' => $isVueEditor ? 'vue' : 'legacy',
            ];
        }

        return $types;
    }

    /**
     * @return array<int, array{type: int, label: string, icon: string}>
     */
    private function getLegacyQuestionTypeDefinitions(): array
    {
        return [
            ['type' => 1, 'label' => 'Multiple choice', 'icon' => 'mcua.png'],
            ['type' => 2, 'label' => 'Multiple answer', 'icon' => 'mcma.png'],
            ['type' => 3, 'label' => 'Fill blanks or form', 'icon' => 'fill_in_blanks.png'],
            ['type' => 4, 'label' => 'Matching', 'icon' => 'matching.png'],
            ['type' => 5, 'label' => 'Open question', 'icon' => 'open_answer.png'],
            ['type' => 6, 'label' => 'Image zones', 'icon' => 'hotspot.png'],
            ['type' => 8, 'label' => 'Hotspot delineation', 'icon' => 'hotspot_delineation.png'],
            ['type' => 9, 'label' => 'Exact Selection', 'icon' => 'mcmac.png'],
            ['type' => 10, 'label' => 'Unique answer with unknown', 'icon' => 'mcuao.png'],
            ['type' => 11, 'label' => "Multiple answer true/false/don't know", 'icon' => 'mcmao.png'],
            ['type' => 12, 'label' => "Combination true/false/don't-know", 'icon' => 'mcmaco.png'],
            ['type' => 13, 'label' => 'Oral expression', 'icon' => 'audio_question.png'],
            ['type' => 14, 'label' => 'Global multiple answer', 'icon' => 'mcmagl.png'],
            ['type' => 15, 'label' => 'Media question', 'icon' => 'media.png'],
            ['type' => 16, 'label' => 'Calculated answer', 'icon' => 'calculated_answer.png'],
            ['type' => 17, 'label' => 'Unique answer with images', 'icon' => 'uaimg.png'],
            ['type' => 18, 'label' => 'Sequence ordering', 'icon' => 'ordering.png'],
            ['type' => 19, 'label' => 'Match by dragging', 'icon' => 'matchingdrag.png'],
            ['type' => 20, 'label' => 'Annotation', 'icon' => 'annotation.png'],
            ['type' => 21, 'label' => 'Reading comprehension', 'icon' => 'reading_comprehension.png'],
            ['type' => 22, 'label' => 'Multiple answer true/false with degree of certainty', 'icon' => 'mccert.png'],
            ['type' => 23, 'label' => 'Upload Answer', 'icon' => 'file_upload_question.png'],
            ['type' => 30, 'label' => 'Answer in Office document', 'icon' => 'file_upload_question.png'],
            ['type' => 24, 'label' => 'Matching combination', 'icon' => 'matching_co.png'],
            ['type' => 25, 'label' => 'Matching draggable combination', 'icon' => 'matchingdrag_co.png'],
            ['type' => 26, 'label' => 'Hotspot combination', 'icon' => 'hotspot_co.png'],
            ['type' => 27, 'label' => 'Fill in blanks combination', 'icon' => 'fill_in_blanks_co.png'],
            ['type' => 28, 'label' => 'Multiple Answer Dropdown Combination', 'icon' => 'mcma_dropdown_co.png'],
            ['type' => 29, 'label' => 'Multiple Answer Dropdown', 'icon' => 'mcma_dropdown.png'],
            ['type' => 31, 'label' => 'Page break', 'icon' => 'page_end.png'],
        ];
    }


    private function isQuestionTypeVisibleInSelector(int $type, int $feedbackType): bool
    {
        if (8 === $type) {
            return 1 === $feedbackType && $this->isSettingEnabled('enable_quiz_scenario');
        }

        if (30 === $type) {
            return 1 !== $feedbackType && $this->isOnlyofficePluginEnabled();
        }

        return true;
    }

    private function isQuestionTypeAllowedByFeedback(int $type, int $feedbackType): bool
    {
        if (1 === $feedbackType) {
            // Legacy question_create.php only allows Unique answer and Hotspot delineation
            // in direct-feedback/adaptive exercises.
            return \in_array($type, [1, 8], true);
        }

        if (3 === $feedbackType) {
            return \in_array($type, [1, 2, 16, 18], true);
        }

        return true;
    }

    private function isVueQuestionEditorType(int $type): bool
    {
        return \in_array($type, [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31], true);
    }


    private function isOnlyofficePluginEnabled(): bool
    {
        try {
            if (!\class_exists('OnlyofficePlugin')) {
                $pluginPath = api_get_path(SYS_PLUGIN_PATH).'Onlyoffice/lib/onlyofficePlugin.php';
                if (is_file($pluginPath)) {
                    require_once $pluginPath;
                }
            }

            if (!\class_exists('OnlyofficePlugin')) {
                return false;
            }

            $plugin = \OnlyofficePlugin::create();
            if (method_exists($plugin, 'isEnabledForCurrentAccessUrl')) {
                return (bool) $plugin->isEnabledForCurrentAccessUrl();
            }

            return 'true' === (string) $plugin->get('enable_onlyoffice_plugin');
        } catch (\Throwable) {
            return false;
        }
    }

    private function getQuestionTypeLabel(int $type): string
    {
        foreach ($this->getLegacyQuestionTypeDefinitions() as $definition) {
            if ($type === $definition['type']) {
                return $definition['label'];
            }
        }

        return 'Question';
    }

    private function getQuestionTypeIcon(int $type): string
    {
        foreach ($this->getLegacyQuestionTypeDefinitions() as $definition) {
            if ($type === $definition['type']) {
                return $definition['icon'];
            }
        }

        return 'quiz.png';
    }
}

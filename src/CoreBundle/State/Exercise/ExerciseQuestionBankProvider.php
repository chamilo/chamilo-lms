<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionBank;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<ExerciseQuestionBank>
 */
final readonly class ExerciseQuestionBankProvider implements ProviderInterface
{
    public const CSRF_TOKEN_ID = 'exercise_question_bank_action';

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionBank
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage the question bank in this context.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        $quiz = 0 < $exerciseId ? $this->getExerciseFromCurrentContext($exerciseId, $course, $session) : null;
        if ($quiz instanceof CQuiz && $this->isAdaptiveFeedbackExercise($quiz)) {
            throw new AccessDeniedHttpException('Question recycling is not available in adaptive/direct feedback exercises.');
        }

        $filters = $this->getFilters($request);
        $page = max(1, (int) $filters['page']);
        $itemsPerPage = min(100, max(5, (int) $filters['itemsPerPage']));
        $existingQuestionIds = $quiz instanceof CQuiz ? $this->getExistingQuestionIds($quiz) : [];
        $totalItems = $this->countQuestions($quiz, $course, $session, $filters);
        $items = $this->getQuestions($quiz, $course, $session, $filters, $existingQuestionIds, $page, $itemsPerPage);

        $response = new ExerciseQuestionBank();
        $response->exerciseId = $exerciseId;
        $response->title = $quiz instanceof CQuiz ? $quiz->getTitle() : 'Recycle existing questions';
        $response->items = $items;
        $response->categoryOptions = $this->getCategoryOptions($course, $session);
        $response->exerciseOptions = $this->getExerciseOptions($quiz, $course, $session);
        $response->difficultyOptions = $this->getDifficultyOptions();
        $response->questionTypeOptions = $this->getQuestionTypeOptions($quiz instanceof CQuiz ? (int) $quiz->getFeedbackType() : 0);
        $response->filters = $filters;
        $response->legacyUrls = [];
        $response->page = $page;
        $response->itemsPerPage = $itemsPerPage;
        $response->totalItems = $totalItems;
        $response->csrfToken = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();
        $response->canManage = true;
        $response->globalMode = !($quiz instanceof CQuiz);
        $response->canDelete = $this->canRunRestrictedAction();

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

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!($quiz instanceof CQuiz)) {
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

        $this->applySessionFilter($queryBuilder, 'links', $session);

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<string, mixed>
     */
    private function getFilters(Request $request): array
    {
        return [
            'page' => max(1, $request->query->getInt('page', 1)),
            'itemsPerPage' => max(5, $request->query->getInt('itemsPerPage', 20)),
            'search' => trim((string) $request->query->get('search', '')),
            'categoryId' => $request->query->getInt('categoryId'),
            'sourceExerciseId' => $request->query->getInt('sourceExerciseId'),
            'difficulty' => $request->query->getInt('difficulty', -1),
            'questionType' => $request->query->getInt('questionType', -1),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function getExistingQuestionIds(CQuiz $quiz): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(relQuestion.question) AS questionId')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(static fn (array $row): int => (int) $row['questionId'], $rows);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function countQuestions(?CQuiz $quiz, Course $course, ?Session $session, array $filters): int
    {
        $queryBuilder = $this->createQuestionQueryBuilder($quiz, $course, $session, $filters);
        $queryBuilder->select('COUNT(DISTINCT question.iid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, int>     $existingQuestionIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function getQuestions(
        ?CQuiz $quiz,
        Course $course,
        ?Session $session,
        array $filters,
        array $existingQuestionIds,
        int $page,
        int $itemsPerPage
    ): array {
        $queryBuilder = $this->createQuestionQueryBuilder($quiz, $course, $session, $filters);
        $queryBuilder
            ->select('DISTINCT question')
            ->addOrderBy('question.question', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $question) {
            if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
                continue;
            }

            $questionId = (int) $question->getIid();
            $type = (int) $question->getType();
            $alreadyInExercise = \in_array($questionId, $existingQuestionIds, true);
            $allowedByFeedback = $quiz instanceof CQuiz ? $this->isQuestionTypeAllowedByFeedback($type, (int) $quiz->getFeedbackType()) : true;
            $usedInActiveQuiz = $this->isQuestionUsedInActiveQuiz($question, $course, $session);
            $items[] = [
                'id' => $questionId,
                'title' => $question->getQuestion(),
                'description' => (string) $question->getDescription(),
                'type' => $type,
                'typeLabel' => $this->getQuestionTypeLabel($type),
                'typeIcon' => $this->getQuestionTypeIcon($type),
                'categoryLabel' => $this->getFirstCategoryTitle($question),
                'difficulty' => max(1, (int) $question->getLevel()),
                'score' => (float) $question->getPonderation(),
                'alreadyInExercise' => $alreadyInExercise,
                'usedInActiveTest' => $usedInActiveQuiz,
                'canReuse' => $quiz instanceof CQuiz && !$alreadyInExercise && $allowedByFeedback,
                'canEdit' => !($quiz instanceof CQuiz) && !$usedInActiveQuiz,
                'canDelete' => !($quiz instanceof CQuiz) && !$usedInActiveQuiz && $this->canRunRestrictedAction(),
                'blockedReason' => $allowedByFeedback ? '' : 'This question type is not compatible with the current feedback mode.',
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function createQuestionQueryBuilder(?CQuiz $quiz, Course $course, ?Session $session, array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(CQuizQuestion::class, 'question')
            ->leftJoin('question.resourceNode', 'questionNode')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
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

        $this->applyQuestionFilters($queryBuilder, $quiz, $course, $session, $filters);

        if (null !== $session) {
            $queryBuilder->setParameter('sessionId', (int) $session->getId(), Types::INTEGER);
        }

        return $queryBuilder;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyQuestionFilters(QueryBuilder $queryBuilder, ?CQuiz $quiz, Course $course, ?Session $session, array $filters): void
    {
        $categoryId = (int) ($filters['categoryId'] ?? 0);
        if (0 < $categoryId) {
            $queryBuilder
                ->innerJoin('question.categories', 'category')
                ->andWhere('category.iid = :categoryId')
                ->setParameter('categoryId', $categoryId, Types::INTEGER)
            ;
        }

        $difficulty = (int) ($filters['difficulty'] ?? -1);
        if (-1 !== $difficulty) {
            $queryBuilder
                ->andWhere('question.level = :difficulty')
                ->setParameter('difficulty', $difficulty, Types::INTEGER)
            ;
        }

        $questionType = (int) ($filters['questionType'] ?? -1);
        if (0 < $questionType) {
            $queryBuilder
                ->andWhere('question.type = :questionType')
                ->setParameter('questionType', $questionType, Types::INTEGER)
            ;
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ('' !== $search) {
            if (ctype_digit($search)) {
                $queryBuilder
                    ->andWhere('(question.iid = :searchId OR question.question LIKE :searchText OR question.description LIKE :searchText)')
                    ->setParameter('searchId', (int) $search, Types::INTEGER)
                    ->setParameter('searchText', '%'.$search.'%', Types::STRING)
                ;
            } else {
                $queryBuilder
                    ->andWhere('(question.question LIKE :searchText OR question.description LIKE :searchText)')
                    ->setParameter('searchText', '%'.$search.'%', Types::STRING)
                ;
            }
        }

        $sourceExerciseId = (int) ($filters['sourceExerciseId'] ?? 0);
        if (0 < $sourceExerciseId) {
            $this->applySourceExerciseFilter($queryBuilder, $sourceExerciseId, $course, $session);
        } elseif (-1 === $sourceExerciseId) {
            $this->applyOrphanQuestionFilter($queryBuilder, $course, $session);
        }
    }

    private function applySourceExerciseFilter(QueryBuilder $queryBuilder, int $sourceExerciseId, Course $course, ?Session $session): void
    {
        $sourceFilter = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(CQuizRelQuestion::class, 'sourceRelation')
            ->innerJoin('sourceRelation.quiz', 'sourceQuiz')
            ->innerJoin('sourceQuiz.resourceNode', 'sourceNode')
            ->innerJoin('sourceNode.resourceLinks', 'sourceLinks')
            ->where('sourceRelation.question = question')
            ->andWhere('sourceQuiz.iid = :sourceExerciseId')
            ->andWhere('IDENTITY(sourceLinks.course) = :courseId')
        ;
        $this->applyActiveLinkConstraints($sourceFilter, 'sourceLinks', $session, true);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->exists($sourceFilter->getDQL()))
            ->setParameter('sourceExerciseId', $sourceExerciseId, Types::INTEGER)
        ;
    }

    private function applyOrphanQuestionFilter(QueryBuilder $queryBuilder, Course $course, ?Session $session): void
    {
        $activeQuizQuestion = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(CQuizRelQuestion::class, 'orphanRelation')
            ->innerJoin('orphanRelation.quiz', 'orphanQuiz')
            ->innerJoin('orphanQuiz.resourceNode', 'orphanNode')
            ->innerJoin('orphanNode.resourceLinks', 'orphanLinks')
            ->where('orphanRelation.question = question')
            ->andWhere('IDENTITY(orphanLinks.course) = :courseId')
        ;
        $this->applyActiveLinkConstraints($activeQuizQuestion, 'orphanLinks', $session, true);

        $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($activeQuizQuestion->getDQL())));
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

    /**
     * @return array<int, array<string, mixed>>
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

        $this->applySessionFilter($queryBuilder, 'links', $session);

        if (null !== $session) {
            $queryBuilder->setParameter('sessionId', (int) $session->getId(), Types::INTEGER);
        }

        $items = [['value' => 0, 'label' => 'All']];
        foreach ($queryBuilder->getQuery()->getResult() as $category) {
            if ($category instanceof CQuizQuestionCategory && null !== $category->getIid()) {
                $items[] = ['value' => (int) $category->getIid(), 'label' => $category->getTitle()];
            }
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExerciseOptions(?CQuiz $currentQuiz, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('quiz.title', 'ASC')
        ;

        $this->applySessionFilter($queryBuilder, 'links', $session);
        if (null !== $session) {
            $queryBuilder->setParameter('sessionId', (int) $session->getId(), Types::INTEGER);
        }

        $items = [
            ['value' => 0, 'label' => 'All tests'],
            ['value' => -1, 'label' => 'Orphan questions'],
        ];
        foreach ($queryBuilder->getQuery()->getResult() as $quiz) {
            if ($quiz instanceof CQuiz && null !== $quiz->getIid()) {
                $label = $quiz->getTitle();
                if ($currentQuiz instanceof CQuiz && (int) $quiz->getIid() === (int) $currentQuiz->getIid()) {
                    $label = '> '.$label;
                }
                $items[] = ['value' => (int) $quiz->getIid(), 'label' => $label];
            }
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDifficultyOptions(): array
    {
        $items = [['value' => -1, 'label' => 'All']];
        for ($level = 0; $level <= 5; $level++) {
            $items[] = ['value' => $level, 'label' => (string) $level];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestionTypeOptions(int $feedbackType): array
    {
        $items = [['value' => -1, 'label' => 'All']];
        foreach ($this->getQuestionTypeDefinitions() as $definition) {
            $type = (int) $definition['type'];
            if (8 === $type || !$this->isQuestionTypeAllowedByFeedback($type, $feedbackType)) {
                continue;
            }

            $items[] = ['value' => $type, 'label' => $definition['label']];
        }

        return $items;
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

    private function isQuestionTypeAllowedByFeedback(int $type, int $feedbackType): bool
    {
        if (1 === $feedbackType) {
            return \in_array($type, [1, 8], true);
        }

        if (3 === $feedbackType) {
            return \in_array($type, [1, 2, 16, 18], true);
        }

        return true;
    }


    private function isAdaptiveFeedbackExercise(CQuiz $quiz): bool
    {
        return 1 === (int) $quiz->getFeedbackType();
    }

    private function canRunRestrictedAction(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access');
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function isQuestionUsedInActiveQuiz(CQuizQuestion $question, Course $course, ?Session $session): bool
    {
        $questionId = (int) ($question->getIid() ?? 0);
        if (0 >= $questionId) {
            return false;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('relQuestion.iid')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.quiz', 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        $this->applySessionFilter($queryBuilder, 'links', $session, true);
        if (null !== $session) {
            $queryBuilder->setParameter('sessionId', (int) $session->getId(), Types::INTEGER);
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function getQuestionTypeLabel(int $type): string
    {
        foreach ($this->getQuestionTypeDefinitions() as $definition) {
            if ($type === (int) $definition['type']) {
                return $definition['label'];
            }
        }

        return 'Question';
    }

    private function getQuestionTypeIcon(int $type): string
    {
        foreach ($this->getQuestionTypeDefinitions() as $definition) {
            if ($type === (int) $definition['type']) {
                return $definition['icon'];
            }
        }

        return 'quiz.png';
    }

    /**
     * @return array<int, array{type: int, label: string, icon: string}>
     */
    private function getQuestionTypeDefinitions(): array
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
            ['type' => 24, 'label' => 'Matching combination', 'icon' => 'matching_co.png'],
            ['type' => 25, 'label' => 'Matching draggable combination', 'icon' => 'matchingdrag_co.png'],
            ['type' => 26, 'label' => 'Hotspot combination', 'icon' => 'hotspot_co.png'],
            ['type' => 27, 'label' => 'Fill in blanks combination', 'icon' => 'fill_in_blanks_co.png'],
            ['type' => 28, 'label' => 'Multiple Answer Dropdown Combination', 'icon' => 'mcma_dropdown_co.png'],
            ['type' => 29, 'label' => 'Multiple Answer Dropdown', 'icon' => 'mcma_dropdown.png'],
            ['type' => 31, 'label' => 'Page break', 'icon' => 'page_end.png'],
        ];
    }


}

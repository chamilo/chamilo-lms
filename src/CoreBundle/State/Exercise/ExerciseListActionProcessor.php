<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
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
 * @implements ProcessorInterface<ExerciseList, ExerciseList>
 */
final readonly class ExerciseListActionProcessor implements ProcessorInterface
{
    private const CSRF_TOKEN_ID = 'exercise_list_action';
    private const ACTION_COPY = 'copy';
    private const ACTION_DELETE = 'delete';
    private const ACTION_TOGGLE_VISIBILITY = 'toggle_visibility';
    private const ACTION_TOGGLE_AUTO_LAUNCH = 'toggle_auto_launch';
    private const ACTION_CLEAN_RESULTS = 'clean_results';
    private const ACTION_CLEAN_ALL_RESULTS = 'clean_all_results';
    private const ACTION_BULK_ACTIVATE = 'bulk_activate';
    private const ACTION_BULK_DEACTIVATE = 'bulk_deactivate';
    private const ACTION_BULK_DELETE = 'bulk_delete';
    private const VISIBILITY_PUBLISHED = 2;
    private const LINK_TYPE_EXERCISE = 1;
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const MATCHING = 4;
    private const MATCHING_DRAGGABLE = 19;
    private const MATCHING_DRAGGABLE_COMBINATION = 25;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private CQuizQuestionRepository $questionRepository,
        private ResourceLinkRepository $resourceLinkRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseList
    {
        if (!$data instanceof ExerciseList) {
            throw new BadRequestHttpException('Invalid exercise action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercises in this context.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $action = strtolower(trim($data->action));

        if (self::ACTION_CLEAN_ALL_RESULTS === $action) {
            $message = $this->cleanAllResults($course, $session);
            $this->entityManager->flush();

            $response = new ExerciseList();
            $response->exerciseId = 0;
            $response->action = $action;
            $response->success = true;
            $response->message = $message;

            return $response;
        }

        if ($this->isBulkAction($action)) {
            $response = $this->runBulkAction($action, $data->exerciseIds, $course, $session);
            $this->entityManager->flush();

            return $response;
        }

        $exerciseId = (int) $data->exerciseId;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);

        $message = match ($action) {
            self::ACTION_COPY => $this->copyExercise($quiz, $course, $session),
            self::ACTION_DELETE => $this->deleteExercise($quiz, $course, $session),
            self::ACTION_TOGGLE_VISIBILITY => $this->toggleVisibility($quiz, $course, $session),
            self::ACTION_TOGGLE_AUTO_LAUNCH => $this->toggleAutoLaunch($quiz, $course, $session),
            self::ACTION_CLEAN_RESULTS => $this->cleanExerciseResults($quiz, $course, $session),
            default => throw new BadRequestHttpException('Unsupported exercise action.'),
        };

        $this->entityManager->flush();

        $response = new ExerciseList();
        $response->exerciseId = $exerciseId;
        $response->action = $action;
        $response->success = true;
        $response->message = $message;

        return $response;
    }

    private function isBulkAction(string $action): bool
    {
        return \in_array(
            $action,
            [
                self::ACTION_BULK_ACTIVATE,
                self::ACTION_BULK_DEACTIVATE,
                self::ACTION_BULK_DELETE,
            ],
            true,
        );
    }

    /**
     * @param array<int, int|string> $exerciseIds
     */
    private function runBulkAction(string $action, array $exerciseIds, Course $course, ?Session $session): ExerciseList
    {
        $ids = $this->normalizeExerciseIds($exerciseIds);
        if ([] === $ids) {
            throw new BadRequestHttpException('At least one exercise must be selected.');
        }

        $processed = 0;
        $skipped = 0;

        foreach ($ids as $exerciseId) {
            try {
                $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
                $this->runBulkActionForExercise($action, $quiz, $course, $session);
                ++$processed;
            } catch (AccessDeniedHttpException|BadRequestHttpException|NotFoundHttpException) {
                ++$skipped;
            }
        }

        $response = new ExerciseList();
        $response->exerciseId = 0;
        $response->exerciseIds = $ids;
        $response->action = $action;
        $response->success = true;
        $response->processedCount = $processed;
        $response->skippedCount = $skipped;
        $response->message = $this->buildBulkActionMessage($action, $processed, $skipped);

        return $response;
    }

    private function runBulkActionForExercise(string $action, CQuiz $quiz, Course $course, ?Session $session): void
    {
        match ($action) {
            self::ACTION_BULK_ACTIVATE => $this->setExerciseVisibility($quiz, $course, $session, true),
            self::ACTION_BULK_DEACTIVATE => $this->setExerciseVisibility($quiz, $course, $session, false),
            self::ACTION_BULK_DELETE => $this->deleteExercise($quiz, $course, $session),
            default => throw new BadRequestHttpException('Unsupported bulk exercise action.'),
        };
    }

    /**
     * @param array<int, int|string> $exerciseIds
     *
     * @return array<int, int>
     */
    private function normalizeExerciseIds(array $exerciseIds): array
    {
        $ids = [];
        foreach ($exerciseIds as $exerciseId) {
            $id = (int) $exerciseId;
            if (0 < $id) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function buildBulkActionMessage(string $action, int $processed, int $skipped): string
    {
        $label = match ($action) {
            self::ACTION_BULK_DELETE => 'exercises deleted',
            default => 'exercises updated',
        };

        return \sprintf('%d %s (%d skipped)', $processed, $label, $skipped);
    }

    private function setExerciseVisibility(CQuiz $quiz, Course $course, ?Session $session, bool $visible): void
    {
        if (!$this->canRunRestrictedAction()) {
            throw new AccessDeniedHttpException('You are not allowed to change this exercise visibility.');
        }

        if ($this->isExerciseReadOnlyFromLearningPath((int) $quiz->getIid())) {
            throw new AccessDeniedHttpException('This exercise visibility is managed from the learning path.');
        }

        if ($visible) {
            $this->quizRepository->setVisibilityPublished($quiz, $course, $session);

            return;
        }

        $this->quizRepository->setVisibilityDraft($quiz, $course, $session);
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

    private function canRunRestrictedAction(): bool
    {
        if (!$this->isSettingEnabled('exercise.limit_exercise_teacher_access')) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canCleanResults(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access')
            && !$this->isSettingEnabled('exercise.disable_clean_exercise_results_for_teachers');
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
            ->select('quiz.iid AS exerciseId')
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

    private function copyExercise(CQuiz $sourceQuiz, Course $course, ?Session $session): string
    {
        $newQuiz = new CQuiz();
        $this->copyExerciseFields($sourceQuiz, $newQuiz);
        $newQuiz
            ->setTitle(trim($sourceQuiz->getTitle()).' - Copy')
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->quizRepository->create($newQuiz);
        $this->entityManager->flush();

        $order = 1;
        foreach ($this->getQuestionRelations($sourceQuiz) as $relation) {
            $newQuestion = $this->duplicateQuestion($relation->getQuestion(), $course, $session, $order);
            $newRelation = new CQuizRelQuestion();
            $newRelation
                ->setQuiz($newQuiz)
                ->setQuestion($newQuestion)
                ->setQuestionOrder($order)
                ->setDestination($relation->getDestination())
            ;
            $this->entityManager->persist($newRelation);
            ++$order;
        }

        return 'Exercise copied';
    }

    private function copyExerciseFields(CQuiz $sourceQuiz, CQuiz $newQuiz): void
    {
        $newQuiz
            ->setDescription((string) $sourceQuiz->getDescription())
            ->setSound((string) $sourceQuiz->getSound())
            ->setType((int) $sourceQuiz->getType())
            ->setRandom((int) $sourceQuiz->getRandom())
            ->setRandomAnswers($sourceQuiz->getRandomAnswers())
            ->setResultsDisabled((int) $sourceQuiz->getResultsDisabled())
            ->setAccessCondition((string) $sourceQuiz->getAccessCondition())
            ->setMaxAttempt((int) $sourceQuiz->getMaxAttempt())
            ->setFeedbackType((int) $sourceQuiz->getFeedbackType())
            ->setExpiredTime((int) $sourceQuiz->getExpiredTime())
            ->setPropagateNeg((int) $sourceQuiz->getPropagateNeg())
            ->setSaveCorrectAnswers((int) ($sourceQuiz->getSaveCorrectAnswers() ?? 0))
            ->setReviewAnswers((int) $sourceQuiz->getReviewAnswers())
            ->setRandomByCategory((int) $sourceQuiz->getRandomByCategory())
            ->setTextWhenFinished((string) $sourceQuiz->getTextWhenFinished())
            ->setTextWhenFinishedFailure((string) $sourceQuiz->getTextWhenFinishedFailure())
            ->setDisplayCategoryName((int) $sourceQuiz->getDisplayCategoryName())
            ->setPassPercentage((int) ($sourceQuiz->getPassPercentage() ?? 0))
            ->setPreventBackwards((int) $sourceQuiz->getPreventBackwards())
            ->setQuestionSelectionType((int) ($sourceQuiz->getQuestionSelectionType() ?? 0))
            ->setHideQuestionNumber((int) ($sourceQuiz->getHideQuestionNumber() ?? 0))
            ->setHideQuestionTitle($sourceQuiz->isHideQuestionTitle())
            ->setShowPreviousButton($sourceQuiz->isShowPreviousButton())
            ->setNotifications($sourceQuiz->getNotifications())
            ->setAutoLaunch($sourceQuiz->isAutoLaunch())
            ->setHideAttemptsTable($sourceQuiz->isHideAttemptsTable())
            ->setPageResultConfiguration($sourceQuiz->getPageResultConfiguration())
            ->setDisplayChartDegreeCertainty($sourceQuiz->getDisplayChartDegreeCertainty())
            ->setSendEmailChartDegreeCertainty($sourceQuiz->getSendEmailChartDegreeCertainty())
            ->setNotDisplayBalancePercentageCategorieQuestion($sourceQuiz->getNotDisplayBalancePercentageCategorieQuestion())
            ->setDisplayChartDegreeCertaintyCategory($sourceQuiz->getDisplayChartDegreeCertaintyCategory())
            ->setGatherQuestionsCategories($sourceQuiz->getGatherQuestionsCategories())
            ->setDuration($sourceQuiz->getDuration())
        ;

        if (null !== $sourceQuiz->getStartTime()) {
            $newQuiz->setStartTime(clone $sourceQuiz->getStartTime());
        }

        if (null !== $sourceQuiz->getEndTime()) {
            $newQuiz->setEndTime(clone $sourceQuiz->getEndTime());
        }

        if (null !== $sourceQuiz->getQuizCategory()) {
            $newQuiz->setQuizCategory($sourceQuiz->getQuizCategory());
        }
    }

    private function deleteExercise(CQuiz $quiz, Course $course, ?Session $session): string
    {
        if (!$this->canRunRestrictedAction()) {
            throw new AccessDeniedHttpException('You are not allowed to delete this exercise.');
        }

        if ($this->isGradebookLocked((int) $quiz->getIid(), $course)) {
            throw new AccessDeniedHttpException('This exercise is locked by the gradebook.');
        }

        $this->resourceLinkRepository->removeByResourceInContext($quiz, $course, $session);

        return 'Exercise deleted';
    }

    private function toggleVisibility(CQuiz $quiz, Course $course, ?Session $session): string
    {
        if (!$this->canRunRestrictedAction()) {
            throw new AccessDeniedHttpException('You are not allowed to change this exercise visibility.');
        }

        if ($this->isExerciseReadOnlyFromLearningPath((int) $quiz->getIid())) {
            throw new AccessDeniedHttpException('This exercise visibility is managed from the learning path.');
        }

        if ($this->isExerciseVisible($quiz, $course, $session)) {
            $this->quizRepository->setVisibilityDraft($quiz, $course, $session);

            return 'Exercise hidden';
        }

        $this->quizRepository->setVisibilityPublished($quiz, $course, $session);

        return 'Exercise visible';
    }

    private function toggleAutoLaunch(CQuiz $quiz, Course $course, ?Session $session): string
    {
        if ($quiz->isAutoLaunch()) {
            $quiz->setAutoLaunch(false);
            $this->entityManager->persist($quiz);

            return 'Autolaunch disabled';
        }

        foreach ($this->getExercisesFromCurrentContext($course, $session) as $currentQuiz) {
            $currentQuiz->setAutoLaunch(false);
            $this->entityManager->persist($currentQuiz);
        }

        $quiz->setAutoLaunch(true);
        $this->entityManager->persist($quiz);

        return 'Autolaunch enabled';
    }

    private function cleanExerciseResults(CQuiz $quiz, Course $course, ?Session $session): string
    {
        if (!$this->canCleanResults()) {
            throw new AccessDeniedHttpException('Cleaning exercise results is not allowed.');
        }

        $exerciseId = (int) $quiz->getIid();
        if ($this->isGradebookLocked($exerciseId, $course)) {
            throw new AccessDeniedHttpException('This exercise is locked by the gradebook.');
        }

        $attempts = $this->getAttemptsForExercises([$exerciseId], $course, $session);
        foreach ($attempts as $attempt) {
            $this->entityManager->remove($attempt);
        }

        return trim($quiz->getTitle()).': '.\count($attempts).' results cleaned';
    }

    private function isExerciseVisible(CQuiz $quiz, Course $course, ?Session $session): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('links.visibility')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
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

        return self::VISIBILITY_PUBLISHED === (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function cleanAllResults(Course $course, ?Session $session): string
    {
        if (!$this->canCleanResults()) {
            throw new AccessDeniedHttpException('Cleaning exercise results is not allowed.');
        }

        $exerciseIds = array_values(array_filter(
            $this->getExerciseIdsFromCurrentContext($course, $session),
            fn (int $exerciseId): bool => !$this->isGradebookLocked($exerciseId, $course),
        ));

        if ([] === $exerciseIds) {
            return '0 results cleaned';
        }

        $attempts = $this->getAttemptsForExercises($exerciseIds, $course, $session);
        foreach ($attempts as $attempt) {
            $this->entityManager->remove($attempt);
        }

        return \count($attempts).' results cleaned';
    }

    /**
     * @return array<int, CQuiz>
     */
    private function getExercisesFromCurrentContext(Course $course, ?Session $session): array
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
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        return array_values(array_filter(
            $queryBuilder->getQuery()->getResult(),
            static fn (mixed $quiz): bool => $quiz instanceof CQuiz,
        ));
    }

    /**
     * @return array<int, int>
     */
    private function getExerciseIdsFromCurrentContext(Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid AS exerciseId')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        return array_values(array_unique(array_map(
            static fn (mixed $exerciseId): int => (int) $exerciseId,
            array_column($queryBuilder->getQuery()->getScalarResult(), 'exerciseId'),
        )));
    }

    /**
     * @param array<int, int> $exerciseIds
     *
     * @return array<int, TrackEExercise>
     */
    private function getAttemptsForExercises(array $exerciseIds, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) IN (:exerciseIds)')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('exerciseIds', $exerciseIds, ArrayParameterType::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        return array_values(array_filter(
            $queryBuilder->getQuery()->getResult(),
            static fn (mixed $attempt): bool => $attempt instanceof TrackEExercise,
        ));
    }

    private function isGradebookLocked(int $exerciseId, Course $course): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if (!$this->isSettingEnabled('gradebook.gradebook_locking_enabled')) {
            return false;
        }

        $lockedLink = $this->entityManager->createQueryBuilder()
            ->select('link.id')
            ->from(GradebookLink::class, 'link')
            ->andWhere('link.locked = :locked')
            ->andWhere('link.refId = :exerciseId')
            ->andWhere('link.type = :linkType')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->setParameter('locked', 1, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('linkType', self::LINK_TYPE_EXERCISE, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $lockedLink;
    }

    private function duplicateQuestion(CQuizQuestion $sourceQuestion, Course $course, ?Session $session, int $position): CQuizQuestion
    {
        $newQuestion = new CQuizQuestion();
        $newQuestion
            ->setQuestion(trim($sourceQuestion->getQuestion()).' - Copy')
            ->setDescription($sourceQuestion->getDescription())
            ->setFeedback($sourceQuestion->getFeedback())
            ->setType((int) $sourceQuestion->getType())
            ->setLevel(max(1, (int) $sourceQuestion->getLevel()))
            ->setPosition($position)
            ->setPonderation((float) $sourceQuestion->getPonderation())
            ->setMandatory((int) $sourceQuestion->getMandatory())
            ->setDuration($sourceQuestion->getDuration())
            ->setParentMediaId($sourceQuestion->getParentMediaId())
            ->setExtra($sourceQuestion->getExtra())
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        foreach ($sourceQuestion->getCategories() as $category) {
            if ($category instanceof CQuizQuestionCategory) {
                $newQuestion->addCategory($category);
            }
        }

        $this->questionRepository->create($newQuestion);

        if ($this->isMatchingQuestion((int) $sourceQuestion->getType())) {
            $this->copyMatchingAnswers($sourceQuestion, $newQuestion);

            return $newQuestion;
        }

        $optionIidMap = $this->copyQuestionOptions($sourceQuestion, $newQuestion);
        foreach ($this->getAnswers($sourceQuestion) as $sourceAnswer) {
            $sourceCorrect = (int) $sourceAnswer->getCorrect();
            $answer = $this->cloneAnswer($sourceAnswer, $newQuestion, (int) ($optionIidMap[$sourceCorrect] ?? $sourceCorrect));
            $this->entityManager->persist($answer);
        }

        return $newQuestion;
    }

    private function isMatchingQuestion(int $type): bool
    {
        return \in_array($type, [self::MATCHING, self::MATCHING_DRAGGABLE, self::MATCHING_DRAGGABLE_COMBINATION], true);
    }

    private function copyMatchingAnswers(CQuizQuestion $sourceQuestion, CQuizQuestion $newQuestion): void
    {
        $sourceAnswers = $this->getAnswers($sourceQuestion);
        $optionIidMap = [];

        foreach ($sourceAnswers as $sourceAnswer) {
            if (0 < (int) $sourceAnswer->getCorrect()) {
                continue;
            }

            $newOption = $this->cloneAnswer($sourceAnswer, $newQuestion, 0);
            $this->entityManager->persist($newOption);
            $this->entityManager->flush();

            if (null !== $sourceAnswer->getIid() && null !== $newOption->getIid()) {
                $optionIidMap[(int) $sourceAnswer->getIid()] = (int) $newOption->getIid();
            }
        }

        foreach ($sourceAnswers as $sourceAnswer) {
            $sourceCorrect = (int) $sourceAnswer->getCorrect();
            if (0 >= $sourceCorrect) {
                continue;
            }

            $newPair = $this->cloneAnswer($sourceAnswer, $newQuestion, (int) ($optionIidMap[$sourceCorrect] ?? 0));
            $this->entityManager->persist($newPair);
        }
    }

    private function cloneAnswer(CQuizAnswer $sourceAnswer, CQuizQuestion $newQuestion, int $correct): CQuizAnswer
    {
        $answer = new CQuizAnswer();
        $answer
            ->setQuestion($newQuestion)
            ->setAnswer($sourceAnswer->getAnswer())
            ->setCorrect($correct)
            ->setComment((string) $sourceAnswer->getComment())
            ->setPonderation((float) $sourceAnswer->getPonderation())
            ->setPosition((int) $sourceAnswer->getPosition())
        ;

        if (null !== $sourceAnswer->getHotspotCoordinates()) {
            $answer->setHotspotCoordinates($sourceAnswer->getHotspotCoordinates());
        }

        if (null !== $sourceAnswer->getHotspotType()) {
            $answer->setHotspotType($sourceAnswer->getHotspotType());
        }

        if (null !== $sourceAnswer->getAnswerCode()) {
            $answer->setAnswerCode($sourceAnswer->getAnswerCode());
        }

        return $answer;
    }

    /**
     * @return array<int, int>
     */
    private function copyQuestionOptions(CQuizQuestion $sourceQuestion, CQuizQuestion $newQuestion): array
    {
        $optionIidMap = [];
        foreach ($sourceQuestion->getOptions() as $sourceOption) {
            if (!$sourceOption instanceof CQuizQuestionOption) {
                continue;
            }

            $newOption = new CQuizQuestionOption();
            $newOption
                ->setQuestion($newQuestion)
                ->setTitle((string) $sourceOption->getTitle())
                ->setPosition((int) $sourceOption->getPosition())
            ;
            $this->entityManager->persist($newOption);
            $this->entityManager->flush();

            if (null !== $sourceOption->getIid() && null !== $newOption->getIid()) {
                $optionIidMap[(int) $sourceOption->getIid()] = (int) $newOption->getIid();
            }
        }

        return $optionIidMap;
    }

    /**
     * @return array<int, CQuizRelQuestion>
     */
    private function getQuestionRelations(CQuiz $quiz): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getAnswers(CQuizQuestion $question): array
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

    private function isExerciseReadOnlyFromLearningPath(int $exerciseId): bool
    {
        if ($this->isSettingEnabled('lp.force_edit_exercise_in_lp')) {
            return false;
        }

        $exerciseIdAsString = (string) $exerciseId;
        $lpItem = $this->entityManager->createQueryBuilder()
            ->select('lpItem.iid')
            ->from(CLpItem::class, 'lpItem')
            ->andWhere('lpItem.itemType = :itemType')
            ->andWhere('lpItem.path = :exerciseId OR lpItem.ref = :exerciseId')
            ->setParameter('itemType', self::LP_ITEM_TYPE_QUIZ, Types::STRING)
            ->setParameter('exerciseId', $exerciseIdAsString, Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $lpItem;
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }
}

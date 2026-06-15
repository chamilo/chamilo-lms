<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseLearningPathItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
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
 * @implements ProcessorInterface<ExerciseLearningPathItem, ExerciseLearningPathItem>
 */
final readonly class ExerciseLearningPathItemProcessor implements ProcessorInterface
{
    private const CSRF_TOKEN_ID = 'exercise_question_action';
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const LP_ITEM_TYPE_DIR = 'dir';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseLearningPathItem
    {
        if (!$data instanceof ExerciseLearningPathItem) {
            throw new BadRequestHttpException('Invalid learning path item payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercises in this context.');
        }

        if (!$this->isLearningPathCreationContext($request)) {
            throw new BadRequestHttpException('This action is only available from a learning path creation context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $lp = $this->getLearningPathFromCurrentContext($request, $course, $session);
        $parent = $this->resolveParentItem($request, $lp) ?? $this->getLearningPathRootItem($lp);
        $lpItem = $this->getExistingLearningPathExerciseItem($lp, $exerciseId);

        if (!$lpItem instanceof CLpItem) {
            $lpItem = $this->createLearningPathExerciseItem($lp, $quiz, $exerciseId, $parent);
        } elseif (null === $lpItem->getParent()) {
            $lpItem->setParent($parent);
        }

        $this->quizRepository->setVisibilityDraft($quiz, $course, $session);
        $lp->setModifiedOn(new DateTime());

        $this->entityManager->flush();

        $response = new ExerciseLearningPathItem();
        $response->exerciseId = $exerciseId;
        $response->lpItemId = (int) $lpItem->getIid();
        $response->success = true;
        $response->message = 'Exercise added to learning path.';

        return $response;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function isLearningPathCreationContext(Request $request): bool
    {
        $origin = strtolower((string) $request->query->get('origin', ''));
        $returnToLp = strtolower((string) $request->query->get('returnToLp', ''));

        return 'learnpath' === $origin || \in_array($returnToLp, ['1', 'true', 'yes'], true);
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

    private function getLearningPathFromCurrentContext(Request $request, Course $course, ?Session $session): CLp
    {
        $lpId = $request->query->getInt('lp_id', $request->query->getInt('learnpath_id'));
        if (0 >= $lpId) {
            throw new BadRequestHttpException('A valid learning path id is required.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('lp')
            ->from(CLp::class, 'lp')
            ->innerJoin('lp.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('lp.iid = :lpId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('lpId', $lpId, Types::INTEGER)
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

        $lp = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$lp instanceof CLp) {
            throw new AccessDeniedHttpException('The requested learning path does not belong to the current course context.');
        }

        return $lp;
    }

    private function getExistingLearningPathExerciseItem(CLp $lp, int $exerciseId): ?CLpItem
    {
        $item = $this->entityManager->createQueryBuilder()
            ->select('item')
            ->from(CLpItem::class, 'item')
            ->andWhere('item.lp = :lp')
            ->andWhere('item.itemType = :itemType')
            ->andWhere('item.path = :path')
            ->setParameter('lp', $lp)
            ->setParameter('itemType', self::LP_ITEM_TYPE_QUIZ)
            ->setParameter('path', (string) $exerciseId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $item instanceof CLpItem ? $item : null;
    }

    private function createLearningPathExerciseItem(CLp $lp, CQuiz $quiz, int $exerciseId, CLpItem $parent): CLpItem
    {
        $lpItem = (new CLpItem())
            ->setTitle($quiz->getTitle())
            ->setDescription('')
            ->setPath((string) $exerciseId)
            ->setRef('')
            ->setLp($lp)
            ->setItemType(self::LP_ITEM_TYPE_QUIZ)
            ->setMaxScore($this->getExerciseMaxScore($quiz))
            ->setMaxTimeAllowed('0')
            ->setPrerequisite('0')
            ->setDisplayOrder($this->getNextDisplayOrder($lp, $parent))
            ->setParent($parent)
        ;

        $this->entityManager->persist($lpItem);
        $this->entityManager->flush();

        return $lpItem;
    }

    private function getExerciseMaxScore(CQuiz $quiz): float
    {
        $maxScore = 0.0;
        foreach ($quiz->getQuestions() as $relQuestion) {
            $maxScore += (float) $relQuestion->getQuestion()->getPonderation();
        }

        return $maxScore;
    }

    private function getLearningPathRootItem(CLp $lp): CLpItem
    {
        $root = $this->entityManager->createQueryBuilder()
            ->select('item')
            ->from(CLpItem::class, 'item')
            ->andWhere('item.lp = :lp')
            ->andWhere('item.path = :path')
            ->setParameter('lp', $lp)
            ->setParameter('path', 'root')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$root instanceof CLpItem) {
            throw new BadRequestHttpException('The learning path root item was not found.');
        }

        return $root;
    }

    private function getNextDisplayOrder(CLp $lp, CLpItem $parent): int
    {
        $maxDisplayOrder = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(MAX(item.displayOrder), 0)')
            ->from(CLpItem::class, 'item')
            ->andWhere('item.lp = :lp')
            ->andWhere('item.parent = :parent')
            ->setParameter('lp', $lp)
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return ((int) $maxDisplayOrder) + 1;
    }

    private function resolveParentItem(Request $request, CLp $lp): ?CLpItem
    {
        foreach (['lp_parent_id', 'parent'] as $parameterName) {
            $candidateId = $request->query->getInt($parameterName);
            if (0 >= $candidateId) {
                continue;
            }

            $candidate = $this->entityManager->createQueryBuilder()
                ->select('item')
                ->from(CLpItem::class, 'item')
                ->andWhere('item.iid = :itemId')
                ->andWhere('item.lp = :lp')
                ->setParameter('itemId', $candidateId, Types::INTEGER)
                ->setParameter('lp', $lp)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;

            if (!$candidate instanceof CLpItem) {
                continue;
            }

            if (self::LP_ITEM_TYPE_DIR === $candidate->getItemType()) {
                return $candidate;
            }

            return $candidate->getParent();
        }

        return null;
    }
}

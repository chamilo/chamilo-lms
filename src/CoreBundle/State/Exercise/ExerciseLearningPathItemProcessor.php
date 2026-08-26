<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseLearningPathItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\Exercise\ExerciseLearningPathItemFactory;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ExerciseLearningPathItem, ExerciseLearningPathItem>
 */
final readonly class ExerciseLearningPathItemProcessor implements ProcessorInterface
{
    private const LP_ITEM_TYPE_QUIZ = 'quiz';
    private const LP_ITEM_TYPE_DIR = 'dir';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private ExerciseLearningPathItemFactory $learningPathItemFactory,
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

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercises in this context.');
        }

        if (!$this->isLearningPathCreationContext($request)) {
            throw new BadRequestHttpException('This action is only available from a learning path creation context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if ($exerciseId <= 0) {
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

    private function isLearningPathCreationContext(Request $request): bool
    {
        $origin = strtolower((string) $request->query->get('origin', ''));
        $returnToLp = strtolower((string) $request->query->get('returnToLp', ''));

        return 'learnpath' === $origin || \in_array($returnToLp, ['1', 'true', 'yes'], true);
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
        if ($lpId <= 0) {
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
            ->setParameter('lp', (int) $lp->getIid())
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
        $lpItem = $this->learningPathItemFactory->create(
            $lp,
            $quiz,
            $exerciseId,
            $parent,
            $this->getNextDisplayOrder($lp, $parent),
        );

        $this->entityManager->persist($lpItem);
        $this->entityManager->flush();

        return $lpItem;
    }

    private function getLearningPathRootItem(CLp $lp): CLpItem
    {
        $root = $this->entityManager->createQueryBuilder()
            ->select('item')
            ->from(CLpItem::class, 'item')
            ->andWhere('item.lp = :lp')
            ->andWhere('item.path = :path')
            ->setParameter('lp', (int) $lp->getIid())
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
            ->setParameter('lp', (int) $lp->getIid())
            ->setParameter('parent', (int) $parent->getIid())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return ((int) $maxDisplayOrder) + 1;
    }

    private function resolveParentItem(Request $request, CLp $lp): ?CLpItem
    {
        foreach (['lp_parent_id', 'parent'] as $parameterName) {
            $candidateId = $request->query->getInt($parameterName);
            if ($candidateId <= 0) {
                continue;
            }

            $candidate = $this->entityManager->createQueryBuilder()
                ->select('item')
                ->from(CLpItem::class, 'item')
                ->andWhere('item.iid = :itemId')
                ->andWhere('item.lp = :lp')
                ->setParameter('itemId', $candidateId, Types::INTEGER)
                ->setParameter('lp', (int) $lp->getIid())
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

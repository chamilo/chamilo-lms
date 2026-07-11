<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<mixed, JsonResponse>
 */
final readonly class LearningPathCategoryReorderProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getJsonData($request);
        $this->validateActionToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertLearningPathTeacher($this->security);

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        $order = $payload['order'] ?? null;
        if (!\is_array($order) || [] === $order) {
            throw new BadRequestHttpException('The learning path category order is required.');
        }

        $orderedIds = array_values(array_map(static fn (mixed $value): int => (int) $value, $order));
        if (\count($orderedIds) !== \count(array_unique($orderedIds)) || min($orderedIds) <= 0) {
            throw new BadRequestHttpException('The learning path category order contains invalid identifiers.');
        }

        $courseNode = $course->getResourceNode();
        if (null === $courseNode) {
            throw new BadRequestHttpException('Course resource node is missing.');
        }

        $courseNodeId = (int) $courseNode->getId();
        if ($courseNodeId <= 0) {
            throw new BadRequestHttpException('Course resource node is missing.');
        }

        $queryBuilder = $this->entityManager->getRepository(CLpCategory::class)->createQueryBuilder('category');
        $queryBuilder
            ->addSelect('resourceNode', 'resourceLink')
            ->join('category.resourceNode', 'resourceNode')
            ->join('resourceNode.resourceLinks', 'resourceLink')
            ->where('IDENTITY(resourceNode.parent) = :courseNodeId')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->andWhere('resourceLink.deletedAt IS NULL')
            ->setParameter('courseNodeId', $courseNodeId)
            ->setParameter('courseId', (int) $course->getId())
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(resourceLink.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId())
            ;
        } else {
            $queryBuilder->andWhere('resourceLink.session IS NULL');
        }

        if (null !== $group) {
            $queryBuilder
                ->andWhere('IDENTITY(resourceLink.group) = :groupId')
                ->setParameter('groupId', (int) $group->getIid())
            ;
        } else {
            $queryBuilder->andWhere('resourceLink.group IS NULL');
        }

        /** @var array<int, CLpCategory> $categories */
        $categories = $queryBuilder->getQuery()->getResult();
        $expectedIds = array_map(static fn (CLpCategory $category): int => (int) $category->getIid(), $categories);
        $sortedExpectedIds = $expectedIds;
        $sortedOrderedIds = $orderedIds;
        sort($sortedExpectedIds);
        sort($sortedOrderedIds);

        if ($sortedExpectedIds !== $sortedOrderedIds) {
            throw new BadRequestHttpException(
                'The order must contain every learning path category from the current context and no others.'
            );
        }

        $linksByCategoryId = [];
        $positions = [];

        foreach ($categories as $category) {
            $resourceNode = $category->getResourceNode();
            if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
                throw new AccessDeniedHttpException('You are not allowed to reorder this learning path category.');
            }

            $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
            if (!$resourceLink instanceof ResourceLink) {
                throw new BadRequestHttpException('A learning path category is not linked to the current context.');
            }

            $categoryId = (int) $category->getIid();
            $linksByCategoryId[$categoryId] = $resourceLink;
            $positions[] = $resourceLink->getDisplayOrder();
        }

        $position = min($positions);
        foreach ($orderedIds as $categoryId) {
            $linksByCategoryId[$categoryId]->setDisplayOrder($position);
            ++$position;
        }

        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }
}

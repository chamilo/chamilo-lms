<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Message;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @template-implements ProviderInterface<Message>
 */
final class MessageStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly CollectionProvider $collectionProvider,
        private readonly ItemProvider $itemProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly RequestStack $requestStack
    ) {}

    /**
     * Provides data based on the operation type (collection or item).
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|Message|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->handleCollection($operation, $context);
        }

        // Delegate to ItemProvider for individual operations
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }

    /**
     * Handles collection-level operations with filtering and pagination.
     */
    private function handleCollection(Operation $operation, array $context): Paginator
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new LogicException('User not found.');
        }

        // Retrieve initial filters if they exist
        $filters = $context['filters'] ?? [];
        $this->applyFilters($filters);

        // Add base filters for the authenticated user
        $filters['sender'] = $user->getId();
        $filters['receivers.receiver'] = $user->getId();

        // Update context with merged filters
        $context['filters'] = $filters;

        // Check if advanced filtering is applied
        $isSearchApplied = isset($filters['search']) || \count($filters) > 2;

        if (!$isSearchApplied) {
            // Delegate to CollectionProvider for standard query handling
            return $this->collectionProvider->provide($operation, [], $context);
        }

        // Build custom query for advanced filters
        return $this->applyCustomQuery($operation, $context, $filters);
    }

    /**
     * Builds and applies a custom query with filtering, sorting, and pagination.
     */
    private function applyCustomQuery(Operation $operation, array $context, array $filters): Paginator
    {
        // Main query
        $queryBuilder = $this->createQueryWithFilters($filters);

        // Apply pagination and sorting
        $order = $context['filters']['order']['sendDate'] ?? 'ASC';
        $queryBuilder->orderBy('m.sendDate', $order);

        $itemsPerPage = (int) ($context['filters']['itemsPerPage'] ?? 10);
        $currentPage = (int) ($context['filters']['page'] ?? 1);

        $queryBuilder->setFirstResult(($currentPage - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        // Count query for total items
        $countQueryBuilder = $this->createQueryWithFilters($filters, true);
        $totalItems = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();

        // Doctrine Paginator
        $doctrinePaginator = new \Doctrine\ORM\Tools\Pagination\Paginator($queryBuilder, true);

        // Adjust OutputWalkers as needed
        $needsOutputWalkers = \count($queryBuilder->getDQLPart('join')) > 0;
        $doctrinePaginator->setUseOutputWalkers($needsOutputWalkers);

        return new Paginator($doctrinePaginator);
    }

    /**
     * Creates a query with filters applied dynamically.
     */
    private function createQueryWithFilters(array $filters, bool $isCountQuery = false): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        // Adjust SELECT statement for count or distinct query
        if ($isCountQuery) {
            $queryBuilder->select('COUNT(DISTINCT m.id)');
        } else {
            $queryBuilder->select('DISTINCT m');
        }

        $queryBuilder->from(Message::class, 'm')
            ->leftJoin('m.receivers', 'r', 'WITH', 'r.deletedAt IS NULL OR r.deletedAt > CURRENT_TIMESTAMP()')
            ->where('m.sender = :user OR r.receiver = :user')
            ->setParameter('user', $filters['sender'])
        ;

        // Dynamically apply filters
        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'msgType':
                    $queryBuilder->andWhere('m.msgType = :msgType')->setParameter('msgType', $value);

                    break;

                case 'status':
                    $queryBuilder->andWhere('m.status = :status')->setParameter('status', $value);

                    break;

                case 'receivers.receiver':
                    $queryBuilder->andWhere('r.receiver = :receiver')->setParameter('receiver', $value);

                    break;

                case 'receivers.receiverType':
                    $queryBuilder->andWhere('r.receiverType = :receiverType')->setParameter('receiverType', $value);

                    break;

                case 'receivers.read':
                    $queryBuilder->andWhere('r.read = :read')->setParameter('read', !('false' === $value));

                    break;

                case 'search':
                    $queryBuilder->andWhere('m.title LIKE :search OR m.content LIKE :search')
                        ->setParameter('search', '%'.$value.'%')
                    ;

                    break;
            }
        }

        return $queryBuilder;
    }

    /**
     * Merges request filters into the provided filter array.
     */
    private function applyFilters(array &$filters): void
    {
        $request = $this->requestStack->getMainRequest();
        if ($request) {
            $requestFilters = $request->query->all();
            $filters = array_merge($filters, $requestFilters);
        }
    }
}

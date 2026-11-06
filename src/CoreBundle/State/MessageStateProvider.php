<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template-implements ProviderInterface<Message>
 */
final class MessageStateProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private readonly ProviderInterface $collectionProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private readonly ProviderInterface $itemProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserHelper $userHelper,
        private readonly RequestStack $requestStack,
        private readonly OrderExtension $orderExtension,
        private readonly PaginationExtension $paginationExtension
    ) {}

    /**
     * Provides data based on the operation type (collection or item).
     *
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<Message>|Message|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->handleCollection($operation, $context);
        }

        // Delegate to ItemProvider for individual operations
        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }

    /**
     * Handles collection-level operations with filtering and pagination.
     *
     * @param array<string, mixed> $context
     *
     * @return iterable<Message>
     */
    private function handleCollection(Operation $operation, array $context): iterable
    {
        $user = $this->userHelper->getCurrent();
        if (!$user) {
            throw new AccessDeniedHttpException('User not found.');
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

        // Build a custom query for advanced filters
        return $this->applyCustomQuery($operation, $context, $filters);
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

    /**
     * Builds the custom query and delegates sorting and pagination to API Platform.
     *
     * Ordering comes from OrderExtension (the operation's declared default order) and
     * pagination from PaginationExtension, so no client-supplied value reaches the DQL
     * by hand. FilterExtension is intentionally skipped: the filters are already applied
     * in createQueryWithFilters(), together with the custom sender/receiver scoping.
     */
    private function applyCustomQuery(Operation $operation, array $context, array $filters): iterable
    {
        $queryBuilder = $this->createQueryWithFilters($filters);
        $queryNameGenerator = new QueryNameGenerator();

        foreach ([$this->orderExtension, $this->paginationExtension] as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, Message::class, $operation, $context);

            if (
                $extension instanceof QueryResultCollectionExtensionInterface
                && $extension->supportsResult(Message::class, $operation, $context)
            ) {
                return $extension->getResult($queryBuilder, Message::class, $operation, $context);
            }
        }

        return $queryBuilder->getQuery()->getResult();
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
}

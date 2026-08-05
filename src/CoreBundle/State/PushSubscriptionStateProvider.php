<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\PushSubscription;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\PushSubscriptionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Restricts every read on push_subscriptions (item and collection) to the rows
 * owned by the authenticated user. Platform administrators bypass the ownership
 * scope and may read any subscription. Filtering (e.g. by `endpoint`), ordering
 * and pagination on the collection are delegated to API Platform's Doctrine
 * extensions.
 *
 * @implements ProviderInterface<PushSubscription>
 */
readonly class PushSubscriptionStateProvider implements ProviderInterface
{
    private array $extensions;

    public function __construct(
        FilterExtension $filterExtension,
        OrderExtension $orderExtension,
        PaginationExtension $paginationExtension,
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $itemProvider,
        private PushSubscriptionRepository $repository,
        private UserHelper $userHelper,
        private Security $security,
    ) {
        $this->extensions = [
            $filterExtension,
            $orderExtension,
            $paginationExtension,
        ];
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $currentUser = $this->userHelper->getCurrent();
        if (null === $currentUser) {
            return $operation instanceof CollectionOperationInterface ? [] : null;
        }

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($operation, $context, $isAdmin ? null : (int) $currentUser->getId());
        }

        $subscription = $this->itemProvider->provide($operation, $uriVariables, $context);

        // Hide the item from non-owners (admins bypass the ownership scope) so a
        // foreign id returns 404 instead of leaking its existence.
        if ($subscription instanceof PushSubscription
            && !$isAdmin
            && $subscription->getUser()?->getId() !== $currentUser->getId()
        ) {
            return null;
        }

        return $subscription;
    }

    private function provideCollection(Operation $operation, array $context, ?int $userId): array|object
    {
        $queryNameGenerator = new QueryNameGenerator();
        $queryBuilder = $this->repository->createQueryBuilder('p');

        // Admins (null $userId) see every subscription; everyone else is scoped.
        if (null !== $userId) {
            $userParam = $queryNameGenerator->generateParameterName('user');
            $queryBuilder
                ->andWhere('p.user = :'.$userParam)
                ->setParameter($userParam, $userId)
            ;
        }

        foreach ($this->extensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, PushSubscription::class, $operation, $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface
                && $extension->supportsResult(PushSubscription::class, $operation, $context)
            ) {
                return $extension->getResult($queryBuilder, PushSubscription::class, $operation, $context);
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

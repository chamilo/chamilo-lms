<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Component\Utils\NameConvention;
use Chamilo\CoreBundle\Entity\User;

/**
 * @template-implements ProviderInterface<User>
 */
class UserProvider implements ProviderInterface
{
    public function __construct(
        private readonly ItemProvider $itemProvider,
        private readonly CollectionProvider $collectionProvider,
        private readonly NameConvention $nameConvention
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $providedUsers = $this->collectionProvider->provide($operation, $uriVariables, $context);

            assert($providedUsers instanceof Paginator);

            /** @var User $user */
            foreach ($providedUsers as $user) {
                $this->nameConvention->getPersonName($user);
            }

            return $providedUsers;
        }

        /** @var User $user */
        $user = $this->itemProvider->provide($operation, $uriVariables, $context);

        $this->nameConvention->getPersonName($user);

        return $user;
    }
}

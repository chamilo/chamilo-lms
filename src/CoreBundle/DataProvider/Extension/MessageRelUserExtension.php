<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class MessageRelUserExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        if (MessageRelUser::class !== $resourceClass) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere("$alias.receiver = :current")
            ->setParameter('current', $user)
        ;
    }
}

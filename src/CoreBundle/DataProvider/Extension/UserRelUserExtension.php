<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Keeps the user relations collection to the ones the caller takes part in,
 * which is the same rule UserRelUserVoter applies to a single relation.
 */
final readonly class UserRelUserExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (UserRelUser::class !== $resourceClass
            || $this->security->isGranted('ROLE_ADMIN')
        ) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new AccessDeniedException('Access Denied.');
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->orX(
                    \sprintf('%s.user = :current_user', $alias),
                    \sprintf('%s.friend = :current_user', $alias)
                )
            )
            ->setParameter('current_user', (int) $user->getId())
        ;
    }
}

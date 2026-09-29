<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Keeps the social posts collection to the ones the caller may read, which is
 * the same rule SocialPostVoter applies to a single post: own posts, posts
 * addressed to them, platform-wide promoted messages, wall content from their
 * friends, and messages of the groups they belong to.
 *
 * SocialWallFilter narrows this further down to one wall, but only when the
 * client asks for it, so it cannot be the only barrier. The social tool being
 * off is already handled by SocialPostStateProvider for every operation.
 */
final readonly class SocialPostExtension implements QueryCollectionExtensionInterface
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
        if (SocialPost::class !== $resourceClass) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new AccessDeniedException('Access Denied.');
        }

        $expr = $queryBuilder->expr();

        $queryBuilder
            ->andWhere(
                $expr->orX(
                    $expr->eq("$alias.sender", ':current_user'),
                    $expr->eq("$alias.userReceiver", ':current_user'),
                    $expr->eq("$alias.type", ':promoted_type'),
                    $expr->andX(
                        $expr->in("$alias.type", ':wall_types'),
                        $expr->exists(
                            \sprintf(
                                'SELECT 1 FROM %s friendship
                                WHERE friendship.user = :current_user
                                    AND friendship.friend = %s.sender
                                    AND friendship.relationType = :friend_relation_type',
                                UserRelUser::class,
                                $alias
                            )
                        )
                    ),
                    $expr->andX(
                        $expr->eq("$alias.type", ':group_type'),
                        $expr->isNotNull("$alias.groupReceiver"),
                        $expr->exists(
                            \sprintf(
                                'SELECT 1 FROM %s membership
                                WHERE membership.user = :current_user
                                    AND membership.usergroup = %s.groupReceiver',
                                UsergroupRelUser::class,
                                $alias
                            )
                        )
                    )
                )
            )
            ->setParameter('current_user', (int) $user->getId())
            ->setParameter('promoted_type', SocialPost::TYPE_PROMOTED_MESSAGE)
            ->setParameter('wall_types', [SocialPost::TYPE_WALL_POST, SocialPost::TYPE_WALL_COMMENT])
            ->setParameter('friend_relation_type', UserRelUser::USER_RELATION_TYPE_FRIEND)
            ->setParameter('group_type', SocialPost::TYPE_GROUP_MESSAGE)
        ;
    }
}

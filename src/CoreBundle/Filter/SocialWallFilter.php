<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyInfo\Type;

class SocialWallFilter extends AbstractFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly Security $security,
        ?LoggerInterface $logger = null,
        ?array $properties = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties);
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description["socialwall_$property"] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_RESOURCE,
                'required' => false,
            ];
        }

        return $description;
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ('socialwall_wallOwner' !== $property) {
            return;
        }

        $wallOwnerId = $this->extractUserId($value);
        if (null === $wallOwnerId) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $expr = $queryBuilder->expr();

        $wallPostVisibility = $expr->orX(
            $expr->eq("IDENTITY($rootAlias.sender)", ':socialWallOwner'),
            $expr->eq("IDENTITY($rootAlias.userReceiver)", ':socialWallOwner')
        );

        if ($this->isCurrentUserWall($wallOwnerId)) {
            $friendRelationTypes = [
                UserRelUser::USER_RELATION_TYPE_FRIEND,
                UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
            ];

            $friendsOfOwnerDql = \sprintf(
                'SELECT social_wall_friend.id FROM %s social_wall_relation JOIN social_wall_relation.friend social_wall_friend WHERE IDENTITY(social_wall_relation.user) = :socialWallOwner AND social_wall_relation.relationType IN (:socialWallFriendRelationTypes)',
                UserRelUser::class
            );

            $friendsWithOwnerDql = \sprintf(
                'SELECT social_wall_friend_owner.id FROM %s social_wall_inverse_relation JOIN social_wall_inverse_relation.user social_wall_friend_owner WHERE IDENTITY(social_wall_inverse_relation.friend) = :socialWallOwner AND social_wall_inverse_relation.relationType IN (:socialWallFriendRelationTypes)',
                UserRelUser::class
            );

            $wallPostVisibility->add(
                $expr->andX(
                    $expr->isNull("$rootAlias.userReceiver"),
                    $expr->orX(
                        $expr->in("IDENTITY($rootAlias.sender)", $friendsOfOwnerDql),
                        $expr->in("IDENTITY($rootAlias.sender)", $friendsWithOwnerDql)
                    )
                )
            );

            $queryBuilder->setParameter(
                'socialWallFriendRelationTypes',
                $friendRelationTypes,
                ArrayParameterType::INTEGER
            );
        }

        $queryBuilder
            ->andWhere(
                $expr->orX(
                    $expr->andX(
                        $expr->eq("$rootAlias.type", ':socialWallPostType'),
                        $wallPostVisibility
                    ),
                    $expr->eq("$rootAlias.type", ':socialWallPromotedPostType')
                )
            )
            ->setParameter('socialWallOwner', $wallOwnerId)
            ->setParameter('socialWallPostType', SocialPost::TYPE_WALL_POST)
            ->setParameter('socialWallPromotedPostType', SocialPost::TYPE_PROMOTED_MESSAGE)
        ;
    }

    private function extractUserId(mixed $value): ?int
    {
        if ($value instanceof User) {
            return $value->getId();
        }

        if (is_numeric($value)) {
            $userId = (int) $value;

            return $userId > 0 ? $userId : null;
        }

        if (\is_string($value) && preg_match('/\d+$/', $value, $matches)) {
            $userId = (int) $matches[0];

            return $userId > 0 ? $userId : null;
        }

        return null;
    }

    private function isCurrentUserWall(int $wallOwnerId): bool
    {
        $currentUser = $this->security->getUser();

        return $currentUser instanceof User && $currentUser->getId() === $wallOwnerId;
    }
}

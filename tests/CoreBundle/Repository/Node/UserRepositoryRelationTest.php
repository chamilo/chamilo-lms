<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

final class UserRepositoryRelationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testRelateUsersPreservesDifferentRelationTypesWithoutDuplicatingTheSameRelation(): void
    {
        $user = $this->createUser('relation_user');
        $friend = $this->createUser('relation_friend');

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $relationRepository = $this->getEntityManager()->getRepository(UserRelUser::class);

        $userRepository->relateUsers($user, $friend, UserRelUser::USER_RELATION_TYPE_FRIEND);
        $userRepository->relateUsers($user, $friend, UserRelUser::USER_RELATION_TYPE_BOSS);

        // Re-adding an existing type must be idempotent while the other type remains untouched.
        $userRepository->relateUsers($user, $friend, UserRelUser::USER_RELATION_TYPE_FRIEND);

        $forwardRelations = $relationRepository->findBy([
            'user' => $user,
            'friend' => $friend,
        ]);
        $inverseRelations = $relationRepository->findBy([
            'user' => $friend,
            'friend' => $user,
        ]);

        $this->assertSame(
            [
                UserRelUser::USER_RELATION_TYPE_FRIEND,
                UserRelUser::USER_RELATION_TYPE_BOSS,
            ],
            $this->getSortedRelationTypes($forwardRelations)
        );
        $this->assertSame(
            [
                UserRelUser::USER_RELATION_TYPE_FRIEND,
                UserRelUser::USER_RELATION_TYPE_BOSS,
            ],
            $this->getSortedRelationTypes($inverseRelations)
        );
    }

    /**
     * @param list<UserRelUser> $relations
     *
     * @return list<int>
     */
    private function getSortedRelationTypes(array $relations): array
    {
        $types = array_map(
            static fn (UserRelUser $relation): int => (int) $relation->getRelationType(),
            $relations
        );
        sort($types);

        return $types;
    }
}

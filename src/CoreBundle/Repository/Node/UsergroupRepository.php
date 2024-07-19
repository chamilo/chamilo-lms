<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class UsergroupRepository extends ResourceRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly IllustrationRepository $illustrationRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {
        parent::__construct($registry, Usergroup::class);
    }

    /**
     * @param int|array $relationType
     */
    public function getGroupsByUser(int $userId, int $relationType = 0, bool $withImage = false): array
    {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.users', 'gu')
            ->where('gu.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('g.groupType = :socialClass')
            ->setParameter('socialClass', Usergroup::SOCIAL_CLASS)
        ;

        if (0 !== $relationType) {
            if (\is_array($relationType)) {
                $qb->andWhere('gu.relationType IN (:relationType)')
                    ->setParameter('relationType', $relationType)
                ;
            } else {
                $qb->andWhere('gu.relationType = :relationType')
                    ->setParameter('relationType', $relationType)
                ;
            }
        }

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();

            $qb->innerJoin('g.urls', 'u')
                ->andWhere('u.url = :urlId')
                ->setParameter('urlId', $accessUrl->getId())
            ;
        }

        $qb->orderBy('g.createdAt', 'DESC');
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function countMembers(int $usergroupId): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('count(gu.id)')
            ->innerJoin('g.users', 'gu')
            ->where('g.id = :usergroupId')
            ->setParameter('usergroupId', $usergroupId)
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getNewestGroups(int $limit = 30, string $query = ''): array
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g, COUNT(gu) AS HIDDEN memberCount')
            ->innerJoin('g.users', 'gu')
            ->where('g.groupType = :socialClass')
            ->setParameter('socialClass', Usergroup::SOCIAL_CLASS)
            ->groupBy('g')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();

            $qb->innerJoin('g.urls', 'u')
                ->andWhere('u.url = :urlId')
                ->setParameter('urlId', $accessUrl->getId())
            ;
        }

        if (!empty($query)) {
            $qb->andWhere('g.title LIKE :query OR g.description LIKE :query')
                ->setParameter('query', '%'.$query.'%')
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function getPopularGroups(int $limit = 30): array
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g, COUNT(gu) as HIDDEN memberCount')
            ->innerJoin('g.users', 'gu')
            ->where('g.groupType = :socialClass')
            ->setParameter('socialClass', Usergroup::SOCIAL_CLASS)
            ->andWhere('gu.relationType IN (:relationTypes)')
            ->setParameter('relationTypes', [
                Usergroup::GROUP_USER_PERMISSION_ADMIN,
                Usergroup::GROUP_USER_PERMISSION_READER,
                Usergroup::GROUP_USER_PERMISSION_HRM,
            ])
            ->groupBy('g')
            ->orderBy('memberCount', 'DESC')
            ->setMaxResults($limit)
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();

            $qb->innerJoin('g.urls', 'u')
                ->andWhere('u.url = :urlId')
                ->setParameter('urlId', $accessUrl->getId())
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function findGroupById($id)
    {
        return $this->createQueryBuilder('ug')
            ->where('ug.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function searchGroups(string $searchTerm): array
    {
        $queryBuilder = $this->createQueryBuilder('g');
        $queryBuilder->where('g.title LIKE :searchTerm')
            ->setParameter('searchTerm', '%'.$searchTerm.'%')
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function getUsersByGroup(int $groupID)
    {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.users', 'gu')
            ->innerJoin('gu.user', 'u')
            ->where('g.id = :groupID')
            ->setParameter('groupID', $groupID)
            ->andWhere('gu.relationType IN (:relationTypes)')
            ->setParameter('relationTypes', [
                Usergroup::GROUP_USER_PERMISSION_ADMIN,
                Usergroup::GROUP_USER_PERMISSION_READER,
                Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION,
            ])
            ->select('u.id, u.username, u.email, gu.relationType, u.pictureUri')
        ;

        $results = $qb->getQuery()->getResult();

        $userRepository = $this->_em->getRepository(User::class);

        foreach ($results as &$user) {
            $user['pictureUri'] = $userRepository->getUserPicture($user['id']);
        }

        return $results;
    }

    public function addUserToGroup(int $userId, int $groupId, int $relationType = Usergroup::GROUP_USER_PERMISSION_READER): void
    {
        $group = $this->find($groupId);
        $user = $this->_em->getRepository(User::class)->find($userId);

        if (!$group || !$user) {
            throw new Exception('Group or User not found');
        }

        if (Usergroup::GROUP_PERMISSION_CLOSED === (int) $group->getVisibility()) {
            $relationType = Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION;
        }

        $existingRelation = $this->_em->getRepository(UsergroupRelUser::class)->findOneBy([
            'usergroup' => $group,
            'user' => $user,
        ]);

        if (!$existingRelation) {
            $existingRelation = new UsergroupRelUser();
            $existingRelation->setUsergroup($group);
            $existingRelation->setUser($user);
        }

        $existingRelation->setRelationType($relationType);

        $this->_em->persist($existingRelation);
        $this->_em->flush();
    }

    public function updateUserRole($userId, $groupId, $relationType = Usergroup::GROUP_USER_PERMISSION_READER): void
    {
        $qb = $this->createQueryBuilder('g');
        $qb->delete(UsergroupRelUser::class, 'gu')
            ->where('gu.usergroup = :groupId')
            ->andWhere('gu.user = :userId')
            ->setParameter('groupId', $groupId)
            ->setParameter('userId', $userId)
        ;

        $query = $qb->getQuery();
        $query->execute();

        $group = $this->find($groupId);
        $user = $this->_em->getRepository(User::class)->find($userId);

        if (!$group || !$user) {
            throw new Exception('Group or User not found');
        }

        $usergroupRelUser = new UsergroupRelUser();
        $usergroupRelUser->setUsergroup($group);
        $usergroupRelUser->setUser($user);
        $usergroupRelUser->setRelationType($relationType);

        $this->_em->persist($usergroupRelUser);
        $this->_em->flush();
    }

    public function removeUserFromGroup(int $userId, int $groupId, bool $checkLeaveRestriction = true): bool
    {
        /** @var Usergroup $group */
        $group = $this->find($groupId);
        $user = $this->_em->getRepository(User::class)->find($userId);

        if (!$group || !$user) {
            throw new Exception('Group or User not found');
        }

        if ($checkLeaveRestriction && !$group->getAllowMembersToLeaveGroup()) {
            throw new Exception('Members are not allowed to leave this group');
        }

        $relation = $this->_em->getRepository(UsergroupRelUser::class)->findOneBy([
            'usergroup' => $group,
            'user' => $user,
        ]);

        if ($relation) {
            $this->_em->remove($relation);
            $this->_em->flush();

            return true;
        }

        return false;
    }

    public function getInvitedUsersByGroup(int $groupID)
    {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.users', 'gu')
            ->innerJoin('gu.user', 'u')
            ->where('g.id = :groupID')
            ->setParameter('groupID', $groupID)
            ->andWhere('gu.relationType = :relationType')
            ->setParameter('relationType', Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION)
            ->select('u.id, u.username, u.email, gu.relationType')
        ;

        return $qb->getQuery()->getResult();
    }

    public function getInvitedUsers(int $groupId): array
    {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.users', 'rel')
            ->innerJoin('rel.user', 'u')
            ->where('g.id = :groupId')
            ->andWhere('rel.relationType = :relationType')
            ->setParameter('groupId', $groupId)
            ->setParameter('relationType', Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION)
            ->select('u')
        ;

        return $qb->getQuery()->getResult();
    }

    public function searchGroupsByTags(string $tag, int $from = 0, int $number_of_items = 10, bool $getCount = false)
    {
        $qb = $this->createQueryBuilder('g');

        if ($getCount) {
            $qb->select('COUNT(g.id)');
        } else {
            $qb->select('g.id, g.title, g.description, g.url, g.picture');
        }

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();

            $qb->innerJoin('g.accessUrls', 'a', 'WITH', 'g.id = a.usergroup')
                ->andWhere('a.url = :urlId')
                ->setParameter('urlId', $accessUrl->getId())
            ;
        }

        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->like('g.title', ':tag'),
                $qb->expr()->like('g.description', ':tag'),
                $qb->expr()->like('g.url', ':tag')
            )
        )
            ->setParameter('tag', '%'.$tag.'%')
        ;

        if (!$getCount) {
            $qb->orderBy('g.title', 'ASC')
                ->setFirstResult($from)
                ->setMaxResults($number_of_items)
            ;
        }

        return $getCount ? $qb->getQuery()->getSingleScalarResult() : $qb->getQuery()->getResult();
    }

    public function getUsergroupPicture($userGroupId): string
    {
        $usergroup = $this->find($userGroupId);
        if (!$usergroup) {
            return '/img/icons/64/group_na.png';
        }

        $url = $this->illustrationRepository->getIllustrationUrl($usergroup);
        $params['w'] = 64;
        $params['rand'] = uniqid('u_', true);
        $paramsToString = '?'.http_build_query($params);

        return $url.$paramsToString;
    }

    public function isGroupMember(int $groupId, User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $userRole = $this->getUserGroupRole($groupId, $user->getId());

        $allowedRoles = [
            Usergroup::GROUP_USER_PERMISSION_ADMIN,
            Usergroup::GROUP_USER_PERMISSION_MODERATOR,
            Usergroup::GROUP_USER_PERMISSION_READER,
            Usergroup::GROUP_USER_PERMISSION_HRM,
        ];

        return \in_array($userRole, $allowedRoles, true);
    }

    public function getUserGroupRole(int $groupId, int $userId): ?int
    {
        $qb = $this->createQueryBuilder('g');
        $qb->innerJoin('g.users', 'gu')
            ->where('g.id = :groupId AND gu.user = :userId')
            ->setParameter('groupId', $groupId)
            ->setParameter('userId', $userId)
            ->orderBy('gu.id', 'DESC')
            ->select('gu.relationType')
            ->setMaxResults(1)
        ;

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result['relationType'] : null;
    }

    public function isGroupModerator(int $groupId, int $userId): bool
    {
        $relationType = $this->getUserGroupRole($groupId, $userId);

        return \in_array($relationType, [
            Usergroup::GROUP_USER_PERMISSION_ADMIN,
            Usergroup::GROUP_USER_PERMISSION_MODERATOR,
        ]);
    }

    /**
     * Determines whether to use the multi-URL feature.
     *
     * @return bool true if multi-URLs should be used, false otherwise
     */
    public function getUseMultipleUrl(): bool
    {
        // TODO: Implement the actual logic to determine if multi-URLs should be used.
        // For now, returning false as a default value.
        return false;
    }

    /**
     * Gets the current access URL ID.
     *
     * @return int the ID of the current access URL
     */
    public function getCurrentAccessUrlId(): int
    {
        // TODO: Implement the actual logic to obtain the current access URL ID.
        // For now, returning 1 as a default value.
        return 1;
    }
}

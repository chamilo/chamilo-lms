<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class WeakPasswordCheckerHelper
{
    /**
     * Common password candidates tested against stored password hashes.
     *
     * Keep this list short because every candidate is checked against every selected user hash.
     */
    private const array COMMON_PASSWORD_CANDIDATES = [
        '123456',
        'password',
        'changeme',
        '123456789',
        'qwerty',
        '12345678',
        '12345',
        'admin',
        'admin123',
        '111111',
        '123123',
        '1234567890',
        '1234567',
        'qwerty123',
        '000000',
        'abc123',
        'password1',
        '1234',
        'password123',
        'welcome',
        'welcome1',
        'letmein',
        'secret',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly AccessUrlScopeHelper $accessUrlScope,
    ) {}

    public function countScannableUsers(User $actor): int
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
        ;

        $this->applyScannableUserFilters($qb, $actor);

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countScannableUsersByIds(array $userIds, User $actor): int
    {
        $ids = $this->normalizeUserIds($userIds);

        if ([] === $ids) {
            return 0;
        }

        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;

        $this->applyScannableUserFilters($qb, $actor);

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array{scanned_users: User[], weak_users: User[]}
     */
    public function scanUsersBatch(int $offset, int $limit, User $actor): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->distinct()
            ->orderBy('u.id', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, $limit))
        ;

        $this->applyScannableUserFilters($qb, $actor);

        /** @var User[] $users */
        $users = $qb->getQuery()->getResult();

        return [
            'scanned_users' => $users,
            'weak_users' => $this->filterUsersWithCommonPasswords($users),
        ];
    }

    /**
     * @return array{scanned_users: User[], weak_users: User[]}
     */
    public function scanUsersByIdsBatch(array $userIds, int $offset, int $limit, User $actor): array
    {
        $ids = $this->normalizeUserIds($userIds);

        if ([] === $ids) {
            return [
                'scanned_users' => [],
                'weak_users' => [],
            ];
        }

        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->distinct()
            ->orderBy('u.id', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, $limit))
        ;

        $this->applyScannableUserFilters($qb, $actor);

        /** @var User[] $users */
        $users = $qb->getQuery()->getResult();

        return [
            'scanned_users' => $users,
            'weak_users' => $this->filterUsersWithCommonPasswords($users),
        ];
    }

    /**
     * @return User[]
     */
    public function findWeakPasswordUsers(User $actor, ?array $onlyUserIds = null): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->distinct()
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
        ;

        $this->applyScannableUserFilters($qb, $actor);

        if (null !== $onlyUserIds) {
            $ids = $this->normalizeUserIds($onlyUserIds);

            if ([] === $ids) {
                return [];
            }

            $qb
                ->andWhere('u.id IN (:ids)')
                ->setParameter('ids', $ids)
            ;
        }

        /** @var User[] $users */
        $users = $qb->getQuery()->getResult();

        return $this->filterUsersWithCommonPasswords($users);
    }

    /**
     * @return User[]
     */
    public function findUsersByIds(array $userIds, User $actor): array
    {
        $ids = $this->normalizeUserIds($userIds);

        if ([] === $ids) {
            return [];
        }

        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->distinct()
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
        ;

        $this->applyScannableUserFilters($qb, $actor);

        return $qb->getQuery()->getResult();
    }

    private function applyScannableUserFilters(QueryBuilder $qb, User $actor): void
    {
        $qb
            ->andWhere('u.active = :active')
            ->andWhere('u.status NOT IN (:excludedStatuses)')
            ->andWhere('u.email IS NOT NULL')
            ->andWhere('u.email <> :emptyEmail')
            ->setParameter('active', User::ACTIVE)
            ->setParameter('excludedStatuses', [
                User::ANONYMOUS,
                User::ROLE_FALLBACK,
            ])
            ->setParameter('emptyEmail', '')
        ;

        $urlIds = $this->getVisibleAccessUrlIds($actor);
        if (null === $urlIds) {
            return;
        }

        if ([] === $urlIds) {
            $qb->andWhere('1 = 0');

            return;
        }

        $qb
            ->innerJoin('u.portals', 'passwordScopeRel')
            ->innerJoin('passwordScopeRel.url', 'passwordScopeUrl')
            ->andWhere('passwordScopeUrl.id IN (:passwordScopeUrlIds)')
            ->setParameter('passwordScopeUrlIds', $urlIds)
        ;
    }

    /**
     * @return list<int>|null null means an unrestricted global administrator
     */
    private function getVisibleAccessUrlIds(User $actor): ?array
    {
        if ($actor->hasRole('ROLE_GLOBAL_ADMIN')) {
            return $this->accessUrlScope->getManagedUrlIds($actor);
        }

        // Plain admins may scan their directly registered URLs, not descendants.
        $urlIds = [];
        foreach ($actor->getPortals() as $relation) {
            /** @var AccessUrlRelUser $relation */
            $urlId = (int) ($relation->getUrl()?->getId() ?? 0);
            if ($urlId > 0) {
                $urlIds[] = $urlId;
            }
        }

        return array_values(array_unique($urlIds));
    }

    /**
     * @param User[] $users
     *
     * @return User[]
     */
    private function filterUsersWithCommonPasswords(array $users): array
    {
        $weakUsers = [];

        foreach ($users as $user) {
            foreach ($this->getPasswordCandidates($user) as $passwordCandidate) {
                if ($this->userRepository->isPasswordValid($user, $passwordCandidate)) {
                    $weakUsers[] = $user;

                    break;
                }
            }
        }

        return $weakUsers;
    }

    /**
     * @return string[]
     */
    private function getPasswordCandidates(User $user): array
    {
        if ('' === (string) $user->getPassword()) {
            return [];
        }

        return self::COMMON_PASSWORD_CANDIDATES;
    }

    /**
     * @return int[]
     */
    private function normalizeUserIds(array $userIds): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            static fn (int $id): bool => $id > 0
        )));
    }
}

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

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
    private const COMMON_PASSWORD_CANDIDATES = [
        '123456',
        '123456789',
        '12345',
        'qwerty',
        'password',
        '12345678',
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
        'admin',
        'admin123',
        'welcome',
        'welcome1',
        'letmein',
        'changeme',
        'secret',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {}

    public function countScannableUsers(): int
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
        ;

        $this->applyScannableUserFilters($qb);

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countScannableUsersByIds(array $userIds): int
    {
        $ids = $this->normalizeUserIds($userIds);

        if ([] === $ids) {
            return 0;
        }

        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;

        $this->applyScannableUserFilters($qb);

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array{scanned_users: User[], weak_users: User[]}
     */
    public function scanUsersBatch(int $offset, int $limit): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, $limit))
        ;

        $this->applyScannableUserFilters($qb);

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
    public function scanUsersByIdsBatch(array $userIds, int $offset, int $limit): array
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
            ->orderBy('u.id', 'ASC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, $limit))
        ;

        $this->applyScannableUserFilters($qb);

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
    public function findWeakPasswordUsers(?array $onlyUserIds = null): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
        ;

        $this->applyScannableUserFilters($qb);

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
    public function findUsersByIds(array $userIds): array
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
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
        ;

        $this->applyScannableUserFilters($qb);

        /** @var User[] $users */
        return $qb->getQuery()->getResult();
    }

    private function applyScannableUserFilters(QueryBuilder $qb): void
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
            foreach (self::COMMON_PASSWORD_CANDIDATES as $passwordCandidate) {
                if ($this->userRepository->isPasswordValid($user, $passwordCandidate)) {
                    $weakUsers[] = $user;

                    break;
                }
            }
        }

        return $weakUsers;
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

<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Repository\PermissionRelRoleRepository;
use Chamilo\CoreBundle\Repository\RoleRepository;

class PermissionHelper
{
    public function __construct(
        private PermissionRelRoleRepository $permissionRelRoleRepository,
        private RoleRepository $roleRepository
    ) {}

    /**
     * Returns the list of role codes currently stored in the 'role' table,
     * excluding internal roles like ANONYMOUS (not assignable).
     *
     * @return string[]
     */
    public function getUserRoles(): array
    {
        $qb = $this->roleRepository->createQueryBuilder('r')
            ->where('r.code <> :anon')
            ->setParameter('anon', 'ANONYMOUS')
            ->orderBy('r.constantValue', 'ASC');

        $roles = $qb->getQuery()->getResult();

        return array_map(static fn ($r) => $r->getCode(), $roles);
    }

    /**
     * Checks if any of the given roles has the specified permission slug.
     *
     * @param string   $permissionSlug The permission slug to check (e.g. "user:create")
     * @param string[] $roles          a list of role codes to check against
     *
     * @return bool true if at least one role has the permission, false otherwise
     */
    public function hasPermission(string $permissionSlug, array $roles): bool
    {
        $queryBuilder = $this->permissionRelRoleRepository->createQueryBuilder('prr')
            ->innerJoin('prr.permission', 'p')
            ->innerJoin('prr.role', 'r')
            ->where('p.slug = :permissionSlug')
            ->andWhere('r.code IN (:roles)')
            ->andWhere('prr.changeable = :changeable')
            ->setParameter('permissionSlug', $permissionSlug)
            ->setParameter('roles', $roles)
            ->setParameter('changeable', true)
        ;

        $results = $queryBuilder->getQuery()->getResult();

        return \count($results) > 0;
    }
}

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
     * Returns the list of role codes currently stored in the 'role' table.
     *
     * @return string[]
     */
    public function getUserRoles(): array
    {
        $roles = $this->roleRepository->findAll();

        return array_map(fn($r) => $r->getCode(), $roles);
    }

    /**
     * Checks if any of the given roles has the specified permission slug.
     *
     * @param string $permissionSlug The permission slug to check (e.g. "user:create")
     * @param string[] $roles        A list of role codes to check against.
     *
     * @return bool True if at least one role has the permission, false otherwise.
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

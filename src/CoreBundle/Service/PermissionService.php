<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Repository\PermissionRelRoleRepository;

class PermissionService
{
    private PermissionRelRoleRepository $permissionRelRoleRepository;

    public function __construct(PermissionRelRoleRepository $permissionRelRoleRepository)
    {
        $this->permissionRelRoleRepository = $permissionRelRoleRepository;
    }

    public function hasPermission(string $permissionSlug, array $roles): bool
    {
        $queryBuilder = $this->permissionRelRoleRepository->createQueryBuilder('prr')
            ->innerJoin('prr.permission', 'p')
            ->where('p.slug = :permissionSlug')
            ->andWhere('prr.roleCode IN (:roles)')
            ->andWhere('prr.changeable = :changeable')
            ->setParameter('permissionSlug', $permissionSlug)
            ->setParameter('roles', $roles)
            ->setParameter('changeable', true);

        $results = $queryBuilder->getQuery()->getResult();

        return count($results) > 0;
    }
}

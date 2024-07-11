<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Repository\PermissionRelRoleRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PermissionServiceHelper
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private PermissionRelRoleRepository $permissionRelRoleRepository
    ) {}

    public function getUserRoles(): array
    {
        $roles = $this->parameterBag->get('security.role_hierarchy.roles');

        return array_filter(array_keys($roles), function ($role) {
            return !str_starts_with($role, 'ROLE_CURRENT_') && $role !== 'ROLE_ANONYMOUS';
        });
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

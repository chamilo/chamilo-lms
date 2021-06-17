<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SysAnnouncementRepository extends ServiceEntityRepository
{
    protected ParameterBagInterface $parameterBag;

    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        parent::__construct($registry, SysAnnouncement::class);
        $this->parameterBag = $parameterBag;
    }

    public function getVisibilityList()
    {
        $hierarchy = $this->parameterBag->get('security.role_hierarchy.roles');
        $roles = [];
        array_walk_recursive($hierarchy, function ($role) use (&$roles): void {
            $roles[$role] = $role;
        });

        return $roles;
    }

    public function update(SysAnnouncement $sysAnnouncement, $andFlush = true): void
    {
        $this->getEntityManager()->persist($sysAnnouncement);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }
}

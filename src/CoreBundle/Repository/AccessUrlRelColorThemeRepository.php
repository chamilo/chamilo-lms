<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessUrlRelColorTheme>
 *
 * @method AccessUrlRelColorTheme|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccessUrlRelColorTheme|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccessUrlRelColorTheme[]    findAll()
 * @method AccessUrlRelColorTheme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class AccessUrlRelColorThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessUrlRelColorTheme::class);
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Language::class);
    }

    public function getAllAvailable(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('l');
        $qb
            ->where(
                $qb->expr()->eq('l.available', true)
            )
            ->andWhere(
                $qb->expr()->isNull('l.parent')
            )
        ;

        return $qb;
    }

    /**
     * Get all the sub languages that are made available by the admin.
     *
     * @return Collection|Language[]
     */
    public function findAllSubLanguages()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l')
            ->where(
                $qb->expr()->eq('l.available', true)
            )
            ->andWhere(
                $qb->expr()->isNotNull('l.parent')
            )
        ;

        return $qb->getQuery()->getResult();
    }
}

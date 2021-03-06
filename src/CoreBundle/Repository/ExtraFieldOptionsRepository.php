<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

class ExtraFieldOptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraFieldOptions::class);
    }

    /**
     * Get the secondary options. For double select extra field.
     *
     * @return array
     */
    public function findSecondaryOptions(ExtraFieldOptions $option)
    {
        $qb = $this->createQueryBuilder('so');
        $qb
            ->where(
                $qb->expr()->eq('so.field', $option->getField()->getId())
            )
            ->andWhere(
                $qb->expr()->eq('so.value', $option->getId())
            )
            ->orderBy('so.displayText', Criteria::ASC)
        ;

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }
}

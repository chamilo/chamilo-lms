<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExtraFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraField::class);
    }

    /**
     * @return ExtraField[]
     */
    public function getExtraFields(int $type)
    {
        $qb = $this->createQueryBuilder('f');
        $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.visibleToSelf', true),
                    $qb->expr()->eq('f.extraFieldType', $type)
                )
            )
        ;

        return $qb->getQuery()->getResult();
    }
}

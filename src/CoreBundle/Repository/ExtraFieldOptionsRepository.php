<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class ExtraFieldOptionsRepository.
 *
 * @package Chamilo\CoreBundle\Repository
 */
class ExtraFieldOptionsRepository extends ServiceEntityRepository
{
    /**
     * ExtraFieldOptionsRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraFieldOptions::class);
    }

    /**
     * Get the secondary options. For double select extra field.
     *
     * @param ExtraFieldOptions $option
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
            ->orderBy('so.displayText', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();
    }
}

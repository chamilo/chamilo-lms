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
     */
    public function findSecondaryOptions(ExtraFieldOptions $option): array
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

    /**
     * Get the complete row of a specific option for a specific field.
     *
     * @return array<int, ExtraFieldOptions>
     */
    public function getFieldOptionByFieldAndOption(int $fieldId, string $optionValue, int $itemType): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->innerJoin('o.field', 'f')
            ->where('o.field = :fieldId')
            ->andWhere('o.value = :optionValue')
            ->andWhere('f.itemType = :itemType')
            ->setParameters([
                'fieldId' => $fieldId,
                'optionValue' => $optionValue,
                'itemType' => $itemType,
            ])
        ;

        return $qb->getQuery()->getResult();
    }
}

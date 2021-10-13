<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ExtraFieldValuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraFieldValues::class);
    }

    /**
     * Get the extra field values for visible extra fields.
     *
     * @param int $extraFieldType The type of extra field
     * @param int $itemId         The item ID
     *
     * @return ExtraFieldValues[]
     */
    public function getVisibleValues(int $extraFieldType, int $itemId)
    {
        $qb = $this->createQueryBuilder('fv');

        $qb
            ->innerJoin(
                'ChamiloCoreBundle:ExtraField',
                'f',
                Join::WITH,
                'fv.field = f.id'
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.extraFieldType', $extraFieldType),
                    $qb->expr()->eq('fv.itemId', $itemId),
                    $qb->expr()->eq('f.visibleToSelf', true)
                )
            )
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return ExtraFieldValues[]
     */
    public function getExtraFieldValuesFromItem(User $user)
    {
        $qb = $this->createQueryBuilder('v');
        $qb
            ->innerJoin('v.field', 'f')
            ->andWhere('v.itemId = :id')
            ->andWhere(
                $qb->expr()->eq('f.visibleToSelf', true)
            )
            ->setParameter(
                'id',
                $user->getId()
            )
        ;

        return $qb->getQuery()->getResult();
    }

    public function updateItemData(ExtraField $extraField, User $user, $data): ?ExtraFieldValues
    {
        $itemId = $user->getId();
        $qb = $this->createQueryBuilder('v');
        $qb
            ->innerJoin('v.field', 'f')
            ->andWhere('v.itemId = :id ')
            ->andWhere('f = :field ')
            ->setParameter('id', $itemId)
            ->setParameter('field', $extraField)
        ;

        $extraFieldValues = $qb->getQuery()->getOneOrNullResult();
        $em = $this->getEntityManager();

        if (null === $extraFieldValues) {
            $extraFieldValues = (new ExtraFieldValues())
                ->setItemId((int) $itemId)
                ->setField($extraField)
                ->setValue($data)
            ;
            $em->persist($extraFieldValues);
            $em->flush();
        } else {
            $extraFieldValues->setValue($data);
            $em->persist($extraFieldValues);
            $em->flush();
        }

        return $extraFieldValues;
    }
}

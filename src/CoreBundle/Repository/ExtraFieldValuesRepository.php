<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldItemInterface;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Julio Montoya
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
                    $qb->expr()->eq('f.itemType', $extraFieldType),
                    $qb->expr()->eq('fv.itemId', $itemId),
                    $qb->expr()->eq('f.visibleToSelf', true)
                )
            )
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ExtraFieldItemInterface $item can be a User|Course|Any Entity that implements ExtraFieldItemInterface
     *
     * @return ExtraFieldValues[]
     */
    public function getExtraFieldValuesFromItem(ExtraFieldItemInterface $item, int $type)
    {
        $qb = $this->createQueryBuilder('v');
        $qb
            ->innerJoin('v.field', 'f')
            ->andWhere('v.itemId = :id')
            ->andWhere(
                $qb->expr()->eq('f.visibleToSelf', true),
                $qb->expr()->eq('f.itemType', $type)
            )
            ->setParameter(
                'id',
                $item->getResourceIdentifier()
            )
        ;

        return $qb->getQuery()->getResult();
    }

    public function updateItemData(ExtraField $extraField, ExtraFieldItemInterface $item, ?string $data): ?ExtraFieldValues
    {
        $itemId = $item->getResourceIdentifier();
        $qb = $this->createQueryBuilder('v');
        $qb
            ->innerJoin('v.field', 'f')
            ->andWhere('v.itemId = :id ')
            ->andWhere('f = :field ')
            ->setParameter('id', $itemId)
            ->setParameter('field', $extraField)
        ;

        /** @var ?ExtraFieldValues $extraFieldValues */
        $extraFieldValues = $qb->getQuery()->getOneOrNullResult();
        $em = $this->getEntityManager();

        if (null === $extraFieldValues) {
            $extraFieldValues = (new ExtraFieldValues())
                ->setItemId($itemId)
                ->setField($extraField)
                ->setFieldValue($data)
            ;
            $em->persist($extraFieldValues);
        } else {
            $extraFieldValues->setFieldValue($data);
            $em->persist($extraFieldValues);
        }

        $em->flush();

        return $extraFieldValues;
    }

    public function findLegalAcceptByItemId($itemId)
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.field', 'sf')
            ->where('s.itemId = :itemId')
            ->andWhere('sf.variable = :variable')
            ->andWhere('sf.itemType = :itemType')
            ->andWhere('s.fieldValue IS NOT NULL')
            ->andWhere('s.fieldValue != :emptyString')
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(1)
            ->setParameter('itemId', $itemId)
            ->setParameter('variable', 'legal_accept')
            ->setParameter('itemType', 1)
            ->setParameter('emptyString', '');

        $result = $qb->getQuery()->getOneOrNullResult();

        if ($result === null) {

            return null;
        } else {
            $resultArray = [
                'id' => $result->getId(),
                'itemId' => $result->getItemId(),
                'value' => $result->getFieldValue(),
            ];

            return $resultArray;
        }
    }
}

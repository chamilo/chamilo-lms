<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * ExtraFieldRelTagRepository.
 *
 * @package Chamilo\CoreBundle\Repository
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ExtraFieldRelTagRepository extends ServiceEntityRepository
{
    /**
     * ExtraFieldRelTagRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraFieldRelTag::class);
    }

    /**
     * Get the tags for a item.
     *
     * @param ExtraField $extraField The extrafield
     * @param int        $itemId     The item ID
     *
     * @return array
     */
    public function getTags(ExtraField $extraField, $itemId)
    {
        $queryBuilder = $this->createQueryBuilder('ft');

        $queryBuilder->select('t')
            ->innerJoin(
                'ChamiloCoreBundle:Tag',
                't',
                Join::WITH,
                'ft.tagId = t.id'
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('ft.itemId', intval($itemId)),
                    $queryBuilder->expr()->eq('ft.fieldId', $extraField->getId())
                )
            );

        return $queryBuilder->getQuery()->getResult();
    }
}

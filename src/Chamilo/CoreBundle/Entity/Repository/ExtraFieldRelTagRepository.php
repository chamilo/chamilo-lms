<?php
/* For licensing terms, see /license.txt */
namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Chamilo\CoreBundle\Entity\ExtraField;
use \Doctrine\ORM\Query\Expr\Join;

/**
 * ExtraFieldRelTagRepository
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ExtraFieldRelTagRepository extends EntityRepository
{
    /**
     * Get the tags for a item
     * @param ExtraField $extraField The extrafield
     * @param int $itemId The item ID
     * @return \Doctrine\ORM\QueryBuilder
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

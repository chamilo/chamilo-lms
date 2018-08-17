<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * ExtraFieldValuesRepository class.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ExtraFieldValuesRepository extends EntityRepository
{
    /**
     * Get the extra field values for visible extra fields.
     *
     * @param int $extraFieldType The type of extra field
     * @param int $itemId         The item ID
     *
     * @return array
     */
    public function getVisibleValues($extraFieldType, $itemId)
    {
        $queryBuilder = $this->createQueryBuilder('fv');

        $queryBuilder
            ->innerJoin(
                'ChamiloCoreBundle:ExtraField',
                'f',
                Join::WITH,
                'fv.field = f.id'
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('f.extraFieldType', intval($extraFieldType)),
                    $queryBuilder->expr()->eq('fv.itemId', intval($itemId)),
                    $queryBuilder->expr()->eq('f.visibleToSelf', true)
                )
            )
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}

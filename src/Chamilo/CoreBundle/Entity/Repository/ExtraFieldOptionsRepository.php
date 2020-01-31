<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Doctrine\ORM\EntityRepository;

/**
 * Class ExtraFieldOptionsRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class ExtraFieldOptionsRepository extends EntityRepository
{
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
            ->orderBy('so.displayText', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();
    }
}

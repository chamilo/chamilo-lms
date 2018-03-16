<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class LanguageRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class LanguageRepository extends EntityRepository
{
    /**
     * Get all the sub languages that are made available by the admin.
     *
     * @return array
     */
    public function findAllPlatformSubLanguages()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l')
            ->where(
                $qb->expr()->eq('l.available', true)
            )
            ->andWhere(
                $qb->expr()->isNotNull('l.parent')
            );

        return $qb->getQuery()->getResult();
    }
}

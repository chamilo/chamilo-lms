<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class LegalRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class LegalRepository extends EntityRepository
{
    /**
     * Count the legal terms by language (only count one set of terms for each
     * language)
     * @return int
     * @throws \Exception
     */
    public function countAllActiveLegalTerms()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l.languageId, COUNT(l.id)')
            ->groupBy('l.languageId');
        return count($qb->getQuery()->getResult());
    }
}

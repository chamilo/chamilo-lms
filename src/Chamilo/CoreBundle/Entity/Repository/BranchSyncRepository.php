<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Class BranchSyncRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class BranchSyncRepository extends NestedTreeRepository
{
    /**
     * @param string $keyword
     *
     * @return mixed
     */
    public function searchByKeyword($keyword)
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT b');

        $qb->from('Chamilo\CoreBundle\Entity\BranchSync', 'b');

        //Selecting courses for users
        //$qb->innerJoin('u.courses', 'c');

        //@todo check app settings
        $qb->add('orderBy', 'b.branchName ASC');
        $qb->where('b.branchName LIKE :keyword');
        $qb->setParameter('keyword', "%$keyword%");
        $q = $qb->getQuery();

        return $q->execute();
    }

    /**
     * Gets the first branch with parent_id = NULL.
     *
     * @return mixed
     */
    public function getTopBranch()
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT b');

        $qb->from('Chamilo\CoreBundle\Entity\BranchSync', 'b');
        $qb->where('b.parentId IS NULL');
        $qb->add('orderBy', 'b.id ASC');
        $qb->setMaxResults(1);
        $q = $qb->getQuery()->getResult();
        if (empty($q)) {
            return null;
        } else {
            foreach ($q as $result) {
                return $result;
            }
        }
    }
}

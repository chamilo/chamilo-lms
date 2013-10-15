<?php

namespace Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Class BranchSyncRepository
 * @package Entity\Repository
 */
class BranchSyncRepository extends NestedTreeRepository
{
    /**
     * @param string $keyword
     * @return mixed
     */
    public function searchByKeyword($keyword)
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT b');

        $qb->from('Entity\BranchSync', 'b');

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
     * Returns the local branch.
     */
    public function getLocalBranch()
    {
        static $local_branch;

        if (isset($local_branch)) {
            return $local_branch;
        }
        $local_branch_settings = api_get_settings_params_simple(array('variable = ?' => 'local_branch_id'));
        if (empty($local_branch_settings['selected_value'])) {
            $local_branch_id = 1;
        }
        else {
            $local_branch_id = $local_branch_settings['selected_value'];
        }

        $local_branch = $this->find($local_branch_id);
        return $local_branch;
    }
}

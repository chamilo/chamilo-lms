<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class GroupRepository.
 *
 * @package Entity\Repository
 */
class GroupRepository extends EntityRepository
{
    /**
     * @return mixed
     */
    public function getAdmins()
    {
        $criteria = ['name' => 'admins'];
        $group = $this->findOneBy($criteria);

        return $group->getUsers();
    }
}

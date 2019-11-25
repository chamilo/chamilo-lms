<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Model\Group;

/**
 * Class GroupRepository.
 *
 * @package Entity\Repository
 */
class GroupRepository
{
    /**
     * @var EntityRepository
     */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Group::class);
    }

    /**
     * @return mixed
     */
    public function getAdmins()
    {
        $criteria = ['name' => 'admins'];
        $group = $this->repository->findOneBy($criteria);

        return $group->getUsers();
    }
}

<?php

namespace Chamilo\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Chamilo\UserBundle\Entity\User as User;

/**
 * Class UserRepository
 * @package Entity\Repository
 */
class GroupRepository extends EntityRepository
{

}

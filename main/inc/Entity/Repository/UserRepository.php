<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Class UserRepository
 * @package Entity\Repository
 */
class UserRepository extends EntityRepository implements UserProviderInterface
{

    /**
     * @param string $username
     * @return mixed
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username)
    {
        $q = $this
            ->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery();

        try {
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException(
                sprintf('Unable to find an active admin User identified by "%s".', $username),
                0,
                $e
            );
        }
        return $user;
    }

    /**
     * @param UserInterface $user
     * @return \Entity\User
     * @throws UnsupportedUserException
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    public function getUsers($limit = null)
    {
        $qb = $this->createQueryBuilder('u')
                   ->select('u')
                   ->addOrderBy('u.username', 'DESC');

        return $qb;
    }

    public function getSubscribedUsers($limit = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->addOrderBy('u.username', 'DESC');
        return $qb;
    }
}

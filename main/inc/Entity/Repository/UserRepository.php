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
    * @param string $keyword
    * @return mixed
    */
    public function searchUserByKeyword($keyword)
    {
        $qb = $this->createQueryBuilder('a');

        // Selecting user info
        $qb->select('DISTINCT u');

        $qb->from('Entity\User', 'u');

        // Selecting courses for users
        // $qb->innerJoin('u.courses', 'c');

        //@todo check app settings
        $qb->add('orderBy', 'u.firstname ASC');
        $qb->where('u.firstname LIKE :keyword OR u.lastname LIKE :keyword OR u.username LIKE :keyword ');
        $qb->setParameter('keyword', "%$keyword%");
        $q = $qb->getQuery();

        return $q->execute();
    }

    /**
     * @param string $keyword
     * @param string $role
     * @return mixed
     */
    public function searchUserByKeywordAndRole($keyword, $role)
    {
        $qb = $this->createQueryBuilder('a');

        // Selecting user info
        $qb->select('DISTINCT u');

        $qb->from('Entity\User', 'u');

        // Selecting courses for users
        $qb->innerJoin('u.roles', 'r');

        //@todo check app settings
        $qb->add('orderBy', 'u.firstname ASC');
        $qb->where('u.firstname LIKE :keyword OR u.lastname LIKE :keyword OR u.username LIKE :keyword ');
        $qb->andWhere('r.role = :role');

        $qb->setParameters(
            array(
                'keyword' => "%$keyword%",
                'role' => $role
            )
        );

        $q = $qb->getQuery();
        return $q->execute();

    }


    /**
     * @param string $username
     * @return \Entity\User
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
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation if it decides to reload the user data
     * from the database, or if it simply merges the passed User into the
     * identity map of an entity manager.
     *
     * @throws UnsupportedUserException if the account is not supported
     * @param UserInterface $user
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        return $user;
        /*
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }
        return $this->loadUserByUsername($user->getUsername());*/
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }
}

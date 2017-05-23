<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sonata\CoreBundle\Model\BaseEntityManager;

/**
 * This UserManageProxy class is used to keep UserManager compatible with Sonata ManagerInterface implementation
 * because UserManager implements FOSUserBundle manager interface.
 *
 * @author Sylvain Deloux <sylvain.deloux@ekino.com>
 */
class UserManagerProxy extends BaseEntityManager
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * UserManagerProxy constructor.
     *
     * @param string          $class
     * @param ManagerRegistry $registry
     * @param UserManager     $userManager
     */
    public function __construct($class, ManagerRegistry $registry, UserManager $userManager)
    {
        parent::__construct($class, $registry);

        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->userManager->getClass();
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->userManager->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->userManager->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->userManager->findOneBy($criteria, $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->userManager->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return $this->userManager->create();
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $andFlush = true)
    {
        return $this->userManager->save($entity, $andFlush);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $andFlush = true)
    {
        return $this->userManager->delete($entity, $andFlush);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        return $this->userManager->getTableName();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->userManager->getConnection();
    }
}

<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Entity;

use FOS\UserBundle\Doctrine\UserManager as BaseUserManager;
use Sonata\UserBundle\Model\UserManagerInterface;

/**
 * Class UserManager
 *
 * @package Sonata\UserBundle\Entity
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class UserManager extends BaseUserManager implements UserManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function findUsersBy(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->repository->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->repository->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return parent::findUsers($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return parent::findUserBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return parent::createUser();
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $andFlush = true)
    {
        parent::updateUser($entity, $andFlush);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $andFlush = true)
    {
        parent::deleteUser($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        return $this->objectManager->getClassMetadata($this->class)->table['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->objectManager->getConnection();
    }
}

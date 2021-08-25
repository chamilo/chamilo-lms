<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Entity;

use FOS\UserBundle\Doctrine\UserManager as BaseUserManager;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\DatagridBundle\Pager\Doctrine\Pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;
use Sonata\UserBundle\Model\UserInterface;
use Sonata\UserBundle\Model\UserManagerInterface;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class UserManager extends BaseUserManager implements UserManagerInterface, ManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function findUsersBy(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
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
    public function save($entity, $andFlush = true): void
    {
        if (!$entity instanceof UserInterface) {
            throw new \InvalidArgumentException('Save method expected entity of type UserInterface');
        }

        parent::updateUser($entity, $andFlush);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $andFlush = true): void
    {
        if (!$entity instanceof UserInterface) {
            throw new \InvalidArgumentException('Save method expected entity of type UserInterface');
        }

        parent::deleteUser($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        return $this->objectManager->getClassMetadata($this->getClass())->table['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->objectManager->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function getPager(array $criteria, $page, $limit = 10, array $sort = [])
    {
        $query = $this->getRepository()
            ->createQueryBuilder('u')
            ->select('u');

        $fields = $this->objectManager->getClassMetadata($this->getClass())->getFieldNames();
        foreach ($sort as $field => $direction) {
            if (!in_array($field, $fields)) {
                throw new \RuntimeException(sprintf("Invalid sort field '%s' in '%s' class", $field, $this->getClass()));
            }
        }
        if (0 == count($sort)) {
            $sort = ['username' => 'ASC'];
        }
        foreach ($sort as $field => $direction) {
            $query->orderBy(sprintf('u.%s', $field), strtoupper($direction));
        }

        if (isset($criteria['enabled'])) {
            $query->andWhere('u.enabled = :enabled');
            $query->setParameter('enabled', $criteria['enabled']);
        }

        $pager = new Pager();
        $pager->setMaxPerPage($limit);
        $pager->setQuery(new ProxyQuery($query));
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }
}

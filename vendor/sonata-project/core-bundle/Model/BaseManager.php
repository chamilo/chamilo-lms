<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

/**
 * Class BaseManager
 *
 * @package Sonata\CoreBundle\Model
 *
 * @author  Hugo Briand <briand@ekino.com>
 */
abstract class BaseManager implements ManagerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $class;

    /**
     * @param string          $class
     * @param ManagerRegistry $registry
     */
    public function __construct($class, ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->class    = $class;
    }

    /**
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->registry->getManagerForClass($this->class);
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
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
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return new $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $andFlush = true)
    {
        $this->checkObject($entity);

        $this->getObjectManager()->persist($entity);

        if ($andFlush) {
            $this->getObjectManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $andFlush = true)
    {
        $this->checkObject($entity);

        $this->getObjectManager()->remove($entity);

        if ($andFlush) {
            $this->getObjectManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        return $this->getObjectManager()->getClassMetadata($this->class)->table['name'];
    }

    /**
     * Returns the related Object Repository.
     *
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->class);
    }

    /**
     * @param $entity
     *
     * @throws \InvalidArgumentException
     */
    protected function checkObject($entity)
    {
        if (!$entity instanceof $this->class) {
            throw new \InvalidArgumentException(sprintf(
                'Entity must be instance of %s, %s given',
                $this->class, is_object($entity)? get_class($entity) : gettype($entity)
            ));
        }
    }
}

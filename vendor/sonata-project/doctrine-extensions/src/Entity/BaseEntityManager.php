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

namespace Sonata\Doctrine\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sonata\Doctrine\Model\BaseManager;

/**
 * @author Sylvain Deloux <sylvain.deloux@ekino.com>
 *
 * @phpstan-template T of object
 * @phpstan-extends BaseManager<T>
 */
abstract class BaseEntityManager extends BaseManager
{
    /**
     * Make sure the code is compatible with legacy code.
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ('em' === $name) {
            return $this->getObjectManager();
        }

        throw new \RuntimeException(sprintf('The property %s does not exists', $name));
    }

    public function getConnection()
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        $objectManager = $this->getObjectManager();

        \assert($objectManager instanceof EntityManager);

        return $objectManager;
    }

    protected function getRepository(): EntityRepository
    {
        $repository = $this->getEntityManager()->getRepository($this->class);

        \assert($repository instanceof EntityRepository);

        return $repository;
    }
}

class_exists(\Sonata\CoreBundle\Model\BaseEntityManager::class);

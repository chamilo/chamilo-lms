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

namespace Sonata\Doctrine\Document;

use Doctrine\Persistence\ObjectManager;
use Sonata\Doctrine\Model\BaseManager;

/**
 * @phpstan-template T of object
 * @phpstan-extends BaseManager<T>
 */
abstract class BasePHPCRManager extends BaseManager
{
    /**
     * Make sure the code is compatible with legacy code.
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ('dm' === $name) {
            return $this->getObjectManager();
        }

        throw new \RuntimeException(sprintf('The property %s does not exists', $name));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException Each call
     */
    public function getConnection()
    {
        throw new \LogicException('PHPCR does not use a database connection.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException Each call
     */
    public function getTableName()
    {
        throw new \LogicException('PHPCR does not use a reference name for a list of data.');
    }

    /**
     * @return ObjectManager
     */
    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }
}

class_exists(\Sonata\CoreBundle\Model\BasePHPCRManager::class);

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

/**
 * Class BasePHPCRManager
 *
 * @package Sonata\CoreBundle\Model
 *
 */
abstract class BasePHPCRManager extends BaseManager
{
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

    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }

    /**
     * Make sure the code is compatible with legacy code
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name == 'dm') {
            return $this->getObjectManager();
        }

        throw new \RuntimeException(sprintf('The property %s does not exists', $name));
    }
}

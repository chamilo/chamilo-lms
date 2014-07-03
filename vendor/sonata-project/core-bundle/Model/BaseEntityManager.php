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

use Doctrine\ORM\EntityManager;

/**
 * Class BaseManager.
 *
 * @author Sylvain Deloux <sylvain.deloux@ekino.com>
 */
abstract class BaseEntityManager extends BaseManager
{
    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
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
        if ($name == 'em') {
            return $this->getObjectManager();
        }

        throw new \RuntimeException(sprintf('The property %s does not exists', $name));
    }
}

<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Model;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
abstract class BaseDocumentManager extends BaseManager
{
    /**
     * Make sure the code is compatible with legacy code.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'dm') {
            return $this->getObjectManager();
        }

        throw new \RuntimeException(sprintf('The property %s does not exists', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->getObjectManager()->getConnection();
    }

    /**
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }
}

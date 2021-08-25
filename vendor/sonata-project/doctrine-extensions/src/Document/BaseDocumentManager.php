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

use Doctrine\ODM\MongoDB\DocumentManager;
use Sonata\Doctrine\Model\BaseManager;

/**
 * @author Hugo Briand <briand@ekino.com>
 *
 * @phpstan-template T of object
 * @phpstan-extends BaseManager<T>
 */
abstract class BaseDocumentManager extends BaseManager
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

    public function getConnection()
    {
        return $this->getObjectManager()->getConnection();
    }

    /**
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        $dm = $this->getObjectManager();

        \assert($dm instanceof DocumentManager);

        return $dm;
    }
}

class_exists(\Sonata\CoreBundle\Model\BaseDocumentManager::class);

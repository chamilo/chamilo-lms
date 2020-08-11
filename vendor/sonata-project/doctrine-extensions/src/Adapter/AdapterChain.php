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

namespace Sonata\Doctrine\Adapter;

class AdapterChain implements AdapterInterface
{
    /**
     * @var AdapterInterface[]
     */
    protected $adapters = [];

    public function addAdapter(AdapterInterface $adapter)
    {
        $this->adapters[] = $adapter;
    }

    public function getNormalizedIdentifier($model)
    {
        foreach ($this->adapters as $adapter) {
            $identifier = $adapter->getNormalizedIdentifier($model);

            if ($identifier) {
                return $identifier;
            }
        }

        return null;
    }

    public function getUrlSafeIdentifier($model)
    {
        foreach ($this->adapters as $adapter) {
            $safeIdentifier = $adapter->getUrlSafeIdentifier($model);

            if ($safeIdentifier) {
                return $safeIdentifier;
            }
        }

        return null;
    }
}

class_exists(\Sonata\CoreBundle\Model\Adapter\AdapterChain::class);

<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Model\Adapter;

class AdapterChain implements AdapterInterface
{
    protected $adapters = array();

    /**
     * @param AdapterInterface $adapter
     */
    public function addAdapter(AdapterInterface $adapter)
    {
        $this->adapters[] = $adapter;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getUrlsafeIdentifier($model)
    {
        foreach ($this->adapters as $adapter) {
            $safeIdentifier = $adapter->getUrlsafeIdentifier($model);

            if ($safeIdentifier) {
                return $safeIdentifier;
            }
        }

        return null;
    }
}
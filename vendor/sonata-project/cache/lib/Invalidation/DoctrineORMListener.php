<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Invalidation;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Sonata\Cache\CacheAdapterInterface;

class DoctrineORMListener implements EventSubscriber
{
    protected $caches = array();

    protected $collectionIdentifiers;

    /**
     * @param ModelCollectionIdentifiers $collectionIdentifiers
     * @param array                      $caches
     */
    public function __construct(ModelCollectionIdentifiers $collectionIdentifiers, $caches)
    {
        $this->collectionIdentifiers = $collectionIdentifiers;

        foreach ($caches as $cache) {
            $this->addCache($cache);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::preRemove,
            Events::preUpdate,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->flush($args);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->flush($args);
    }

    /**
     * {@inheritdoc}
     */
    protected function flush(LifecycleEventArgs $args)
    {
        $identifier = $this->collectionIdentifiers->getIdentifier($args->getEntity());

        if ($identifier === false) {
            return;
        }

        $parameters = array(
            ClassUtils::getClass($args->getEntity()) => $identifier,
        );

        foreach ($this->caches as $cache) {
            $cache->flush($parameters);
        }
    }

    /**
     * @param CacheAdapterInterface $cache
     */
    public function addCache(CacheAdapterInterface $cache)
    {
        if (!$cache->isContextual()) {
            return;
        }

        $this->caches[] = $cache;
    }
}

<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Invalidation;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\Event;
use Sonata\Cache\CacheAdapterInterface;

class DoctrinePHPCRODMListener implements EventSubscriber
{
    protected $caches = [];

    protected $collectionIdentifiers;

    /**
     * @param ModelCollectionIdentifiers $collectionIdentifiers
     * @param array                      $caches
     */
    public function __construct(ModelCollectionIdentifiers $collectionIdentifiers, array $caches)
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
        return [
            Event::preRemove,
            Event::preUpdate,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->flush($args);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->flush($args);
    }

    /**
     * {@inheritdoc}
     */
    public function addCache(CacheAdapterInterface $cache): void
    {
        if (!$cache->isContextual()) {
            return;
        }

        $this->caches[] = $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function flush(LifecycleEventArgs $args): void
    {
        $identifier = $this->collectionIdentifiers->getIdentifier($args->getObject());

        if (false === $identifier) {
            return;
        }

        $parameters = [
            ClassUtils::getClass($args->getObject()) => $identifier,
        ];

        foreach ($this->caches as $cache) {
            $cache->flush($parameters);
        }
    }
}

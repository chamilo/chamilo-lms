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

use Sonata\Cache\CacheAdapterInterface;
use Psr\Log\LoggerInterface;

class SimpleCacheInvalidation implements InvalidationInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(array $caches, array $keys)
    {
        foreach ($caches as $cache) {

            if (!$cache instanceof CacheAdapterInterface) {
                throw new \RuntimeException('The object must implements the CacheAdapterInterface interface');
            }

            try {
                if ($this->logger) {
                    $this->logger->info(sprintf('[%s] flushing cache keys : %s', __CLASS__, json_encode($keys)));
                }

                $cache->flush($keys);

            } catch (\Exception $e) {

                if ($this->logger) {
                    $this->logger->alert(sprintf('[%s] %s', __CLASS__, $e->getMessage()));
                } else {
                    throw new \RunTimeException(null, null, $e);
                }
            }
        }

        return true;
    }
}

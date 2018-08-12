<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Adapter\Cache;

use Sonata\Cache\CacheElement;
use Sonata\Cache\CacheElementInterface;
use Sonata\Cache\Exception\UnsupportedException;

/**
 * Handles OpCode cache.
 *
 * For user cache this Adapter use this extensions:
 *  - Apc extension for PHP version < 5.5.0 (see http://php.net/manual/fr/book.apc.php)
 *  - Apcu extension for PHP version >= 5.5.0 (see https://github.com/krakjoe/apcu)
 * And for opcode cache use the Apc extension for PHP version < 5.5.0 and opcache instead (see http://php.net/manual/fr/book.opcache.php)
 *
 * @author Amine Zaghdoudi <amine.zaghdoudi@ekino.com>
 */
class OpCodeCache extends BaseCacheHandler
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var array
     */
    protected $servers;

    /**
     * @var bool
     */
    protected $currentOnly;

    /**
     * @var array
     */
    protected $timeout = [];

    /**
     * Constructor.
     *
     * @param string $url     A router instance
     * @param string $prefix  A prefix to avoid clash between instances
     * @param array  $servers An array of servers
     * @param array  $timeout An array of timeout options
     */
    public function __construct(string $url, string $prefix, array $servers, array $timeout = [])
    {
        $this->url = $url;
        $this->prefix = $prefix;
        $this->servers = $servers;

        $defaultTimeout = [
            'sec' => 5,
            'usec' => 0,
        ];

        $this->timeout['RCV'] = isset($timeout['RCV']) ? array_merge($defaultTimeout, $timeout['RCV']) : $defaultTimeout;
        $this->timeout['SND'] = isset($timeout['SND']) ? array_merge($defaultTimeout, $timeout['SND']) : $defaultTimeout;
    }

    /**
     * @param bool $bool
     */
    public function setCurrentOnly($bool): void
    {
        $this->currentOnly = $bool;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll(): bool
    {
        if ($this->currentOnly) {
            if (version_compare(PHP_VERSION, '5.5.0', '>=') && function_exists('opcache_reset')) {
                opcache_reset();
            }

            if (function_exists('apc_clear_cache')) {
                apc_clear_cache('user') && apc_clear_cache();
            }

            return true;
        }

        $result = true;

        foreach ($this->servers as $server) {
            if (4 == count(explode('.', $server['ip']))) {
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            } else {
                $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
            }

            // generate the raw http request
            $command = sprintf("GET %s HTTP/1.1\r\n", $this->getUrl());
            $command .= sprintf("Host: %s\r\n", $server['domain']);

            if ($server['basic']) {
                $command .= sprintf("Authorization: Basic %s\r\n", $server['basic']);
            }

            $command .= "Connection: Close\r\n\r\n";

            // setup the default timeout (avoid max execution time)
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $this->timeout['SND']);
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $this->timeout['RCV']);

            socket_connect($socket, $server['ip'], $server['port']);
            socket_write($socket, $command);

            $content = '';

            do {
                $buffer = socket_read($socket, 1024);
                $content .= $buffer;
            } while (!empty($buffer));

            if ($result) {
                $result = 'ok' == substr($content, -2);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = []): bool
    {
        if ($this->currentOnly) {
            $this->checkApc();

            return apc_delete($this->computeCacheKeys($keys));
        }

        return $this->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys): bool
    {
        $this->checkApc();

        return apc_exists($this->computeCacheKeys($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, int $ttl = CacheElement::DAY, array $contextualKeys = []): CacheElementInterface
    {
        $this->checkApc();

        $cacheElement = new CacheElement($keys, $data, $ttl);

        apc_store(
            $this->computeCacheKeys($keys),
            $cacheElement,
            $cacheElement->getTtl()
        );

        return $cacheElement;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys): CacheElementInterface
    {
        $this->checkApc();

        return $this->handleGet($keys, apc_fetch($this->computeCacheKeys($keys)));
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return $this->url;
    }

    /**
     * Check that Apc is enabled.
     *
     * @return bool
     *
     * @throws UnsupportedException
     */
    protected function checkApc()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enabled')) {
            throw new UnsupportedException(__CLASS__.' does not support data caching. you should install APC or APCu to use it');
        }
    }

    /**
     * Computes the given cache keys.
     *
     * @param array $keys
     *
     * @return string
     */
    protected function computeCacheKeys($keys)
    {
        ksort($keys);

        return md5($this->prefix.serialize($keys));
    }
}

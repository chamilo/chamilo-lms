<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache;

final class CacheElement implements CacheElementInterface
{
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    const MONTH = 2.63e+6;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var array
     */
    private $keys = [];

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var array
     */
    private $contextualKeys = [];

    /**
     * Constructor.
     *
     * @param array $keys           An array of keys
     * @param mixed $data           Data
     * @param int   $ttl            A time to live, default 86400 seconds (CacheElement::DAY)
     * @param array $contextualKeys An array of contextual keys
     */
    public function __construct(array $keys, $data, int $ttl = self::DAY, array $contextualKeys = [])
    {
        $this->createdAt = new \DateTime();
        $this->keys = $keys;
        $this->ttl = $ttl;
        $this->data = $data;
        $this->contextualKeys = $contextualKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired(): bool
    {
        return strtotime('now') > ($this->createdAt->format('U') + $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpirationDate(): \DateTimeInterface
    {
        if ($this->isExpired()) {
            return new \DateTime();
        }

        $date = clone $this->createdAt;
        $date = $date->add(new \DateInterval(sprintf('PT%sS', $this->ttl)));

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getContextualKeys(): array
    {
        return $this->contextualKeys;
    }
}

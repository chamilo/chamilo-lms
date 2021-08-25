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

interface CacheElementInterface
{
    /**
     * Returns the keys.
     *
     * @return array
     */
    public function getKeys(): array;

    /**
     * Returns the time to live.
     *
     * @return int
     */
    public function getTtl(): int;

    /**
     * Returns the data.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Returns TRUE whether the cache is expired.
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * @return \DateTime
     */
    public function getExpirationDate(): \DateTimeInterface;

    /**
     * Returns the contextual keys.
     *
     * @return array
     */
    public function getContextualKeys(): array;
}

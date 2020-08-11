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

interface CounterAdapterInterface
{
    /**
     * @param Counter|string $counter
     * @param int            $number
     *
     * @return Counter
     */
    public function increment(Counter $counter, int $number = 1): Counter;

    /**
     * @param Counter|string $counter
     * @param int            $number
     *
     * @return Counter
     */
    public function decrement(Counter $counter, int $number = 1): Counter;

    /**
     * @param Counter $counter
     *
     * @return Counter
     */
    public function set(Counter $counter): Counter;

    /**
     * @param string $name
     *
     * @return Counter
     */
    public function get(string $name): Counter;
}

<?php

/*
 * This file is part of the Sonata package.
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
    public function increment(/*Counter*/ $counter, $number = 1);

    /**
     * @param Counter|string $counter
     * @param int            $number
     *
     * @return Counter
     */
    public function decrement(/*Counter*/ $counter, $number = 1);

    /**
     * @param Counter $counter
     *
     * @return Counter
     */
    public function set(Counter $counter);

    /**
     * @param string $name
     *
     * @return Counter
     */
    public function get($name);
}
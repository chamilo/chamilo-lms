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

final class Counter
{
    protected $name;

    protected $value;

    /**
     * @param string $name
     * @param mixed  $value
     */
    private function __construct($name, $value = 0)
    {
        if (!is_int($value)) {
            throw new \RuntimeException('The value is not numeric for the counter');
        }

        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return Counter
     */
    static function create($name, $value = 0)
    {
        return new self($name, $value);
    }
}
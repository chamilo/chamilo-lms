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

final class Counter
{
    private $name;

    private $value;

    /**
     * @param string $name
     * @param int    $value
     */
    private function __construct(string $name, int $value = 0)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param string $name
     * @param int    $value
     *
     * @return Counter
     */
    public static function create(string $name, int $value = 0): self
    {
        return new self($name, $value);
    }
}

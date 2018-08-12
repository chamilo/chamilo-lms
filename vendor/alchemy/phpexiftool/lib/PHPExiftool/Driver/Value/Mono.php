<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Value;

class Mono implements ValueInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->set($value);
    }

    public function getType()
    {
        return self::TYPE_MONO;
    }

    public function set($value)
    {
        $this->value = (string) $value;

        return $this;
    }

    public function asString()
    {
        return $this->value;
    }

    public function asArray()
    {
        return (array) $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }
}

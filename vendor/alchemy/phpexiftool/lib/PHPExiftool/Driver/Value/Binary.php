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

use PHPExiftool\Exception\InvalidArgumentException;

class Binary implements ValueInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->set($value);
    }

    public function getType()
    {
        return self::TYPE_BINARY;
    }

    public function asString()
    {
        return $this->value;
    }

    public function asArray()
    {
        return (array) $this->value;
    }

    public function asBase64()
    {
        return base64_encode($this->value);
    }

    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    public function setBase64Value($base64Value)
    {
        if (false === $value = base64_decode($base64Value, true)) {
            throw new InvalidArgumentException('The value should be base64 encoded');
        }

        $this->value = $value;

        return $this;
    }

    public static function loadFromBase64($base64Value)
    {
        if (false === $value = base64_decode($base64Value, true)) {
            throw new InvalidArgumentException('The value should be base64 encoded');
        }

        return new static($value);
    }
}

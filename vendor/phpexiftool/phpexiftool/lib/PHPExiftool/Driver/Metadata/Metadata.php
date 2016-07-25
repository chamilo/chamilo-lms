<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Metadata;

use PHPExiftool\Driver\TagInterface;
use PHPExiftool\Driver\Value\ValueInterface;

/**
 * Metadata Object for mapping a Tag to a value
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class Metadata
{
    protected $tag;
    protected $value;

    public function __construct(TagInterface $tag, ValueInterface $value)
    {
        $this->tag = $tag;
        $this->value = $value;

        return $this;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function getValue()
    {
        return $this->value;
    }
}

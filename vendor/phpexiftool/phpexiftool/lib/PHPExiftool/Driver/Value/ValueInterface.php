<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Value;

interface ValueInterface
{
    const TYPE_BINARY = 'bianry';
    const TYPE_MONO = 'mono';
    const TYPE_MULTI = 'multi';

    public function set($value);

    public function getType();

    public function asString();

    public function asArray();
}

<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Type;

use PHPExiftool\Driver\AbstractType;

class VarInt8u extends AbstractType
{

    protected $ExiftoolName = 'var_int8u';

    protected $PHPMap = 'int';

}

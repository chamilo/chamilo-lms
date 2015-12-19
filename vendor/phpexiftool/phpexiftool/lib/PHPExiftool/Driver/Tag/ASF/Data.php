<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ASF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Data extends AbstractTag
{

    protected $Id = '75B22636-668E-11CF-A6D9-00AA0062CE6C';

    protected $Name = 'Data';

    protected $FullName = 'ASF::Main';

    protected $GroupName = 'ASF';

    protected $g0 = 'ASF';

    protected $g1 = 'ASF';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Data';

}

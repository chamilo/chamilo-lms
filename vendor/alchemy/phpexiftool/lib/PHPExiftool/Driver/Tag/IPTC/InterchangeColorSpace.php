<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InterchangeColorSpace extends AbstractTag
{

    protected $Id = 64;

    protected $Name = 'InterchangeColorSpace';

    protected $FullName = 'IPTC::NewsPhoto';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Interchange Color Space';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'X,Y,Z CIE',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'RGB SMPTE',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Y,U,V (K) (D65)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'RGB Device Dependent',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'CMY (K) Device Dependent',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Lab (K) CIE',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'YCbCr',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'sRGB',
        ),
    );

}

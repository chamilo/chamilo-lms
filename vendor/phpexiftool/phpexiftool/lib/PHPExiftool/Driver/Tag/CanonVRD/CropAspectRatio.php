<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CropAspectRatio extends AbstractTag
{

    protected $Id = 608;

    protected $Name = 'CropAspectRatio';

    protected $FullName = 'CanonVRD::Ver1';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Crop Aspect Ratio';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Free',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '3:2',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '2:3',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '4:3',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '3:4',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'A-size Landscape',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'A-size Portrait',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Letter-size Landscape',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Letter-size Portrait',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '4:5',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '5:4',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '1:1',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Circle',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'Custom',
        ),
    );

}

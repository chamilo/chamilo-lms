<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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

    protected $Id = 'mixed';

    protected $Name = 'CropAspectRatio';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Crop Aspect Ratio';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Free',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Custom',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '1:1',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3:2',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '2:3',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '4:3',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '3:4',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '5:4',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '4:5',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '16:9',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '9:16',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Free',
        ),
        12 => array(
            'Id' => 1,
            'Label' => '3:2',
        ),
        13 => array(
            'Id' => 2,
            'Label' => '2:3',
        ),
        14 => array(
            'Id' => 3,
            'Label' => '4:3',
        ),
        15 => array(
            'Id' => 4,
            'Label' => '3:4',
        ),
        16 => array(
            'Id' => 5,
            'Label' => 'A-size Landscape',
        ),
        17 => array(
            'Id' => 6,
            'Label' => 'A-size Portrait',
        ),
        18 => array(
            'Id' => 7,
            'Label' => 'Letter-size Landscape',
        ),
        19 => array(
            'Id' => 8,
            'Label' => 'Letter-size Portrait',
        ),
        20 => array(
            'Id' => 9,
            'Label' => '4:5',
        ),
        21 => array(
            'Id' => 10,
            'Label' => '5:4',
        ),
        22 => array(
            'Id' => 11,
            'Label' => '1:1',
        ),
        23 => array(
            'Id' => 12,
            'Label' => 'Circle',
        ),
        24 => array(
            'Id' => 65535,
            'Label' => 'Custom',
        ),
    );

}

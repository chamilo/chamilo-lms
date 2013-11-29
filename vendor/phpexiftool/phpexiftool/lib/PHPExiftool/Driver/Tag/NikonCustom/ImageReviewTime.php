<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageReviewTime extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ImageReviewTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Image Review Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '8 s',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '20 s',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1 min',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '10 min',
        ),
        5 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        6 => array(
            'Id' => 32,
            'Label' => '8 s',
        ),
        7 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        8 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        9 => array(
            'Id' => 128,
            'Label' => '10 min',
        ),
        10 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        11 => array(
            'Id' => 1,
            'Label' => '10 s',
        ),
        12 => array(
            'Id' => 2,
            'Label' => '20 s',
        ),
        13 => array(
            'Id' => 3,
            'Label' => '1 min',
        ),
        14 => array(
            'Id' => 4,
            'Label' => '5 min',
        ),
        15 => array(
            'Id' => 5,
            'Label' => '10 min',
        ),
        16 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        17 => array(
            'Id' => 32,
            'Label' => '10 s',
        ),
        18 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        19 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        20 => array(
            'Id' => 128,
            'Label' => '5 min',
        ),
        21 => array(
            'Id' => 160,
            'Label' => '10 min',
        ),
        22 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        23 => array(
            'Id' => 32,
            'Label' => '10 s',
        ),
        24 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        25 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        26 => array(
            'Id' => 128,
            'Label' => '5 min',
        ),
        27 => array(
            'Id' => 160,
            'Label' => '10 min',
        ),
    );

}

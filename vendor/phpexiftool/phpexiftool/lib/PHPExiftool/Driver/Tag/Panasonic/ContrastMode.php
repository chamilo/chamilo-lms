<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ContrastMode extends AbstractTag
{

    protected $Id = 44;

    protected $Name = 'ContrastMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Contrast Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 6,
            'Label' => 'Medium Low',
        ),
        4 => array(
            'Id' => 7,
            'Label' => 'Medium High',
        ),
        5 => array(
            'Id' => 13,
            'Label' => 'High Dynamic',
        ),
        6 => array(
            'Id' => 256,
            'Label' => 'Low',
        ),
        7 => array(
            'Id' => 272,
            'Label' => 'Normal',
        ),
        8 => array(
            'Id' => 288,
            'Label' => 'High',
        ),
        9 => array(
            'Id' => 0,
            'Label' => '-2',
        ),
        10 => array(
            'Id' => 1,
            'Label' => '-1',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Normal',
        ),
        12 => array(
            'Id' => 3,
            'Label' => '+1',
        ),
        13 => array(
            'Id' => 4,
            'Label' => '+2',
        ),
        14 => array(
            'Id' => 7,
            'Label' => 'Nature (Color Film)',
        ),
        15 => array(
            'Id' => 9,
            'Label' => 'Expressive',
        ),
        16 => array(
            'Id' => 12,
            'Label' => 'Smooth (Color Film) or Pure (My Color)',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Dynamic (B&W Film)',
        ),
        18 => array(
            'Id' => 22,
            'Label' => 'Smooth (B&W Film)',
        ),
        19 => array(
            'Id' => 25,
            'Label' => 'High Dynamic',
        ),
        20 => array(
            'Id' => 26,
            'Label' => 'Retro',
        ),
        21 => array(
            'Id' => 27,
            'Label' => 'Dynamic (Color Film)',
        ),
        22 => array(
            'Id' => 28,
            'Label' => 'Low Key',
        ),
        23 => array(
            'Id' => 29,
            'Label' => 'Toy Effect',
        ),
        24 => array(
            'Id' => 32,
            'Label' => 'Vibrant (Color Film) or Expressive (My Color)',
        ),
        25 => array(
            'Id' => 33,
            'Label' => 'Elegant (My Color)',
        ),
        26 => array(
            'Id' => 37,
            'Label' => 'Nostalgic (Color Film)',
        ),
        27 => array(
            'Id' => 41,
            'Label' => 'Dynamic Art (My Color)',
        ),
        28 => array(
            'Id' => 42,
            'Label' => 'Retro (My Color)',
        ),
        29 => array(
            'Id' => 45,
            'Label' => 'Cinema',
        ),
        30 => array(
            'Id' => 47,
            'Label' => 'Dynamic Mono',
        ),
        31 => array(
            'Id' => 50,
            'Label' => 'Impressive Art',
        ),
        32 => array(
            'Id' => 51,
            'Label' => 'Cross Process',
        ),
        33 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        34 => array(
            'Id' => 1,
            'Label' => '-2',
        ),
        35 => array(
            'Id' => 2,
            'Label' => '+2',
        ),
        36 => array(
            'Id' => 5,
            'Label' => '-1',
        ),
        37 => array(
            'Id' => 6,
            'Label' => '+1',
        ),
    );

    protected $Index = 'mixed';

}

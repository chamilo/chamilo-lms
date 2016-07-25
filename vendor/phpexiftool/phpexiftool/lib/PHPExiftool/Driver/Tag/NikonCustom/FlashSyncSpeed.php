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
class FlashSyncSpeed extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashSyncSpeed';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Sync Speed';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1/250 s (auto FP)',
        ),
        1 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        2 => array(
            'Id' => 64,
            'Label' => '1/200 s',
        ),
        3 => array(
            'Id' => 96,
            'Label' => '1/160 s',
        ),
        4 => array(
            'Id' => 128,
            'Label' => '1/125 s',
        ),
        5 => array(
            'Id' => 160,
            'Label' => '1/100 s',
        ),
        6 => array(
            'Id' => 192,
            'Label' => '1/80 s',
        ),
        7 => array(
            'Id' => 224,
            'Label' => '1/60 s',
        ),
        8 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        9 => array(
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        10 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        11 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        12 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        13 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        14 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        15 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        16 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
        17 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        18 => array(
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        19 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        20 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        21 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        22 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        23 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        24 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        25 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
        26 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        27 => array(
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        28 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        29 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        30 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        31 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        32 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        33 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        34 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
        35 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        36 => array(
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        37 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        38 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        39 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        40 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        41 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        42 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        43 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
    );

    protected $Index = 'mixed';

}

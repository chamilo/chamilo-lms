<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        18 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        19 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        20 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        21 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        22 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        23 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        24 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
        25 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        26 => array(
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        27 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        28 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        29 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        30 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        31 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        32 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        33 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
        34 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        35 => array(
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        36 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        37 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        38 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        39 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        40 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        41 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        42 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
        43 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        44 => array(
            'Id' => 16,
            'Label' => '1/250 s (auto FP)',
        ),
        45 => array(
            'Id' => 32,
            'Label' => '1/250 s',
        ),
        46 => array(
            'Id' => 48,
            'Label' => '1/200 s',
        ),
        47 => array(
            'Id' => 64,
            'Label' => '1/160 s',
        ),
        48 => array(
            'Id' => 80,
            'Label' => '1/125 s',
        ),
        49 => array(
            'Id' => 96,
            'Label' => '1/100 s',
        ),
        50 => array(
            'Id' => 112,
            'Label' => '1/80 s',
        ),
        51 => array(
            'Id' => 128,
            'Label' => '1/60 s',
        ),
        52 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        53 => array(
            'Id' => 32,
            'Label' => '1/250 s (auto FP)',
        ),
        54 => array(
            'Id' => 48,
            'Label' => '1/250 s',
        ),
        55 => array(
            'Id' => 80,
            'Label' => '1/200 s',
        ),
        56 => array(
            'Id' => 96,
            'Label' => '1/160 s',
        ),
        57 => array(
            'Id' => 112,
            'Label' => '1/125 s',
        ),
        58 => array(
            'Id' => 128,
            'Label' => '1/100 s',
        ),
        59 => array(
            'Id' => 144,
            'Label' => '1/80 s',
        ),
        60 => array(
            'Id' => 160,
            'Label' => '1/60 s',
        ),
    );

    protected $Index = 'mixed';

}

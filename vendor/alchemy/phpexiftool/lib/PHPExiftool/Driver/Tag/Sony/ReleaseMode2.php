<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ReleaseMode2 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ReleaseMode2';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Release Mode 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Continuous - Exposure Bracketing',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Continuous - White Balance Bracketing',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Continuous - Burst',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Single Frame - Capture During Movie',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Continuous - Sweep Panorama',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Continuous - Anti-Motion Blur, Hand-held Twilight',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Continuous - HDR',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Continuous - Background defocus',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Continuous - 3D Sweep Panorama',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Continuous - High Resolution Sweep Panorama',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Continuous - 3D Image',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Continuous - Burst 2',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Continuous - Speed/Advance Priority',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Continuous - Multi Frame NR',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Single-frame - Exposure Bracketing',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Continuous Low',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Continuous - High Sensitivity',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Smile Shutter',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Continuous - Tele-zoom Advance Priority',
        ),
        146 => array(
            'Id' => 146,
            'Label' => 'Single Frame - Movie Capture',
        ),
    );

}

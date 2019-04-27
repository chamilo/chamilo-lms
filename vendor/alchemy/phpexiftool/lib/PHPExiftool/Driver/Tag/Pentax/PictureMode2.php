<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PictureMode2 extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'PictureMode2';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Picture Mode 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Scene Mode',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Auto PICT',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Green Mode',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Shutter Speed Priority',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Aperture Priority',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Program Tv Shift',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Program Av Shift',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Manual',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Bulb',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Aperture Priority, Off-Auto-Aperture',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Manual, Off-Auto-Aperture',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Bulb, Off-Auto-Aperture',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Shutter & Aperture Priority AE',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Sensitivity Priority AE',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Flash X-Sync Speed AE',
        ),
    );

}

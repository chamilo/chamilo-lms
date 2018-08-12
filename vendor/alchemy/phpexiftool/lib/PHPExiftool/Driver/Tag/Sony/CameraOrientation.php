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
class CameraOrientation extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CameraOrientation';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Camera Orientation';

    protected $flag_Permanent = true;

    protected $Index = 1;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Horizontal (normal)',
        ),
        1 => array(
            'Id' => 64,
            'Label' => 'Rotate 90 CW',
        ),
        2 => array(
            'Id' => 128,
            'Label' => 'Rotate 270 CW',
        ),
        3 => array(
            'Id' => 192,
            'Label' => 'Rotate 180',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Horizontal (normal)',
        ),
        5 => array(
            'Id' => 16,
            'Label' => 'Rotate 90 CW',
        ),
        6 => array(
            'Id' => 32,
            'Label' => 'Rotate 270 CW',
        ),
        7 => array(
            'Id' => 48,
            'Label' => 'Rotate 180',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Horizontal (normal)',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'Rotate 180',
        ),
        10 => array(
            'Id' => 6,
            'Label' => 'Rotate 90 CW',
        ),
        11 => array(
            'Id' => 8,
            'Label' => 'Rotate 270 CW',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Horizontal (normal)',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Rotate 180',
        ),
        14 => array(
            'Id' => 6,
            'Label' => 'Rotate 90 CW',
        ),
        15 => array(
            'Id' => 8,
            'Label' => 'Rotate 270 CW',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Horizontal (normal)',
        ),
        17 => array(
            'Id' => 3,
            'Label' => 'Rotate 180',
        ),
        18 => array(
            'Id' => 6,
            'Label' => 'Rotate 90 CW',
        ),
        19 => array(
            'Id' => 8,
            'Label' => 'Rotate 270 CW',
        ),
    );

}

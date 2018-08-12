<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class CameraOrientation extends AbstractTag
{

    protected $Id = 143;

    protected $Name = 'CameraOrientation';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Camera Orientation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Rotate CW',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Rotate 180',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Rotate CCW',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Tilt Upwards',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Tilt Downwards',
        ),
    );

}

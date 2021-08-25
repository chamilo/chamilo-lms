<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFSensorActive extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'AFSensorActive';

    protected $FullName = 'Minolta::CameraInfoA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Sensor Active';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Top-right',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Bottom-right',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bottom',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Middle Horizontal',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Center Vertical',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Top',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Top-left',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Bottom-left',
        ),
    );

}

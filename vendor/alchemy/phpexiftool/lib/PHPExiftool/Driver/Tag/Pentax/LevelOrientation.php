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
class LevelOrientation extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'LevelOrientation';

    protected $FullName = 'Pentax::LevelInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8s';

    protected $Writable = true;

    protected $Description = 'Level Orientation';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Horizontal (normal)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Rotate 180',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Rotate 90 CW',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Rotate 270 CW',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Horizontal; Off Level',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Rotate 180; Off Level',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Rotate 90 CW; Off Level',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Rotate 270 CW; Off Level',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Upwards',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Downwards',
        ),
    );

}

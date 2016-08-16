<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RMETA;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Azimuth extends AbstractTag
{

    protected $Id = 'Azimuth';

    protected $Name = 'Azimuth';

    protected $FullName = 'Ricoh::RMETA';

    protected $GroupName = 'RMETA';

    protected $g0 = 'APP5';

    protected $g1 = 'RMETA';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Azimuth';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'N',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'NNE',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'NE',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'ENE',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'E',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'ESE',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'SE',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'SSE',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'S',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'SSW',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'SW',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'WSW',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'W',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'WNW',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'NW',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'NNW',
        ),
    );

}

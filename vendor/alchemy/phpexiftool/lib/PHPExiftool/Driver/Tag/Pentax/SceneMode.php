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
class SceneMode extends AbstractTag
{

    protected $Id = 15;

    protected $Name = 'SceneMode';

    protected $FullName = 'Pentax::AEInfo2';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Scene Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'HDR',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Auto PICT',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Portrait',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Landscape',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Macro',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Sport',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Night Scene Portrait',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'No Flash',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Night Scene',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Surf & Snow',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Sunset',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Kids',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Pet',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Candlelight',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Museum',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Food',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Stage Lighting',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Night Snap',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Night Scene HDR',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Blue Sky',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Forest',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Backlight Silhouette',
        ),
    );

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Kodak;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SceneModeUsed extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SceneModeUsed';

    protected $FullName = 'Kodak::SubIFD2';

    protected $GroupName = 'Kodak';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Kodak';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Scene Mode Used';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Program',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Aperture Priority',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Shutter Priority',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Portrait',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Sport',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Children',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Museum',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'High ISO',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Text',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Macro',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Back Light',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Landscape',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Night Landscape',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Night Portrait',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Snow',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Beach',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Fireworks',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Sunset',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Candlelight',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Panorama',
        ),
    );

}

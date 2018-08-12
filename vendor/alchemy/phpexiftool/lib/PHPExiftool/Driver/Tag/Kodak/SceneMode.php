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
class SceneMode extends AbstractTag
{

    protected $Id = 64002;

    protected $Name = 'SceneMode';

    protected $FullName = 'Kodak::SubIFD0';

    protected $GroupName = 'Kodak';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Kodak';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Scene Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Sport',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Landscape',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Beach',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Night Portrait',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Night Landscape',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Snow',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Text',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Fireworks',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Macro',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Museum',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Children',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Program',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Aperture Priority',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Shutter Priority',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Manual',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Back Light',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Candlelight',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Sunset',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Panorama Left-right',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Panorama Right-left',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Smart Scene',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'High ISO',
        ),
    );

}

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
class AEProgramMode extends AbstractTag
{

    protected $Id = 6;

    protected $Name = 'AEProgramMode';

    protected $FullName = 'Pentax::AEInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AE Program Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'M, P or TAv',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Av, B or X',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Tv',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Sv or Green Mode',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Hi-speed Program',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Hi-speed Program (P-Shift)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'DOF Program',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'DOF Program (P-Shift)',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'MTF Program',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'MTF Program (P-Shift)',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Standard',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'Portrait',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Landscape',
        ),
        59 => array(
            'Id' => 59,
            'Label' => 'Macro',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Sport',
        ),
        75 => array(
            'Id' => 75,
            'Label' => 'Night Scene Portrait',
        ),
        83 => array(
            'Id' => 83,
            'Label' => 'No Flash',
        ),
        91 => array(
            'Id' => 91,
            'Label' => 'Night Scene',
        ),
        99 => array(
            'Id' => 99,
            'Label' => 'Surf & Snow',
        ),
        104 => array(
            'Id' => 104,
            'Label' => 'Night Snap',
        ),
        107 => array(
            'Id' => 107,
            'Label' => 'Text',
        ),
        115 => array(
            'Id' => 115,
            'Label' => 'Sunset',
        ),
        123 => array(
            'Id' => 123,
            'Label' => 'Kids',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'Pet',
        ),
        139 => array(
            'Id' => 139,
            'Label' => 'Candlelight',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'SCN',
        ),
        147 => array(
            'Id' => 147,
            'Label' => 'Museum',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Program',
        ),
        184 => array(
            'Id' => 184,
            'Label' => 'Shallow DOF Program',
        ),
        216 => array(
            'Id' => 216,
            'Label' => 'HDR',
        ),
    );

}

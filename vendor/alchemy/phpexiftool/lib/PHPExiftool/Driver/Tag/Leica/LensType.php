<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leica;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensType';

    protected $FullName = 'mixed';

    protected $GroupName = 'Leica';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Leica';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Lens Type';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Elmarit-M 21mm f/2.8',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Elmarit-M 28mm f/2.8 (III)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Tele-Elmarit-M 90mm f/2.8 (II)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Summilux-M 50mm f/1.4 (II)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Summicron-M 35mm f/2 (IV)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Summicron-M 90mm f/2 (II)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Elmarit-M 135mm f/2.8 (I/II)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Tri-Elmar-M 16-18-21mm f/4 ASPH.',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Summicron-M 50mm f/2 (III)',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Elmarit-M 21mm f/2.8 ASPH.',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Elmarit-M 24mm f/2.8 ASPH.',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Summicron-M 28mm f/2 ASPH.',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Elmarit-M 28mm f/2.8 (IV)',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Elmarit-M 28mm f/2.8 ASPH.',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Summilux-M 35mm f/1.4 ASPH.',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Summicron-M 35mm f/2 ASPH.',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Noctilux-M 50mm f/1',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Summilux-M 50mm f/1.4 ASPH.',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Summicron-M 50mm f/2 (IV, V)',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Elmar-M 50mm f/2.8',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Summilux-M 75mm f/1.4',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Apo-Summicron-M 75mm f/2 ASPH.',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Apo-Summicron-M 90mm f/2 ASPH.',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Elmarit-M 90mm f/2.8',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Macro-Elmar-M 90mm f/4',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Macro-Adapter M',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'Tri-Elmar-M 28-35-50mm f/4 ASPH.',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'Summarit-M 35mm f/2.5',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'Summarit-M 50mm f/2.5',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'Summarit-M 75mm f/2.5',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'Summarit-M 90mm f/2.5',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'Summilux-M 21mm f/1.4 ASPH.',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Summilux-M 24mm f/1.4 ASPH.',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Noctilux-M 50mm f/0.95 ASPH.',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Elmar-M 24mm f/3.8 ASPH.',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Super-Elmar-M 21mm f/3.4 Asph',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Super-Elmar-M 18mm f/3.8 ASPH.',
        ),
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'Uncoded lens',
        ),
        '6 0' => array(
            'Id' => '6 0',
            'Label' => 'Summilux-M 35mm f/1.4',
        ),
        '9 0' => array(
            'Id' => '9 0',
            'Label' => 'Apo-Telyt-M 135mm f/3.4',
        ),
        '16 1' => array(
            'Id' => '16 1',
            'Label' => 'Tri-Elmar-M 16-18-21mm f/4 ASPH. (at 16mm)',
        ),
        '16 2' => array(
            'Id' => '16 2',
            'Label' => 'Tri-Elmar-M 16-18-21mm f/4 ASPH. (at 18mm)',
        ),
        '16 3' => array(
            'Id' => '16 3',
            'Label' => 'Tri-Elmar-M 16-18-21mm f/4 ASPH. (at 21mm)',
        ),
        '29 0' => array(
            'Id' => '29 0',
            'Label' => 'Summilux-M 35mm f/1.4 ASPHERICAL',
        ),
        '31 0' => array(
            'Id' => '31 0',
            'Label' => 'Noctilux-M 50mm f/1.2',
        ),
        '39 0' => array(
            'Id' => '39 0',
            'Label' => 'Tele-Elmar-M 135mm f/4 (II)',
        ),
        '41 3' => array(
            'Id' => '41 3',
            'Label' => 'Apo-Summicron-M 50mm f/2 Asph',
        ),
        '42 1' => array(
            'Id' => '42 1',
            'Label' => 'Tri-Elmar-M 28-35-50mm f/4 ASPH. (at 28mm)',
        ),
        '42 2' => array(
            'Id' => '42 2',
            'Label' => 'Tri-Elmar-M 28-35-50mm f/4 ASPH. (at 35mm)',
        ),
        '42 3' => array(
            'Id' => '42 3',
            'Label' => 'Tri-Elmar-M 28-35-50mm f/4 ASPH. (at 50mm)',
        ),
        '51 2' => array(
            'Id' => '51 2',
            'Label' => 'Super-Elmar-M 14mm f/3.8 Asph',
        ),
        '53 2' => array(
            'Id' => '53 2',
            'Label' => 'Apo-Telyt-M 135mm f/3.4',
        ),
        '53 3' => array(
            'Id' => '53 3',
            'Label' => 'Apo-Summicron-M 50mm f/2 (VI)',
        ),
    );

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPoint extends AbstractTag
{

    protected $Id = 776;

    protected $Name = 'AFPoint';

    protected $FullName = 'Olympus::FocusInfo';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Top-left (horizontal)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top-center (horizontal)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Top-right (horizontal)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Left (horizontal)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Mid-left (horizontal)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Center (horizontal)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Mid-right (horizontal)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Right (horizontal)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Bottom-left (horizontal)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Bottom-center (horizontal)',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Bottom-right (horizontal)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Top-left (vertical)',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Top-center (vertical)',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Top-right (vertical)',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Left (vertical)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Mid-left (vertical)',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Center (vertical)',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Mid-right (vertical)',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Right (vertical)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Bottom-left (vertical)',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Bottom-center (vertical)',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Bottom-right (vertical)',
        ),
        23 => array(
            'Id' => 31,
            'Label' => 'n/a',
        ),
        24 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        25 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'Left (or n/a)',
        ),
        27 => array(
            'Id' => 1,
            'Label' => 'Center (horizontal)',
        ),
        28 => array(
            'Id' => 2,
            'Label' => 'Right',
        ),
        29 => array(
            'Id' => 3,
            'Label' => 'Center (vertical)',
        ),
        30 => array(
            'Id' => 255,
            'Label' => 'None',
        ),
    );

    protected $Index = 'mixed';

}

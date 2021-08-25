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
class ISOSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ISOSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'ISO Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 100,
        ),
        1 => array(
            'Id' => 1,
            'Label' => 200,
        ),
        2 => array(
            'Id' => 2,
            'Label' => 400,
        ),
        3 => array(
            'Id' => 3,
            'Label' => 800,
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 64,
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 100,
        ),
        8 => array(
            'Id' => 3,
            'Label' => 200,
        ),
        9 => array(
            'Id' => 4,
            'Label' => 400,
        ),
        10 => array(
            'Id' => 5,
            'Label' => 800,
        ),
        11 => array(
            'Id' => 6,
            'Label' => 1600,
        ),
        12 => array(
            'Id' => 7,
            'Label' => 3200,
        ),
        13 => array(
            'Id' => 8,
            'Label' => '200 (Zone Matching High)',
        ),
        14 => array(
            'Id' => 10,
            'Label' => '80 (Zone Matching Low)',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 100,
        ),
        17 => array(
            'Id' => 3,
            'Label' => 200,
        ),
        18 => array(
            'Id' => 4,
            'Label' => 400,
        ),
        19 => array(
            'Id' => 5,
            'Label' => 800,
        ),
        20 => array(
            'Id' => 6,
            'Label' => 1600,
        ),
        21 => array(
            'Id' => 7,
            'Label' => 3200,
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        23 => array(
            'Id' => 48,
            'Label' => 100,
        ),
        24 => array(
            'Id' => 56,
            'Label' => 200,
        ),
        25 => array(
            'Id' => 64,
            'Label' => 400,
        ),
        26 => array(
            'Id' => 72,
            'Label' => 800,
        ),
        27 => array(
            'Id' => 80,
            'Label' => 1600,
        ),
        28 => array(
            'Id' => 174,
            'Label' => '80 (Zone Matching Low)',
        ),
        29 => array(
            'Id' => 184,
            'Label' => '200 (Zone Matching High)',
        ),
    );

}

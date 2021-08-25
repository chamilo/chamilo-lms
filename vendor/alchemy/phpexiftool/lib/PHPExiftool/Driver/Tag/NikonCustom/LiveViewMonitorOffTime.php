<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LiveViewMonitorOffTime extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LiveViewMonitorOffTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Live View Monitor Off Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '5 min',
        ),
        1 => array(
            'Id' => 4,
            'Label' => '10 min',
        ),
        2 => array(
            'Id' => 8,
            'Label' => '15 min',
        ),
        3 => array(
            'Id' => 12,
            'Label' => '20 min',
        ),
        4 => array(
            'Id' => 16,
            'Label' => '30 min',
        ),
        5 => array(
            'Id' => 20,
            'Label' => 'No Limit',
        ),
        6 => array(
            'Id' => 0,
            'Label' => '3 min',
        ),
        7 => array(
            'Id' => 4,
            'Label' => '5 min',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        9 => array(
            'Id' => 12,
            'Label' => '15 min',
        ),
        10 => array(
            'Id' => 16,
            'Label' => '20 min',
        ),
        11 => array(
            'Id' => 20,
            'Label' => '30 min',
        ),
        12 => array(
            'Id' => 4,
            'Label' => '5 min',
        ),
        13 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        14 => array(
            'Id' => 12,
            'Label' => '15 min',
        ),
        15 => array(
            'Id' => 16,
            'Label' => '20 min',
        ),
        16 => array(
            'Id' => 20,
            'Label' => '30 min',
        ),
        17 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        18 => array(
            'Id' => 4,
            'Label' => '10 s',
        ),
        19 => array(
            'Id' => 8,
            'Label' => '20 s',
        ),
        20 => array(
            'Id' => 12,
            'Label' => '1 min',
        ),
        21 => array(
            'Id' => 16,
            'Label' => '5 min',
        ),
        22 => array(
            'Id' => 20,
            'Label' => '10 min',
        ),
        23 => array(
            'Id' => 4,
            'Label' => '5 min',
        ),
        24 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        25 => array(
            'Id' => 12,
            'Label' => '15 min',
        ),
        26 => array(
            'Id' => 16,
            'Label' => '20 min',
        ),
        27 => array(
            'Id' => 20,
            'Label' => '30 min',
        ),
        28 => array(
            'Id' => 24,
            'Label' => 'No Limit',
        ),
    );

}

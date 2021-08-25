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
class WhiteBalanceMode extends AbstractTag
{

    protected $Id = 26;

    protected $Name = 'WhiteBalanceMode';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'White Balance Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Auto (Daylight)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Auto (Shade)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Auto (Flash)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Auto (Tungsten)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Auto (Daylight Fluorescent)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Auto (Day White Fluorescent)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Auto (White Fluorescent)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Auto (Cloudy)',
        ),
        65534 => array(
            'Id' => 65534,
            'Label' => 'Unknown',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'User-Selected',
        ),
    );

}

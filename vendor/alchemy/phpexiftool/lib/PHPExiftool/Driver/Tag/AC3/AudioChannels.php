<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\AC3;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioChannels extends AbstractTag
{

    protected $Id = 'AudioChannels';

    protected $Name = 'AudioChannels';

    protected $FullName = 'M2TS::AC3';

    protected $GroupName = 'AC3';

    protected $g0 = 'M2TS';

    protected $g1 = 'AC3';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Audio Channels';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1 + 1',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 1,
        ),
        2 => array(
            'Id' => 2,
            'Label' => 2,
        ),
        3 => array(
            'Id' => 3,
            'Label' => 3,
        ),
        4 => array(
            'Id' => 4,
            'Label' => '2/1',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '3/1',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '2/2',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '3/2',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 1,
        ),
        9 => array(
            'Id' => 9,
            'Label' => '2 max',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '3 max',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '4 max',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '5 max',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '6 max',
        ),
    );

}

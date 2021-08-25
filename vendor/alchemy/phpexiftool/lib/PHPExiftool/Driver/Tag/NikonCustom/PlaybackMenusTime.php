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
class PlaybackMenusTime extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PlaybackMenusTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Playback Menus Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '8 s',
        ),
        1 => array(
            'Id' => 32,
            'Label' => '12 s',
        ),
        2 => array(
            'Id' => 64,
            'Label' => '20 s',
        ),
        3 => array(
            'Id' => 96,
            'Label' => '1 min',
        ),
        4 => array(
            'Id' => 128,
            'Label' => '10 min',
        ),
        5 => array(
            'Id' => 32,
            'Label' => '8 s',
        ),
        6 => array(
            'Id' => 128,
            'Label' => '20 s',
        ),
        7 => array(
            'Id' => 160,
            'Label' => '1 min',
        ),
        8 => array(
            'Id' => 192,
            'Label' => '5 min',
        ),
        9 => array(
            'Id' => 224,
            'Label' => '10 min',
        ),
    );

}

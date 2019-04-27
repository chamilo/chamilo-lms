<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sanyo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SanyoQuality extends AbstractTag
{

    protected $Id = 513;

    protected $Name = 'SanyoQuality';

    protected $FullName = 'Sanyo::Main';

    protected $GroupName = 'Sanyo';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sanyo';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Sanyo Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal/Very Low',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Normal/Low',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Normal/Medium Low',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Normal/Medium',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Normal/Medium High',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Normal/High',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Normal/Very High',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Normal/Super High',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Fine/Very Low',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'Fine/Low',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'Fine/Medium Low',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'Fine/Medium',
        ),
        260 => array(
            'Id' => 260,
            'Label' => 'Fine/Medium High',
        ),
        261 => array(
            'Id' => 261,
            'Label' => 'Fine/High',
        ),
        262 => array(
            'Id' => 262,
            'Label' => 'Fine/Very High',
        ),
        263 => array(
            'Id' => 263,
            'Label' => 'Fine/Super High',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Super Fine/Very Low',
        ),
        513 => array(
            'Id' => 513,
            'Label' => 'Super Fine/Low',
        ),
        514 => array(
            'Id' => 514,
            'Label' => 'Super Fine/Medium Low',
        ),
        515 => array(
            'Id' => 515,
            'Label' => 'Super Fine/Medium',
        ),
        516 => array(
            'Id' => 516,
            'Label' => 'Super Fine/Medium High',
        ),
        517 => array(
            'Id' => 517,
            'Label' => 'Super Fine/High',
        ),
        518 => array(
            'Id' => 518,
            'Label' => 'Super Fine/Very High',
        ),
        519 => array(
            'Id' => 519,
            'Label' => 'Super Fine/Super High',
        ),
    );

}

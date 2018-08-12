<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SetButtonCrossKeysFunc extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'SetButtonCrossKeysFunc';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Set Button Cross Keys Func';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Set: Quality',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Set: Parameter',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Set: Playback',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Cross keys: AF point select',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Set: Picture Style',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Set: Quality',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Set: Flash Exposure Comp',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Set: Playback',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Cross keys: AF point select',
        ),
    );

}

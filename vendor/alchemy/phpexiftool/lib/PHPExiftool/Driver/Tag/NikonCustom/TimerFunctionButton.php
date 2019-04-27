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
class TimerFunctionButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'TimerFunctionButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Timer Function Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Shooting Mode',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Image Quality/Size',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'ISO',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'White Balance',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Self-timer',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Self-timer',
        ),
        6 => array(
            'Id' => 8,
            'Label' => 'Release Mode',
        ),
        7 => array(
            'Id' => 16,
            'Label' => 'Image Quality/Size',
        ),
        8 => array(
            'Id' => 24,
            'Label' => 'ISO',
        ),
        9 => array(
            'Id' => 32,
            'Label' => 'White Balance',
        ),
        10 => array(
            'Id' => 40,
            'Label' => 'Active D-Lighting',
        ),
        11 => array(
            'Id' => 48,
            'Label' => '+ NEF (RAW)',
        ),
        12 => array(
            'Id' => 56,
            'Label' => 'Auto Bracketing',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Self-timer',
        ),
        14 => array(
            'Id' => 8,
            'Label' => 'Release Mode',
        ),
        15 => array(
            'Id' => 16,
            'Label' => 'Image Quality/Size',
        ),
        16 => array(
            'Id' => 24,
            'Label' => 'ISO',
        ),
        17 => array(
            'Id' => 32,
            'Label' => 'White Balance',
        ),
        18 => array(
            'Id' => 40,
            'Label' => 'Active D-Lighting',
        ),
        19 => array(
            'Id' => 48,
            'Label' => '+ NEF (RAW)',
        ),
        20 => array(
            'Id' => 56,
            'Label' => 'Auto Bracketing',
        ),
    );

}

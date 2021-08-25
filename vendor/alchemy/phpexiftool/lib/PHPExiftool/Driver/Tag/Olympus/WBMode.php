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
class WBMode extends AbstractTag
{

    protected $Id = 4117;

    protected $Name = 'WBMode';

    protected $FullName = 'Olympus::Main';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'WB Mode';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 'Auto',
        ),
        '1 2' => array(
            'Id' => '1 2',
            'Label' => 'Auto (2)',
        ),
        '1 4' => array(
            'Id' => '1 4',
            'Label' => 'Auto (4)',
        ),
        '2 2' => array(
            'Id' => '2 2',
            'Label' => '3000 Kelvin',
        ),
        '2 3' => array(
            'Id' => '2 3',
            'Label' => '3700 Kelvin',
        ),
        '2 4' => array(
            'Id' => '2 4',
            'Label' => '4000 Kelvin',
        ),
        '2 5' => array(
            'Id' => '2 5',
            'Label' => '4500 Kelvin',
        ),
        '2 6' => array(
            'Id' => '2 6',
            'Label' => '5500 Kelvin',
        ),
        '2 7' => array(
            'Id' => '2 7',
            'Label' => '6500 Kelvin',
        ),
        '2 8' => array(
            'Id' => '2 8',
            'Label' => '7500 Kelvin',
        ),
        '3 0' => array(
            'Id' => '3 0',
            'Label' => 'One-touch',
        ),
    );

}

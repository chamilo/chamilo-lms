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
class DriveMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'DriveMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Drive Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Self-timer',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Bracketing',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Interval',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'UHS continuous',
        ),
        6 => array(
            'Id' => 7,
            'Label' => 'HS continuous',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Single Frame',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Self-timer',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'Continuous Bracketing',
        ),
        11 => array(
            'Id' => 4,
            'Label' => 'Single-Frame Bracketing',
        ),
        12 => array(
            'Id' => 5,
            'Label' => 'White Balance Bracketing',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Self-timer 10 sec',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Single-frame Exposure Bracketing',
        ),
        16 => array(
            'Id' => 3,
            'Label' => 'Continuous Exposure Bracketing',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'Self-Timer 2 sec',
        ),
        18 => array(
            'Id' => 5,
            'Label' => 'Single Frame',
        ),
        19 => array(
            'Id' => 8,
            'Label' => 'White Balance Bracketing Low',
        ),
        20 => array(
            'Id' => 9,
            'Label' => 'White Balance Bracketing High',
        ),
    );

}

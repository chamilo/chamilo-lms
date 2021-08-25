<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

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

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Drive Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Single Frame',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Continuous High',
        ),
        2 => array(
            'Id' => 4,
            'Label' => 'Self-timer 10 sec',
        ),
        3 => array(
            'Id' => 5,
            'Label' => 'Self-timer 2 sec, Mirror Lock-up',
        ),
        4 => array(
            'Id' => 6,
            'Label' => 'Single-frame Bracketing',
        ),
        5 => array(
            'Id' => 7,
            'Label' => 'Continuous Bracketing',
        ),
        6 => array(
            'Id' => 10,
            'Label' => 'Remote Commander',
        ),
        7 => array(
            'Id' => 11,
            'Label' => 'Mirror Lock-up',
        ),
        8 => array(
            'Id' => 18,
            'Label' => 'Continuous Low',
        ),
        9 => array(
            'Id' => 24,
            'Label' => 'White Balance Bracketing Low',
        ),
        10 => array(
            'Id' => 25,
            'Label' => 'D-Range Optimizer Bracketing Low',
        ),
        11 => array(
            'Id' => 40,
            'Label' => 'White Balance Bracketing High',
        ),
        12 => array(
            'Id' => 41,
            'Label' => 'D-Range Optimizer Bracketing High',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Single Frame',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Continuous High',
        ),
        15 => array(
            'Id' => 4,
            'Label' => 'Self-timer 10 sec',
        ),
        16 => array(
            'Id' => 5,
            'Label' => 'Self-timer 2 sec, Mirror Lock-up',
        ),
        17 => array(
            'Id' => 7,
            'Label' => 'Continuous Bracketing',
        ),
        18 => array(
            'Id' => 10,
            'Label' => 'Remote Commander',
        ),
        19 => array(
            'Id' => 11,
            'Label' => 'Continuous Self-timer',
        ),
        20 => array(
            'Id' => 16,
            'Label' => 'Single Frame',
        ),
        21 => array(
            'Id' => 33,
            'Label' => 'Continuous High',
        ),
        22 => array(
            'Id' => 34,
            'Label' => 'Continuous Low',
        ),
        23 => array(
            'Id' => 48,
            'Label' => 'Speed Priority Continuous',
        ),
        24 => array(
            'Id' => 81,
            'Label' => 'Self-timer 10 sec',
        ),
        25 => array(
            'Id' => 82,
            'Label' => 'Self-timer 2 sec, Mirror Lock-up',
        ),
        26 => array(
            'Id' => 113,
            'Label' => 'Continuous Bracketing 0.3 EV',
        ),
        27 => array(
            'Id' => 117,
            'Label' => 'Continuous Bracketing 0.7 EV',
        ),
        28 => array(
            'Id' => 145,
            'Label' => 'White Balance Bracketing Low',
        ),
        29 => array(
            'Id' => 146,
            'Label' => 'White Balance Bracketing High',
        ),
        30 => array(
            'Id' => 192,
            'Label' => 'Remote Commander',
        ),
        31 => array(
            'Id' => 209,
            'Label' => 'Continuous - HDR',
        ),
        32 => array(
            'Id' => 210,
            'Label' => 'Continuous - Multi Frame NR',
        ),
        33 => array(
            'Id' => 211,
            'Label' => 'Continuous - Handheld Night Shot',
        ),
        34 => array(
            'Id' => 212,
            'Label' => 'Continuous - Anti Motion Blur',
        ),
        35 => array(
            'Id' => 213,
            'Label' => 'Continuous - Sweep Panorama',
        ),
        36 => array(
            'Id' => 214,
            'Label' => 'Continuous - 3D Sweep Panorama',
        ),
    );

}

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
class DriveModeSetting extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'DriveModeSetting';

    protected $FullName = 'Sony::CameraSettings3';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Drive Mode Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        16 => array(
            'Id' => 16,
            'Label' => 'Single Frame',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Continuous High',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Continuous Low',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Speed Priority Continuous',
        ),
        81 => array(
            'Id' => 81,
            'Label' => 'Self-timer 10 sec',
        ),
        82 => array(
            'Id' => 82,
            'Label' => 'Self-timer 2 sec, Mirror Lock-up',
        ),
        113 => array(
            'Id' => 113,
            'Label' => 'Continuous Bracketing 0.3 EV',
        ),
        117 => array(
            'Id' => 117,
            'Label' => 'Continuous Bracketing 0.7 EV',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'White Balance Bracketing Low',
        ),
        146 => array(
            'Id' => 146,
            'Label' => 'White Balance Bracketing High',
        ),
        192 => array(
            'Id' => 192,
            'Label' => 'Remote Commander',
        ),
    );

}

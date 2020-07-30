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
class DriveMode2 extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'DriveMode2';

    protected $FullName = 'Minolta::CameraSettingsA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Drive Mode 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Self-timer 10 sec',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Self-timer 2 sec',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Single Frame',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'White Balance Bracketing Low',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'White Balance Bracketing High',
        ),
        770 => array(
            'Id' => 770,
            'Label' => 'Single-frame Bracketing Low',
        ),
        771 => array(
            'Id' => 771,
            'Label' => 'Continous Bracketing Low',
        ),
        1794 => array(
            'Id' => 1794,
            'Label' => 'Single-frame Bracketing High',
        ),
        1795 => array(
            'Id' => 1795,
            'Label' => 'Continuous Bracketing High',
        ),
    );

}

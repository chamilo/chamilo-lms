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
class ShootingModeSetting extends AbstractTag
{

    protected $Id = '5.1';

    protected $Name = 'ShootingModeSetting';

    protected $FullName = 'NikonCustom::SettingsD40';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Shooting Mode Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single Frame',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Continuous',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Self-timer',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Delayed Remote',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Quick-response Remote',
        ),
    );

}

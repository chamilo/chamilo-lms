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
class BatteryState extends AbstractTag
{

    protected $Id = 96;

    protected $Name = 'BatteryState';

    protected $FullName = 'Minolta::CameraSettingsA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Battery State';

    protected $flag_Permanent = true;

    protected $Values = array(
        3 => array(
            'Id' => 3,
            'Label' => 'Very Low',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Low',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Half Full',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Sufficient Power Remaining',
        ),
    );

}

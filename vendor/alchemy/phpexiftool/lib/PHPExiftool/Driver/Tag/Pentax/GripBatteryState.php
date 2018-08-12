<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GripBatteryState extends AbstractTag
{

    protected $Id = '1.2';

    protected $Name = 'GripBatteryState';

    protected $FullName = 'Pentax::BatteryInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Grip Battery State';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Empty or Missing',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Almost Empty',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Running Low',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Full',
        ),
    );

    protected $Index = 'mixed';

}

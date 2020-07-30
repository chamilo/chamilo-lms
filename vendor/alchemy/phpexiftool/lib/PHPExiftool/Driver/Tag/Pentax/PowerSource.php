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
class PowerSource extends AbstractTag
{

    protected $Id = '0.1';

    protected $Name = 'PowerSource';

    protected $FullName = 'Pentax::BatteryInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Power Source';

    protected $flag_Permanent = true;

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => 'Body Battery',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Grip Battery',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'External Power Supply',
        ),
    );

}

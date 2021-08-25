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
class AFOnForMBD12 extends AbstractTag
{

    protected $Id = '50.1';

    protected $Name = 'AF-OnForMB-D12';

    protected $FullName = 'NikonCustom::SettingsD810';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF-On For MB-D12';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AE/AF Lock',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AE Lock Only',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AF Lock Only',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AE Lock (hold)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AE Lock (reset)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AF-On',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'FV Lock',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Same As Fn Button',
        ),
    );

}

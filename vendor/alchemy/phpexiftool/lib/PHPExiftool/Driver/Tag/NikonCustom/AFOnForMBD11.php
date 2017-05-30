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
class AFOnForMBD11 extends AbstractTag
{

    protected $Id = '2.2';

    protected $Name = 'AF-OnForMB-D11';

    protected $FullName = 'NikonCustom::SettingsD7000';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF-On For MB-D11';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AE/AF Lock',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'AF Lock Only',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'AE Lock (hold)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'AF-ON',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'FV Lock',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Same as FUNC Button',
        ),
    );

}

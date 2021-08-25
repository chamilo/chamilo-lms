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
class AFOnForMBD10 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AF-OnForMB-D10';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF-On For MB-D10';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF-On',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'AE/AF Lock',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'AE Lock Only',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'AE Lock (reset on release)',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'AE Lock (hold)',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'AF Lock Only',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Same as FUNC Button',
        ),
    );

}

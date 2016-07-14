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
class AssignRemoteFnButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AssignRemoteFnButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assign Remote Fn Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Live View',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
    );

}

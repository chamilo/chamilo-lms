<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SetFunctionWhenShooting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SetFunctionWhenShooting';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Set Function When Shooting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Default (no function)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Change quality',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Change Parameters',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Menu display',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Image replay',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Default (no function)',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Change quality',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Change Picture Style',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Menu display',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Image replay',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Default (no function)',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Change quality',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Change Parameters',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Menu display',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 'Image replay',
        ),
    );

}

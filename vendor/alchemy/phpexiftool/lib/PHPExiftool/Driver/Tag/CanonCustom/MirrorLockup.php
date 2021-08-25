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
class MirrorLockup extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MirrorLockup';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Mirror Lockup';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Enable: Down with Set',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Disable',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Enable',
        ),
    );

}

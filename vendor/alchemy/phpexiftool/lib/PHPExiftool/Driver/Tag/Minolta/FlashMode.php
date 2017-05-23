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
class FlashMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Fill flash',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Red-eye reduction',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Rear flash sync',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Wireless',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Off?',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Red-eye reduction',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Rear flash sync',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Red-eye reduction',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Rear flash sync',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Rear Sync',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Wireless',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 'Fill Flash',
        ),
    );

}

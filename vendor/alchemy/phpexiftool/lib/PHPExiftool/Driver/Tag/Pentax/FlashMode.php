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
class FlashMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto, Did not fire',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Off, Did not fire',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'On, Did not fire',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Auto, Did not fire, Red-eye reduction',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'On, Did not fire, Wireless (Master)',
        ),
        5 => array(
            'Id' => 256,
            'Label' => 'Auto, Fired',
        ),
        6 => array(
            'Id' => 258,
            'Label' => 'On, Fired',
        ),
        7 => array(
            'Id' => 259,
            'Label' => 'Auto, Fired, Red-eye reduction',
        ),
        8 => array(
            'Id' => 260,
            'Label' => 'On, Red-eye reduction',
        ),
        9 => array(
            'Id' => 261,
            'Label' => 'On, Wireless (Master)',
        ),
        10 => array(
            'Id' => 262,
            'Label' => 'On, Wireless (Control)',
        ),
        11 => array(
            'Id' => 264,
            'Label' => 'On, Soft',
        ),
        12 => array(
            'Id' => 265,
            'Label' => 'On, Slow-sync',
        ),
        13 => array(
            'Id' => 266,
            'Label' => 'On, Slow-sync, Red-eye reduction',
        ),
        14 => array(
            'Id' => 267,
            'Label' => 'On, Trailing-curtain Sync',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'Off',
        ),
        18 => array(
            'Id' => 6,
            'Label' => 'Red-eye reduction',
        ),
    );

}

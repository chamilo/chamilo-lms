<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\H264;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Flash extends AbstractTag
{

    protected $Id = 166;

    protected $Name = 'Flash';

    protected $FullName = 'H264::MDPM';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Flash';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Flash',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Fired, Return not detected',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Fired, Return detected',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'On, Did not fire',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'On, Fired',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'On, Return not detected',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'On, Return detected',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Off, Did not fire',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Off, Did not fire, Return not detected',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Auto, Did not fire',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Auto, Fired',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Auto, Fired, Return not detected',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Auto, Fired, Return detected',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'No flash function',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Off, No flash function',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'Fired, Red-eye reduction',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'Fired, Red-eye reduction, Return not detected',
        ),
        71 => array(
            'Id' => 71,
            'Label' => 'Fired, Red-eye reduction, Return detected',
        ),
        73 => array(
            'Id' => 73,
            'Label' => 'On, Red-eye reduction',
        ),
        77 => array(
            'Id' => 77,
            'Label' => 'On, Red-eye reduction, Return not detected',
        ),
        79 => array(
            'Id' => 79,
            'Label' => 'On, Red-eye reduction, Return detected',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Off, Red-eye reduction',
        ),
        88 => array(
            'Id' => 88,
            'Label' => 'Auto, Did not fire, Red-eye reduction',
        ),
        89 => array(
            'Id' => 89,
            'Label' => 'Auto, Fired, Red-eye reduction',
        ),
        93 => array(
            'Id' => 93,
            'Label' => 'Auto, Fired, Red-eye reduction, Return not detected',
        ),
        95 => array(
            'Id' => 95,
            'Label' => 'Auto, Fired, Red-eye reduction, Return detected',
        ),
    );

}

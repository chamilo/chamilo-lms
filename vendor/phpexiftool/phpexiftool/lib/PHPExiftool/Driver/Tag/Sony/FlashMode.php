<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

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

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'ADI',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'TTL',
        ),
        2 => array(
            'Id' => 1,
            'Label' => 'Flash Off',
        ),
        3 => array(
            'Id' => 16,
            'Label' => 'Autoflash',
        ),
        4 => array(
            'Id' => 17,
            'Label' => 'Fill-flash',
        ),
        5 => array(
            'Id' => 18,
            'Label' => 'Slow Sync',
        ),
        6 => array(
            'Id' => 19,
            'Label' => 'Rear Sync',
        ),
        7 => array(
            'Id' => 20,
            'Label' => 'Wireless',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Flash Off',
        ),
        9 => array(
            'Id' => 16,
            'Label' => 'Autoflash',
        ),
        10 => array(
            'Id' => 17,
            'Label' => 'Fill-flash',
        ),
        11 => array(
            'Id' => 18,
            'Label' => 'Slow Sync',
        ),
        12 => array(
            'Id' => 19,
            'Label' => 'Rear Sync',
        ),
        13 => array(
            'Id' => 20,
            'Label' => 'Wireless',
        ),
    );

}

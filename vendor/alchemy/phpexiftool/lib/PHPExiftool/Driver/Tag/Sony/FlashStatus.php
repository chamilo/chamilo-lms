<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class FlashStatus extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashStatus';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Status';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'External',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Built-in',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'External',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'No Flash present',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Flash Inhibited',
        ),
        7 => array(
            'Id' => 64,
            'Label' => 'Built-in Flash present',
        ),
        8 => array(
            'Id' => 65,
            'Label' => 'Built-in Flash Fired',
        ),
        9 => array(
            'Id' => 66,
            'Label' => 'Built-in Flash Inhibited',
        ),
        10 => array(
            'Id' => 128,
            'Label' => 'External Flash present',
        ),
        11 => array(
            'Id' => 129,
            'Label' => 'External Flash Fired',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'No Flash present',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'Flash Inhibited',
        ),
        14 => array(
            'Id' => 64,
            'Label' => 'Built-in Flash present',
        ),
        15 => array(
            'Id' => 65,
            'Label' => 'Built-in Flash Fired',
        ),
        16 => array(
            'Id' => 66,
            'Label' => 'Built-in Flash Inhibited',
        ),
        17 => array(
            'Id' => 128,
            'Label' => 'External Flash present',
        ),
        18 => array(
            'Id' => 129,
            'Label' => 'External Flash Fired',
        ),
    );

}

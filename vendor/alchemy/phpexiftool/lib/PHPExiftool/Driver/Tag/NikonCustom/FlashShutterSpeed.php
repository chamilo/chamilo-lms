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
class FlashShutterSpeed extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashShutterSpeed';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Shutter Speed';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1/60 s',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1/30 s',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '1/15 s',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1/8 s',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '1/4 s',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '1/2 s',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '1 s',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '2 s',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '4 s',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '8 s',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '15 s',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '30 s',
        ),
    );

}

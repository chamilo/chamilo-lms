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
class AFPointsSelected extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'AFPointsSelected';

    protected $FullName = 'Sony::Tag940a';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'AF Points Selected';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Upper-right',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Lower-right',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Bottom',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Lower-left',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Left',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Upper-left',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Far Right',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Far Left',
        ),
        2048 => array(
            'Id' => 2048,
            'Label' => 'Upper-middle',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'Near Right',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'Lower-middle',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'Near Left',
        ),
        30721 => array(
            'Id' => 30721,
            'Label' => 'Center Zone',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Upper Far Right',
        ),
        65536 => array(
            'Id' => 65536,
            'Label' => 'Lower Far Right',
        ),
        98844 => array(
            'Id' => 98844,
            'Label' => 'Right Zone',
        ),
        131072 => array(
            'Id' => 131072,
            'Label' => 'Lower Far Left',
        ),
        262144 => array(
            'Id' => 262144,
            'Label' => 'Upper Far Left',
        ),
        394688 => array(
            'Id' => 394688,
            'Label' => 'Left Zone',
        ),
        '2147483647' => array(
            'Id' => '2147483647',
            'Label' => '(all)',
        ),
    );

}

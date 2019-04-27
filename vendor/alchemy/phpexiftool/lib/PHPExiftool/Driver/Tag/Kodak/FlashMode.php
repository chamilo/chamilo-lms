<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Kodak;

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

    protected $GroupName = 'Kodak';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Kodak';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Fill Flash',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Red-Eye',
        ),
        4 => array(
            'Id' => 16,
            'Label' => 'Fill Flash',
        ),
        5 => array(
            'Id' => 32,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 64,
            'Label' => 'Red-Eye?',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'Red-Eye',
        ),
    );

}

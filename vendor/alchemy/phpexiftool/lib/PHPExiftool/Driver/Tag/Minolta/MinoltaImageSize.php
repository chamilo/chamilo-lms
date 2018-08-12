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
class MinoltaImageSize extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MinoltaImageSize';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Minolta Image Size';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Full',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1600x1200',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '1280x960',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '640x480',
        ),
        4 => array(
            'Id' => 6,
            'Label' => '2080x1560',
        ),
        5 => array(
            'Id' => 7,
            'Label' => '2560x1920',
        ),
        6 => array(
            'Id' => 8,
            'Label' => '3264x2176',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Large',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Medium',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Small',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Large',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Medium',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Small',
        ),
        13 => array(
            'Id' => 1,
            'Label' => '1600x1200',
        ),
        14 => array(
            'Id' => 2,
            'Label' => '1280x960',
        ),
        15 => array(
            'Id' => 3,
            'Label' => '640x480',
        ),
        16 => array(
            'Id' => 5,
            'Label' => '2560x1920',
        ),
        17 => array(
            'Id' => 6,
            'Label' => '2272x1704',
        ),
        18 => array(
            'Id' => 7,
            'Label' => '2048x1536',
        ),
    );

    protected $Index = 'mixed';

}

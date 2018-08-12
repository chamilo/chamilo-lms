<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CFALayout extends AbstractTag
{

    protected $Id = 50711;

    protected $Name = 'CFALayout';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'CFA Layout';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Rectangular',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Even columns offset down 1/2 row',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Even columns offset up 1/2 row',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Even rows offset right 1/2 column',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Even rows offset left 1/2 column',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Even rows offset up by 1/2 row, even columns offset left by 1/2 column',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Even rows offset up by 1/2 row, even columns offset right by 1/2 column',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Even rows offset down by 1/2 row, even columns offset left by 1/2 column',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Even rows offset down by 1/2 row, even columns offset right by 1/2 column',
        ),
    );

}

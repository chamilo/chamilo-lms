<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ComponentsConfiguration extends AbstractTag
{

    protected $Id = 800;

    protected $Name = 'ComponentsConfiguration';

    protected $FullName = 'DPX::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Components Configuration';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'User-defined single component',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Red (R)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Green (G)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Blue (B)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Alpha (matte)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Luminance (Y)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Chrominance (Cb, Cr, subsampled by two)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Depth (Z)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Composite video',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'R, G, B',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'R, G, B, Alpha',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Alpha, B, G, R',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'Cb, Y, Cr, Y (4:2:2)',
        ),
        101 => array(
            'Id' => 101,
            'Label' => 'Cb, Y, A, Cr, Y, A (4:2:2:4)',
        ),
        102 => array(
            'Id' => 102,
            'Label' => 'Cb, Y, Cr (4:4:4)',
        ),
        103 => array(
            'Id' => 103,
            'Label' => 'Cb, Y, Cr, A (4:4:4:4)',
        ),
        150 => array(
            'Id' => 150,
            'Label' => 'User-defined 2 component element',
        ),
        151 => array(
            'Id' => 151,
            'Label' => 'User-defined 3 component element',
        ),
        152 => array(
            'Id' => 152,
            'Label' => 'User-defined 4 component element',
        ),
        153 => array(
            'Id' => 153,
            'Label' => 'User-defined 5 component element',
        ),
        154 => array(
            'Id' => 154,
            'Label' => 'User-defined 6 component element',
        ),
        155 => array(
            'Id' => 155,
            'Label' => 'User-defined 7 component element',
        ),
        156 => array(
            'Id' => 156,
            'Label' => 'User-defined 8 component element',
        ),
    );

}

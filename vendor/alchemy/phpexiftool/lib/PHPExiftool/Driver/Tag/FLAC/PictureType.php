<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLAC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PictureType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'PictureType';

    protected $FullName = 'FLAC::Picture';

    protected $GroupName = 'FLAC';

    protected $g0 = 'FLAC';

    protected $g1 = 'FLAC';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Picture Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Other',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '32x32 PNG Icon',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Other Icon',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Front Cover',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Back Cover',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Leaflet',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Media',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Lead Artist',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Artist',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Conductor',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Band',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Composer',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Lyricist',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Recording Studio or Location',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Recording Session',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Performance',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Capture from Movie or Video',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Bright(ly) Colored Fish',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Illustration',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Band Logo',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Publisher Logo',
        ),
    );

}

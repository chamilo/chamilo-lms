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
class FaxProfile extends AbstractTag
{

    protected $Id = 402;

    protected $Name = 'FaxProfile';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Fax Profile';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Minimal B&W lossless, S',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Extended B&W lossless, F',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Lossless JBIG B&W, J',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Lossy color and grayscale, C',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Lossless color and grayscale, L',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Mixed raster content, M',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Profile T',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Multi Profiles',
        ),
    );

}

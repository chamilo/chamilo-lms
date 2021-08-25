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
class ImageDataDiscard extends AbstractTag
{

    protected $Id = 48324;

    protected $Name = 'ImageDataDiscard';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Image Data Discard';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Full Resolution',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Flexbits Discarded',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'HighPass Frequency Data Discarded',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Highpass and LowPass Frequency Data Discarded',
        ),
    );

}

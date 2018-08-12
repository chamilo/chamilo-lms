<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IFD0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Thresholding extends AbstractTag
{

    protected $Id = 263;

    protected $Name = 'Thresholding';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Thresholding';

    protected $flag_Unsafe = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'No dithering or halftoning',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Ordered dither or halftone',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Randomized dither',
        ),
    );

}

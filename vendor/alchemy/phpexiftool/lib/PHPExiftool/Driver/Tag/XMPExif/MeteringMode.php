<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExif;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MeteringMode extends AbstractTag
{

    protected $Id = 'MeteringMode';

    protected $Name = 'MeteringMode';

    protected $FullName = 'XMP::exif';

    protected $GroupName = 'XMP-exif';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exif';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Metering Mode';

    protected $local_g2 = 'Camera';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Average',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Center-weighted average',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Spot',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Multi-spot',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Multi-segment',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Partial',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Other',
        ),
    );

}

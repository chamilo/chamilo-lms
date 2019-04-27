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
class SensingMethod extends AbstractTag
{

    protected $Id = 'SensingMethod';

    protected $Name = 'SensingMethod';

    protected $FullName = 'XMP::exif';

    protected $GroupName = 'XMP-exif';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exif';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Sensing Method';

    protected $local_g2 = 'Camera';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Monochrome area',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'One-chip color area',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Two-chip color area',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Three-chip color area',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Color sequential area',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Monochrome linear',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Trilinear',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Color sequential linear',
        ),
    );

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\H264;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExposureProgram extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExposureProgram';

    protected $FullName = 'mixed';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Exposure Program';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'Gain',
        ),
        2 => array(
            'Id' => 32,
            'Label' => 'Shutter speed priority AE',
        ),
        3 => array(
            'Id' => 48,
            'Label' => 'Aperture-priority AE',
        ),
        4 => array(
            'Id' => 64,
            'Label' => 'Manual',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Not Defined',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        10 => array(
            'Id' => 5,
            'Label' => 'Creative (Slow speed)',
        ),
        11 => array(
            'Id' => 6,
            'Label' => 'Action (High speed)',
        ),
        12 => array(
            'Id' => 7,
            'Label' => 'Portrait',
        ),
        13 => array(
            'Id' => 8,
            'Label' => 'Landscape',
        ),
    );

}

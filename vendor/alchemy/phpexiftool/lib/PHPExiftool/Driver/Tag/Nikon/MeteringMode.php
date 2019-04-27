<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MeteringMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MeteringMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Metering Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
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

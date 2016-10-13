<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SweepPanoramaDirection extends AbstractTag
{

    protected $Id = 147;

    protected $Name = 'SweepPanoramaDirection';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Sweep Panorama Direction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Left to Right',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Right to Left',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Top to Bottom',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Bottom to Top',
        ),
    );

}

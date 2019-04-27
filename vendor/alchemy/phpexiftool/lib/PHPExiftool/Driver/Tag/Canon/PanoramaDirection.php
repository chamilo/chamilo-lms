<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PanoramaDirection extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'PanoramaDirection';

    protected $FullName = 'Canon::Panorama';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Panorama Direction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Left to Right',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Right to Left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bottom to Top',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Top to Bottom',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '2x2 Matrix (Clockwise)',
        ),
    );

}

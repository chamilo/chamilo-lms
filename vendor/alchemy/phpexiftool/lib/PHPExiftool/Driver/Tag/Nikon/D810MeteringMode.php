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
class D810MeteringMode extends AbstractTag
{

    protected $Id = 5982;

    protected $Name = 'D810MeteringMode';

    protected $FullName = 'Nikon::ShotInfoD810';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'D810 Metering Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Matrix',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Spot',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Highlight',
        ),
    );

}

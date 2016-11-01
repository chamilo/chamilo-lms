<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GPS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSDifferential extends AbstractTag
{

    protected $Id = 30;

    protected $Name = 'GPSDifferential';

    protected $FullName = 'GPS::Main';

    protected $GroupName = 'GPS';

    protected $g0 = 'EXIF';

    protected $g1 = 'GPS';

    protected $g2 = 'Location';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'GPS Differential';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Correction',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Differential Corrected',
        ),
    );

}

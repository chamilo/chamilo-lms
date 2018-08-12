<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEGPS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSMeasureMode extends AbstractTag
{

    protected $Id = 'MeasureMode';

    protected $Name = 'GPSMeasureMode';

    protected $FullName = 'MIE::GPS';

    protected $GroupName = 'MIE-GPS';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-GPS';

    protected $g2 = 'Location';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'GPS Measure Mode';

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => '2-D',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3-D',
        ),
    );

}

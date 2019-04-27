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
class GPSDifferential extends AbstractTag
{

    protected $Id = 'Differential';

    protected $Name = 'GPSDifferential';

    protected $FullName = 'MIE::GPS';

    protected $GroupName = 'MIE-GPS';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-GPS';

    protected $g2 = 'Location';

    protected $Type = 'int8u';

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

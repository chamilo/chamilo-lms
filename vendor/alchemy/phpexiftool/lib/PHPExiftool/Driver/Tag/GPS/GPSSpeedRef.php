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
class GPSSpeedRef extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'GPSSpeedRef';

    protected $FullName = 'mixed';

    protected $GroupName = 'GPS';

    protected $g0 = 'mixed';

    protected $g1 = 'mixed';

    protected $g2 = 'mixed';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'GPS Speed Ref';

    protected $MaxLength = 2;

    protected $Values = array(
        'K' => array(
            'Id' => 'K',
            'Label' => 'km/h',
        ),
        'M' => array(
            'Id' => 'M',
            'Label' => 'mph',
        ),
        'N' => array(
            'Id' => 'N',
            'Label' => 'knots',
        ),
    );

    protected $local_g1 = 'mixed';

    protected $local_g2 = 'mixed';

}

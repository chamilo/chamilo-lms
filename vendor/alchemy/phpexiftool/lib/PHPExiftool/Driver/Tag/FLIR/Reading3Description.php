<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLIR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Reading3Description extends AbstractTag
{

    protected $Id = 228;

    protected $Name = 'Reading3Description';

    protected $FullName = 'FLIR::MeterLink';

    protected $GroupName = 'FLIR';

    protected $g0 = 'APP1';

    protected $g1 = 'FLIR';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Reading 3 Description';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Humidity',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Moisture',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Dew Point',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Air Temperature',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'IR Temperature',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Difference Temperature',
        ),
    );

}

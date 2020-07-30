<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class QuantizationMethod extends AbstractTag
{

    protected $Id = 120;

    protected $Name = 'QuantizationMethod';

    protected $FullName = 'IPTC::NewsPhoto';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Quantization Method';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Linear Reflectance/Transmittance',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Linear Density',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'IPTC Ref B',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Linear Dot Percent',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AP Domestic Analogue',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Compression Method Specific',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Color Space Specific',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Gamma Compensated',
        ),
    );

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExifEX;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SensitivityType extends AbstractTag
{

    protected $Id = 'SensitivityType';

    protected $Name = 'SensitivityType';

    protected $FullName = 'XMP::exifEX';

    protected $GroupName = 'XMP-exifEX';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exifEX';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Sensitivity Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Standard Output Sensitivity',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Recommended Exposure Index',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'ISO Speed',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Standard Output Sensitivity and Recommended Exposure Index',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Standard Output Sensitivity and ISO Speed',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Recommended Exposure Index and ISO Speed',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Standard Output Sensitivity, Recommended Exposure Index and ISO Speed',
        ),
    );

}

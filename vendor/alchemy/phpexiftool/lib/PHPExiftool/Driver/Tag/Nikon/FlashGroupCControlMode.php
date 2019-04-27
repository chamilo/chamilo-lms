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
class FlashGroupCControlMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashGroupCControlMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Group C Control Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'iTTL-BL',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'iTTL',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Auto Aperture',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Automatic',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'GN (distance priority)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Manual',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Repeating Flash',
        ),
    );

}

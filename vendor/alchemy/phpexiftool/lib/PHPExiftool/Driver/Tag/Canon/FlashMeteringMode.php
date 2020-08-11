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
class FlashMeteringMode extends AbstractTag
{

    protected $Id = 21;

    protected $Name = 'FlashMeteringMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Metering Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'E-TTL',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'TTL',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'External Auto',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'External Manual',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Off',
        ),
    );

}

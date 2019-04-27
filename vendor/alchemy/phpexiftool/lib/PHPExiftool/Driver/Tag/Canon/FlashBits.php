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
class FlashBits extends AbstractTag
{

    protected $Id = 29;

    protected $Name = 'FlashBits';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Flash Bits';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'TTL',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'A-TTL',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'E-TTL',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'FP sync enabled',
        ),
        128 => array(
            'Id' => 128,
            'Label' => '2nd-curtain sync used',
        ),
        2048 => array(
            'Id' => 2048,
            'Label' => 'FP sync used',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'Built-in',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'External',
        ),
    );

}

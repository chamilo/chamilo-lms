<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\H264;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageStabilization extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'ImageStabilization';

    protected $FullName = 'H264::Camera2';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Image Stabilization';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        63 => array(
            'Id' => 63,
            'Label' => 'On (0x3f)',
        ),
        191 => array(
            'Id' => 191,
            'Label' => 'Off (0xbf)',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'n/a',
        ),
    );

}

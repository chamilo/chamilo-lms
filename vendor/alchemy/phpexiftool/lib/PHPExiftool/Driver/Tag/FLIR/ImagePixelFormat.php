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
class ImagePixelFormat extends AbstractTag
{

    protected $Id = 42;

    protected $Name = 'ImagePixelFormat';

    protected $FullName = 'FLIR::FPF';

    protected $GroupName = 'FLIR';

    protected $g0 = 'FLIR';

    protected $g1 = 'FLIR';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Image Pixel Format';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '2-byte short integer',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '4-byte long integer',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '4-byte float',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '8-byte double',
        ),
    );

}

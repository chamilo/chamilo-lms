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
class ImageType extends AbstractTag
{

    protected $Id = 40;

    protected $Name = 'ImageType';

    protected $FullName = 'FLIR::FPF';

    protected $GroupName = 'FLIR';

    protected $g0 = 'FLIR';

    protected $g1 = 'FLIR';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Image Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Temperature',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Temperature Difference',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Object Signal',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Object Signal Difference',
        ),
    );

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhotoCD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageMedium extends AbstractTag
{

    protected $Id = 21;

    protected $Name = 'ImageMedium';

    protected $FullName = 'PhotoCD::Main';

    protected $GroupName = 'PhotoCD';

    protected $g0 = 'PhotoCD';

    protected $g1 = 'PhotoCD';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Image Medium';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Color negative',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Color reversal',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Color hard copy',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Thermal hard copy',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Black and white negative',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Black and white reversal',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Black and white hard copy',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Internegative',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Synthetic image',
        ),
    );

}

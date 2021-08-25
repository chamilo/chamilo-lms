<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FlashPix;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class TestTarget extends AbstractTag
{

    protected $Id = 587202560;

    protected $Name = 'TestTarget';

    protected $FullName = 'FlashPix::ImageInfo';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Test Target';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Color Chart',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Gray Card',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Grayscale',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Resolution Chart',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Inch Scale',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Centimeter Scale',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Millimeter Scale',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Micrometer Scale',
        ),
    );

}

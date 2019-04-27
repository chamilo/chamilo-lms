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
class TypeOfOriginal extends AbstractTag
{

    protected $Id = 687865859;

    protected $Name = 'TypeOfOriginal';

    protected $FullName = 'FlashPix::ImageInfo';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Type Of Original';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'B&W Print',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Color Print',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'B&W Document',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Color Document',
        ),
    );

}

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
class FileSource extends AbstractTag
{

    protected $Id = 553648128;

    protected $Name = 'FileSource';

    protected $FullName = 'FlashPix::ImageInfo';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'File Source';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Film Scanner',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Reflection Print Scanner',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Digital Camera',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Video Capture',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Computer Graphics',
        ),
    );

}

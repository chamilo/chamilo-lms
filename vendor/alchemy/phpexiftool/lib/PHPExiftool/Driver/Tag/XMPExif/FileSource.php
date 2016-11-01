<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExif;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FileSource extends AbstractTag
{

    protected $Id = 'FileSource';

    protected $Name = 'FileSource';

    protected $FullName = 'XMP::exif';

    protected $GroupName = 'XMP-exif';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exif';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

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
    );

}

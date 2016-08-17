<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FileSource extends AbstractTag
{

    protected $Id = 41728;

    protected $Name = 'FileSource';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'File Source';

    protected $local_g1 = 'ExifIFD';

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
        '\\x03\\x00\\x00\\x00' => array(
            'Id' => '\\x03\\x00\\x00\\x00',
            'Label' => 'Sigma Digital Camera',
        ),
    );

}

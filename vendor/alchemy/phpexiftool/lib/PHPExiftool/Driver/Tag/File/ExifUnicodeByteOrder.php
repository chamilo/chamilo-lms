<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExifUnicodeByteOrder extends AbstractTag
{

    protected $Id = 'ExifUnicodeByteOrder';

    protected $Name = 'ExifUnicodeByteOrder';

    protected $FullName = 'Extra';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Exif Unicode Byte Order';

    protected $Values = array(
        'II' => array(
            'Id' => 'II',
            'Label' => 'Little-endian (Intel, II)',
        ),
        'MM' => array(
            'Id' => 'MM',
            'Label' => 'Big-endian (Motorola, MM)',
        ),
    );

}

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
class SampleFormat extends AbstractTag
{

    protected $Id = 339;

    protected $Name = 'SampleFormat';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Sample Format';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Unsigned',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Signed',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Float',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Undefined',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Complex int',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Complex float',
        ),
    );

}

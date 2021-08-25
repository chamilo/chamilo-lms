<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EXIF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class EXIF extends AbstractTag
{

    protected $Id = 'EXIF';

    protected $Name = 'EXIF';

    protected $FullName = 'Extra';

    protected $GroupName = 'EXIF';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'EXIF';

    protected $local_g0 = 'EXIF';

    protected $local_g1 = 'EXIF';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

}

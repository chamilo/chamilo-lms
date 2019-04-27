<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IFD0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class OriginalDefaultCropSize extends AbstractTag
{

    protected $Id = 51091;

    protected $Name = 'OriginalDefaultCropSize';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'rational64u';

    protected $Writable = true;

    protected $Description = 'Original Default Crop Size';

    protected $flag_Unsafe = true;

    protected $MaxLength = 2;

}

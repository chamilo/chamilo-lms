<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\InteropIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RelatedImageWidth extends AbstractTag
{

    protected $Id = 4097;

    protected $Name = 'RelatedImageWidth';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'InteropIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Related Image Width';

    protected $local_g1 = 'InteropIFD';

    protected $flag_Unsafe = true;

}

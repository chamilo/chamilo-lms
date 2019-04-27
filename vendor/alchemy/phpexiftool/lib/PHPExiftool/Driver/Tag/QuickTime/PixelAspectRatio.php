<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PixelAspectRatio extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PixelAspectRatio';

    protected $FullName = 'mixed';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'mixed';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Pixel Aspect Ratio';

    protected $MaxLength = 'mixed';

}

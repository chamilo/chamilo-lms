<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PNGPHYs;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PixelsPerUnitY extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'PixelsPerUnitY';

    protected $FullName = 'PNG::PhysicalPixel';

    protected $GroupName = 'PNG-pHYs';

    protected $g0 = 'PNG';

    protected $g1 = 'PNG-pHYs';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Pixels Per Unit Y';

}

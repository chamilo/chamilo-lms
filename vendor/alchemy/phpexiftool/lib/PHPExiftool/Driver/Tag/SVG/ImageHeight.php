<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SVG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageHeight extends AbstractTag
{

    protected $Id = 'height';

    protected $Name = 'ImageHeight';

    protected $FullName = 'XMP::SVG';

    protected $GroupName = 'SVG';

    protected $g0 = 'SVG';

    protected $g1 = 'SVG';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Image Height';

}

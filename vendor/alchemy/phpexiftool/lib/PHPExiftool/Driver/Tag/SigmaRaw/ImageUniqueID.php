<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SigmaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageUniqueID extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'ImageUniqueID';

    protected $FullName = 'SigmaRaw::Header';

    protected $GroupName = 'SigmaRaw';

    protected $g0 = 'SigmaRaw';

    protected $g1 = 'SigmaRaw';

    protected $g2 = 'Other';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Image Unique ID';

    protected $MaxLength = 16;

}

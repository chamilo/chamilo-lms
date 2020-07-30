<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPCell;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LocationAreaCode extends AbstractTag
{

    protected $Id = 'lac';

    protected $Name = 'LocationAreaCode';

    protected $FullName = 'XMP::cell';

    protected $GroupName = 'XMP-cell';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-cell';

    protected $g2 = 'Location';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Location Area Code';

}

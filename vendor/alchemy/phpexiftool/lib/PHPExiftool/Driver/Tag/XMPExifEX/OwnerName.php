<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExifEX;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class OwnerName extends AbstractTag
{

    protected $Id = 'CameraOwnerName';

    protected $Name = 'OwnerName';

    protected $FullName = 'XMP::exifEX';

    protected $GroupName = 'XMP-exifEX';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exifEX';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Owner Name';

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPMP1;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WhiteBalance1 extends AbstractTag
{

    protected $Id = 'WhiteBalance1';

    protected $Name = 'WhiteBalance1';

    protected $FullName = 'Microsoft::MP1';

    protected $GroupName = 'XMP-MP1';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-MP1';

    protected $g2 = 'Image';

    protected $Type = 'real';

    protected $Writable = true;

    protected $Description = 'White Balance 1';

}

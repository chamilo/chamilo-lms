<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NITF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BitDepth extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'BitDepth';

    protected $FullName = 'JPEG::NITF';

    protected $GroupName = 'NITF';

    protected $g0 = 'APP6';

    protected $g1 = 'NITF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Bit Depth';

}

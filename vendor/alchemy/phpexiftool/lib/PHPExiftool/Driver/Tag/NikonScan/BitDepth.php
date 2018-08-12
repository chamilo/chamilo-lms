<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonScan;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BitDepth extends AbstractTag
{

    protected $Id = 65;

    protected $Name = 'BitDepth';

    protected $FullName = 'Nikon::Scan';

    protected $GroupName = 'NikonScan';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonScan';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Bit Depth';

    protected $flag_Permanent = true;

}

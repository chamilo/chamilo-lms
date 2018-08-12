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
class ColorGain extends AbstractTag
{

    protected $Id = 81;

    protected $Name = 'ColorGain';

    protected $FullName = 'Nikon::Scan';

    protected $GroupName = 'NikonScan';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonScan';

    protected $g2 = 'Image';

    protected $Type = 'rational64s';

    protected $Writable = true;

    protected $Description = 'Color Gain';

    protected $flag_Permanent = true;

    protected $MaxLength = 3;

}

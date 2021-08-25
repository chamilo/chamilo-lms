<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BarometerInfoVersion extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'BarometerInfoVersion';

    protected $FullName = 'Nikon::BarometerInfo';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Location';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Barometer Info Version';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

}

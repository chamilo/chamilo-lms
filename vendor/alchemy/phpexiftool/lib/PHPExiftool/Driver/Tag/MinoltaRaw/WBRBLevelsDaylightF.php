<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MinoltaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBRBLevelsDaylightF extends AbstractTag
{

    protected $Id = 36;

    protected $Name = 'WB_RBLevelsDaylightF';

    protected $FullName = 'MinoltaRaw::RIF';

    protected $GroupName = 'MinoltaRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'MinoltaRaw';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'WB RB Levels Daylight F';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

}

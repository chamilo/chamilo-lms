<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PanasonicRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBRBLevels7 extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'WB_RBLevels7';

    protected $FullName = 'PanasonicRaw::WBInfo';

    protected $GroupName = 'PanasonicRaw';

    protected $g0 = 'PanasonicRaw';

    protected $g1 = 'PanasonicRaw';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'WB RB Levels 7';

    protected $MaxLength = 2;

}

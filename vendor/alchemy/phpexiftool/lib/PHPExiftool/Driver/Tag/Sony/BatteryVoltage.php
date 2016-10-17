<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BatteryVoltage extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'BatteryVoltage';

    protected $FullName = 'Sony::ExtraInfo';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Battery Voltage';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

}

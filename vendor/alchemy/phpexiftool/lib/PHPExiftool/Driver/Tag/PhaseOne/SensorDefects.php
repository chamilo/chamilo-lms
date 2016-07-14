<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhaseOne;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SensorDefects extends AbstractTag
{

    protected $Id = 1024;

    protected $Name = 'SensorDefects';

    protected $FullName = 'PhaseOne::SensorCalibration';

    protected $GroupName = 'PhaseOne';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'PhaseOne';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Sensor Defects';

    protected $flag_Binary = true;

    protected $flag_Permanent = true;

}

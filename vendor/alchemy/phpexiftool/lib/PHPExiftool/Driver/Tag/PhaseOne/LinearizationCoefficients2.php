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
class LinearizationCoefficients2 extends AbstractTag
{

    protected $Id = 1050;

    protected $Name = 'LinearizationCoefficients2';

    protected $FullName = 'PhaseOne::SensorCalibration';

    protected $GroupName = 'PhaseOne';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'PhaseOne';

    protected $g2 = 'Camera';

    protected $Type = 'float';

    protected $Writable = false;

    protected $Description = 'Linearization Coefficients 2';

    protected $flag_Permanent = true;

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MXF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AnalogMonitoringAndControlCodingKind extends AbstractTag
{

    protected $Id = '060e2b34.0101.0101.04050201.00000000';

    protected $Name = 'AnalogMonitoringAndControlCodingKind';

    protected $FullName = 'MXF::Main';

    protected $GroupName = 'MXF';

    protected $g0 = 'MXF';

    protected $g1 = 'MXF';

    protected $g2 = 'Video';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Analog Monitoring And Control Coding Kind';

}

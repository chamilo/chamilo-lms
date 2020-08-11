<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLIR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class UnknownTemperature5 extends AbstractTag
{

    protected $Id = 168;

    protected $Name = 'UnknownTemperature5';

    protected $FullName = 'FLIR::CameraInfo';

    protected $GroupName = 'FLIR';

    protected $g0 = 'APP1';

    protected $g1 = 'FLIR';

    protected $g2 = 'Camera';

    protected $Type = 'float';

    protected $Writable = false;

    protected $Description = 'Unknown Temperature 5';

}

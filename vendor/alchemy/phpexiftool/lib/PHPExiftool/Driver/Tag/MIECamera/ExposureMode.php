<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIECamera;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExposureMode extends AbstractTag
{

    protected $Id = 'ExposureMode';

    protected $Name = 'ExposureMode';

    protected $FullName = 'MIE::Camera';

    protected $GroupName = 'MIE-Camera';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Camera';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Exposure Mode';

}

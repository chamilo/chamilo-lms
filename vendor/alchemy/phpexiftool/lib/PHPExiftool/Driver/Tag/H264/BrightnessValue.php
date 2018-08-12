<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\H264;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BrightnessValue extends AbstractTag
{

    protected $Id = 163;

    protected $Name = 'BrightnessValue';

    protected $FullName = 'H264::MDPM';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Camera';

    protected $Type = 'rational32s';

    protected $Writable = false;

    protected $Description = 'Brightness Value';

    protected $local_g2 = 'Image';

}

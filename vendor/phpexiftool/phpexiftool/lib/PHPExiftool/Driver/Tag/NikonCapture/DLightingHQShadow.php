<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCapture;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DLightingHQShadow extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'D-LightingHQShadow';

    protected $FullName = 'NikonCapture::DLightingHQ';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'D-Lighting HQ Shadow';

    protected $flag_Permanent = true;

}

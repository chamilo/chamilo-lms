<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPMP1;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CameraModelID extends AbstractTag
{

    protected $Id = 'CameraModelID';

    protected $Name = 'CameraModelID';

    protected $FullName = 'Microsoft::MP1';

    protected $GroupName = 'XMP-MP1';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-MP1';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Camera Model ID';

    protected $flag_Avoid = true;

}

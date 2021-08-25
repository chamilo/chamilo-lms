<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SPIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageWidth extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'ImageWidth';

    protected $FullName = 'JPEG::SPIFF';

    protected $GroupName = 'SPIFF';

    protected $g0 = 'APP8';

    protected $g1 = 'SPIFF';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Image Width';

}

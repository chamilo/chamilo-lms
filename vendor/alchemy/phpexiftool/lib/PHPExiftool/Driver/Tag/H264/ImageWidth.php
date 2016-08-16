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
class ImageWidth extends AbstractTag
{

    protected $Id = 'ImageWidth';

    protected $Name = 'ImageWidth';

    protected $FullName = 'H264::Main';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Image Width';

}

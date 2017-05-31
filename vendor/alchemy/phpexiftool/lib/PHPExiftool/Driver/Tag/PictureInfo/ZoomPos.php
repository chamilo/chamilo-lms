<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PictureInfo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ZoomPos extends AbstractTag
{

    protected $Id = 'ZoomPos';

    protected $Name = 'ZoomPos';

    protected $FullName = 'APP12::PictureInfo';

    protected $GroupName = 'PictureInfo';

    protected $g0 = 'APP12';

    protected $g1 = 'PictureInfo';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Zoom Pos';

}

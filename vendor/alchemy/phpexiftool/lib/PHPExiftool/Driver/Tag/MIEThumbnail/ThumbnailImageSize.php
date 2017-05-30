<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEThumbnail;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ThumbnailImageSize extends AbstractTag
{

    protected $Id = 'ImageSize';

    protected $Name = 'ThumbnailImageSize';

    protected $FullName = 'MIE::Thumbnail';

    protected $GroupName = 'MIE-Thumbnail';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Thumbnail';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Thumbnail Image Size';

}

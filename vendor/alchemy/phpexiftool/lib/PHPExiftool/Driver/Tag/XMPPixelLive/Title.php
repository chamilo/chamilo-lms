<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPixelLive;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Title extends AbstractTag
{

    protected $Id = 'TITLE';

    protected $Name = 'Title';

    protected $FullName = 'XMP::PixelLive';

    protected $GroupName = 'XMP-PixelLive';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-PixelLive';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Title';

    protected $flag_Avoid = true;

}

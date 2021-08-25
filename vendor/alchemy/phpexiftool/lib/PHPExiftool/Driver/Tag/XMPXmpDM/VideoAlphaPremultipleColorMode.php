<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpDM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class VideoAlphaPremultipleColorMode extends AbstractTag
{

    protected $Id = 'videoAlphaPremultipleColorMode';

    protected $Name = 'VideoAlphaPremultipleColorMode';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Video Alpha Premultiple Color Mode';

    protected $Values = array(
        'CMYK' => array(
            'Id' => 'CMYK',
            'Label' => 'CMYK',
        ),
        'LAB' => array(
            'Id' => 'LAB',
            'Label' => 'Lab',
        ),
        'RGB' => array(
            'Id' => 'RGB',
            'Label' => 'RGB',
        ),
    );

}

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
class VideoPixelDepth extends AbstractTag
{

    protected $Id = 'videoPixelDepth';

    protected $Name = 'VideoPixelDepth';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Video Pixel Depth';

    protected $Values = array(
        '16Int' => array(
            'Id' => '16Int',
            'Label' => '16-bit integer',
        ),
        '24Int' => array(
            'Id' => '24Int',
            'Label' => '24-bit integer',
        ),
        '32Float' => array(
            'Id' => '32Float',
            'Label' => '32-bit float',
        ),
        '32Int' => array(
            'Id' => '32Int',
            'Label' => '32-bit integer',
        ),
        '8Int' => array(
            'Id' => '8Int',
            'Label' => '8-bit integer',
        ),
        'Other' => array(
            'Id' => 'Other',
            'Label' => 'Other',
        ),
    );

}

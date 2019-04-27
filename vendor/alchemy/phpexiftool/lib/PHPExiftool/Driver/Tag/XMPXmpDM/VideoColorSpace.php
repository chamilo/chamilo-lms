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
class VideoColorSpace extends AbstractTag
{

    protected $Id = 'videoColorSpace';

    protected $Name = 'VideoColorSpace';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Video Color Space';

    protected $Values = array(
        'CCIR-601' => array(
            'Id' => 'CCIR-601',
            'Label' => 'CCIR-601',
        ),
        'CCIR-709' => array(
            'Id' => 'CCIR-709',
            'Label' => 'CCIR-709',
        ),
        'sRGB' => array(
            'Id' => 'sRGB',
            'Label' => 'sRGB',
        ),
    );

}

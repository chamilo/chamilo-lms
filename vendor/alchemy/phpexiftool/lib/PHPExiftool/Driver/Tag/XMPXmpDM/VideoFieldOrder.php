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
class VideoFieldOrder extends AbstractTag
{

    protected $Id = 'videoFieldOrder';

    protected $Name = 'VideoFieldOrder';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Video Field Order';

    protected $Values = array(
        'Lower' => array(
            'Id' => 'Lower',
            'Label' => 'Lower',
        ),
        'Progressive' => array(
            'Id' => 'Progressive',
            'Label' => 'Progressive',
        ),
        'Upper' => array(
            'Id' => 'Upper',
            'Label' => 'Upper',
        ),
    );

}

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
class ScaleType extends AbstractTag
{

    protected $Id = 'scaleType';

    protected $Name = 'ScaleType';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Scale Type';

    protected $Values = array(
        'Both' => array(
            'Id' => 'Both',
            'Label' => 'Both',
        ),
        'Major' => array(
            'Id' => 'Major',
            'Label' => 'Major',
        ),
        'Minor' => array(
            'Id' => 'Minor',
            'Label' => 'Minor',
        ),
        'Neither' => array(
            'Id' => 'Neither',
            'Label' => 'Neither',
        ),
    );

}

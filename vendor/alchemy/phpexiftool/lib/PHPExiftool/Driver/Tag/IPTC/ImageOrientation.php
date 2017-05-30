<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageOrientation extends AbstractTag
{

    protected $Id = 131;

    protected $Name = 'ImageOrientation';

    protected $FullName = 'IPTC::ApplicationRecord';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Orientation';

    protected $local_g2 = 'Image';

    protected $MaxLength = 1;

    protected $Values = array(
        'L' => array(
            'Id' => 'L',
            'Label' => 'Landscape',
        ),
        'P' => array(
            'Id' => 'P',
            'Label' => 'Portrait',
        ),
        'S' => array(
            'Id' => 'S',
            'Label' => 'Square',
        ),
    );

}

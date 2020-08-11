<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPlus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageFileSizeAsDelivered extends AbstractTag
{

    protected $Id = 'ImageFileSizeAsDelivered';

    protected $Name = 'ImageFileSizeAsDelivered';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image File Size As Delivered';

    protected $Values = array(
        'SZ-G50' => array(
            'Id' => 'SZ-G50',
            'Label' => 'Greater than 50 MB',
        ),
        'SZ-U01' => array(
            'Id' => 'SZ-U01',
            'Label' => 'Up to 1 MB',
        ),
        'SZ-U10' => array(
            'Id' => 'SZ-U10',
            'Label' => 'Up to 10 MB',
        ),
        'SZ-U30' => array(
            'Id' => 'SZ-U30',
            'Label' => 'Up to 30 MB',
        ),
        'SZ-U50' => array(
            'Id' => 'SZ-U50',
            'Label' => 'Up to 50 MB',
        ),
    );

}

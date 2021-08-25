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
class ImageType extends AbstractTag
{

    protected $Id = 'ImageType';

    protected $Name = 'ImageType';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Type';

    protected $Values = array(
        'TY-ILL' => array(
            'Id' => 'TY-ILL',
            'Label' => 'Illustrated Image',
        ),
        'TY-MCI' => array(
            'Id' => 'TY-MCI',
            'Label' => 'Multimedia or Composited Image',
        ),
        'TY-OTR' => array(
            'Id' => 'TY-OTR',
            'Label' => 'Other',
        ),
        'TY-PHO' => array(
            'Id' => 'TY-PHO',
            'Label' => 'Photographic Image',
        ),
        'TY-VID' => array(
            'Id' => 'TY-VID',
            'Label' => 'Video',
        ),
    );

}

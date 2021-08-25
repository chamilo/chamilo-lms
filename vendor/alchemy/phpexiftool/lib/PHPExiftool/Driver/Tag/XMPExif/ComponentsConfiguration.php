<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExif;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ComponentsConfiguration extends AbstractTag
{

    protected $Id = 'ComponentsConfiguration';

    protected $Name = 'ComponentsConfiguration';

    protected $FullName = 'XMP::exif';

    protected $GroupName = 'XMP-exif';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exif';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Components Configuration';

    protected $flag_List = true;

    protected $flag_Seq = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '-',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Y',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Cb',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Cr',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'R',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'G',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'B',
        ),
    );

}

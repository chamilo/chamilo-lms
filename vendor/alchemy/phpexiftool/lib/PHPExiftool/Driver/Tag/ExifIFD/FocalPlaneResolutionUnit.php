<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocalPlaneResolutionUnit extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocalPlaneResolutionUnit';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Focal Plane Resolution Unit';

    protected $local_g1 = 'ExifIFD';

    protected $local_g2 = 'Camera';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'None',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'inches',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'cm',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'mm',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'um',
        ),
    );

}

<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageStabilization extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ImageStabilization';

    protected $FullName = 'mixed';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Image Stabilization';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        3 => array(
            'Id' => 10116,
            'Label' => 'On',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'On',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
    );

    protected $Index = 'mixed';

}

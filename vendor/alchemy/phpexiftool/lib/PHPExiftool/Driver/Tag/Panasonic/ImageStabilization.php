<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageStabilization extends AbstractTag
{

    protected $Id = 26;

    protected $Name = 'ImageStabilization';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Image Stabilization';

    protected $flag_Permanent = true;

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => 'On, Mode 1',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'On, Mode 2',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Panning',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'On, Mode 3',
        ),
    );

}

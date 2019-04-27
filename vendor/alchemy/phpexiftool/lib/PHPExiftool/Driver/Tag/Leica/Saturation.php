<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leica;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Saturation extends AbstractTag
{

    protected $Id = 12301;

    protected $Name = 'Saturation';

    protected $FullName = 'Panasonic::Subdir';

    protected $GroupName = 'Leica';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Leica';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Saturation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Low',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Medium Low',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Normal',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Medium High',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'High',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Black & White',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Vintage B&W',
        ),
    );

}

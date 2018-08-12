<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WorkColorSpace extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WorkColorSpace';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Work Color Space';

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'sRGB',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Adobe RGB',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Wide Gamut RGB',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Apple RGB',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'ColorMatch RGB',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'sRGB',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Adobe RGB',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Wide Gamut RGB',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Apple RGB',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'ColorMatch RGB',
        ),
    );

}

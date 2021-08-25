<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpace extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ColorSpace';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Color Space';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'sRGB',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Adobe RGB',
        ),
        2 => array(
            'Id' => 5,
            'Label' => 'Adobe RGB (A700)',
        ),
        3 => array(
            'Id' => 5,
            'Label' => 'Adobe RGB',
        ),
        4 => array(
            'Id' => 6,
            'Label' => 'sRGB',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'sRGB',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Adobe RGB',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'sRGB',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Adobe RGB',
        ),
    );

}

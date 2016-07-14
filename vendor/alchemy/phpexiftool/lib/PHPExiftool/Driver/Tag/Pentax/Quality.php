<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Quality extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Quality';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Good',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Better',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Best',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'TIFF',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'RAW',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Premium',
        ),
        6 => array(
            'Id' => 7,
            'Label' => 'RAW (pixel shift enabled)',
        ),
        7 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Good',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Better',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Best',
        ),
    );

}

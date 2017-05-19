<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPointsUsed extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'AFPointsUsed';

    protected $FullName = 'Nikon::AFInfo2';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'AF Points Used';

    protected $flag_Permanent = true;

    protected $MaxLength = 'mixed';

    protected $Index = 'mixed';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Bottom',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Mid-left',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Upper-left',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Lower-left',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Far Left',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Mid-right',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Upper-right',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Lower-right',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Far Right',
        ),
        2047 => array(
            'Id' => 2047,
            'Label' => 'All 11 Points',
        ),
    );

}

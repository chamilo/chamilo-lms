<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AutoBracketModeM extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AutoBracketModeM';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Auto Bracket Mode M';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Flash/Speed',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'Flash/Speed/Aperture',
        ),
        2 => array(
            'Id' => 32,
            'Label' => 'Flash/Aperture',
        ),
        3 => array(
            'Id' => 48,
            'Label' => 'Flash Only',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Flash/Speed',
        ),
        5 => array(
            'Id' => 8,
            'Label' => 'Flash/Speed/Aperture',
        ),
        6 => array(
            'Id' => 16,
            'Label' => 'Flash/Aperture',
        ),
        7 => array(
            'Id' => 24,
            'Label' => 'Flash Only',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Flash/Speed',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Flash/Speed/Aperture',
        ),
        10 => array(
            'Id' => 8,
            'Label' => 'Flash/Aperture',
        ),
        11 => array(
            'Id' => 12,
            'Label' => 'Flash Only',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Flash/Speed',
        ),
        13 => array(
            'Id' => 16,
            'Label' => 'Flash/Speed/Aperture',
        ),
        14 => array(
            'Id' => 32,
            'Label' => 'Flash/Aperture',
        ),
        15 => array(
            'Id' => 48,
            'Label' => 'Flash Only',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Flash/Speed',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'Flash/Speed/Aperture',
        ),
        18 => array(
            'Id' => 8,
            'Label' => 'Flash/Aperture',
        ),
        19 => array(
            'Id' => 12,
            'Label' => 'Flash Only',
        ),
    );

    protected $Index = 'mixed';

}

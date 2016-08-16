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
class CommandDialsReverseRotation extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CommandDialsReverseRotation';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Command Dials Reverse Rotation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        1 => array(
            'Id' => 128,
            'Label' => 'Yes',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        3 => array(
            'Id' => 8,
            'Label' => 'Shutter Speed & Aperture',
        ),
        4 => array(
            'Id' => 16,
            'Label' => 'Exposure Compensation',
        ),
        5 => array(
            'Id' => 24,
            'Label' => 'Exposure Compensation, Shutter Speed & Aperture',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        7 => array(
            'Id' => 128,
            'Label' => 'Yes',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        9 => array(
            'Id' => 128,
            'Label' => 'Yes',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        11 => array(
            'Id' => 128,
            'Label' => 'Yes',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        13 => array(
            'Id' => 128,
            'Label' => 'Yes',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        15 => array(
            'Id' => 8,
            'Label' => 'Shutter Speed & Aperture',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Exposure Compensation',
        ),
        17 => array(
            'Id' => 24,
            'Label' => 'Exposure Compensation, Shutter Speed & Aperture',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        19 => array(
            'Id' => 128,
            'Label' => 'Yes',
        ),
    );

}

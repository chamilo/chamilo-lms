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
class AssignMovieRecordButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AssignMovieRecordButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assign Movie Record Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area (FX/DX/5:4)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Shutter Speed & Aperture Lock',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'ISO Sensitivity',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Shooting Bank Menu',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area (FX/DX/5:4)',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Shutter Speed & Aperture Lock',
        ),
        8 => array(
            'Id' => 9,
            'Label' => 'White Balance',
        ),
        9 => array(
            'Id' => 10,
            'Label' => 'ISO Sensitivity',
        ),
    );

}

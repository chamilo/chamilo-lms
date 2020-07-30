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
class RawAndJpgRecording extends AbstractTag
{

    protected $Id = 13;

    protected $Name = 'RawAndJpgRecording';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Raw And Jpg Recording';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'JPEG (Best)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'RAW (PEF, Best)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'RAW+JPEG (PEF, Best)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'RAW (DNG, Best)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'RAW+JPEG (DNG, Best)',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'JPEG (Better)',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'RAW (PEF, Better)',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'RAW+JPEG (PEF, Better)',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'RAW (DNG, Better)',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'RAW+JPEG (DNG, Better)',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'JPEG (Good)',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'RAW (PEF, Good)',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'RAW+JPEG (PEF, Good)',
        ),
        72 => array(
            'Id' => 72,
            'Label' => 'RAW (DNG, Good)',
        ),
        73 => array(
            'Id' => 73,
            'Label' => 'RAW+JPEG (DNG, Good)',
        ),
    );

}

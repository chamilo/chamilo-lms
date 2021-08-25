<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawDevPMPictureTone extends AbstractTag
{

    protected $Id = 273;

    protected $Name = 'RawDevPMPictureTone';

    protected $FullName = 'Olympus::RawDevelopment2';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Raw Dev PM Picture Tone';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Neutral',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Sepia',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Blue',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Purple',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Green',
        ),
    );

}
